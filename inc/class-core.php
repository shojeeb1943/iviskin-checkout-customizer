<?php
namespace Iviskin\CheckoutCustomizer;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Core {

	private static $instance = null;

	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		$this->init_hooks();
		$this->init_components();
	}

	private function init_hooks() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'woocommerce_before_checkout_form', array( $this, 'set_default_payment_gateway' ) );
		add_filter( 'woocommerce_default_payment_method', array( $this, 'force_dintero_default' ) );
		add_action( 'wp_footer', array( $this, 'force_dintero_selection' ) );
		add_action( 'wp_loaded', array( $this, 'clear_checkout_notices' ) );
		
		// Move Coupon Form
		remove_action( 'woocommerce_before_checkout_form', 'woocommerce_checkout_coupon_form', 10 );
		add_action( 'woocommerce_review_order_before_payment', 'woocommerce_checkout_coupon_form', 10 );
	}

	private function init_components() {
		Ajax::instance();
		Template_Loader::instance();
		Checkout_Fields::instance();
	}

	public function enqueue_scripts() {
		if ( is_checkout() ) {
			wp_enqueue_style( 'icc-checkout-css', ICC_PLUGIN_URL . 'assets/css/checkout.css', array(), ICC_VERSION );
			wp_enqueue_script( 'icc-checkout-js', ICC_PLUGIN_URL . 'assets/js/checkout.js', array( 'jquery' ), ICC_VERSION, true );

			wp_localize_script( 'icc-checkout-js', 'iviskin_cart_ajax', array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'iviskin_cart_nonce' )
			) );
		}
	}

	public function set_default_payment_gateway() {
		if ( WC()->session ) {
			$available_gateways = WC()->payment_gateways->get_available_payment_gateways();
			if ( isset( $available_gateways['dintero_checkout'] ) ) {
				WC()->session->set( 'chosen_payment_method', 'dintero_checkout' );
			}
		}
	}

	public function force_dintero_default( $default_gateway ) {
		$available_gateways = WC()->payment_gateways->get_available_payment_gateways();
		if ( isset( $available_gateways['dintero_checkout'] ) ) {
			return 'dintero_checkout';
		}
		return $default_gateway;
	}

	public function force_dintero_selection() {
		if ( ! is_checkout() ) {
			return;
		}
		?>
		<script type="text/javascript">
		jQuery(document).ready(function($) {
			function selectDinteroGateway() {
				var $dinteroRadio = $('input[name="payment_method"][value="dintero_checkout"]');
				if ($dinteroRadio.length && !$dinteroRadio.is(':checked')) {
					$dinteroRadio.prop('checked', true).trigger('change');
				}
			}
			selectDinteroGateway();
			$(document.body).on('updated_checkout', function() {
				setTimeout(selectDinteroGateway, 100);
			});
		});
		</script>
		<?php
	}

	public function clear_checkout_notices() {
		if ( is_admin() || ! is_checkout() ) {
			return;
		}
		add_action( 'woocommerce_before_checkout_form', function() {
			$notices = wc_get_notices( 'success' );
			if ( ! empty( $notices ) ) {
				foreach ( $notices as $notice ) {
					if ( strpos( $notice['notice'], 'Coupon code' ) !== false || strpos( $notice['notice'], 'rabattkode' ) !== false ) {
						wc_clear_notices();
						break;
					}
				}
			}
		}, 1 );
	}
}
