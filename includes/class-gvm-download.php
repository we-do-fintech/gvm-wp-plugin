<?php
/**
 * Download strategy: protected file storage + signature-verified download endpoint.
 *
 * @package Gvm_Wp
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Stores gated files in a protected uploads subdir and serves them only after
 * a valid gvm-signature (server-side download strategy).
 */
class Gvm_Download {

	const QUERY_VAR = 'gvm_download';
	const SUBDIR    = 'gvm';
	const AJAX_ACTION = 'gvm_upload_download';
	const NONCE_ACTION = 'gvm_upload';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'template_redirect', array( __CLASS__, 'maybe_serve' ), 0 );
		add_action( 'wp_ajax_' . self::AJAX_ACTION, array( __CLASS__, 'ajax_upload' ) );
		add_action( 'save_post', array( __CLASS__, 'sync_file_references' ), 10, 2 );
	}

	/**
	 * Absolute filesystem path of the protected directory.
	 *
	 * @return string
	 */
	public static function dir() {
		$uploads = wp_get_upload_dir();

		return trailingslashit( $uploads['basedir'] ) . self::SUBDIR . '/';
	}

	/**
	 * Public URL of the protected directory (blocked via .htaccess).
	 *
	 * @return string
	 */
	public static function url() {
		$uploads = wp_get_upload_dir();

		return trailingslashit( $uploads['baseurl'] ) . self::SUBDIR . '/';
	}

	/**
	 * Ensure the protected directory exists and direct access is blocked.
	 *
	 * @return void
	 */
	public static function ensure_protected() {
		$dir = self::dir();

		if ( ! wp_mkdir_p( $dir ) ) {
			return;
		}

		$htaccess = $dir . '.htaccess';
		if ( ! file_exists( $htaccess ) ) {
			$rules  = "<IfModule mod_authz_core.c>\nRequire all denied\n</IfModule>\n";
			$rules .= "<IfModule !mod_authz_core.c>\nOrder deny,allow\nDeny from all\n</IfModule>\n";
			@file_put_contents( $htaccess, $rules ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		}

		$index = $dir . 'index.html';
		if ( ! file_exists( $index ) ) {
			@file_put_contents( $index, '' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		}
	}

	/**
	 * The download endpoint URL for a post + file (SDK appends signature params).
	 *
	 * @param int    $post_id  Post ID.
	 * @param string $filename Filename (empty = first file in the post's list).
	 * @return string
	 */
	public static function download_url( $post_id, $filename = '' ) {
		$url = home_url( '/?gvm_download=' . absint( $post_id ) );

		if ( '' !== $filename ) {
			$url = add_query_arg( 'file', rawurlencode( $filename ), $url );
		}

		return $url;
	}

	/**
	 * Deterministic per-file reference: <base>-<filename-slug>.
	 *
	 * Both the renderer and the endpoint use this, so a payment for one file
	 * cannot be replayed against another file. The result is capped below 60
	 * chars because gvm.js rejects `data-gvm-reference` of 60+ chars.
	 *
	 * @param string $base_reference Base reference (post reference or block override).
	 * @param string $filename       Filename.
	 * @return string
	 */
	public static function file_reference( $base_reference, $filename ) {
		$base = Gvm_Post::sanitize_reference( $base_reference );
		$slug = sanitize_title_with_dashes( pathinfo( $filename, PATHINFO_FILENAME ) );

		if ( '' === $slug ) {
			$slug = 'file';
		}

		$reference = '' === $base ? $slug : $base . '-' . $slug;
		$reference = Gvm_Post::sanitize_reference( $reference );

		if ( '' === $reference ) {
			$reference = Gvm_Post::sanitize_reference( 'file-' . $slug );
		}

		return $reference;
	}

	/**
	 * The reference used for a gated file.
	 *
	 * An explicit reference declared on the `gvm/download` block (or the
	 * `[gvm-download]` shortcode) wins; otherwise the file reference is derived
	 * from the post reference + filename. Explicit references are recorded in
	 * `_gvm_file_refs` on save so the download endpoint can verify the payment.
	 *
	 * @param int    $post_id  Post ID.
	 * @param string $filename Filename.
	 * @return string
	 */
	public static function reference_for_file( $post_id, $filename ) {
		$filename = sanitize_file_name( wp_basename( (string) $filename ) );
		$refs     = Gvm_Post::file_references( $post_id );

		if ( isset( $refs[ $filename ] ) ) {
			$explicit = Gvm_Post::sanitize_reference( $refs[ $filename ] );
			if ( '' !== $explicit ) {
				return $explicit;
			}
		}

		$config = Gvm_Post::get_config( $post_id );

		return self::file_reference( $config['reference'], $filename );
	}

	/**
	 * Record explicit per-file references declared in the post content.
	 *
	 * Runs on save_post for both block and shortcode authoring so the download
	 * endpoint can bind a payment to the exact file. Only runs for enabled post
	 * types and when the author is allowed to edit the post.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 * @return void
	 */
	public static function sync_file_references( $post_id, $post ) {
		if ( ! $post || ! in_array( $post->post_type, Gvm_Settings::post_types(), true ) ) {
			return;
		}

		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$content = (string) $post->post_content;
		$refs    = array();

		self::collect_block_references( parse_blocks( $content ), $refs );
		self::collect_shortcode_references( $content, $refs );

		$stored = Gvm_Post::file_references( $post_id );

		if ( $refs === $stored ) {
			return;
		}

		if ( empty( $refs ) ) {
			delete_post_meta( $post_id, Gvm_Post::FILE_REFS );
			return;
		}

		update_post_meta( $post_id, Gvm_Post::FILE_REFS, $refs );
	}

	/**
	 * Collect explicit references from `gvm/download` blocks (recursively).
	 *
	 * @param array                $blocks Parsed blocks.
	 * @param array<string,string> $refs   Collected file => reference map (by reference).
	 * @return void
	 */
	private static function collect_block_references( $blocks, &$refs ) {
		foreach ( $blocks as $block ) {
			if ( ! empty( $block['blockName'] ) && 'gvm/download' === $block['blockName'] ) {
				$attrs = isset( $block['attrs'] ) && is_array( $block['attrs'] ) ? $block['attrs'] : array();
				$file  = isset( $attrs['file'] ) ? sanitize_file_name( wp_basename( (string) $attrs['file'] ) ) : '';
				$ref   = isset( $attrs['reference'] ) ? Gvm_Post::sanitize_reference( $attrs['reference'] ) : '';

				if ( '' !== $file && '' !== $ref ) {
					$refs[ $file ] = $ref;
				}
			}

			if ( ! empty( $block['innerBlocks'] ) ) {
				self::collect_block_references( $block['innerBlocks'], $refs );
			}
		}
	}

	/**
	 * Collect explicit references from `[gvm-download]` shortcodes.
	 *
	 * @param string               $content Post content.
	 * @param array<string,string> $refs    Collected file => reference map.
	 * @return void
	 */
	private static function collect_shortcode_references( $content, &$refs ) {
		if ( ! has_shortcode( $content, 'gvm-download' ) ) {
			return;
		}

		if ( ! preg_match_all( '/\[gvm-download\b([^\]]*)\]/i', $content, $matches ) ) {
			return;
		}

		foreach ( $matches[1] as $atts_string ) {
			$atts = shortcode_parse_atts( $atts_string );
			if ( ! is_array( $atts ) ) {
				continue;
			}

			$file = isset( $atts['file'] ) ? sanitize_file_name( wp_basename( (string) $atts['file'] ) ) : '';
			$ref  = isset( $atts['reference'] ) ? Gvm_Post::sanitize_reference( $atts['reference'] ) : '';

			if ( '' !== $file && '' !== $ref ) {
				$refs[ $file ] = $ref;
			}
		}
	}

	/**
	 * Move an uploaded file into the protected directory.
	 *
	 * @param array $file `$_FILES` entry (name, tmp_name, error, size).
	 * @return string|WP_Error Filename on success, WP_Error otherwise.
	 */
	public static function handle_upload( $file ) {
		if ( empty( $file['tmp_name'] ) || ! is_uploaded_file( $file['tmp_name'] ) ) {
			return new WP_Error( 'gvm_upload', __( 'No file uploaded.', 'gvm-wp' ) );
		}

		self::ensure_protected();

		$filename = sanitize_file_name( wp_basename( $file['name'] ) );
		$target   = self::dir() . $filename;

		if ( ! move_uploaded_file( $file['tmp_name'], $target ) ) {
			return new WP_Error( 'gvm_upload', __( 'Could not store the file.', 'gvm-wp' ) );
		}

		return $filename;
	}

	/**
	 * AJAX handler: upload a gated file into the protected directory.
	 *
	 * @return void
	 */
	public static function ajax_upload() {
		if ( ! current_user_can( 'upload_files' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'gvm-wp' ) ), 403 );
		}

		check_ajax_referer( self::NONCE_ACTION, 'nonce' );

		if ( empty( $_FILES['file'] ) ) {
			wp_send_json_error( array( 'message' => __( 'No file received.', 'gvm-wp' ) ) );
		}

		$result = self::handle_upload( $_FILES['file'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array( 'filename' => $result ) );
	}

	/**
	 * Upload nonce for the AJAX upload endpoint.
	 *
	 * @return string
	 */
	public static function upload_nonce() {
		return wp_create_nonce( self::NONCE_ACTION );
	}

	/**
	 * AJAX endpoint URL.
	 *
	 * @return string
	 */
	public static function ajax_url() {
		return admin_url( 'admin-ajax.php' );
	}

	/**
	 * Enqueue the small upload helper used by the classic editor meta box.
	 *
	 * @return void
	 */
	public static function enqueue_upload_assets() {
		wp_enqueue_script(
			'gvm-upload',
			GVM_WP_URL . 'assets/upload.js',
			array(),
			GVM_WP_VERSION,
			true
		);

		wp_localize_script(
			'gvm-upload',
			'gvmUpload',
			array(
				'ajaxurl' => self::ajax_url(),
				'action'  => self::AJAX_ACTION,
				'nonce'   => self::upload_nonce(),
			)
		);
	}

	/**
	 * Render the reusable upload field (file picker + filename input).
	 *
	 * @param string $current Current filename.
	 * @return void
	 */
	public static function upload_field( $current = '' ) {
		?>
		<div class="gvm-upload">
			<input type="file" class="gvm-upload-file" hidden />
			<button type="button" class="button gvm-upload-btn"><?php esc_html_e( 'Upload file', 'gvm-wp' ); ?></button>
			<input type="text" id="gvm_download" name="gvm_download" class="gvm-upload-filename widefat" value="<?php echo esc_attr( $current ); ?>" placeholder="<?php esc_attr_e( 'filename in uploads/gvm/', 'gvm-wp' ); ?>" />
			<span class="gvm-upload-status description"></span>
		</div>
		<?php
	}

	/**
	 * Serve a gated file when the request carries a valid gvm-signature.
	 *
	 * @return void
	 */
	public static function maybe_serve() {
		if ( ! isset( $_GET[ self::QUERY_VAR ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		$post_id = absint( $_GET[ self::QUERY_VAR ] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$post    = get_post( $post_id );

		if ( ! $post ) {
			self::deny();
		}

		// The file comes from the URL (block/shortcode). Post-level download was
		// removed in 0.1.1 — files are always sold through a block or shortcode.
		$filename = isset( $_GET['file'] ) ? sanitize_file_name( wp_basename( wp_unslash( $_GET['file'] ) ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( '' === $filename ) {
			self::deny();
		}

		if ( ! Gvm_Signature::verify_query_signature() ) {
			self::deny();
		}

		$params   = Gvm_Signature::from_request();
		$expected = self::reference_for_file( $post_id, $filename );

		if ( ! hash_equals( $expected, $params['reference'] ) ) {
			self::deny();
		}

		self::serve( $filename );
	}

	/**
	 * Stream a file from the protected directory.
	 *
	 * @param string $filename Filename.
	 * @return void
	 */
	private static function serve( $filename ) {
		$file = self::dir() . sanitize_file_name( wp_basename( $filename ) );

		if ( ! is_file( $file ) ) {
			self::deny();
		}

		$mime = wp_check_filetype( $filename );
		$mime = $mime['type'] ? $mime['type'] : 'application/octet-stream';

		nocache_headers();
		header( 'Content-Type: ' . $mime );
		header( 'Content-Disposition: attachment; filename="' . rawurlencode( wp_basename( $filename ) ) . '"' );
		header( 'Content-Length: ' . (int) filesize( $file ) );

		readfile( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile
		exit;
	}

	/**
	 * Deny the request with a 403.
	 *
	 * @return void
	 */
	private static function deny() {
		status_header( 403 );
		nocache_headers();
		wp_die( esc_html__( 'Access denied.', 'gvm-wp' ), '', array( 'response' => 403 ) );
	}
}
