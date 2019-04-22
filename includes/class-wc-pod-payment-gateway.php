<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       http://pod.land
 * @since      1.0.0
 *
 * @package    WC_Pod_Payment_Gateway
 * @subpackage WC_Pod_Payment_Gateway/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    WC_Pod_Payment_Gateway
 * @subpackage WC_Pod_Payment_Gateway/includes
 * @author     Ehsan Houshmand <houshmand2007@gmail.com>
 */
class WC_Pod_Payment_Gateway {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      WC_Pod_Payment_Gateway_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $wc_pod_payment_gateway    The string used to uniquely identify this plugin.
	 */
	protected $wc_pod_payment_gateway;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( defined( 'WC_POD_PAYMENT_GATEWAY_VERSION' ) ) {
			$this->version = WC_POD_PAYMENT_GATEWAY_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->wc_pod_payment_gateway = 'wc-pod-payment-gateway';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_payment_status();

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - WC_Pod_Payment_Gateway_Loader. Orchestrates the hooks of the plugin.
	 * - WC_Pod_Payment_Gateway_i18n. Defines internationalization functionality.
	 * - WC_Pod_Payment_Gateway_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wc-pod-payment-gateway-loader.php';

		/**
		 * The class core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wc-pod-payment-gateway-core.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wc-pod-payment-gateway-i18n.php';

		$this->loader = new WC_Pod_Payment_Gateway_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the WC_Pod_Payment_Gateway_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new WC_Pod_Payment_Gateway_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	private function define_payment_status() {
		add_action( 'add_meta_boxes', 'add_meta_boxesws' );
		function add_meta_boxesws()
		{
			add_meta_box( 'custom_order_meta_box', __( 'پی پاد | PayPod' ),
				'custom_metabox_content', 'shop_order', 'normal', 'high');
		}

		function custom_metabox_content(){
			$post_id = isset($_GET['post']) ? $_GET['post'] : false;
			if(! $post_id ) return; // Exit

			$invoice_id = get_post_meta($post_id,'_transaction_id', true);
			$invoice_state = get_post_meta($post_id,'_invoice_state', true);

			if(isset($_GET['pod_job'])){
				$options = get_option( 'podsso_options' );
				if($_GET['pod_job']=='close'){

					$server_url = $options['api_url'] . '/nzh/biz/closeInvoice/?id='.$invoice_id;

					$requestArray = array(
						'method'      => 'GET',
						'timeout'     => 45,
						'redirection' => 5,
						'httpversion' => '1.0',
						'blocking'    => true,
						'headers'     => array(
							'_token_' => $options['api_token'],
							'_token_issuer_' => '1',
						),
						'cookies'     => array(),
						'sslverify'   => false
					);
					$response   = wp_remote_post( $server_url, $requestArray );
					$res_info = json_decode( $response['body'] );

					if ( isset( $res_info->error ) ) {
						wp_die( $res_info->error_description );
					}
					if( $res_info-> hasError ){
						wp_die("Contact Admin, Error Code: ".$res_info->errorCode);
					}
					update_post_meta($post_id, '_invoice_state', 'closed');
					$invoice_state = 'closed';

				}
				else if($_GET['pod_job']=='cancel')
				{
					$server_url = $options['api_url'] . '/nzh/biz/cancelInvoice/?id='.$invoice_id;

					$requestArray = array(
						'method'      => 'GET',
						'timeout'     => 45,
						'redirection' => 5,
						'httpversion' => '1.0',
						'blocking'    => true,
						'headers'     => array(
							'_token_' => $options['api_token'],
							'_token_issuer_' => '1',
						),
						'cookies'     => array(),
						'sslverify'   => false
					);
					$response   = wp_remote_post( $server_url, $requestArray );
					$res_info = json_decode( $response['body'] );

					if ( isset( $res_info->error ) ) {
						wp_die( $res_info->error_description );
					}
					if( $res_info-> hasError ){
						wp_die("Contact Admin, Error Code: ".$res_info->errorCode);
					}
					update_post_meta($post_id, '_invoice_state', 'canceled');
					$invoice_state = 'canceled';
				}
			}

			if($invoice_state == 'notpaid' && $invoice_id){
				echo "<ul><li><b>وضعیت فاکتور:</b><span style='color:red'> پرداخت نشده</span></li></ul>";
			}
			elseif ($invoice_state == 'open'){
				?>
				<ul><li><b>وضعیت فاکتور:</b><span> پرداخت شده | باز</span></li></ul>
				<p><a href="?post=<?php echo $post_id; ?>&action=edit&pod_job=close" class="button"><?php _e('بستن فاکتور'); ?></a></p>
				<p><a href="?post=<?php echo $post_id; ?>&action=edit&pod_job=cancel" class="button"><?php _e('ابطال فاکتور'); ?></a></p>
				<?php
			}
			elseif ($invoice_state == 'canceled')
			{
				echo "<ul><li><b>وضعیت فاکتور:</b><span> 	باطل شده پس از پرداخت </span></li></ul>";
			}
			elseif ($invoice_state == 'closed')
			{
				echo "<ul><li><b>وضعیت فاکتور:</b><span> پرداخت شده | بسته شده	</span></li></ul>";
			}
			else
			{
				echo "این سفارش با درگاه پی‌پاد پرداخت نشده است.";
			}
		}
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_wc_pod_payment_gateway() {
		return $this->wc_pod_payment_gateway;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    WC_Pod_Payment_Gateway_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

}
