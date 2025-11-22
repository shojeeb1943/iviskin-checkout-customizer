<?php
/**
 * Review order table
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/review-order.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 5.2.0
 */

defined( 'ABSPATH' ) || exit;
?>
<table class="shop_table woocommerce-checkout-review-order-table">
	<thead>
		<tr>
			<th class="product-name"><?php esc_html_e( 'Product', 'woocommerce' ); ?></th>
			<th class="product-total"><?php esc_html_e( 'Subtotal', 'woocommerce' ); ?></th>
		</tr>
	</thead>
	<tbody>
		<?php
		do_action( 'woocommerce_review_order_before_cart_contents' );

		foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
			$_product = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );

			if ( $_product && $_product->exists() && $cart_item['quantity'] > 0 && apply_filters( 'woocommerce_checkout_cart_item_visible', true, $cart_item, $cart_item_key ) ) {
				?>
				<tr class="<?php echo esc_attr( apply_filters( 'woocommerce_cart_item_class', 'cart_item', $cart_item, $cart_item_key ) ); ?>" data-cart-item-key="<?php echo esc_attr( $cart_item_key ); ?>">
					<td class="product-name">
                        <div class="cart-item-info">
                            <div class="product-image">
                                <?php echo apply_filters( 'woocommerce_cart_item_thumbnail', $_product->get_image(), $cart_item, $cart_item_key ); ?>
                            </div>
                            <div class="cart-item-data">
                                <span class="cart-item-title"><?php echo wp_kses_post( apply_filters( 'woocommerce_cart_item_name', $_product->get_name(), $cart_item, $cart_item_key ) ) . '&nbsp;'; ?></span>
                                
                                <div class="quantity-controls">
                                    <button type="button" class="quantity-btn quantity-minus without-button" data-cart-item-key="<?php echo esc_attr( $cart_item_key ); ?>"></button>
                                    <input type="number" 
                                           class="qty-input" 
                                           data-cart-item-key="<?php echo esc_attr( $cart_item_key ); ?>"
                                           value="<?php echo esc_attr( $cart_item['quantity'] ); ?>" 
                                           min="1" 
                                           max="999"
                                           readonly>
                                    <button type="button" class="quantity-btn quantity-plus without-button" data-cart-item-key="<?php echo esc_attr( $cart_item_key ); ?>"></button>
                                </div>
                                
                                <?php echo wc_get_formatted_cart_item_data( $cart_item ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                            </div>
                        </div>
					</td>
					<td class="product-total position-relative">
                        <button type="button" class="remove-cart-item" data-cart-item-key="<?php echo esc_attr( $cart_item_key ); ?>" title="Fjern vare">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512">
                                <path d="M408 120c-4.406 0-8 3.578-8 8v312c0 30.88-25.12 56-56 56h-240c-30.88 0-56-25.12-56-56V128c0-4.422-3.594-8-8-8S32 123.6 32 128v312C32 479.7 64.31 512 104 512h240c39.69 0 72-32.3 72-72V128C416 123.6 412.4 120 408 120zM440 64h-116.6l-20.95-41.88C295.6 8.469 281.9 0 266.7 0H181.3C166.1 0 152.4 8.469 145.6 22.11L124.6 64H8C3.594 64 0 67.58 0 72S3.594 80 8 80h432C444.4 80 448 76.42 448 72S444.4 64 440 64zM142.5 64l17.37-34.75C163.1 21.08 172.2 16 181.3 16h85.31c9.156 0 17.38 5.078 21.47 13.27L305.5 64H142.5zM136 432v-256c0-4.422-3.594-8-8-8S120 171.6 120 176v256c0 4.422 3.594 8 8 8S136 436.4 136 432zM232 432v-256c0-4.422-3.594-8-8-8S216 171.6 216 176v256c0 4.422 3.594 8 8 8S232 436.4 232 432zM328 432v-256c0-4.422-3.594-8-8-8s-8 3.578-8 8v256c0 4.422 3.594 8 8 8S328 436.4 328 432z"></path>
                            </svg>
                        </button>
						<?php echo apply_filters( 'woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal( $_product, $cart_item['quantity'] ), $cart_item, $cart_item_key ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</td>
				</tr>
				<?php
			}
		}

		do_action( 'woocommerce_review_order_after_cart_contents' );
		?>
	</tbody>
	<tfoot>

		<tr class="cart-subtotal">
			<th><?php esc_html_e( 'Subtotal', 'woocommerce' ); ?></th>
			<td><?php wc_cart_totals_subtotal_html(); ?></td>
		</tr>

		<?php foreach ( WC()->cart->get_coupons() as $code => $coupon ) : ?>
			<tr class="cart-discount coupon-<?php echo esc_attr( sanitize_title( $code ) ); ?>">
				<th><?php wc_cart_totals_coupon_label( $coupon ); ?></th>
				<td><?php wc_cart_totals_coupon_html( $coupon ); ?></td>
			</tr>
		<?php endforeach; ?>

		<?php if ( WC()->cart->needs_shipping() && WC()->cart->show_shipping() ) : ?>

			<?php do_action( 'woocommerce_review_order_before_shipping' ); ?>

			<?php wc_cart_totals_shipping_html(); ?>

			<?php do_action( 'woocommerce_review_order_after_shipping' ); ?>

		<?php endif; ?>

		<?php foreach ( WC()->cart->get_fees() as $fee ) : ?>
			<tr class="fee">
				<th><?php echo esc_html( $fee->name ); ?></th>
				<td><?php wc_cart_totals_fee_html( $fee ); ?></td>
			</tr>
		<?php endforeach; ?>

		<?php if ( wc_tax_enabled() && ! WC()->cart->display_prices_including_tax() ) : ?>
			<?php if ( 'itemized' === get_option( 'woocommerce_tax_total_display' ) ) : ?>
				<?php foreach ( WC()->cart->get_tax_totals() as $code => $tax ) : // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited ?>
					<tr class="tax-rate tax-rate-<?php echo esc_attr( sanitize_title( $code ) ); ?>">
						<th><?php echo esc_html( $tax->label ); ?></th>
						<td><?php echo wp_kses_post( $tax->formatted_amount ); ?></td>
					</tr>
				<?php endforeach; ?>
			<?php else : ?>
				<tr class="tax-total">
					<th><?php echo esc_html( WC()->countries->tax_or_vat() ); ?></th>
					<td><?php wc_cart_totals_taxes_total_html(); ?></td>
				</tr>
			<?php endif; ?>
		<?php endif; ?>

		<?php do_action( 'woocommerce_review_order_before_order_total' ); ?>

		<tr class="order-total">
			<th><?php esc_html_e( 'Total', 'woocommerce' ); ?></th>
			<td><?php wc_cart_totals_order_total_html(); ?></td>
		</tr>

		<?php do_action( 'woocommerce_review_order_after_order_total' ); ?>

	</tfoot>
</table>
