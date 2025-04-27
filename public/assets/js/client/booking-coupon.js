document.addEventListener('DOMContentLoaded', function() {
    // Coupon Code Handling
    const couponInput = document.getElementById('coupon_code');
    const checkCouponBtn = document.getElementById('check-coupon');
    const couponMessage = document.getElementById('coupon-message');
    const couponDetails = document.getElementById('coupon-details');
    const couponCodeDisplay = document.getElementById('coupon-code-display');
    const couponDiscountDisplay = document.getElementById('coupon-discount-display');
    const removeCouponBtn = document.getElementById('remove-coupon');

    if (checkCouponBtn && couponInput) {
        // Add input animation effect
        couponInput.addEventListener('focus', function() {
            this.closest('.coupon-input-container').classList.add('focused');
        });

        couponInput.addEventListener('blur', function() {
            this.closest('.coupon-input-container').classList.remove('focused');
        });

        // Handle enter key press
        couponInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter' && checkCouponBtn) {
                e.preventDefault();
                checkCouponBtn.click();
            }
        });

        // Add button click animation
        checkCouponBtn.addEventListener('mousedown', function() {
            this.classList.add('btn-pressed');
        });

        document.addEventListener('mouseup', function() {
            checkCouponBtn.classList.remove('btn-pressed');
        });

        // Handle coupon verification
        checkCouponBtn.addEventListener('click', function() {
            const couponCode = couponInput.value.trim();
            if (!couponCode) {
                showCouponMessage('يرجى إدخال كود الخصم', 'warning');
                shakeCouponInput();
                return;
            }

            const selectedPackageRadio = document.querySelector('.package-select:checked');
            if (!selectedPackageRadio) {
                showCouponMessage('يرجى اختيار باقة أولاً', 'warning');
                return;
            }

            // Show loading state
            showLoadingState(true);

            const packageId = selectedPackageRadio.value;
            validateCoupon(couponCode, packageId);
        });
    }

    if (removeCouponBtn) {
        removeCouponBtn.addEventListener('click', function() {
            const appliedCoupon = this.closest('.applied-coupon');
            appliedCoupon.classList.add('slide-out');

            // Add slight delay before actually removing the coupon
            setTimeout(() => {
                removeCoupon();
            }, 300);
        });
    }

    function showLoadingState(isLoading) {
        if (isLoading) {
            checkCouponBtn.disabled = true;
            checkCouponBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            couponInput.disabled = true;
        } else {
            checkCouponBtn.disabled = false;
            checkCouponBtn.innerHTML = '<span class="verify-text">تحقق</span><i class="fas fa-check"></i>';
            couponInput.disabled = false;
        }
    }

    function shakeCouponInput() {
        const container = couponInput.closest('.coupon-input-container');
        container.classList.add('shake-animation');
        setTimeout(() => {
            container.classList.remove('shake-animation');
        }, 500);
    }

    function validateCoupon(couponCode, packageId) {
        showCouponMessage('جاري التحقق من كود الخصم...', 'info');

        const tokenElement = document.querySelector('meta[name="csrf-token"]');
        if (!tokenElement) {
            showLoadingState(false);
            showCouponMessage('حدث خطأ في النظام. يرجى تحديث الصفحة والمحاولة مرة أخرى', 'danger');
            return;
        }

        const token = tokenElement.getAttribute('content');

        fetch('/client/bookings/validate-coupon', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                coupon_code: couponCode,
                package_id: packageId
            })
        })
        .then(response => response.json())
        .then(data => {
            showLoadingState(false);

            if (data.status === 'success') {
                showCouponMessage('تم تطبيق كود الخصم بنجاح!', 'success');
                setTimeout(() => {
                    couponMessage.textContent = '';
                    applyCoupon(data.coupon);
                }, 1000);
            } else {
                showCouponMessage(data.message || 'كود الخصم غير صالح', 'danger');
                shakeCouponInput();
            }
        })
        .catch(error => {
            console.error('Error validating coupon:', error);
            showLoadingState(false);
            showCouponMessage('حدث خطأ أثناء التحقق من كود الخصم', 'danger');
        });
    }

    function applyCoupon(coupon) {
        // Format coupon details
        couponCodeDisplay.textContent = coupon.code;

        let discountText = '';
        if (coupon.discount_type === 'percentage') {
            discountText = `خصم ${coupon.discount_value}%`;
        } else {
            discountText = `خصم ${coupon.discount_value} ريال`;
        }

        couponDiscountDisplay.textContent = discountText;

        // Animate coupon details appearing
        couponDetails.classList.remove('d-none');

        // Hide the input container with animation
        const couponInputContainer = document.querySelector('.coupon-input-container');
        couponInputContainer.style.opacity = '0.5';
        couponInputContainer.style.transform = 'scale(0.98)';

        // Update total calculation if needed
        updateTotalWithDiscount(coupon);

        // Dispatch event for other components to respond to coupon application
        document.dispatchEvent(new CustomEvent('couponApplied', {
            detail: { coupon }
        }));
    }

    // Make removeCoupon function global for other modules to use
    window.removeCoupon = function() {
        // Reset input state
        const couponInputContainer = document.querySelector('.coupon-input-container');
        couponInput.value = '';
        couponInput.disabled = false;
        couponInputContainer.style.opacity = '1';
        couponInputContainer.style.transform = 'scale(1)';

        // Reset button state
        checkCouponBtn.disabled = false;
        checkCouponBtn.innerHTML = '<span class="verify-text">تحقق</span><i class="fas fa-check"></i>';

        // Hide coupon details section
        couponDetails.classList.add('d-none');

        // Clear any messages
        couponMessage.textContent = '';

        // Reset any price calculations
        updateTotalWithDiscount(null);

        // Add focus to input field
        setTimeout(() => {
            couponInput.focus();
        }, 100);

        // Dispatch event for other components
        document.dispatchEvent(new CustomEvent('couponRemoved'));
    }

    function showCouponMessage(message, type) {
        // Create message element with icon
        const icon = type === 'info' ? 'info-circle' :
                    type === 'success' ? 'check-circle' :
                    type === 'warning' ? 'exclamation-triangle' :
                    'exclamation-circle';

        // Clear previous content
        couponMessage.textContent = '';

        // Create message div with proper class
        const messageContent = document.createElement('div');
        messageContent.className = `message-content text-${type}`;

        // Create and append icon
        const iconElement = document.createElement('i');
        iconElement.className = `fas fa-${icon} me-2`;
        messageContent.appendChild(iconElement);

        // Add message text
        const messageText = document.createElement('span');
        messageText.textContent = message;
        messageContent.appendChild(messageText);

        // Append to coupon message container
        couponMessage.appendChild(messageContent);

        // Animate the message appearance
        if (messageContent) {
            messageContent.style.opacity = '0';
            messageContent.style.transform = 'translateY(10px)';

            setTimeout(() => {
                messageContent.style.transition = 'all 0.3s ease';
                messageContent.style.opacity = '1';
                messageContent.style.transform = 'translateY(0)';
            }, 10);
        }
    }

    function updateTotalWithDiscount(coupon) {
        if (!coupon) {
            document.getElementById('price-breakdown-section').style.display = 'none';
            return;
        }

        // Get selected package price
        const selectedPackageRadio = document.querySelector('.package-select:checked');
        if (!selectedPackageRadio) return;

        const packageCard = selectedPackageRadio.closest('.package-card');
        const priceText = packageCard.querySelector('.fa-tag').parentElement.textContent;
        const originalPrice = parseFloat(priceText.match(/\d+(\.\d+)?/)[0]);

        // Calculate discount
        let discountAmount = 0;
        if (coupon.discount_type === 'percentage') {
            discountAmount = (originalPrice * parseFloat(coupon.discount_value) / 100);
        } else {
            discountAmount = parseFloat(coupon.discount_value);
        }

        // Make sure discount doesn't exceed the original price
        discountAmount = Math.min(discountAmount, originalPrice);

        // Calculate final price and savings percentage
        const finalPrice = originalPrice - discountAmount;
        const savingsPercentage = Math.round((discountAmount / originalPrice) * 100);

        // Update the price breakdown display
        document.getElementById('original-price-display').textContent = originalPrice.toFixed(2) + ' ريال';
        document.getElementById('discount-amount-display').textContent = discountAmount.toFixed(2) + ' ريال';
        document.getElementById('final-price-display').textContent = finalPrice.toFixed(2) + ' ريال';
        document.getElementById('savings-percentage').textContent = savingsPercentage + '%';

        // Add subtle animation to highlight the savings
        const savingsBadge = document.querySelector('.savings-badge');
        savingsBadge.classList.remove('pulsate-animation');
        void savingsBadge.offsetWidth; // Trigger reflow to restart animation
        savingsBadge.classList.add('pulsate-animation');

        // Show the price breakdown section with animation
        const priceSection = document.getElementById('price-breakdown-section');
        priceSection.style.display = 'block';
        priceSection.classList.remove('animate-slide-in');
        void priceSection.offsetWidth; // Trigger reflow to restart animation
        priceSection.classList.add('animate-slide-in');

        // Add selected addons to the total (if any)
        const addonCheckboxes = document.querySelectorAll('input[name^="addons"]:checked');
        let addonsTotal = 0;
        if (addonCheckboxes.length > 0) {
            addonCheckboxes.forEach(checkbox => {
                const addonCard = checkbox.closest('.card');
                const addonPriceText = addonCard.querySelector('.badge').textContent;
                const addonPrice = parseFloat(addonPriceText.match(/\d+(\.\d+)?/)[0]);
                addonsTotal += addonPrice;
            });

            // Add a row for addons if it doesn't exist
            let addonRow = document.querySelector('.price-row.addons-row');
            if (!addonRow) {
                const priceBody = document.querySelector('.price-breakdown-body');
                const totalRow = document.querySelector('.price-row.total-row');

                addonRow = document.createElement('div');
                addonRow.className = 'price-row addons-row';

                const addonLabel = document.createElement('div');
                addonLabel.className = 'price-label';
                addonLabel.innerHTML = '<i class="fas fa-plus-circle me-2" style="color: #21B3B0;"></i>الإضافات';

                const addonValue = document.createElement('div');
                addonValue.className = 'price-value addons-value';
                addonValue.id = 'addons-price-display';

                addonRow.appendChild(addonLabel);
                addonRow.appendChild(addonValue);

                priceBody.insertBefore(addonRow, totalRow);
            }

            // Update addons price
            document.getElementById('addons-price-display').textContent = addonsTotal.toFixed(2) + ' ريال';

            // Update final price to include addons
            const totalWithAddons = finalPrice + addonsTotal;
            document.getElementById('final-price-display').textContent = totalWithAddons.toFixed(2) + ' ريال';

            // Notify other components about price change
            document.dispatchEvent(new CustomEvent('priceUpdate', {
                detail: {
                    price: totalWithAddons,
                    originalPrice: originalPrice,
                    discountAmount: discountAmount,
                    addonsTotal: addonsTotal
                }
            }));
        } else {
            // Remove addons row if no addons are selected
            const addonRow = document.querySelector('.price-row.addons-row');
            if (addonRow) {
                addonRow.remove();
            }

            // Notify other components about price change
            document.dispatchEvent(new CustomEvent('priceUpdate', {
                detail: {
                    price: finalPrice,
                    originalPrice: originalPrice,
                    discountAmount: discountAmount,
                    addonsTotal: 0
                }
            }));
        }
    }

    // Listen for addon changes to update price calculation
    document.addEventListener('click', function(e) {
        if (e.target && e.target.classList.contains('addon-checkbox')) {
            // If a coupon is applied, update the price
            if (!couponDetails.classList.contains('d-none')) {
                const couponCode = couponCodeDisplay.textContent;
                // Re-fetch the coupon details to ensure correct application
                const selectedPackageRadio = document.querySelector('.package-select:checked');
                if (selectedPackageRadio) {
                    fetch('/client/bookings/validate-coupon', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({
                            coupon_code: couponCode,
                            package_id: selectedPackageRadio.value
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            updateTotalWithDiscount(data.coupon);
                        }
                    })
                    .catch(error => {
                        console.error('Error refreshing coupon data:', error);
                    });
                }
            }
        }
    });
});
