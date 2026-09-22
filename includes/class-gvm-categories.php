<?php
/**
 * Content categories: remote catalog with a bundled fallback.
 *
 * @package Gvm_Wp
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Resolves the gvm content-category catalog.
 *
 * The catalog lives in the gvm-sdk-admin backend and is exposed as
 * `GET https://overlay.<env>.gvm.wdft.ovh/categories` returning
 * `{ "categories": [ { "name": "article", "prettyName": "Article" } ] }`.
 *
 * The remote catalog is fetched lazily from the WordPress admin and cached in a
 * transient. When the fetch fails (or the environment is not a named
 * environment), the bundled `assets/categories.json` is used. A hard-coded
 * copy of the default catalog is the last resort so the UI always has values.
 */
class Gvm_Categories {

	const TRANSIENT_PREFIX = 'gvm_categories_';
	const CACHE_TTL        = 12 * HOUR_IN_SECONDS;

	/**
	 * Default category for page/post paywalls.
	 *
	 * @return string
	 */
	public static function default_post_category() {
		return 'article';
	}

	/**
	 * Default category for file downloads.
	 *
	 * @return string
	 */
	public static function default_download_category() {
		return 'report_pdf';
	}

	/**
	 * Remote categories endpoint for the configured environment (empty when none).
	 *
	 * @return string
	 */
	public static function endpoint() {
		$env = Gvm_Settings::env();

		$map = array(
			'dev'  => 'https://overlay.dev.gvm.wdft.ovh/categories',
			'qa'   => 'https://overlay.qa.gvm.wdft.ovh/categories',
			'prod' => 'https://overlay.gvm.wdft.ovh/categories',
		);

		$url = isset( $map[ $env ] ) ? $map[ $env ] : '';

		/**
		 * Filter the categories catalog endpoint.
		 *
		 * @param string $url Endpoint URL (empty to disable remote loading).
		 * @param string $env Current environment.
		 */
		return (string) apply_filters( 'gvm_categories_endpoint', $url, $env );
	}

	/**
	 * The full category catalog as name => prettyName.
	 *
	 * Fetches (and caches) the remote catalog, falling back to the bundled file
	 * and finally to the built-in defaults.
	 *
	 * @return array<string,string>
	 */
	public static function all() {
		$env = Gvm_Settings::env();
		$key = self::TRANSIENT_PREFIX . ( '' === $env ? 'default' : $env );

		$cached = get_transient( $key );
		if ( is_array( $cached ) && ! empty( $cached ) ) {
			return $cached;
		}

		$categories = self::fetch_remote();

		if ( empty( $categories ) ) {
			$categories = self::load_bundled();
		}

		if ( empty( $categories ) ) {
			$categories = self::builtin();
		}

		set_transient( $key, $categories, self::CACHE_TTL );

		return $categories;
	}

	/**
	 * The built-in default catalog (mirrors gvm-sdk-admin/categories.json).
	 *
	 * @return array<string,string>
	 */
	public static function builtin() {
		return array(
			'article'            => 'Article',
			'ebook'              => 'Ebook',
			'ebook_isbn'         => 'Ebook ISBN',
			'audiobook_stream'   => 'Audiobook (stream)',
			'audiobook_download' => 'Audiobook (download)',
			'movie_download'     => 'Movie (download)',
			'music_download'     => 'Music (download)',
			'vod_movie'          => 'VOD Movie',
			'vod_series'         => 'VOD Series',
			'vod_liveshow'       => 'VOD Live show',
			'report_pdf'         => 'Report PDF',
		);
	}

	/**
	 * Fetch and parse the remote catalog.
	 *
	 * @return array<string,string>
	 */
	private static function fetch_remote() {
		$url = self::endpoint();

		if ( '' === $url ) {
			return array();
		}

		$response = wp_remote_get(
			$url,
			array(
				'timeout' => 3,
				'headers' => array( 'Accept' => 'application/json' ),
			)
		);

		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			return array();
		}

		return self::parse( wp_remote_retrieve_body( $response ) );
	}

	/**
	 * Load the bundled catalog (vendored by build.sh).
	 *
	 * @return array<string,string>
	 */
	private static function load_bundled() {
		$file = GVM_WP_DIR . 'assets/categories.json';

		if ( ! is_readable( $file ) ) {
			return array();
		}

		return self::parse( (string) file_get_contents( $file ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_get_contents
	}

	/**
	 * Parse a `{ categories: [ { name, prettyName } ] }` payload.
	 *
	 * @param string $json Raw JSON.
	 * @return array<string,string>
	 */
	private static function parse( $json ) {
		$data = json_decode( (string) $json, true );

		if ( ! is_array( $data ) || empty( $data['categories'] ) || ! is_array( $data['categories'] ) ) {
			return array();
		}

		$out = array();

		foreach ( $data['categories'] as $entry ) {
			if ( ! is_array( $entry ) || empty( $entry['name'] ) ) {
				continue;
			}

			$name = Gvm_Post::sanitize_category( $entry['name'] );
			if ( '' === $name ) {
				continue;
			}

			$out[ $name ] = ! empty( $entry['prettyName'] )
				? sanitize_text_field( (string) $entry['prettyName'] )
				: $name;
		}

		return $out;
	}

	/**
	 * Catalog as a list of `{ name, prettyName }` objects (for the block editor).
	 *
	 * @return array<int,array{name:string,prettyName:string}>
	 */
	public static function list() {
		$out = array();

		foreach ( self::all() as $name => $pretty ) {
			$out[] = array(
				'name'       => $name,
				'prettyName' => $pretty,
			);
		}

		return $out;
	}
}