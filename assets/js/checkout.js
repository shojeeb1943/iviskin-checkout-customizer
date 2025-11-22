/**
 * Checkout Customizer Scripts
 * 
 * Handles AJAX cart updates, coupon application, and UI interactions.
 */
(function ($) {

    $(document).ready(function () {
        // Coupon toggle functionality
        $('.showcoupon').on('click', function (e) {
            e.preventDefault();
            $('.coupon').toggleClass('active').find('input[name="coupon_code"]').focus();
        });

        // AJAX coupon application
        $(document).on('click', 'button[name="apply_coupon"]', function (e) {
            e.preventDefault();
            applyCouponAjax();
        });

        // Apply coupon on Enter key press
        $(document).on('keypress', 'input[name="coupon_code"]', function (e) {
            if (e.which === 13) {
                e.preventDefault();
                applyCouponAjax();
            }
        });

        // Checkout cart quantity controls
        if ($('.woocommerce-checkout-review-order-table').length) {
            initCheckoutCartControls();
        }

        // Fix for Dintero iframe and hidden elements
        if ($('#dintero-checkout-iframe').length === 0) {
            setTimeout(() => {
                $('.shipping__table.shipping__table--multiple').fadeIn();
                $('#customer_details').fadeIn();
                $('#place_order').fadeIn();
                $('.woocommerce-privacy-policy-text').fadeIn();
            }, 2000);
        }
    });

    // Update checkout fragments (shared function)
    function updateCheckoutFragments(fragments, skipCheckoutUpdate) {
        $.each(fragments, function (key, value) {
            $(key).replaceWith(value);
        });

        // Only trigger WooCommerce update event if not explicitly skipped
        if (!skipCheckoutUpdate) {
            $('body').trigger('updated_checkout');
        }
    }

    // Show message to user (shared function)
    function showMessage(message, type, target) {
        // For coupon messages, show below coupon form
        if (target === 'coupon') {
            showCouponMessage(message, type);
            return;
        }

        // For cart messages, show in review order area
        var messageClass = type === 'success' ? 'woocommerce-message' : 'woocommerce-error';
        var $message = $('<div class="' + messageClass + '">' + message + '</div>');

        $('.woocommerce-checkout-review-order').prepend($message);

        setTimeout(function () {
            $message.fadeOut(function () {
                $(this).remove();
            });
        }, 3000);
    }

    // Show coupon-specific messages below coupon form
    function showCouponMessage(message, type) {
        // Remove existing coupon messages
        $('.coupon-message').remove();

        var iconSvg = '';
        var messageClass = 'coupon-message coupon-message-' + type;

        if (type === 'success') {
            iconSvg = '<svg class="message-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22,4 12,14.01 9,11.01"></polyline></svg>';
        } else {
            iconSvg = '<svg class="message-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>';
        }

        var $message = $('<div class="' + messageClass + '">' + iconSvg + '<span>' + message + '</span></div>');

        // Insert message below coupon form
        $('.coupon').after($message);

        // Auto-hide message after 4 seconds
        setTimeout(function () {
            $message.fadeOut(function () {
                $(this).remove();
            });
        }, 4000);
    }

    // Apply coupon via AJAX
    function applyCouponAjax() {
        var couponCode = $('input[name="coupon_code"]').val().trim();
        var $button = $('button[name="apply_coupon"]');
        var $input = $('input[name="coupon_code"]');

        if (!couponCode) {
            showMessage('Vennligst skriv inn en rabattkode', 'error', 'coupon');
            $input.focus();
            return;
        }

        // Add loading state
        var originalButtonText = $button.text();
        $button.prop('disabled', true).html('<div class="loading-spinner"></div>');
        $input.prop('disabled', true);

        $.ajax({
            url: iviskin_cart_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'iviskin_apply_coupon',
                coupon_code: couponCode,
                nonce: iviskin_cart_ajax.nonce
            },
            success: function (response) {
                if (response.success) {
                    // Update the checkout page with new fragments (skip checkout update to maintain Dintero state)
                    updateCheckoutFragments(response.data.fragments, true);

                    // Clear coupon input
                    $input.val('');

                    // Hide coupon form
                    $('.coupon').removeClass('active');

                    // Trigger Dintero update if available (with longer delay to ensure DOM is ready)
                    if (typeof window.dintero_checkout !== 'undefined') {
                        setTimeout(function () {
                            window.dintero_checkout.refresh();
                        }, 300);
                    }

                    // Show success message below coupon form
                    showMessage('Rabattkode lagt til', 'success', 'coupon');
                } else {
                    showMessage('Ugyldig rabattkode', 'error', 'coupon');
                }
            },
            error: function () {
                showMessage('Ugyldig rabattkode', 'error', 'coupon');
            },
            complete: function () {
                // Reset button state
                $button.prop('disabled', false).html(originalButtonText);
                $input.prop('disabled', false);
            }
        });
    }

    // Initialize checkout cart controls
    function initCheckoutCartControls() {
        var updateTimeout;

        // Quantity increase button
        $(document).on('click', '.quantity-plus', function (e) {
            e.preventDefault();
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
        $(document).on('click', '.quantity-minus', function (e) {
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
        $(document).on('click', '.remove-cart-item', function (e) {
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
                success: function (response) {
                    if (response.success) {
                        // Update the checkout page with new fragments
                        updateCheckoutFragments(response.data.fragments);

                        // Trigger Dintero update if available
                        if (typeof window.dintero_checkout !== 'undefined') {
                            setTimeout(function () {
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
                error: function () {
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

            updateTimeout = setTimeout(function () {
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
                success: function (response) {
                    if (response.success) {
                        // Update the checkout page with new fragments
                        updateCheckoutFragments(response.data.fragments);

                        // Trigger Dintero update if available
                        if (typeof window.dintero_checkout !== 'undefined') {
                            setTimeout(function () {
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
                error: function () {
                    showMessage('En feil oppstod. Prøv igjen.', 'error');
                    $row.removeClass('updating');
                }
            });
        }
    }

})(jQuery);