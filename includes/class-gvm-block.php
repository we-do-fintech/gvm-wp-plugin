<?php
/**
 * Gutenberg integration: block + sidebar panel + editor assets.
 *
 * @package Gvm_Wp
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the gvm/paywall block and the block editor assets.
 */
class Gvm_Block {

	const BLOCK_NAME          = 'gvm/paywall';
	const DOWNLOAD_BLOCK_NAME = 'gvm/download';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_block' ) );
		add_action( 'enqueue_block_editor_assets', array( __CLASS__, 'enqueue_editor_assets' ) );
	}

	/**
	 * Register the server-rendered blocks.
	 *
	 * @return void
	 */
	public static function register_block() {
		register_block_type(
			self::BLOCK_NAME,
			array(
				'api_version'     => 3,
				'editor_script'   => 'gvm-editor',
				'render_callback' => array( __CLASS__, 'render_block' ),
				'attributes'      => array(
					'price'          => array( 'type' => 'string', 'default' => '' ),
					'hide_strategy'  => array( 'type' => 'string', 'default' => 'hide' ),
					'hide_percent'   => array( 'type' => 'integer', 'default' => 0 ),
					'hide_sections'  => array( 'type' => 'integer', 'default' => 6 ),
					'hide_words'     => array( 'type' => 'integer', 'default' => 0 ),
					'reference'      => array( 'type' => 'string', 'default' => '' ),
					'title'          => array( 'type' => 'string', 'default' => '' ),
					'cond'           => array( 'type' => 'string', 'default' => '' ),
				),
			)
		);

		register_block_type(
			self::DOWNLOAD_BLOCK_NAME,
			array(
				'api_version'     => 3,
				'editor_script'   => 'gvm-editor',
				'render_callback' => array( __CLASS__, 'render_download_block' ),
				'attributes'      => array(
					'file'  => array( 'type' => 'string', 'default' => '' ),
					'price' => array( 'type' => 'string', 'default' => '' ),
					'cond'  => array( 'type' => 'string', 'default' => '' ),
				),
			)
		);
	}

	/**
	 * Server-side "paid content" block render.
	 *
	 * @param array  $attributes Block attributes.
	 * @param string $content    Inner blocks HTML.
	 * @return string
	 */
	public static function render_block( $attributes, $content ) {
		return Gvm_Render::paywall(
			(string) $content,
			array(
				'price'          => isset( $attributes['price'] ) ? $attributes['price'] : '',
				'hide_strategy'  => isset( $attributes['hide_strategy'] ) ? $attributes['hide_strategy'] : 'hide',
				'hide_percent'   => isset( $attributes['hide_percent'] ) ? $attributes['hide_percent'] : 0,
				'hide_sections'  => isset( $attributes['hide_sections'] ) ? $attributes['hide_sections'] : 6,
				'hide_words'     => isset( $attributes['hide_words'] ) ? $attributes['hide_words'] : 0,
				'reference'      => isset( $attributes['reference'] ) ? $attributes['reference'] : '',
				'metadata_title' => isset( $attributes['title'] ) ? $attributes['title'] : '',
				'cond'           => isset( $attributes['cond'] ) ? $attributes['cond'] : '',
			)
		);
	}

	/**
	 * Server-side "paid download" block render.
	 *
	 * @param array $attributes Block attributes.
	 * @return string
	 */
	public static function render_download_block( $attributes ) {
		$post = get_post();
		$file = sanitize_file_name( (string) ( isset( $attributes['file'] ) ? $attributes['file'] : '' ) );

		if ( ! $post || '' === $file ) {
			return '';
		}

		$config = Gvm_Post::get_config( $post->ID );

		return Gvm_Render::paywall(
			'',
			array(
				'price'       => isset( $attributes['price'] ) ? $attributes['price'] : '',
				'reference'   => Gvm_Download::file_reference( $config['reference'], $file ),
				'cond'        => isset( $attributes['cond'] ) ? $attributes['cond'] : '',
				'download'    => true,
				'download_to' => Gvm_Download::download_url( $post->ID, $file ),
				'filename'    => $file,
			)
		);
	}

	/**
	 * Enqueue editor JS/CSS for enabled post types.
	 *
	 * @return void
	 */
	public static function enqueue_editor_assets() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( ! $screen || ! isset( $screen->post_type ) || ! in_array( $screen->post_type, Gvm_Settings::post_types(), true ) ) {
			return;
		}

		wp_enqueue_script(
			'gvm-editor',
			GVM_WP_URL . 'assets/editor.js',
			array( 'wp-blocks', 'wp-element', 'wp-i18n', 'wp-components', 'wp-data', 'wp-plugins', 'wp-edit-post', 'wp-editor', 'wp-block-editor', 'wp-compose' ),
			GVM_WP_VERSION,
			true
		);

		wp_enqueue_style(
			'gvm-editor',
			GVM_WP_URL . 'assets/editor.css',
			array(),
			GVM_WP_VERSION
		);

		wp_localize_script(
			'gvm-editor',
			'gvmEditorConfig',
			array(
				'defaults' => array(
					'price' => Gvm_Settings::default_price(),
				),
				'upload'   => array(
					'ajaxurl' => Gvm_Download::ajax_url(),
					'action'  => Gvm_Download::AJAX_ACTION,
					'nonce'   => Gvm_Download::upload_nonce(),
				),
			)
		);
	}
}
