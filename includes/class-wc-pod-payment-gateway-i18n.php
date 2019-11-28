<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       http://pod.ir
 * @since      1.0.0
 *
 * @package    WC_Pod_Payment_Gateway
 * @subpackage WC_Pod_Payment_Gateway/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    WC_Pod_Payment_Gateway
 * @subpackage WC_Pod_Payment_Gateway/includes
 * @author     Ehsan Houshmand <houshmand2007@gmail.com>
 */
class WC_Pod_Payment_Gateway_i18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'wc-pod-payment-gateway',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}



}
