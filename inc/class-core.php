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
		
		// Move Coupon Form to sidebar
		remove_action( 'woocommerce_before_checkout_form', 'woocommerce_checkout_coupon_form', 10 );
		add_action( 'icc_checkout_sidebar', 'woocommerce_checkout_coupon_form', 20 );
		
		// Customize checkout layout
		add_action( 'woocommerce_checkout_order_review', array( $this, 'customize_order_review' ), 5 );
		add_action( 'icc_checkout_sidebar', array( $this, 'render_cart_summary' ), 10 );
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

	public function customize_order_review() {
		// Remove the cart table from order review, keep only payment section
		remove_action( 'woocommerce_checkout_order_review', 'woocommerce_order_review', 10 );
		remove_action( 'woocommerce_checkout_order_review', 'woocommerce_checkout_payment', 20 );
		
		// Add payment heading
		add_action( 'woocommerce_checkout_order_review', array( $this, 'render_payment_heading' ), 15 );

		// Add shipping section before payment
		add_action( 'woocommerce_checkout_order_review', array( $this, 'render_shipping_section' ), 10 );

		// Add payment section back
		add_action( 'woocommerce_checkout_order_review', 'woocommerce_checkout_payment', 20 );

		// Add fragment update for shipping section
		add_filter( 'woocommerce_update_order_review_fragments', array( $this, 'add_shipping_fragment' ) );
	}

	public function render_payment_heading() {
		?>
		<h3 id="payment_heading"><?php esc_html_e( 'Zapłata', 'iviskin-checkout-customizer' ); ?></h3>
		<?php
	}

	public function render_shipping_section() {
		if ( ! WC()->cart->needs_shipping() || ! WC()->cart->show_shipping() ) {
			return;
		}

		$packages = WC()->shipping()->get_packages();

		echo '<div id="custom_shipping_methods_wrapper">';
		echo '<h3>' . esc_html__( 'Wysyłka', 'iviskin-checkout-customizer' ) . '</h3>';
		
		foreach ( $packages as $i => $package ) {
			$chosen_method = isset( WC()->session->chosen_shipping_methods[ $i ] ) ? WC()->session->chosen_shipping_methods[ $i ] : '';
			$product_names = array();

			if ( count( $packages ) > 1 ) {
				foreach ( $package['contents'] as $item_id => $values ) {
					$product_names[ $item_id ] = $values['data']->get_name() . ' &times;' . $values['quantity'];
				}
				$product_names = implode( ', ', $product_names );
			} else {
				$product_names = ''; // Don't show package name if only one package
			}

			?>
			<div class="custom-shipping-package">
				<?php if ( ! empty( $product_names ) ) : ?>
					<h4 class="package-name"><?php echo wp_kses_post( $product_names ); ?></h4>
				<?php endif; ?>

				<?php if ( count( $package['rates'] ) > 0 ) : ?>
					<ul id="shipping_method" class="woocommerce-shipping-methods">
						<?php foreach ( $package['rates'] as $method ) : ?>
							<li>
								<input type="radio" name="shipping_method[<?php echo esc_attr( $i ); ?>]" data-index="<?php echo esc_attr( $i ); ?>" id="shipping_method_<?php echo esc_attr( $i ); ?>_<?php echo esc_attr( sanitize_title( $method->id ) ); ?>" value="<?php echo esc_attr( $method->id ); ?>" class="shipping_method" <?php checked( $method->id, $chosen_method ); ?> />
								<label for="shipping_method_<?php echo esc_attr( $i ); ?>_<?php echo esc_attr( sanitize_title( $method->id ) ); ?>"><?php echo wc_cart_totals_shipping_method_label( $method ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></label>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php else : ?>
					<?php echo esc_html__( 'No shipping methods were found. Please double check your address, or contact us if you need any help.', 'woocommerce' ); ?>
				<?php endif; ?>
			</div>
			<?php
		}
		echo '</div>';
	}

	public function add_shipping_fragment( $fragments ) {
		ob_start();
		$this->render_shipping_section();
		$fragments['#custom_shipping_methods_wrapper'] = ob_get_clean();
		return $fragments;
	}

	public function render_cart_summary() {
		woocommerce_order_review();
	}
}
