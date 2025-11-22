/**
 * Checkout Customizer Scripts
 * 
 * Handles AJAX cart updates, coupon application, and UI interactions.
 */
(function($){

    $(document).ready(function(){
        // Coupon toggle functionality
        $('.showcoupon').on('click', function(e) {
            e.preventDefault();
            $('.coupon').toggleClass('active').find('input[name="coupon_code"]').focus();
        });

        // AJAX coupon application
        $(document).on('click', 'button[name="apply_coupon"]', function(e) {
            e.preventDefault();
            applyCouponAjax();
        });

        // Apply coupon on Enter key press
        $(document).on('keypress', 'input[name="coupon_code"]', function(e) {
            if (e.which === 13) {
                e.preventDefault();
                applyCouponAjax();
            }
        });

        // Checkout cart quantity controls
        if ($('.woocommerce-checkout-review-order-table').length) {
            initCheckoutCartControls();
        }

        if ($('#dintero-checkout-iframe').length === 0) {
            setTimeout(() => {
                $('.shipping__table.shipping__table--multiple').fadeIn();
                $('#customer_details').fadeIn();
                $('#place_order').fadeIn();
            e.stopPropagation();
            const btn = $(this);
            var $input = btn.siblings('.qty-input');
            var currentVal = parseInt($input.val()) || 1;
            var maxVal = parseInt($input.attr('max')) || 999;
            
            if (currentVal < maxVal) {
                $input.val(currentVal + 1);
                scheduleCartUpdate($input);
            }
        });
        
        // Quantity decrease button  
        $(document).on('click', '.quantity-minus', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const btn = $(this);
            var $input = btn.siblings('.qty-input');
            var currentVal = parseInt($input.val()) || 1;
            var minVal = parseInt($input.attr('min')) || 1;
            
            if (currentVal > minVal) {
                $input.val(currentVal - 1);
                scheduleCartUpdate($input);
            }
        });
        
        // Remove cart item button
        $(document).on('click', '.remove-cart-item', function(e) {
            e.preventDefault();
            
            var cartItemKey = $(this).data('cart-item-key');
            var $row = $(this).closest('tr');
            
            // Add loading state
            $row.addClass('updating');
            
            // Show loading indicator
            $(this).html('<div class="loading-spinner"></div>');
            
            $.ajax({
                url: iviskin_cart_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'iviskin_remove_cart_item',
                    cart_item_key: cartItemKey,
                    nonce: iviskin_cart_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Update the checkout page with new fragments
                        updateCheckoutFragments(response.data.fragments);
                        
                        // Trigger Dintero update if available
                        if (typeof window.dintero_checkout !== 'undefined') {
                            setTimeout(function() {
                                window.dintero_checkout.refresh();
                            }, 100);
                        }
                        
                        // Show Norwegian success message
                        showMessage('Vare fjernet fra handlekurven', 'success');
                    } else {
                        showMessage('Kunne ikke fjerne vare fra handlekurven', 'error');
                        $row.removeClass('updating');
                    }
                },
                error: function() {
                    showMessage('En feil oppstod. Prøv igjen.', 'error');
                    $row.removeClass('updating');
                }
            });
        });
        
        // Schedule cart update with debouncing
        function scheduleCartUpdate($input) {
            clearTimeout(updateTimeout);
            
            // Add loading state
            $input.closest('tr').addClass('updating');
            
            updateTimeout = setTimeout(function() {
                updateCartQuantity($input);
            }, 1000); // 1 second delay
        }
        
        // Update cart quantity via AJAX
        function updateCartQuantity($input) {
            var cartItemKey = $input.data('cart-item-key');
            var quantity = parseInt($input.val()) || 1;
            var $row = $input.closest('tr');
            
            $.ajax({
                url: iviskin_cart_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'iviskin_update_cart_quantity',
                    cart_item_key: cartItemKey,
                    quantity: quantity,
                    nonce: iviskin_cart_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Update the checkout page with new fragments
                        updateCheckoutFragments(response.data.fragments);
                        
                        // Trigger Dintero update if available
                        if (typeof window.dintero_checkout !== 'undefined') {
                            setTimeout(function() {
                                window.dintero_checkout.refresh();
                            }, 100);
                        }
                        
                        // Show Norwegian success message
                        showMessage('Handlekurv oppdatert', 'success');
                    } else {
                        showMessage('Kunne ikke oppdatere handlekurven', 'error');
                    }
                    
                    $row.removeClass('updating');
                },
                error: function() {
                    showMessage('En feil oppstod. Prøv igjen.', 'error');
                    $row.removeClass('updating');
                }
            });
        }
    }

})(jQuery);