document.addEventListener('DOMContentLoaded', function() {
    const couponInput = document.getElementById('coupon_code');
    const checkCouponBtn = document.getElementById('check-coupon');
    const couponMessage = document.getElementById('coupon-message');
    const couponDetails = document.getElementById('coupon-details');
    const couponCodeDisplay = document.getElementById('coupon-code-display');
    const couponDiscountDisplay = document.getElementById('coupon-discount-display');
    const removeCouponBtn = document.getElementById('remove-coupon');

    if (checkCouponBtn && couponInput) {
        couponInput.addEventListener('focus', function() {
            this.closest('.coupon-input-container').classList.add('focused');
        });

        couponInput.addEventListener('blur', function() {
            this.closest('.coupon-input-container').classList.remove('focused');
        });

        couponInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter' && checkCouponBtn) {
                e.preventDefault();
                checkCouponBtn.click();
            }
        });

        checkCouponBtn.addEventListener('mousedown', function() {
            this.classList.add('btn-pressed');
        });

        document.addEventListener('mouseup', function() {
            checkCouponBtn.classList.remove('btn-pressed');
        });

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

            showLoadingState(true);

            const packageId = selectedPackageRadio.value;
            validateCoupon(couponCode, packageId);
        });
    }

    if (removeCouponBtn) {
        removeCouponBtn.addEventListener('click', function() {
            const appliedCoupon = this.closest('.applied-coupon');
            appliedCoupon.classList.add('slide-out');

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
        couponCodeDisplay.textContent = coupon.code;

        let discountText = '';
        if (coupon.discount_type === 'percentage') {
            discountText = `خصم ${coupon.discount_value}%`;
        } else {
            discountText = `خصم ${coupon.discount_value} ريال`;
        }

        couponDiscountDisplay.textContent = discountText;

        couponDetails.classList.remove('d-none');

        const couponInputContainer = document.querySelector('.coupon-input-container');
        couponInputContainer.style.opacity = '0.5';
        couponInputContainer.style.transform = 'scale(0.98)';

        updateTotalWithDiscount(coupon);

        document.dispatchEvent(new CustomEvent('couponApplied', {
            detail: { coupon }
        }));
    }

    window.removeCoupon = function() {
        const couponInputContainer = document.querySelector('.coupon-input-container');
        couponInput.value = '';
        couponInput.disabled = false;
        couponInputContainer.style.opacity = '1';
        couponInputContainer.style.transform = 'scale(1)';

        checkCouponBtn.disabled = false;
        checkCouponBtn.innerHTML = '<span class="verify-text">تحقق</span><i class="fas fa-check"></i>';

        couponDetails.classList.add('d-none');

        couponMessage.textContent = '';

        updateTotalWithDiscount(null);

        setTimeout(() => {
            couponInput.focus();
        }, 100);

        document.dispatchEvent(new CustomEvent('couponRemoved'));
    }

    function showCouponMessage(message, type) {
        const icon = type === 'info' ? 'info-circle' :
                    type === 'success' ? 'check-circle' :
                    type === 'warning' ? 'exclamation-triangle' :
                    'exclamation-circle';

        couponMessage.textContent = '';

        const messageContent = document.createElement('div');
        messageContent.className = `message-content text-${type}`;

        const iconElement = document.createElement('i');
        iconElement.className = `fas fa-${icon} me-2`;
        messageContent.appendChild(iconElement);

        const messageText = document.createElement('span');
        messageText.textContent = message;
        messageContent.appendChild(messageText);

        couponMessage.appendChild(messageContent);

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

        const selectedPackageRadio = document.querySelector('.package-select:checked');
        if (!selectedPackageRadio) return;

        const packageCard = selectedPackageRadio.closest('.package-card');
        const priceText = packageCard.querySelector('.fa-tag').parentElement.textContent;
        const originalPrice = parseFloat(priceText.match(/\d+(\.\d+)?/)[0]);

        let discountAmount = 0;
        if (coupon.discount_type === 'percentage') {
            discountAmount = (originalPrice * parseFloat(coupon.discount_value) / 100);
        } else {
            discountAmount = parseFloat(coupon.discount_value);
        }

        discountAmount = Math.min(discountAmount, originalPrice);

        const finalPrice = originalPrice - discountAmount;
        const savingsPercentage = Math.round((discountAmount / originalPrice) * 100);

        document.getElementById('original-price-display').textContent = originalPrice.toFixed(2) + ' ريال';
        document.getElementById('discount-amount-display').textContent = discountAmount.toFixed(2) + ' ريال';
        document.getElementById('final-price-display').textContent = finalPrice.toFixed(2) + ' ريال';
        document.getElementById('savings-percentage').textContent = savingsPercentage + '%';

        const savingsBadge = document.querySelector('.savings-badge');
        savingsBadge.classList.remove('pulsate-animation');
        void savingsBadge.offsetWidth;
        savingsBadge.classList.add('pulsate-animation');

        const priceSection = document.getElementById('price-breakdown-section');
        priceSection.style.display = 'block';
        priceSection.classList.remove('animate-slide-in');
        void priceSection.offsetWidth;
        priceSection.classList.add('animate-slide-in');

        // Calculate addons total including all types of addons
        let addonsTotal = 0;
        let hasAddons = false;

        // Regular addons
        const addonCheckboxes = document.querySelectorAll('input[name^="addons"]:checked');
        if (addonCheckboxes.length > 0) {
            hasAddons = true;
            addonCheckboxes.forEach(checkbox => {
                const addonCard = checkbox.closest('.card');
                const addonPriceText = addonCard.querySelector('.badge').textContent;
                const addonPrice = parseFloat(addonPriceText.match(/\d+(\.\d+)?/)[0]);
                addonsTotal += addonPrice;
            });
        }

        // Tabi and Promo addons
        const tabiPromoCheckboxes = document.querySelectorAll('input[type="checkbox"][name^="tabby_"]:checked, input[type="checkbox"][name^="promo_"]:checked');
        if (tabiPromoCheckboxes.length > 0) {
            hasAddons = true;
            tabiPromoCheckboxes.forEach(checkbox => {
                const priceElement = checkbox.closest('.form-check').querySelector('.badge');
                if (priceElement) {
                    const priceText = priceElement.textContent;
                    const priceMatch = priceText.match(/\d+(\.\d+)?/);
                    if (priceMatch && priceMatch[0]) {
                        addonsTotal += parseFloat(priceMatch[0]);
                    }
                }
            });
        }

        // Any other addon inputs
        const otherAddonInputs = document.querySelectorAll('input[type="checkbox"][name*="addon"]:checked:not([name^="addons"]), input[type="radio"][name*="addon"]:checked');
        if (otherAddonInputs.length > 0) {
            hasAddons = true;
            otherAddonInputs.forEach(input => {
                const priceElement = input.closest('.form-check, .card-body').querySelector('.badge');
                if (priceElement) {
                    const priceText = priceElement.textContent;
                    const priceMatch = priceText.match(/\d+(\.\d+)?/);
                    if (priceMatch && priceMatch[0]) {
                        addonsTotal += parseFloat(priceMatch[0]);
                    }
                }
            });
        }

        if (hasAddons) {
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

            document.getElementById('addons-price-display').textContent = addonsTotal.toFixed(2) + ' ريال';

            const totalWithAddons = finalPrice + addonsTotal;
            document.getElementById('final-price-display').textContent = totalWithAddons.toFixed(2) + ' ريال';

            document.dispatchEvent(new CustomEvent('priceUpdate', {
                detail: {
                    price: totalWithAddons,
                    originalPrice: originalPrice,
                    discountAmount: discountAmount,
                    addonsTotal: addonsTotal
                }
            }));
        } else {
            const addonRow = document.querySelector('.price-row.addons-row');
            if (addonRow) {
                addonRow.remove();
            }

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

    document.addEventListener('click', function(e) {
        // Check for addon selections and update price calculations
        const isAddonInput =
            (e.target && e.target.classList.contains('addon-checkbox')) ||
            (e.target && (e.target.name && (e.target.name.startsWith('tabby_') || e.target.name.startsWith('promo_')))) ||
            (e.target && (e.target.name && e.target.name.includes('addon')));

        if (isAddonInput) {
            if (!couponDetails.classList.contains('d-none')) {
                const couponCode = couponCodeDisplay.textContent;
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
