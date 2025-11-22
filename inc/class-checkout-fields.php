<?php
namespace Iviskin\CheckoutCustomizer;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Checkout_Fields {

	private static $instance = null;

	/**
	 * Get the singleton instance.
	 *
	 * @return Checkout_Fields
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
		add_filter( 'woocommerce_checkout_fields', array( $this, 'customize_checkout_fields' ), 9999 );
		add_filter( 'woocommerce_default_address_fields', array( $this, 'customize_default_address_fields' ) );
		add_action( 'wp_footer', array( $this, 'add_co_field_script' ) );
	}

	/**
	 * Customize checkout fields.
	 *
	 * @param array $fields Checkout fields.
	 * @return array
	 */
	public function customize_checkout_fields( $fields ) {
		// Remove unwanted fields if necessary (e.g. company)
		unset( $fields['billing']['billing_company'] );
		unset( $fields['shipping']['shipping_company'] );
		unset( $fields['billing']['billing_address_2'] ); // We will handle C/O differently or re-add it
		unset( $fields['shipping']['shipping_address_2'] );

		// Reorder and resize Billing Fields
		$billing_order = array(
			'billing_email',
			'billing_phone',
			'billing_first_name',
			'billing_last_name',
			'billing_country',
			'billing_address_1',
			'billing_address_2', // Re-adding for C/O logic
			'billing_postcode',
			'billing_city',
		);

		$fields['billing'] = $this->reorder_fields( $fields['billing'], $billing_order );

		// Email - Full Width
		$fields['billing']['billing_email']['class'] = array( 'form-row-wide' );
		$fields['billing']['billing_email']['priority'] = 10;

		// Phone - Full Width
		$fields['billing']['billing_phone']['class'] = array( 'form-row-wide' );
		$fields['billing']['billing_phone']['priority'] = 20;

		// First Name - Half Width (First)
		$fields['billing']['billing_first_name']['class'] = array( 'form-row-first' );
		$fields['billing']['billing_first_name']['priority'] = 30;

		// Last Name - Half Width (Last)
		$fields['billing']['billing_last_name']['class'] = array( 'form-row-last' );
		$fields['billing']['billing_last_name']['priority'] = 40;

		// Country - Full Width
		$fields['billing']['billing_country']['class'] = array( 'form-row-wide' );
		$fields['billing']['billing_country']['priority'] = 50;

		// Address - Full Width
		$fields['billing']['billing_address_1']['class'] = array( 'form-row-wide' );
		$fields['billing']['billing_address_1']['priority'] = 60;
		$fields['billing']['billing_address_1']['placeholder'] = '';

		// C/O (Address 2) - Hidden initially, Full Width
		$fields['billing']['billing_address_2'] = array(
			'label'       => __( 'C/O', 'iviskin-checkout-customizer' ),
			'placeholder' => 'C/O',
			'required'    => false,
			'class'       => array( 'form-row-wide', 'billing-co-field' ),
			'clear'       => true,
			'priority'    => 65,
		);

		// Postcode - Half Width (First)
		$fields['billing']['billing_postcode']['class'] = array( 'form-row-first' );
		$fields['billing']['billing_postcode']['priority'] = 70;

		// City - Half Width (Last)
		$fields['billing']['billing_city']['class'] = array( 'form-row-last' );
		$fields['billing']['billing_city']['priority'] = 80;

		return $fields;
	}

	/**
	 * Customize default address fields.
	 *
	 * @param array $fields Address fields.
	 * @return array
	 */
	public function customize_default_address_fields( $fields ) {
		// Ensure default address fields also follow the structure where possible
		return $fields;
	}

	/**
	 * Reorder fields based on a specific order array.
	 *
	 * @param array $fields Fields to reorder.
	 * @param array $order  Order of field keys.
	 * @return array
	 */
	private function reorder_fields( $fields, $order ) {
		$new_fields = array();
		foreach ( $order as $field_key ) {
			if ( isset( $fields[ $field_key ] ) ) {
				$new_fields[ $field_key ] = $fields[ $field_key ];
			}
		}
		// Add back any remaining fields not in the order array
		foreach ( $fields as $field_key => $field_data ) {
			if ( ! isset( $new_fields[ $field_key ] ) ) {
				$new_fields[ $field_key ] = $field_data;
			}
		}
		return $new_fields;
	}

	/**
	 * Add script for C/O field toggle.
	 */
	public function add_co_field_script() {
		if ( ! is_checkout() ) {
			return;
		}
		?>
		<script type="text/javascript">
		jQuery(document).ready(function($) {
			// Add "Add C/O" link inside the Address label or field wrapper
			var $addressRow = $('#billing_address_1_field');
			var $coRow = $('#billing_address_2_field');
			
			// Initially hide C/O field if empty
			if( $coRow.find('input').val() === '' ) {
				$coRow.hide();
			}

			// Create the toggle link
			var $toggleLink = $('<a href="#" class="add-co-link" style="float:right; font-size: 12px; text-decoration: underline;">Add C/O</a>');
			
			// Append to the label of address field
			$addressRow.find('label').append($toggleLink);

			$toggleLink.on('click', function(e) {
				e.preventDefault();
				$coRow.slideToggle();
				$(this).text( $coRow.is(':visible') ? 'Remove C/O' : 'Add C/O' );
			});
		});
		</script>
		<?php
	}
}
