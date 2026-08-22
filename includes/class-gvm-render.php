<?php
/**
 * Paywall rendering: builds the data-gvm-* container and templates.
 *
 * @package Gvm_Wp
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shared renderer used by the content filter, shortcodes and the block.
 */
class Gvm_Render {

	/**
	 * Whether a paywall has been rendered for the current request.
	 *
	 * @var bool
	 */
	private static $present = false;

	/**
	 * Monotonic counter to keep node ids / template names unique.
	 *
	 * @var int
	 */
	private static $count = 0;

	/**
	 * Collected <template> definitions to print in the footer.
	 *
	 * @var array
	 */
	private static $templates = array();

	/**
	 * Template names already registered (to avoid duplicate payment templates).
	 *
	 * @var array
	 */
	private static $registered = array();

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_filter( 'the_content', array( __CLASS__, 'filter_content' ), 20 );
	}

	/**
	 * Mark a paywall as present and enqueue the module script.
	 *
	 * @return void
	 */
	public static function enqueue() {
		self::$present = true;

		Gvm_Enqueue::enqueue();
	}

	/**
	 * Whether a paywall is present.
	 *
	 * @return bool
	 */
	public static function is_present() {
		return self::$present;
	}

	/**
	 * Wrap whole post content in a paywall when enabled via post meta.
	 *
	 * @param string $content Post content.
	 * @return string
	 */
	public static function filter_content( $content ) {
		if ( is_admin() || is_feed() || wp_doing_ajax() ) {
			return $content;
		}

		$post = get_post();

		if ( ! $post || ! in_array( $post->post_type, Gvm_Settings::post_types(), true ) ) {
			return $content;
		}

		$config = Gvm_Post::get_config( $post->ID );

		if ( ! $config['enabled'] ) {
			return $content;
		}

		return self::paywall(
			$content,
			array(
				'price'          => $config['price'],
				'template'       => $config['template'],
				'hide_strategy'  => $config['hide_strategy'],
				'hide_percent'   => $config['hide_percent'],
				'hide_sections'  => $config['hide_sections'],
				'hide_words'     => $config['hide_words'],
				'reference'      => $config['reference'],
				'metadata_title' => get_the_title( $post ),
			)
		);
	}

	/**
	 * Render a paywall container around content.
	 *
	 * @param string $content Inner HTML.
	 * @param array  $args    Config (price, template, hide_*, reference, metadata_title).
	 * @return string
	 */
	public static function paywall( $content, $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'price'          => Gvm_Settings::default_price(),
				'template'       => Gvm_Settings::default_template(),
				'hide_strategy'  => 'hide',
				'hide_percent'   => 0,
				'hide_sections'  => 6,
				'hide_words'     => 0,
				'reference'      => '',
				'metadata_title' => '',
			)
		);

		$strategy = in_array( $args['hide_strategy'], array( 'none', 'blur', 'hide', 'mangle-blur' ), true ) ? $args['hide_strategy'] : 'hide';

		$reference     = self::resolve_reference( $args['reference'] );
		$template_slug = sanitize_key( (string) $args['template'] );
		if ( '' === $template_slug ) {
			$template_slug = Gvm_Settings::default_template();
		}

		self::$count++;
		$node_id   = 'gvm-paywall-' . self::$count;
		$hide_name = $template_slug . '-' . self::$count;

		$attrs = array(
			'data-gvm-reference'     => $reference,
			'data-gvm-price'         => self::format_price( $args['price'] ),
			'data-gvm-template-name' => 'payment',
		);

		if ( '' !== (string) $args['metadata_title'] ) {
			$attrs['data-gvm-metadata-title'] = (string) $args['metadata_title'];
		}

		if ( 'none' !== $strategy ) {
			$attrs['data-gvm-hide-strategy']      = $strategy;
			$attrs['data-gvm-hide-template-name'] = $hide_name;

			if ( (int) $args['hide_sections'] > 1 ) {
				$attrs['data-gvm-hide-sections'] = (int) $args['hide_sections'];
			} elseif ( (int) $args['hide_percent'] >= 1 && (int) $args['hide_percent'] <= 100 ) {
				$attrs['data-gvm-hide-percent'] = (int) $args['hide_percent'];
			} elseif ( (int) $args['hide_words'] > 5 ) {
				$attrs['data-gvm-hide-words'] = (int) $args['hide_words'];
			}

			self::register_hide_template( $hide_name, $node_id, $template_slug );
		}

		$attr_html = '';
		foreach ( $attrs as $key => $value ) {
			$attr_html .= ' ' . $key . '="' . esc_attr( (string) $value ) . '"';
		}

		self::register_payment_template();
		self::enqueue();

		return '<div class="gvm-paywall" id="' . esc_attr( $node_id ) . '"' . $attr_html . '>' . $content . '</div>';
	}

	/**
	 * Resolve a reference, auto-generating from the current post when empty.
	 *
	 * @param string $reference Explicit reference.
	 * @return string
	 */
	public static function resolve_reference( $reference ) {
		$reference = Gvm_Post::sanitize_reference( (string) $reference );

		if ( '' !== $reference ) {
			return $reference;
		}

		$post = get_post();
		if ( $post ) {
			return Gvm_Post::reference( $post->ID );
		}

		return 'gvm-' . ( self::$count + 1 );
	}

	/**
	 * Format a price for the data-gvm-price attribute (0.01-10).
	 *
	 * @param mixed $price Raw price.
	 * @return string
	 */
	public static function format_price( $price ) {
		$price = (float) $price;

		if ( $price <= 0 ) {
			$price = Gvm_Settings::default_price();
		}
		if ( $price > 10 ) {
			$price = 10;
		}

		return number_format( $price, 2, '.', '' );
	}

	/**
	 * Register the shared payment modal template (once).
	 *
	 * @return void
	 */
	private static function register_payment_template() {
		if ( in_array( 'payment', self::$registered, true ) ) {
			return;
		}

		self::$registered[] = 'payment';
		self::$templates[]  = array(
			'name'      => 'payment',
			'inject_to' => 'body',
			'html'      => self::template_html( 'payment' ),
		);
	}

	/**
	 * Register a hide/paywall template for a specific node.
	 *
	 * @param string $name          Unique template name.
	 * @param string $node_id       Container node id.
	 * @param string $template_slug Template fragment slug.
	 * @return void
	 */
	private static function register_hide_template( $name, $node_id, $template_slug ) {
		$slug = '' === $template_slug ? 'paywall' : $template_slug;

		self::$templates[] = array(
			'name'      => $name,
			'inject_to' => '#' . $node_id,
			'html'      => self::template_html( $slug ),
		);
	}

	/**
	 * Return all collected <template> markup for the footer.
	 *
	 * @return string
	 */
	public static function render_templates() {
		$out = '';

		foreach ( self::$templates as $template ) {
			$out .= '<template data-gvm-template="' . esc_attr( $template['name'] ) . '" data-gvm-template-inject-to="' . esc_attr( $template['inject_to'] ) . '">';
			$out .= $template['html'];
			$out .= '</template>';
		}

		return $out;
	}

	/**
	 * Load a template fragment. Filterable by slug for theme overrides.
	 *
	 * @param string $slug Template slug (payment|paywall).
	 * @return string
	 */
	public static function template_html( $slug ) {
		$default = GVM_WP_DIR . 'templates/' . $slug . '.php';

		/**
		 * Filter the path of a gvm template fragment.
		 *
		 * @param string $default Absolute path.
		 * @param string $slug    Template slug.
		 */
		$file = apply_filters( "gvm_template_path_{$slug}", $default );

		if ( ! is_readable( $file ) ) {
			return '';
		}

		ob_start();
		include $file;

		return (string) ob_get_clean();
	}
}
