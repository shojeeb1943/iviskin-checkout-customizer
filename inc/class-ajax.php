<?php
namespace Iviskin\CheckoutCustomizer;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Ajax {

	private static $instance = null;

	/**
	 * Get the singleton instance.
	 *
	 * @return Ajax
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 */
	private function init_hooks() {
		add_action( 'wp_ajax_iviskin_update_cart_quantity', array( $this, 'update_cart_quantity' ) );
		add_action( 'wp_ajax_nopriv_iviskin_update_cart_quantity', array( $this, 'update_cart_quantity' ) );

		add_action( 'wp_ajax_iviskin_remove_cart_item', array( $this, 'remove_cart_item' ) );
		add_action( 'wp_ajax_nopriv_iviskin_remove_cart_item', array( $this, 'remove_cart_item' ) );

		add_action( 'wp_ajax_iviskin_apply_coupon', array( $this, 'apply_coupon' ) );
		add_action( 'wp_ajax_nopriv_iviskin_apply_coupon', array( $this, 'apply_coupon' ) );
	}

	/**
	 * Update cart quantity via AJAX.
	 */
	public function update_cart_quantity() {
		check_ajax_referer( 'iviskin_cart_nonce', 'nonce' );

		$cart_item_key = sanitize_text_field( $_POST['cart_item_key'] );
		$quantity      = intval( $_POST['quantity'] );

		if ( empty( $cart_item_key ) || $quantity < 0 ) {
			wp_die();
		}

		if ( $quantity == 0 ) {
			\WC()->cart->remove_cart_item( $cart_item_key );
		} else {
			\WC()->cart->set_quantity( $cart_item_key, $quantity );
		}

		\WC()->cart->calculate_totals();

		$fragments = array();
		$fragments['.woocommerce-checkout-review-order-table'] = $this->get_checkout_review_order_table();

		// Only update the table, not the entire wrapper (which includes payment methods)
		// This prevents payment gateways from disappearing due to missing address data in this AJAX context
		// $fragments['.woocommerce-checkout-review-order'] = ...

		wp_send_json_success( array(
			'fragments'  => $fragments,
			'cart_total' => \WC()->cart->get_total(),
		) );
	}

	/**
	 * Remove cart item via AJAX.
	 */
	public function remove_cart_item() {
		check_ajax_referer( 'iviskin_cart_nonce', 'nonce' );

		$cart_item_key = sanitize_text_field( $_POST['cart_item_key'] );

		if ( empty( $cart_item_key ) ) {
			wp_die();
		}

		\WC()->cart->remove_cart_item( $cart_item_key );
		\WC()->cart->calculate_totals();

		$fragments = array();
		$fragments['.woocommerce-checkout-review-order-table'] = $this->get_checkout_review_order_table();

		// Only update the table
		// ob_start();
		// woocommerce_order_review();
		// $fragments['.woocommerce-checkout-review-order'] = ob_get_clean();

		wp_send_json_success( array(
			'fragments'  => $fragments,
			'cart_total' => \WC()->cart->get_total(),
			'cart_count' => \WC()->cart->get_cart_contents_count(),
		) );
	}

	/**
	 * Apply coupon via AJAX.
	 */
	public function apply_coupon() {
		check_ajax_referer( 'iviskin_cart_nonce', 'nonce' );

		$coupon_code = sanitize_text_field( $_POST['coupon_code'] );

		if ( empty( $coupon_code ) ) {
			wp_send_json_error( array( 'message' => 'Vennligst skriv inn en rabattkode' ) );
		}

		$coupon = new \WC_Coupon( $coupon_code );

		if ( ! $coupon->is_valid() ) {
			wp_send_json_error( array( 'message' => 'Ugyldig rabattkode' ) );
		}

		if ( \WC()->cart->has_discount( $coupon_code ) ) {
			wp_send_json_error( array( 'message' => 'Rabattkoden er allerede brukt' ) );
		}

		wc_clear_notices();

		$result = \WC()->cart->apply_coupon( $coupon_code );

		if ( $result ) {
			wc_clear_notices();
			\WC()->cart->calculate_totals();

			$fragments = array();
			$fragments['.woocommerce-checkout-review-order-table'] = $this->get_checkout_review_order_table();

			// Only update the table
			// ob_start();
			// woocommerce_order_review();
			// $fragments['.woocommerce-checkout-review-order'] = ob_get_clean();
			wc_clear_notices();

			wp_send_json_success( array(
				'fragments'  => $fragments,
				'message'    => sprintf( 'Rabattkode "%s" er brukt!', $coupon_code ),
				'cart_total' => \WC()->cart->get_total(),
			) );
		} else {
			$notices = wc_get_notices( 'error' );
			$error_message = 'Kunne ikke bruke rabattkoden';
			if ( ! empty( $notices ) ) {
				$error_message = $notices[0]['notice'];
				wc_clear_notices();
			}
			wp_send_json_error( array( 'message' => $error_message ) );
		}
	}

	/**
	 * Get the checkout review order table fragment.
	 *
	 * @return string HTML content.
	 */
	private function get_checkout_review_order_table() {
		ob_start();
		wc_get_template( 'checkout/review-order.php' );
		return ob_get_clean();
	}
}
