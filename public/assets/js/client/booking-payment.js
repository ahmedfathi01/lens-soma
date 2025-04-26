document.addEventListener('DOMContentLoaded', function() {
    // Payment Method Related Functionality
    const tabbyRadio = document.getElementById('payment_tabby');
    const codRadio = document.getElementById('payment_cod');
    const submitBtn = document.getElementById('submitBtn');
    const tabbyContainer = document.getElementById('tabby-container');

    // Only proceed if payment options exist on the page (user is logged in)
    if (tabbyRadio && codRadio) {
        // Update button text based on payment method
        function updateButtonText() {
            if (tabbyRadio.checked) {
                submitBtn.innerHTML = '<i class="fas fa-shopping-bag me-2"></i> متابعة للدفع مع تابي';
                tabbyContainer.style.display = 'block';

                // تحديث مبلغ التقسيط عند اختيار الدفع بتابي
                const selectedPackageRadio = document.querySelector('.package-select:checked');
                if (selectedPackageRadio) {
                    const packageCard = selectedPackageRadio.closest('.package-card');
                    const priceText = packageCard.querySelector('.fa-tag').parentElement.textContent;
                    const packagePrice = parseFloat(priceText.match(/\d+(\.\d+)?/)[0]);
                    updateTabbyWidgets(packagePrice);
                }
            } else if (codRadio.checked) {
                submitBtn.innerHTML = '<i class="fas fa-check me-2"></i> تأكيد الحجز';
                tabbyContainer.style.display = 'none';
            }
        }

        // Initialize button text
        updateButtonText();

        // Update button text when payment method changes
        tabbyRadio.addEventListener('change', function() {
            updateButtonText();

            // Get current price and update Tabby widget
            const finalPriceElement = document.getElementById('final-price-display');
            if (finalPriceElement) {
                const finalPriceText = finalPriceElement.textContent;
                const finalPrice = parseFloat(finalPriceText.match(/\d+(\.\d+)?/)[0]);
                updateTabbyWidgets(finalPrice);
            } else {
                // If price breakdown doesn't exist, use package price
                const selectedPackageRadio = document.querySelector('.package-select:checked');
                if (selectedPackageRadio) {
                    const packageCard = selectedPackageRadio.closest('.package-card');
                    const priceText = packageCard.querySelector('.fa-tag').parentElement.textContent;
                    const packagePrice = parseFloat(priceText.match(/\d+(\.\d+)?/)[0]);
                    updateTabbyWidgets(packagePrice);
                }
            }
        });

        // Add listener for cash on delivery option
        codRadio.addEventListener('change', updateButtonText);
    }

    // Update Tabby widgets when price changes
    function updateTabbyWidgets(price) {
        if (typeof TabbyPromo === 'undefined') {
            console.error('TabbyPromo is not defined');
            return;
        }

        try {
            // Tabby Product Widget
            new TabbyPromo({
                selector: '#tabby-product-widget',
                currency: 'SAR',
                price: price.toString(),
                lang: 'ar',
                source: 'checkout'
            });

            // تحديث مبلغ التقسيط في النص
            const installmentAmount = (price / 4).toFixed(2);
            const tabbyInstallmentText = document.querySelector('.tabby-installment-amount');
            if (tabbyInstallmentText) {
                tabbyInstallmentText.textContent = installmentAmount;
            }

            // Update global package data for other components
            window.packageData.price = price;
        } catch (error) {
            console.error('Error updating Tabby widget:', error);
        }
    }

    // Listen for price update events from other modules
    document.addEventListener('priceUpdate', function(e) {
        if (tabbyRadio && tabbyRadio.checked) {
            updateTabbyWidgets(e.detail.price);
        }
    });

    // Listen for coupon application events
    document.addEventListener('couponApplied', function(e) {
        if (tabbyRadio && tabbyRadio.checked) {
            // Get current price from price display
            const finalPriceElement = document.getElementById('final-price-display');
            if (finalPriceElement) {
                const finalPriceText = finalPriceElement.textContent;
                const finalPrice = parseFloat(finalPriceText.match(/\d+(\.\d+)?/)[0]);
                updateTabbyWidgets(finalPrice);
            }
        }
    });

    // Listen for coupon removal events
    document.addEventListener('couponRemoved', function() {
        if (tabbyRadio && tabbyRadio.checked) {
            // Return to original package price
            const selectedPackageRadio = document.querySelector('.package-select:checked');
            if (selectedPackageRadio) {
                const packageCard = selectedPackageRadio.closest('.package-card');
                const priceText = packageCard.querySelector('.fa-tag').parentElement.textContent;
                const packagePrice = parseFloat(priceText.match(/\d+(\.\d+)?/)[0]);
                updateTabbyWidgets(packagePrice);
            }
        }
    });

    // Handle form submission
    const checkoutForm = document.getElementById('checkout-form');
    if (checkoutForm) {
        checkoutForm.addEventListener('submit', function() {
            // Add loading state to form
            this.classList.add('loading');

            // Disable submit button to prevent double submission
            const submitBtn = document.getElementById('submitBtn');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> جاري المعالجة...';
            }
        });
    }

    // Initialize Tabby widget if a package is already selected and Tabby payment is selected
    const initialSelectedPackage = document.querySelector('.package-select:checked');
    if (initialSelectedPackage && tabbyRadio && tabbyRadio.checked) {
        const packageCard = initialSelectedPackage.closest('.package-card');
        const priceText = packageCard.querySelector('.fa-tag').parentElement.textContent;
        const packagePrice = parseFloat(priceText.match(/\d+(\.\d+)?/)[0]);
        updateTabbyWidgets(packagePrice);
    }
});
