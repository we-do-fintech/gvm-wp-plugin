<?php
/**
 * Plugin Name:       GVM (GetViaMsg)
 * Plugin URI:        https://github.com/we-do-fintech/gvm-wp-plugin
 * Description:       Integrates GetViaMsg (gvm.js) paywalls into WordPress sites with per-article pricing, templates and server-side signature verification.
 * Version:           0.1.4
 * Requires at least: 6.3
 * Requires PHP:      7.4
 * Author:            WDFT
 * Author URI:        https://wdft.ovh/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       gvm-wp
 *
 * @package Gvm_Wp
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'GVM_WP_VERSION', '0.1.4' );
define( 'GVM_WP_FILE', __FILE__ );
define( 'GVM_WP_DIR', plugin_dir_path( __FILE__ ) );
define( 'GVM_WP_URL', plugin_dir_url( __FILE__ ) );
define( 'GVM_WP_BASENAME', plugin_basename( __FILE__ ) );

require_once GVM_WP_DIR . 'includes/class-gvm-settings.php';
require_once GVM_WP_DIR . 'includes/class-gvm-categories.php';
require_once GVM_WP_DIR . 'includes/class-gvm-post.php';
require_once GVM_WP_DIR . 'includes/class-gvm-signature.php';
require_once GVM_WP_DIR . 'includes/class-gvm-render.php';
require_once GVM_WP_DIR . 'includes/class-gvm-download.php';
require_once GVM_WP_DIR . 'includes/class-gvm-enqueue.php';
require_once GVM_WP_DIR . 'includes/class-gvm-post-meta.php';
require_once GVM_WP_DIR . 'includes/class-gvm-block.php';
require_once GVM_WP_DIR . 'includes/class-gvm-shortcode.php';

/**
 * Bootstrap the plugin on plugins_loaded.
 *
 * @return void
 */
function gvm_init() {
	Gvm_Settings::init();
	Gvm_Post::init();
	Gvm_Render::init();
	Gvm_Download::init();
	Gvm_Enqueue::init();
	Gvm_Post_Meta::init();
	Gvm_Block::init();
	Gvm_Shortcode::init();
}
add_action( 'plugins_loaded', 'gvm_init' );

/**
 * Activation: store defaults and create the protected downloads directory.
 *
 * @return void
 */
function gvm_activate() {
	Gvm_Settings::install_defaults();
	Gvm_Download::ensure_protected();
}
register_activation_hook( __FILE__, 'gvm_activate' );
