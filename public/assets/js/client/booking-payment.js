document.addEventListener('DOMContentLoaded', function() {
    const tabbyRadio = document.getElementById('payment_tabby');
    const codRadio = document.getElementById('payment_cod');
    const submitBtn = document.getElementById('submitBtn');
    const tabbyContainer = document.getElementById('tabby-container');
    const bankTransferContainer = document.getElementById('bank-transfer-container');

    if (tabbyRadio && codRadio) {
        // Set bank transfer as default payment method
        if (codRadio && !codRadio.checked) {
            codRadio.checked = true;
            tabbyRadio.checked = false;
        }

        function updateButtonText() {
            if (tabbyRadio.checked) {
                submitBtn.innerHTML = '<i class="fas fa-shopping-bag me-2"></i> متابعة للدفع مع تابي';
                tabbyContainer.style.display = 'block';
                bankTransferContainer.style.display = 'none';

                // Get the current price - first try from the final price display, then from selected package
                updateTabbyFromCurrentPrice();
            } else if (codRadio.checked) {
                submitBtn.innerHTML = '<i class="fas fa-check me-2"></i> تأكيد الحجز';
                tabbyContainer.style.display = 'none';
                bankTransferContainer.style.display = 'block';
            }
        }

        // Function to get current price and update Tabby
        function updateTabbyFromCurrentPrice() {
            let price = 0;

            // First try to get price from final price display (which includes discounts)
            const finalPriceElement = document.getElementById('final-price-display');
            if (finalPriceElement && finalPriceElement.textContent) {
                const priceMatch = finalPriceElement.textContent.match(/(\d+(\.\d+)?)/);
                if (priceMatch && priceMatch[0]) {
                    price = parseFloat(priceMatch[0]);
                }
            }

            // If no price found or price is 0, calculate from package and add-ons
            if (!price) {
                // Get base package price
                const selectedPackageRadio = document.querySelector('.package-select:checked');
                if (selectedPackageRadio) {
                    const packageCard = selectedPackageRadio.closest('.package-card');
                    if (packageCard) {
                        const priceElement = packageCard.querySelector('.fa-tag');
                        if (priceElement && priceElement.parentElement) {
                            const priceText = priceElement.parentElement.textContent;
                            const priceMatch = priceText.match(/(\d+(\.\d+)?)/);
                            if (priceMatch && priceMatch[0]) {
                                price = parseFloat(priceMatch[0]);
                            }
                        }
                    }
                }

                // Add price from selected add-ons
                const selectedAddons = document.querySelectorAll('.addon-checkbox:checked');
                selectedAddons.forEach(addon => {
                    const addonCard = addon.closest('.card');
                    if (addonCard) {
                        const priceElement = addonCard.querySelector('.badge');
                        if (priceElement) {
                            const priceText = priceElement.textContent;
                            const priceMatch = priceText.match(/(\d+(\.\d+)?)/);
                            if (priceMatch && priceMatch[0]) {
                                price += parseFloat(priceMatch[0]);
                            }
                        }
                    }
                });
            }

            // Update Tabby widget if we have a valid price
            if (price > 0) {
                updateTabbyWidgets(price);
            } else {
                console.warn('No valid price found for Tabby widget');
            }
        }

        // Call updateButtonText initially to set correct UI state
        updateButtonText();

        // Listen for changes in payment method
        tabbyRadio.addEventListener('change', updateButtonText);
        codRadio.addEventListener('change', updateButtonText);

        // Listen for package selection changes
        document.addEventListener('click', function(e) {
            const packageRadio = e.target.closest('.package-select');
            if (packageRadio && tabbyRadio && tabbyRadio.checked) {
                // Short delay to ensure price is updated in the DOM
                setTimeout(updateTabbyFromCurrentPrice, 100);
            }

            // Also update on addon selection
            const addonCheckbox = e.target.closest('.addon-checkbox');
            if (addonCheckbox && tabbyRadio && tabbyRadio.checked) {
                // Short delay to ensure price is updated in the DOM
                setTimeout(updateTabbyFromCurrentPrice, 100);
            }
        });
    }

    // Add copy functionality for bank transfer details
    const copyButtons = document.querySelectorAll('.copy-btn');
    copyButtons.forEach(button => {
        button.addEventListener('click', function() {
            const textToCopy = this.getAttribute('data-clipboard-text');
            navigator.clipboard.writeText(textToCopy)
                .then(() => {
                    // Change button appearance briefly to indicate successful copy
                    const originalHTML = this.innerHTML;
                    this.innerHTML = '<i class="fas fa-check"></i>';
                    this.classList.add('btn-success');
                    this.classList.remove('btn-outline-secondary');

                    setTimeout(() => {
                        this.innerHTML = originalHTML;
                        this.classList.remove('btn-success');
                        this.classList.add('btn-outline-secondary');
                    }, 1500);
                })
                .catch(err => {
                    console.error('Could not copy text: ', err);
                });
        });
    });

    function updateTabbyWidgets(price) {
        if (typeof TabbyPromo === 'undefined') {
            console.error('TabbyPromo is not defined');
            return;
        }

        try {
            console.log('Updating Tabby widget with price:', price);
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

    // Listen for price updates from other components
    document.addEventListener('priceUpdate', function(e) {
        if (tabbyRadio && tabbyRadio.checked) {
            updateTabbyWidgets(e.detail.price);
        }
    });

    document.addEventListener('couponApplied', function(e) {
        if (tabbyRadio && tabbyRadio.checked) {
            updateTabbyFromCurrentPrice();
        }
    });

    document.addEventListener('couponRemoved', function() {
        if (tabbyRadio && tabbyRadio.checked) {
            updateTabbyFromCurrentPrice();
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

    // Also listen for package-selection events from booking-core.js
    document.addEventListener('packageSelected', function(e) {
        if (tabbyRadio && tabbyRadio.checked) {
            setTimeout(updateTabbyFromCurrentPrice, 100);
        }
    });

    // Check if a package is already selected on page load
    const initialSelectedPackage = document.querySelector('.package-select:checked');
    if (initialSelectedPackage && tabbyRadio && tabbyRadio.checked) {
        setTimeout(updateTabbyFromCurrentPrice, 100);
    }
});
