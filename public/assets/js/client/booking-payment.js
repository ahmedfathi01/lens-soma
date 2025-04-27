document.addEventListener('DOMContentLoaded', function() {
    const tabbyRadio = document.getElementById('payment_tabby');
    const codRadio = document.getElementById('payment_cod');
    const submitBtn = document.getElementById('submitBtn');
    const tabbyContainer = document.getElementById('tabby-container');

    if (tabbyRadio && codRadio) {
        function updateButtonText() {
            if (tabbyRadio.checked) {
                submitBtn.innerHTML = '<i class="fas fa-shopping-bag me-2"></i> متابعة للدفع مع تابي';
                tabbyContainer.style.display = 'block';

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

        updateButtonText();

        tabbyRadio.addEventListener('change', function() {
            updateButtonText();

            const finalPriceElement = document.getElementById('final-price-display');
            if (finalPriceElement) {
                const finalPriceText = finalPriceElement.textContent;
                const finalPrice = parseFloat(finalPriceText.match(/\d+(\.\d+)?/)[0]);
                updateTabbyWidgets(finalPrice);
            } else {
                const selectedPackageRadio = document.querySelector('.package-select:checked');
                if (selectedPackageRadio) {
                    const packageCard = selectedPackageRadio.closest('.package-card');
                    const priceText = packageCard.querySelector('.fa-tag').parentElement.textContent;
                    const packagePrice = parseFloat(priceText.match(/\d+(\.\d+)?/)[0]);
                    updateTabbyWidgets(packagePrice);
                }
            }
        });

        codRadio.addEventListener('change', updateButtonText);
    }

    function updateTabbyWidgets(price) {
        if (typeof TabbyPromo === 'undefined') {
            console.error('TabbyPromo is not defined');
            return;
        }

        try {
            new TabbyPromo({
                selector: '#tabby-product-widget',
                currency: 'SAR',
                price: price.toString(),
                lang: 'ar',
                source: 'checkout'
            });

            const installmentAmount = (price / 4).toFixed(2);
            const tabbyInstallmentText = document.querySelector('.tabby-installment-amount');
            if (tabbyInstallmentText) {
                tabbyInstallmentText.textContent = installmentAmount;
            }

            window.packageData.price = price;
        } catch (error) {
            console.error('Error updating Tabby widget:', error);
        }
    }

    document.addEventListener('priceUpdate', function(e) {
        if (tabbyRadio && tabbyRadio.checked) {
            updateTabbyWidgets(e.detail.price);
        }
    });

    document.addEventListener('couponApplied', function(e) {
        if (tabbyRadio && tabbyRadio.checked) {
            const finalPriceElement = document.getElementById('final-price-display');
            if (finalPriceElement) {
                const finalPriceText = finalPriceElement.textContent;
                const finalPrice = parseFloat(finalPriceText.match(/\d+(\.\d+)?/)[0]);
                updateTabbyWidgets(finalPrice);
            }
        }
    });

    document.addEventListener('couponRemoved', function() {
        if (tabbyRadio && tabbyRadio.checked) {
            const selectedPackageRadio = document.querySelector('.package-select:checked');
            if (selectedPackageRadio) {
                const packageCard = selectedPackageRadio.closest('.package-card');
                const priceText = packageCard.querySelector('.fa-tag').parentElement.textContent;
                const packagePrice = parseFloat(priceText.match(/\d+(\.\d+)?/)[0]);
                updateTabbyWidgets(packagePrice);
            }
        }
    });

    const checkoutForm = document.getElementById('checkout-form');
    if (checkoutForm) {
        checkoutForm.addEventListener('submit', function() {
            this.classList.add('loading');

            const submitBtn = document.getElementById('submitBtn');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> جاري المعالجة...';
            }
        });
    }

    const initialSelectedPackage = document.querySelector('.package-select:checked');
    if (initialSelectedPackage && tabbyRadio && tabbyRadio.checked) {
        const packageCard = initialSelectedPackage.closest('.package-card');
        const priceText = packageCard.querySelector('.fa-tag').parentElement.textContent;
        const packagePrice = parseFloat(priceText.match(/\d+(\.\d+)?/)[0]);
        updateTabbyWidgets(packagePrice);
    }
});
