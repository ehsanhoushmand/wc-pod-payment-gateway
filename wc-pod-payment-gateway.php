<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              http://pod.ir
 * @since             1.0.0
 * @package           WC_Pod_Payment_Gateway
 *
 * @wordpress-plugin
 * Plugin Name:       Pod Payment Gateway
 * Plugin URI:        http://pod.ir
 * Description:       Pod payment gateway of Woocommerce plugin
 * Version:           1.0.0
 * Author:            Ehsan Houshmand
 * Author URI:        http://ehsanhoushmand.ir/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       wc-pod-payment-gateway
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'WC_POD_PAYMENT_GATEWAY_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-wc-pod-payment-gateway-activator.php
 */
function activate_wc_pod_payment_gateway() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-wc-pod-payment-gateway-activator.php';
	WC_Pod_Payment_Gateway_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-wc-pod-payment-gateway-deactivator.php
 */
function deactivate_wc_pod_payment_gateway() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-wc-pod-payment-gateway-deactivator.php';
	WC_Pod_Payment_Gateway_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_wc_pod_payment_gateway' );
register_deactivation_hook( __FILE__, 'deactivate_wc_pod_payment_gateway' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-wc-pod-payment-gateway.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_wc_pod_payment_gateway() {

	$plugin = new WC_Pod_Payment_Gateway();
	$plugin->run();

}
run_wc_pod_payment_gateway();
