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
	 * data-gvm-cond condition for the current request (declared per article,
	 * injected onto <body>). Only one condition is supported per page.
	 *
	 * @var string
	 */
	private static $condition = '';

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
	 * The active data-gvm-cond condition (empty when none).
	 *
	 * @return string
	 */
	public static function condition() {
		return self::$condition;
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

		// Only gate the single post view, never archives/lists/excerpts.
		if ( ! is_singular() ) {
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

		$stats = self::reading_stats( $content );

		if ( ! empty( $config['redirect'] ) ) {
			if ( self::is_unlocked( $config['reference'] ) ) {
				return $content;
			}

			// Teaser + a click-to-unlock trigger (redirect strategy).
			return self::teaser( $content, $config )
				. self::paywall(
					'',
					array(
						'price'           => $config['price'],
						'template'        => $config['template'],
						'reference'       => $config['reference'],
						'metadata_title'  => get_the_title( $post ),
						'cond'            => isset( $config['cond'] ) ? $config['cond'] : '',
						'category'        => isset( $config['category'] ) ? $config['category'] : '',
						'redirect'        => true,
						'redirect_to'     => get_permalink( $post ),
						'reading_words'   => $stats['words'],
						'reading_minutes' => $stats['minutes'],
					)
				);
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
				'cond'           => isset( $config['cond'] ) ? $config['cond'] : '',
				'category'       => isset( $config['category'] ) ? $config['category'] : '',
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
				'hide_strategy'  => Gvm_Settings::default_hide_strategy(),
				'hide_percent'   => 0,
				'hide_sections'  => 6,
				'hide_words'     => 0,
				'reference'      => '',
				'metadata_title' => '',
				'cond'           => '',
				'category'       => '',
				'redirect'       => false,
				'redirect_to'    => '',
				'download'       => false,
				'download_to'    => '',
			)
		);

		$strategy = in_array( $args['hide_strategy'], Gvm_Settings::hide_strategies(), true ) ? $args['hide_strategy'] : Gvm_Settings::default_hide_strategy();

		$condition = Gvm_Post::sanitize_cond( (string) $args['cond'] );
		if ( '' !== $condition ) {
			self::$condition = $condition;
		}

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

		$category = Gvm_Post::sanitize_category( (string) $args['category'] );
		if ( '' === $category ) {
			$category = Gvm_Categories::default_post_category();
		}
		if ( '' !== $category ) {
			$attrs['data-gvm-category'] = $category;
		}

		if ( ! empty( $args['download'] ) ) {
			// Download strategy: the node is the click trigger; gvm.js appends
			// the signature params to the download URL after payment.
			$attrs['data-gvm-http-download'] = (string) $args['download_to'];
			$content = self::static_trigger( $args, 'download' );
		} elseif ( ! empty( $args['redirect'] ) ) {
			// Redirect strategy: no hide action. The node itself is the click
			// trigger; gvm.js redirects with the signature params after payment
			// (server renders full content on the target URL).
			$redirect_to = (string) $args['redirect_to'];
			$attrs['data-gvm-http-redirect-to'] = '' !== $redirect_to ? $redirect_to : self::current_url();
			$content = self::static_trigger( $args, $template_slug );
		} elseif ( 'none' !== $strategy ) {
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
	 * Whether the current request is unlocked for a given reference.
	 *
	 * @param string $reference Expected reference (empty = any reference).
	 * @return bool
	 */
	public static function is_unlocked( $reference ) {
		if ( ! Gvm_Signature::verify_query_signature() ) {
			return false;
		}

		if ( '' === $reference ) {
			return true;
		}

		$params = Gvm_Signature::from_request();

		return hash_equals( $reference, $params['reference'] );
	}

	/**
	 * Build a teaser from content following the hide settings (sections/words/percent).
	 *
	 * @param string $content Full content.
	 * @param array  $args    Config (hide_sections, hide_words, hide_percent).
	 * @return string
	 */
	public static function teaser( $content, $args = array() ) {
		$sections = (int) ( isset( $args['hide_sections'] ) ? $args['hide_sections'] : 0 );
		$words    = (int) ( isset( $args['hide_words'] ) ? $args['hide_words'] : 0 );
		$percent  = (int) ( isset( $args['hide_percent'] ) ? $args['hide_percent'] : 0 );

		if ( $sections > 1 ) {
			$teaser = self::first_blocks( $content, $sections );
			if ( '' !== $teaser ) {
				return $teaser;
			}
		}

		$limit = 50;

		if ( $words > 5 ) {
			$limit = $words;
		} elseif ( $percent >= 1 && $percent <= 100 ) {
			$total = str_word_count( wp_strip_all_tags( $content ) );
			$limit = (int) round( $total * $percent / 100 );
		}

		if ( $limit < 1 ) {
			$limit = 50;
		}

		return wp_trim_words( $content, $limit, '&hellip;' );
	}

	/**
	 * Keep the first N top-level HTML blocks.
	 *
	 * @param string $content Full content.
	 * @param int    $count   Number of blocks to keep.
	 * @return string
	 */
	private static function first_blocks( $content, $count ) {
		if ( ! preg_match_all( '#<(p|h[1-6]|ul|ol|blockquote|figure|table|div)\b[^>]*>(?:(?!</\1>).)*</\1>#is', $content, $matches ) ) {
			return '';
		}

		return implode( "\n", array_slice( $matches[0], 0, (int) $count ) );
	}

	/**
	 * Reading stats (word count + minutes), mirroring gvm.js Utils.readingStats().
	 *
	 * @param string $content Full content.
	 * @return array{words:int,minutes:int}
	 */
	public static function reading_stats( $content ) {
		$text = (string) $content;

		// Approximate innerText: insert a space at block boundaries before stripping tags.
		$text = preg_replace( '#<(p|h[1-6]|li|br|div|section|article|blockquote|tr)[^>]*>#i', ' ', $text );
		$text = wp_strip_all_tags( $text );
		$text = html_entity_decode( $text, ENT_QUOTES, 'UTF-8' );

		$words   = array_filter( preg_split( '/\s+/', trim( $text ) ) );
		$count   = count( $words );
		$minutes = (int) ceil( $count / 200 );

		return array(
			'words'   => $count,
			'minutes' => $minutes,
		);
	}

	/**
	 * Build a visible trigger rendered server-side for redirect/download mode.
	 *
	 * Reuses a gvm template (paywall/download) for a consistent look. gvm.js's
	 * redirect/download action binds a click listener to the whole node, so
	 * data-gvm-bind-pay is not used here — the static bindings (price, currency,
	 * reading time/words, reference) are injected server-side.
	 *
	 * @param array  $args Paywall args (price, reading stats, reference).
	 * @param string $slug Template slug to render.
	 * @return string
	 */
	private static function static_trigger( $args, $slug ) {
		$slug = sanitize_key( (string) $slug );
		if ( '' === $slug ) {
			$slug = Gvm_Settings::default_template();
		}

		$html = self::template_html( $slug );

		$bindings = array(
			'price'         => esc_html( self::format_price( $args['price'] ) ),
			'currency'      => esc_html( Gvm_Settings::currency() ),
			'reading-time'  => isset( $args['reading_minutes'] ) ? (int) $args['reading_minutes'] : 0,
			'reading-words' => isset( $args['reading_words'] ) ? (int) $args['reading_words'] : 0,
			'reference'     => esc_html( (string) $args['reference'] ),
			'filename'      => isset( $args['filename'] ) ? esc_html( (string) $args['filename'] ) : '',
		);

		foreach ( $bindings as $key => $value ) {
			$html = str_replace(
				'<span data-gvm-bind-' . $key . '></span>',
				'<span>' . $value . '</span>',
				$html
			);
		}

		$html = str_replace( ' data-gvm-bind-pay', '', $html );

		return $html;
	}

	/**
	 * Current request URL (for data-gvm-http-redirect-to fallback).
	 *
	 * @return string
	 */
	public static function current_url() {
		$url = home_url( add_query_arg( array() ) );

		return (string) apply_filters( 'gvm_redirect_to', $url );
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
	 * Load a template fragment.
	 *
	 * For "payment", "paywall" and "download", the admin-defined settings
	 * template takes precedence; otherwise the bundled file (or a theme
	 * override via filter) is used.
	 *
	 * @param string $slug Template slug (payment|paywall|download).
	 * @return string
	 */
	public static function template_html( $slug ) {
		if ( 'payment' === $slug || 'paywall' === $slug || 'inline' === $slug || 'download' === $slug ) {
			$option = '';

			if ( 'payment' === $slug ) {
				$option = Gvm_Settings::template_payment();
			} elseif ( 'paywall' === $slug ) {
				$option = Gvm_Settings::template_paywall();
			} elseif ( 'inline' === $slug ) {
				$option = Gvm_Settings::template_inline();
			} else {
				$option = Gvm_Settings::template_download();
			}

			if ( '' !== trim( $option ) ) {
				return $option;
			}
		}

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
