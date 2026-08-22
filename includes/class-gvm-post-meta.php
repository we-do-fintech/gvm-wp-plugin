<?php
/**
 * Classic editor per-article paywall meta box.
 *
 * @package Gvm_Wp
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders and saves the per-article paywall meta box.
 */
class Gvm_Post_Meta {

	const NONCE_ACTION = 'gvm_save_meta';
	const NONCE_NAME   = 'gvm_meta_nonce';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_box' ) );
		add_action( 'save_post', array( __CLASS__, 'save' ) );
	}

	/**
	 * Add the meta box for enabled post types.
	 *
	 * @param string $post_type Current post type.
	 * @return void
	 */
	public static function add_meta_box( $post_type ) {
		if ( ! in_array( $post_type, Gvm_Settings::post_types(), true ) ) {
			return;
		}

		add_meta_box(
			'gvm-paywall',
			__( 'GetViaMsg Paywall', 'gvm-wp' ),
			array( __CLASS__, 'render' ),
			$post_type,
			'side',
			'default'
		);
	}

	/**
	 * Render the meta box.
	 *
	 * @param WP_Post $post Current post.
	 * @return void
	 */
	public static function render( $post ) {
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );

		$config = Gvm_Post::get_config( $post->ID );
		?>
		<p>
			<label>
				<input type="checkbox" name="gvm_enabled" value="1" <?php checked( $config['enabled'], true ); ?> />
				<?php esc_html_e( 'Enable paywall', 'gvm-wp' ); ?>
			</label>
		</p>

		<p>
			<label for="gvm_price"><?php esc_html_e( 'Price', 'gvm-wp' ); ?></label>
			<input type="number" id="gvm_price" name="gvm_price" step="0.01" min="0.01" max="10" class="widefat" value="<?php echo esc_attr( (string) $config['price'] ); ?>" />
		</p>

		<p>
			<label for="gvm_template"><?php esc_html_e( 'Template', 'gvm-wp' ); ?></label>
			<input type="text" id="gvm_template" name="gvm_template" class="widefat" value="<?php echo esc_attr( $config['template'] ); ?>" />
		</p>

		<p>
			<label for="gvm_hide_strategy"><?php esc_html_e( 'Hide strategy', 'gvm-wp' ); ?></label>
			<select id="gvm_hide_strategy" name="gvm_hide_strategy" class="widefat">
				<option value="none" <?php selected( $config['hide_strategy'], 'none' ); ?>><?php esc_html_e( 'None', 'gvm-wp' ); ?></option>
				<option value="hide" <?php selected( $config['hide_strategy'], 'hide' ); ?>><?php esc_html_e( 'Hide', 'gvm-wp' ); ?></option>
				<option value="blur" <?php selected( $config['hide_strategy'], 'blur' ); ?>><?php esc_html_e( 'Blur', 'gvm-wp' ); ?></option>
				<option value="mangle-blur" <?php selected( $config['hide_strategy'], 'mangle-blur' ); ?>><?php esc_html_e( 'Mangle blur', 'gvm-wp' ); ?></option>
			</select>
		</p>

		<p>
			<label for="gvm_hide_sections"><?php esc_html_e( 'Hide sections', 'gvm-wp' ); ?></label>
			<input type="number" id="gvm_hide_sections" name="gvm_hide_sections" min="0" class="widefat" value="<?php echo esc_attr( (string) $config['hide_sections'] ); ?>" />
		</p>

		<p>
			<label for="gvm_hide_percent"><?php esc_html_e( 'Hide percent (1-100)', 'gvm-wp' ); ?></label>
			<input type="number" id="gvm_hide_percent" name="gvm_hide_percent" min="0" max="100" class="widefat" value="<?php echo esc_attr( (string) $config['hide_percent'] ); ?>" />
		</p>

		<p>
			<label for="gvm_hide_words"><?php esc_html_e( 'Hide words', 'gvm-wp' ); ?></label>
			<input type="number" id="gvm_hide_words" name="gvm_hide_words" min="0" class="widefat" value="<?php echo esc_attr( (string) $config['hide_words'] ); ?>" />
		</p>

		<p>
			<label for="gvm_reference"><?php esc_html_e( 'Reference', 'gvm-wp' ); ?></label>
			<input type="text" id="gvm_reference" name="gvm_reference" class="widefat" value="<?php echo esc_attr( $config['reference'] ); ?>" placeholder="<?php echo esc_attr( Gvm_Post::reference( $post->ID ) ); ?>" />
			<span class="description"><?php esc_html_e( 'Leave empty to auto-generate from slug or post ID.', 'gvm-wp' ); ?></span>
		</p>
		<?php
	}

	/**
	 * Save meta box data.
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public static function save( $post_id ) {
		if ( ! isset( $_POST[ self::NONCE_NAME ] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE_NAME ] ) ), self::NONCE_ACTION ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}

		$enabled = isset( $_POST['gvm_enabled'] ) ? (bool) $_POST['gvm_enabled'] : false;
		update_post_meta( $post_id, Gvm_Post::ENABLED, $enabled );

		if ( isset( $_POST['gvm_price'] ) ) {
			$price = (float) wp_unslash( $_POST['gvm_price'] );
			update_post_meta( $post_id, Gvm_Post::PRICE, $price <= 0 ? '' : max( 0.01, min( 10, $price ) ) );
		}

		if ( isset( $_POST['gvm_template'] ) ) {
			update_post_meta( $post_id, Gvm_Post::TEMPLATE, sanitize_key( wp_unslash( $_POST['gvm_template'] ) ) );
		}

		if ( isset( $_POST['gvm_hide_strategy'] ) ) {
			$strategy = sanitize_text_field( wp_unslash( $_POST['gvm_hide_strategy'] ) );
			update_post_meta( $post_id, Gvm_Post::HIDE_STRATEGY, in_array( $strategy, array( 'none', 'blur', 'hide', 'mangle-blur' ), true ) ? $strategy : 'hide' );
		}

		if ( isset( $_POST['gvm_hide_percent'] ) ) {
			update_post_meta( $post_id, Gvm_Post::HIDE_PERCENT, max( 0, min( 100, (int) wp_unslash( $_POST['gvm_hide_percent'] ) ) ) );
		}

		if ( isset( $_POST['gvm_hide_sections'] ) ) {
			update_post_meta( $post_id, Gvm_Post::HIDE_SECTIONS, max( 0, (int) wp_unslash( $_POST['gvm_hide_sections'] ) ) );
		}

		if ( isset( $_POST['gvm_hide_words'] ) ) {
			update_post_meta( $post_id, Gvm_Post::HIDE_WORDS, max( 0, (int) wp_unslash( $_POST['gvm_hide_words'] ) ) );
		}

		if ( isset( $_POST['gvm_reference'] ) ) {
			$reference = Gvm_Post::sanitize_reference( wp_unslash( $_POST['gvm_reference'] ) );
			update_post_meta( $post_id, Gvm_Post::REFERENCE, $reference );
		}
	}
}
