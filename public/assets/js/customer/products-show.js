let selectedColor = null;
let selectedSize = null;
let selectedQuantityId = null;
let selectedQuantityValue = null;
let selectedQuantityPrice = null;

function getAppointmentsStatus() {
    return document.getElementById('appointmentsEnabled')?.value === 'true';
}

function updateMainImage(src, thumbnail) {
    const sanitizedSrc = encodeURI(src).replace(/[\\'"]/g, '');
    document.getElementById('mainImage').src = sanitizedSrc;
    document.querySelectorAll('.thumbnail-wrapper').forEach(thumb => {
        thumb.classList.remove('active');
    });
    if (thumbnail) {
        thumbnail.classList.add('active');
    }
}

function updateMainImageSafe(src, thumbnail) {
    const sanitizedSrc = encodeURI(src).replace(/[\\'"]/g, '');
    updateMainImage(sanitizedSrc, thumbnail);
}

function selectColor(element) {
    if (!element.classList.contains('available')) return;

    if (element.classList.contains('active')) {
        element.classList.remove('active');
        selectedColor = null;
        return;
    }

    const useCustomColorCheckbox = document.getElementById('useCustomColor');
    if (useCustomColorCheckbox) {
        useCustomColorCheckbox.checked = false;
        document.getElementById('customColorGroup').classList.add('d-none');
        document.getElementById('customColor').value = '';
        document.getElementById('customColor').disabled = true;
    }

    document.querySelectorAll('.color-item').forEach(item => {
        item.classList.remove('active');
    });

    element.classList.add('active');
    selectedColor = element.dataset.color;
}

function selectSize(element) {
    if (!element.classList.contains('available')) return;

    if (element.classList.contains('active')) {
        element.classList.remove('active');
        selectedSize = null;

        updatePrice();
        return;
    }

    const useCustomSizeCheckbox = document.getElementById('useCustomSize');
    if (useCustomSizeCheckbox) {
        useCustomSizeCheckbox.checked = false;
        document.getElementById('customSizeGroup').classList.add('d-none');
        document.getElementById('customSize').value = '';
        document.getElementById('customSize').disabled = true;
    }

    document.querySelectorAll('.size-option').forEach(item => {
        item.classList.remove('active');
    });

    element.classList.add('active');
    selectedSize = element.dataset.size;

    updatePrice();

    document.getElementById('needsAppointment').checked = false;
}

function updatePageQuantity(change) {
    const quantityInput = document.getElementById('quantity');
    let newQuantity = parseInt(quantityInput.value) + change;
    const maxStock = parseInt(quantityInput.getAttribute('max'));

    if (newQuantity >= 1 && newQuantity <= maxStock) {
        quantityInput.value = newQuantity;
    }
}

function updateWorkingHoursDisplay() {
    if (window.studioWorkingHours) {
        const startTime = window.studioWorkingHours.startFormatted || '10:00';
        const endTime = window.studioWorkingHours.endFormatted || '18:00';

        const formatTimeArabic = (timeStr) => {
            const [hours, minutes] = timeStr.split(':');
            const hour = parseInt(hours);

            if (hour < 12) {
                return `${hour}${minutes !== '00' ? `:${minutes}` : ''} صباحاً`;
            } else if (hour === 12) {
                return `${hour}${minutes !== '00' ? `:${minutes}` : ''} ظهراً`;
            } else {
                return `${hour - 12}${minutes !== '00' ? `:${minutes}` : ''} مساءً`;
            }
        };

        const startTimeEl = document.getElementById('studioStartTime');
        const endTimeEl = document.getElementById('studioEndTime');

        if (startTimeEl) startTimeEl.textContent = formatTimeArabic(startTime);
        if (endTimeEl) endTimeEl.textContent = formatTimeArabic(endTime);
    }
}

function showAppointmentModal(cartItemId) {
    if (!getAppointmentsStatus() || !document.getElementById('needsAppointment')?.checked) {
        showNotification('تم إضافة المنتج للسلة بنجاح', 'success');
        return;
    }

    fetch(`/cart/items/${cartItemId}/check-appointment`)
        .then(response => response.json())
        .then(data => {
            if (data.needs_appointment) {
                document.getElementById('cart_item_id').value = cartItemId;
                document.getElementById('appointmentForm').reset();
                document.getElementById('addressField').classList.add('d-none');
                document.getElementById('appointmentErrors').classList.add('d-none');

                fetchWorkingHours();

                const modal = new bootstrap.Modal(document.getElementById('appointmentModal'));
                modal.show();
            } else {
                const currentUrl = new URL(window.location.href);
                currentUrl.searchParams.delete('pending_appointment');
                window.history.replaceState({}, '', currentUrl);
            }
        })
        .catch(error => {
            showNotification('حدث خطأ أثناء التحقق من حالة الموعد', 'error');
        });
}

function fetchWorkingHours() {
    const today = new Date().toISOString().split('T')[0];
    fetch(`/appointments/check-availability?date=${today}`)
        .then(response => response.json())
        .then(data => {
            if (data.workingHours) {
                const startHour = data.workingHours.start;
                const endHour = data.workingHours.end;
                const isOvernight = endHour < startHour;

                window.studioWorkingHours = {
                    ...data.workingHours,
                    isOvernight: isOvernight
                };

                updateWorkingHoursDisplay();
            }
        });
}

function closeAppointmentModal() {
    const currentUrl = new URL(window.location.href);
    currentUrl.searchParams.delete('pending_appointment');
    window.history.replaceState({}, '', currentUrl);

    const modal = bootstrap.Modal.getInstance(document.getElementById('appointmentModal'));
    if (modal) {
        modal.hide();
    }
}

function toggleAddress() {
    const location = document.getElementById('location').value;
    const addressField = document.getElementById('addressField');
    const addressInput = document.getElementById('address');

    if (location === 'client_location') {
        addressField.classList.remove('d-none');
        addressInput.setAttribute('required', 'required');
    } else {
        addressField.classList.add('d-none');
        addressInput.removeAttribute('required');
        addressInput.value = '';
    }
}

function updatePrice() {
    const priceElement = document.getElementById('product-price');
    const originalPriceElement = document.getElementById('original-price');

    if (!priceElement && !originalPriceElement) {
        return;
    }

    const originalPrice = originalPriceElement ? parseFloat(originalPriceElement.value) : 0;
    let currentPrice = originalPrice;
    let sizePrice = 0;
    let quantityPrice = 0;

    if (selectedSize) {
        const sizeElement = document.querySelector(`.size-option[data-size="${selectedSize}"]`);
        if (sizeElement && sizeElement.dataset.price) {
            sizePrice = parseFloat(sizeElement.dataset.price);
        }
    }

    if (selectedQuantityPrice) {
        quantityPrice = parseFloat(selectedQuantityPrice);
    }

    if (selectedSize && selectedQuantityId) {
        currentPrice = sizePrice + quantityPrice;
    }
    else if (selectedSize) {
        currentPrice = sizePrice;
    }
    else if (selectedQuantityId) {
        currentPrice = quantityPrice;
    }
    else {
        currentPrice = originalPrice;
    }

    if (priceElement) {
        priceElement.textContent = currentPrice.toFixed(2) + ' ر.س';
    }
}

document.querySelectorAll('.size-option').forEach(el => {
    el.addEventListener('click', function() {
        selectedSize = this.dataset.size;
        document.querySelectorAll('.size-option').forEach(s => s.classList.remove('active'));
        this.classList.add('active');
        updatePrice();
    });
});

function addToCart() {
    const productId = document.getElementById('product-id').value;
    const quantity = document.getElementById('quantity')?.value || 1;
    const appointmentsEnabled = getAppointmentsStatus();
    const needsAppointmentCheckbox = document.getElementById('needsAppointment');

    const needsAppointment = appointmentsEnabled && needsAppointmentCheckbox?.checked;

    const errorMessage = document.getElementById('errorMessage');
    errorMessage.classList.add('d-none');

    const hasColorSelectionEnabled = document.querySelector('.colors-section') !== null;
    const hasCustomColorEnabled = document.getElementById('customColor') !== null;
    const hasSizeSelectionEnabled = document.querySelector('.available-sizes') !== null;
    const hasCustomSizeEnabled = document.getElementById('customSize') !== null;

    const quantityOptions = document.querySelectorAll('.quantity-option');
    const quantityErrorAlert = document.getElementById('quantity-error-alert');

    if (quantityOptions.length > 0 && !selectedQuantityId) {
        showNotification('يرجى اختيار إحدى خيارات الكمية المتاحة', 'error');

        if (quantityErrorAlert) {
            quantityErrorAlert.classList.remove('d-none');
        }

        document.querySelector('.quantity-pricing').scrollIntoView({ behavior: 'smooth', block: 'center' });

        const firstAvailableOption = document.querySelector('.quantity-option.available');
        if (firstAvailableOption) {
            firstAvailableOption.classList.add('highlight');
            setTimeout(() => {
                firstAvailableOption.classList.remove('highlight');
            }, 2000);
        }
        return;
    }

    if (quantityErrorAlert) {
        quantityErrorAlert.classList.add('d-none');
    }

    let colorValue = null;
    if (hasColorSelectionEnabled && selectedColor) {
        colorValue = selectedColor;
    } else if (hasCustomColorEnabled) {
        const customColor = document.getElementById('customColor').value.trim();
        if (customColor) {
            colorValue = customColor;
        }
    }

    let sizeValue = null;
    if (hasSizeSelectionEnabled && selectedSize) {
        sizeValue = selectedSize;
    } else if (hasCustomSizeEnabled) {
        const customSize = document.getElementById('customSize').value.trim();
        if (customSize) {
            sizeValue = customSize;
        }
    }

    if ((hasColorSelectionEnabled || hasCustomColorEnabled) && !colorValue) {
        let errorText = '';
        if (hasColorSelectionEnabled && hasCustomColorEnabled) {
            errorText = 'يرجى اختيار لون أو كتابة اللون المطلوب';
        } else if (hasColorSelectionEnabled) {
            errorText = 'يرجى اختيار لون للمنتج';
        } else if (hasCustomColorEnabled) {
            errorText = 'يرجى كتابة اللون المطلوب';
        }
        errorMessage.textContent = errorText;
        errorMessage.classList.remove('d-none');
        return;
    }

    if ((hasSizeSelectionEnabled || hasCustomSizeEnabled) && !sizeValue) {
        let errorText = '';
        if (hasSizeSelectionEnabled && hasCustomSizeEnabled) {
            errorText = 'يرجى اختيار مقاس أو كتابة المقاس المطلوب';
        } else if (hasSizeSelectionEnabled) {
            errorText = 'يرجى اختيار مقاس للمنتج';
        } else if (hasCustomSizeEnabled) {
            errorText = 'يرجى كتابة المقاس المطلوب';
        }
        errorMessage.textContent = errorText;
        errorMessage.classList.remove('d-none');
        return;
    }

    const addToCartBtn = document.querySelector('.btn-primary[onclick="addToCart()"]');
    const originalBtnText = addToCartBtn.innerHTML;
    addToCartBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>جاري الإضافة...';
    addToCartBtn.disabled = true;

    const data = {
        product_id: productId,
        quantity: quantity,
        color: colorValue,
        size: sizeValue,
        quantity_option_id: selectedQuantityId,
        needs_appointment: needsAppointment
    };

    fetch('/cart/add', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.querySelectorAll('.cart-count').forEach(element => {
                element.textContent = data.cart_count;
            });

            showNotification('تم إضافة المنتج للسلة بنجاح', 'success');

            if (needsAppointment) {
                showAppointmentModal(data.cart_item_id);
            }

            loadCartItems();

            if (document.querySelector('.colors-section')) {
                selectedColor = null;
                document.querySelectorAll('.color-item').forEach(item => {
                    item.classList.remove('active');
                });
            }
            if (document.querySelector('.available-sizes')) {
                selectedSize = null;
                document.querySelectorAll('.size-option').forEach(item => {
                    item.classList.remove('active');
                });
            }

            if (document.getElementById('customColor')) {
                document.getElementById('customColor').value = '';
            }
            if (document.getElementById('customSize')) {
                document.getElementById('customSize').value = '';
            }

            document.getElementById('quantity').value = 1;
        } else {
            showNotification(data.message || 'حدث خطأ أثناء إضافة المنتج للسلة', 'error');
        }
    })
    .catch(error => {
        showNotification('حدث خطأ أثناء إضافة المنتج إلى السلة', 'error');
    })
    .finally(() => {
        const addToCartBtn = document.querySelector('.btn-primary[onclick="addToCart()"]');
        addToCartBtn.innerHTML = '<i class="fas fa-shopping-cart me-2"></i>أضف إلى السلة';
        addToCartBtn.disabled = false;
    });
}

function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type} notification-toast position-fixed top-0 start-50 translate-middle-x mt-3`;
    notification.style.zIndex = '9999';
    notification.style.opacity = '1';

    const textContent = document.createTextNode(message);
    notification.appendChild(textContent);

    document.body.appendChild(notification);

    setTimeout(() => {
        notification.style.opacity = '0';
        notification.style.transition = 'opacity 0.5s ease';
        setTimeout(() => notification.remove(), 500);
    }, 6000);
}

function updateCartDisplay(data) {
    const cartItems = document.getElementById('cartItems');
    const cartTotal = document.getElementById('cartTotal');
    const cartCountElements = document.querySelectorAll('.cart-count');

    cartCountElements.forEach(element => {
        element.textContent = data.count;
    });

    cartTotal.textContent = data.total + ' ر.س';

    while (cartItems.firstChild) {
        cartItems.removeChild(cartItems.firstChild);
    }

    if (!data.items || data.items.length === 0) {
        const emptyCartDiv = document.createElement('div');
        emptyCartDiv.className = 'cart-empty text-center p-4';

        const cartIcon = document.createElement('i');
        cartIcon.className = 'fas fa-shopping-cart fa-3x mb-3';
        emptyCartDiv.appendChild(cartIcon);

        const emptyText = document.createElement('p');
        emptyText.className = 'mb-3';
        emptyText.textContent = 'السلة فارغة';
        emptyCartDiv.appendChild(emptyText);

        const browseLink = document.createElement('a');
        browseLink.href = '/products';
        browseLink.className = 'btn btn-primary';
        browseLink.textContent = 'تصفح المنتجات';
        emptyCartDiv.appendChild(browseLink);

        cartItems.appendChild(emptyCartDiv);
        return;
    }

    data.items.forEach(item => {
        const itemElement = document.createElement('div');
        itemElement.className = 'cart-item';
        itemElement.dataset.itemId = item.id;

        const itemInner = document.createElement('div');
        itemInner.className = 'cart-item-inner p-3 border-bottom';

        const removeBtn = document.createElement('button');
        removeBtn.className = 'remove-btn btn btn-link text-danger';
        removeBtn.onclick = function() { removeFromCart(this, item.id); };

        const removeIcon = document.createElement('i');
        removeIcon.className = 'fas fa-times';
        removeBtn.appendChild(removeIcon);
        itemInner.appendChild(removeBtn);

        const flexContainer = document.createElement('div');
        flexContainer.className = 'd-flex gap-3';

        const itemImage = document.createElement('img');
        itemImage.src = item.image;
        itemImage.alt = item.name;
        itemImage.className = 'cart-item-image';
        flexContainer.appendChild(itemImage);

        const detailsContainer = document.createElement('div');
        detailsContainer.className = 'cart-item-details flex-grow-1';

        const titleElement = document.createElement('h5');
        titleElement.className = 'cart-item-title mb-2';
        titleElement.textContent = item.name;
        detailsContainer.appendChild(titleElement);

        const infoElement = document.createElement('div');
        infoElement.className = 'cart-item-info mb-2';

        const additionalInfo = [];
        if (item.color) additionalInfo.push(`اللون: ${item.color}`);
        if (item.size) additionalInfo.push(`المقاس: ${item.size}`);

        if (additionalInfo.length > 0) {
            const infoText = document.createElement('small');
            infoText.className = 'text-muted';
            infoText.textContent = additionalInfo.join(' | ');
            infoElement.appendChild(infoText);
        }

        if (item.needs_appointment) {
            if (additionalInfo.length > 0) {
                const separator = document.createElement('small');
                separator.className = 'text-muted';
                separator.textContent = ' | ';
                infoElement.appendChild(separator);
            }

            const statusSpan = document.createElement('span');

            if (item.has_appointment) {
                statusSpan.className = 'text-success';
                const checkIcon = document.createElement('i');
                checkIcon.className = 'fas fa-check-circle';
                statusSpan.appendChild(checkIcon);
                statusSpan.appendChild(document.createTextNode(' تم حجز موعد'));
            } else {
                statusSpan.className = 'text-warning';
                const clockIcon = document.createElement('i');
                clockIcon.className = 'fas fa-clock';
                statusSpan.appendChild(clockIcon);
                statusSpan.appendChild(document.createTextNode(' بانتظار حجز موعد'));
            }

            const statusWrapper = document.createElement('small');
            statusWrapper.appendChild(statusSpan);
            infoElement.appendChild(statusWrapper);
        }

        detailsContainer.appendChild(infoElement);

        const priceElement = document.createElement('div');
        priceElement.className = 'cart-item-price mb-2';
        priceElement.textContent = `${item.price} ر.س`;
        detailsContainer.appendChild(priceElement);

        const quantityControls = document.createElement('div');
        quantityControls.className = 'quantity-controls d-flex align-items-center gap-2';

        const minusBtn = document.createElement('button');
        minusBtn.className = 'btn btn-sm btn-outline-secondary';
        minusBtn.textContent = '-';
        minusBtn.onclick = function() { updateCartQuantity(item.id, -1); };
        quantityControls.appendChild(minusBtn);

        const quantityInput = document.createElement('input');
        quantityInput.type = 'number';
        quantityInput.value = item.quantity;
        quantityInput.min = '1';
        quantityInput.className = 'form-control form-control-sm quantity-input';
        quantityInput.onchange = function() { updateCartQuantity(item.id, 0, this.value); };
        quantityControls.appendChild(quantityInput);

        const plusBtn = document.createElement('button');
        plusBtn.className = 'btn btn-sm btn-outline-secondary';
        plusBtn.textContent = '+';
        plusBtn.onclick = function() { updateCartQuantity(item.id, 1); };
        quantityControls.appendChild(plusBtn);

        detailsContainer.appendChild(quantityControls);

        const subtotalElement = document.createElement('div');
        subtotalElement.className = 'cart-item-subtotal mt-2 text-primary';
        subtotalElement.textContent = `الإجمالي: ${item.subtotal} ر.س`;
        detailsContainer.appendChild(subtotalElement);

        flexContainer.appendChild(detailsContainer);
        itemInner.appendChild(flexContainer);
        itemElement.appendChild(itemInner);

        cartItems.appendChild(itemElement);
    });
}

function updateCartQuantity(itemId, change, newValue = null) {
    const quantityInput = document.querySelector(`[data-item-id="${itemId}"] .quantity-input`);
    const currentValue = parseInt(quantityInput.value);
    let quantity = newValue !== null ? parseInt(newValue) : currentValue + change;

    if (quantity < 1) {
        return;
    }

    const cartItem = quantityInput.closest('.cart-item');
    cartItem.style.opacity = '0.5';

    fetch(`/cart/items/${itemId}`, {
        method: 'PATCH',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        },
        body: JSON.stringify({ quantity })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            quantityInput.value = quantity;
            const subtotalElement = cartItem.querySelector('.cart-item-subtotal');
            subtotalElement.textContent = `الإجمالي: ${data.item_subtotal} ر.س`;

            const cartTotal = document.getElementById('cartTotal');
            if (cartTotal) {
                cartTotal.textContent = data.cart_total + ' ر.س';
            }

            const cartCountElements = document.querySelectorAll('.cart-count');
            cartCountElements.forEach(element => {
                element.textContent = data.cart_count;
            });

            showNotification('تم تحديث الكمية بنجاح', 'success');
        } else {
            quantityInput.value = currentValue;
            showNotification(data.message || 'فشل تحديث الكمية', 'error');
        }
    })
    .catch(error => {
        quantityInput.value = currentValue;
        showNotification('حدث خطأ أثناء تحديث الكمية', 'error');
    })
    .finally(() => {
        cartItem.style.opacity = '1';
    });
}

function removeFromCart(button, cartItemId) {
    if (event) {
        event.preventDefault();
    }

    if (!confirm('هل أنت متأكد من حذف هذا المنتج من السلة؟')) {
        return;
    }

    const cartItem = button.closest('.cart-item') || document.querySelector(`[data-item-id="${cartItemId}"]`);
    if (cartItem) {
        cartItem.style.opacity = '0.5';
    }

    fetch(`/cart/remove/${cartItemId}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (cartItem) {
                cartItem.style.opacity = '0';
                cartItem.style.transform = 'translateX(50px)';
            }

            updateCartDisplay(data);
            showNotification('تم حذف المنتج من السلة بنجاح', 'success');

            const appointmentModal = document.getElementById('appointmentModal');
            if (appointmentModal && bootstrap.Modal.getInstance(appointmentModal)) {
                appointmentModal.setAttribute('data-allow-close', 'true');
                bootstrap.Modal.getInstance(appointmentModal).hide();
            }

            loadCartItems();
        } else {
            if (cartItem) {
                cartItem.style.opacity = '1';
            }
            showNotification(data.message || 'حدث خطأ أثناء حذف المنتج', 'error');
        }
    })
    .catch(error => {
        if (cartItem) {
            cartItem.style.opacity = '1';
        }
        showNotification('حدث خطأ أثناء حذف المنتج', 'error');
    });
}

function openCart() {
    document.getElementById('cartSidebar').classList.add('active');
    document.querySelector('.cart-overlay').classList.add('active');
    document.body.classList.add('cart-open');
}

function closeCart() {
    document.getElementById('cartSidebar').classList.remove('active');
    document.querySelector('.cart-overlay').classList.remove('active');
    document.body.classList.remove('cart-open');
}

function loadCartItems() {
    fetch('/cart/items')
        .then(response => response.json())
        .then(data => {
            updateCartDisplay(data);
        });
}

function showLoginPrompt(loginUrl) {
    const currentUrl = window.location.href;
    const modal = new bootstrap.Modal(document.getElementById('loginPromptModal'));
    document.getElementById('loginButton').href = `${loginUrl}?redirect=${encodeURIComponent(currentUrl)}`;
    modal.show();
}

function updateFeatureVisibility(productFeatures) {
    if (!productFeatures) {
        return;
    }

    const colorsSection = document.querySelector('.colors-section');
    const customColorSection = document.querySelector('.custom-color-section');
    const useCustomColorCheckbox = document.getElementById('useCustomColor');
    const customColorGroup = document.getElementById('customColorGroup');

    if (colorsSection) {
        colorsSection.style.display = productFeatures.allow_color_selection ? 'block' : 'none';
    }

    if (customColorSection) {
        customColorSection.style.display = productFeatures.allow_custom_color ? 'block' : 'none';
    }

    if (useCustomColorCheckbox && customColorGroup) {
        useCustomColorCheckbox.closest('.custom-color-input').style.display =
            productFeatures.allow_custom_color ? 'block' : 'none';
    }

    const sizesSection = document.querySelector('.available-sizes');
    const customSizeInput = document.querySelector('.custom-size-input');
    const useCustomSizeCheckbox = document.getElementById('useCustomSize');
    const customSizeGroup = document.getElementById('customSizeGroup');

    if (sizesSection) {
        sizesSection.style.display = productFeatures.allow_size_selection ? 'block' : 'none';
    }

    if (customSizeInput) {
        customSizeInput.style.display = productFeatures.allow_custom_size ? 'block' : 'none';
    }

    const appointmentSection = document.querySelector('.custom-measurements-section');
    if (appointmentSection) {
        appointmentSection.style.display = productFeatures.allow_appointment ? 'block' : 'none';
    }
}

function toggleCart() {
    const cartSidebar = document.getElementById('cartSidebar');
    if (cartSidebar.classList.contains('active')) {
        closeCart();
    } else {
        openCart();
    }
}

document.addEventListener('DOMContentLoaded', function() {
    if (document.body.classList.contains('user-logged-in')) {
        loadCartItems();
    }

    document.getElementById('closeCart').addEventListener('click', closeCart);
    document.getElementById('cartToggle').addEventListener('click', toggleCart);
    document.getElementById('fixedCartBtn').addEventListener('click', toggleCart);

    const form = document.getElementById('appointmentForm');
    const dateInput = document.getElementById('appointment_date');
    const timeSelect = document.getElementById('appointment_time');
    const submitBtn = document.getElementById('submitBtn');
    const spinner = document.querySelector('.loading-spinner');
    const errorDiv = document.getElementById('appointmentErrors');
    const dateError = document.getElementById('date-error');
    const timeError = document.getElementById('time-error');

    if (dateInput) {
        const today = new Date();
        const maxDate = new Date();
        maxDate.setDate(today.getDate() + 30);

        dateInput.min = today.toISOString().split('T')[0];
        dateInput.max = maxDate.toISOString().split('T')[0];

        dateInput.addEventListener('change', async function() {
            const selectedDate = this.value;
            const timeSelect = document.getElementById('appointment_time');
            const dateSuggestion = document.getElementById('dateSuggestion');
            const suggestionText = document.getElementById('suggestionText');
            const acceptSuggestion = document.getElementById('acceptSuggestion');

            try {
                // التحقق ما إذا كان اليوم المختار هو يوم الجمعة
                const selectedDay = new Date(selectedDate).getDay();
                if (selectedDay === 5) { // 5 = الجمعة
                    dateSuggestion.classList.remove('d-none');
                    suggestionText.textContent = `يوم الجمعة هو يوم الراحة الأسبوعية. يرجى اختيار يوم آخر.`;

                    // العثور على اليوم التالي المتاح
                    const nextAvailableDate = await findNextAvailableDate(selectedDate);
                    if (nextAvailableDate) {
                        const formattedDate = new Date(nextAvailableDate).toLocaleDateString('ar-SA', {
                            weekday: 'long',
                            year: 'numeric',
                            month: 'long',
                            day: 'numeric'
                        });

                        suggestionText.textContent += ` أقرب يوم متاح هو ${formattedDate}`;

                        acceptSuggestion.onclick = function() {
                            dateInput.value = nextAvailableDate;
                            dateInput.dispatchEvent(new Event('change'));
                            dateSuggestion.classList.add('d-none');
                        };
                    }

                    return;
                }
                const response = await fetch(`/appointments/check-availability?date=${selectedDate}`);
                const data = await response.json();

                const appointments = data.appointments || [];

                if (data.workingHours) {
                    window.studioWorkingHours = data.workingHours;
                    updateWorkingHoursDisplay();
                }

                const bookedTimes = appointments.map(app => app.time);

                const availableSlots = getAvailableTimeSlots(selectedDate, bookedTimes);

                if (availableSlots.length === 0) {
                    const nextAvailableDate = await findNextAvailableDate(selectedDate);

                    if (nextAvailableDate) {
                        dateSuggestion.classList.remove('d-none');
                        const formattedDate = new Date(nextAvailableDate).toLocaleDateString('ar-SA', {
                            weekday: 'long',
                            year: 'numeric',
                            month: 'long',
                            day: 'numeric'
                        });
                        suggestionText.textContent = `هذا اليوم مكتمل. أقرب يوم متاح هو ${formattedDate}`;

                        acceptSuggestion.onclick = function() {
                            dateInput.value = nextAvailableDate;
                            dateInput.dispatchEvent(new Event('change'));
                            dateSuggestion.classList.add('d-none');
                        };
                    }
                } else {
                    dateSuggestion.classList.add('d-none');
                    populateTimeSelect(timeSelect, availableSlots);
                }
            } catch (error) {
                console.error('Error checking availability:', error);
            }
        });
    }

    const useCustomColorCheckbox = document.getElementById('useCustomColor');
    const customColorGroup = document.getElementById('customColorGroup');

    if (useCustomColorCheckbox) {
        useCustomColorCheckbox.addEventListener('change', function() {
            if (this.checked) {
                customColorGroup.classList.remove('d-none');
                document.querySelectorAll('.color-item').forEach(item => {
                    item.classList.remove('active');
                });
                selectedColor = null;
            } else {
                customColorGroup.classList.add('d-none');
                document.getElementById('customColor').value = '';
            }
        });
    }

    const useCustomSizeCheckbox = document.getElementById('useCustomSize');
    const customSizeGroup = document.getElementById('customSizeGroup');

    if (useCustomSizeCheckbox) {
        useCustomSizeCheckbox.addEventListener('change', function() {
            if (this.checked) {
                customSizeGroup.classList.remove('d-none');
                document.querySelectorAll('.size-item').forEach(item => {
                    item.classList.remove('active');
                });
                selectedSize = null;
            } else {
                customSizeGroup.classList.add('d-none');
                document.getElementById('customSize').value = '';
            }
        });
    }

    const customSizeInput = document.getElementById('customSize');
    if (customSizeInput) {
        customSizeInput.addEventListener('input', function() {
            const errorMessage = document.getElementById('errorMessage');
            if (errorMessage && !errorMessage.classList.contains('d-none')) {
                errorMessage.classList.add('d-none');
            }
        });
    }

    const customColorInput = document.getElementById('customColor');
    if (customColorInput) {
        customColorInput.addEventListener('input', function() {
            const errorMessage = document.getElementById('errorMessage');
            if (errorMessage && !errorMessage.classList.contains('d-none')) {
                errorMessage.classList.add('d-none');
            }
        });
    }

    document.getElementById('needsAppointment')?.addEventListener('change', function(e) {
        if (e.target.checked) {
            document.querySelectorAll('.color-item').forEach(item => {
                item.classList.remove('active');
            });
            selectedColor = null;

            if (document.getElementById('customColor')) {
                document.getElementById('customColor').value = '';
            }

            document.querySelectorAll('.size-option').forEach(item => {
                item.classList.remove('active');
            });
            selectedSize = null;

            if (document.getElementById('customSize')) {
                document.getElementById('customSize').value = '';
            }

            const errorMessage = document.getElementById('errorMessage');
            if (errorMessage && !errorMessage.classList.contains('d-none')) {
                errorMessage.classList.add('d-none');
            }

            showNotification('تم اختيار خيار حجز موعد لأخذ المقاسات', 'success');
        }
    });

    const appointmentModal = document.getElementById('appointmentModal');
    if (appointmentModal) {
        const modal = new bootstrap.Modal(appointmentModal);

        const appointmentForm = document.getElementById('appointmentForm');
        if (appointmentForm) {
            appointmentForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const submitBtn = document.getElementById('submitBtn');
                const spinner = submitBtn.querySelector('.loading-spinner');
                const errorDiv = document.getElementById('appointmentErrors');

                submitBtn.disabled = true;
                spinner.classList.remove('d-none');
                errorDiv.classList.add('d-none');

                fetch('/appointments', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(Object.fromEntries(new FormData(appointmentForm)))
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification(data.message, 'success');

                        const urlParams = new URLSearchParams(window.location.search);
                        let redirectUrl = urlParams.has('pending_appointment') ?
                            '/cart' :
                            (data.redirect_url || '/appointments');

                        if (redirectUrl.startsWith('http')) {
                            try {
                                const url = new URL(redirectUrl);
                                if (url.origin !== window.location.origin) {
                                    redirectUrl = '/appointments';
                                }
                            } catch (e) {
                                redirectUrl = '/appointments';
                            }
                        } else if (!redirectUrl.startsWith('/')) {
                            redirectUrl = '/' + redirectUrl;
                        }

                        setTimeout(() => {
                            window.location.href = redirectUrl;
                        }, 2000);
                    } else {
                        throw new Error(data.message || 'حدث خطأ أثناء حجز الموعد');
                    }
                })
                .catch(error => {
                    errorDiv.textContent = error.message;
                    if (error.errors) {
                        const errorList = document.createElement('ul');
                        Object.values(error.errors).forEach(error => {
                            const li = document.createElement('li');
                            li.textContent = error[0];
                            errorList.appendChild(li);
                        });
                        errorDiv.appendChild(errorList);
                    }
                    errorDiv.classList.remove('d-none');
                })
                .finally(() => {
                    submitBtn.disabled = false;
                    spinner.classList.add('d-none');
                });
            });
        }
    }

    const urlParams = new URLSearchParams(window.location.search);
    const pendingAppointment = urlParams.get('pending_appointment');

    if (pendingAppointment) {
        showAppointmentModal(pendingAppointment);
    }

    const appointmentsEnabled = getAppointmentsStatus();
    const needsAppointmentCheckbox = document.getElementById('needsAppointment');

    if (needsAppointmentCheckbox) {
        const measurementsSection = document.querySelector('.custom-measurements-section');
        if (!appointmentsEnabled && measurementsSection) {
            measurementsSection.style.display = 'none';
        }
    }

    const cancelAppointmentBtn = document.getElementById('cancelAppointment');

    if (cancelAppointmentBtn) {
        cancelAppointmentBtn.addEventListener('click', function() {
            if (confirm('هل أنت متأكد من إلغاء حجز الموعد؟ سيتم إزالة المنتج من السلة.')) {
                const cartItemId = document.getElementById('cart_item_id').value;

                fetch(`/cart/remove/${cartItemId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.querySelectorAll('.cart-count').forEach(el => {
                            el.textContent = data.count;
                        });

                        showNotification('تم إلغاء الموعد وإزالة المنتج من السلة', 'success');

                        const urlParams = new URLSearchParams(window.location.search);
                        if (urlParams.has('pending_appointment')) {
                            window.location.href = '/cart';
                        } else {
                            bootstrap.Modal.getInstance(document.getElementById('appointmentModal')).hide();
                            loadCartItems();
                        }
                    } else {
                        throw new Error(data.message || 'حدث خطأ أثناء إلغاء الموعد');
                    }
                })
                .catch(error => {
                    showNotification(error.message, 'error');
                });
            }
        });
    }

    const firstQuantityOption = document.querySelector('.quantity-option.available');
    if (firstQuantityOption) {
        try {
            selectQuantityOption(firstQuantityOption);
        } catch (error) {
        }
    }

    const productId = document.getElementById('product-id').value;

    fetch(`/products/${productId}/details`)
        .then(response => {
            if (!response.ok) {
                if (response.status === 404) {
                    return { features: [] };
                }
                throw new Error(`HTTP error! Status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data && data.features) {
                updateFeatureVisibility(data.features);
            }
        })
        .catch(error => {
        });
});

async function findNextAvailableDate(startDate) {
    const maxTries = 30;
    let currentDate = new Date(startDate);

    for (let i = 1; i <= maxTries; i++) {
        currentDate.setDate(currentDate.getDate() + 1);
        const dateString = currentDate.toISOString().split('T')[0];

        if (currentDate.getDay() === 5) {
            continue;
        }

        try {
            const response = await fetch(`/appointments/check-availability?date=${dateString}`);
            const data = await response.json();

            const appointments = data.appointments || [];

            if (data.workingHours) {
                const startHour = data.workingHours.start;
                const endHour = data.workingHours.end;
                const isOvernight = endHour < startHour;

                window.studioWorkingHours = data.workingHours;
                updateWorkingHoursDisplay();
            }

            const bookedTimes = appointments.map(app => app.time);
            const availableSlots = getAvailableTimeSlots(dateString, bookedTimes);

            if (availableSlots.length > 0) {
                return dateString;
            }
        } catch (error) {
        }
    }

    return null;
}

function getAvailableTimeSlots(date, bookedTimes) {
    const workingHours = window.studioWorkingHours || {
        start: 10,
        end: 18,
        isOvernight: false
    };

    const slots = [];
    const selectedDate = new Date(date);
    const dayOfWeek = selectedDate.getDay();

    if (dayOfWeek !== 5) {
        const isOvernight = workingHours.isOvernight || (workingHours.end < workingHours.start);

        let startHour = workingHours.start;
        let endHour = isOvernight ? 24 : workingHours.end;

        for (let hour = startHour; hour < endHour; hour++) {
            for (let minute = 0; minute < 60; minute += 30) {
                const timeString = `${hour.toString().padStart(2, '0')}:${minute.toString().padStart(2, '0')}`;
                if (!bookedTimes.includes(timeString)) {
                    slots.push(timeString);
                }
            }
        }

        if (isOvernight) {
            for (let hour = 0; hour < workingHours.end; hour++) {
                for (let minute = 0; minute < 60; minute += 30) {
                    const timeString = `${hour.toString().padStart(2, '0')}:${minute.toString().padStart(2, '0')}`;
                    if (!bookedTimes.includes(timeString)) {
                        slots.push(timeString);
                    }
                }
            }
        }
    }

    return slots;
}

function populateTimeSelect(selectElement, availableSlots) {
    selectElement.innerHTML = '<option value="">اختر الوقت</option>';
    selectElement.disabled = false;

    availableSlots.forEach(slot => {
        const option = document.createElement('option');
        option.value = slot;
        option.textContent = slot;
        selectElement.appendChild(option);
    });
}

function selectQuantityOption(option) {
    if (!option || option.classList.contains('disabled')) {
        return;
    }

    if (option.classList.contains('active')) {
        option.classList.remove('active');
        selectedQuantityId = null;
        selectedQuantityValue = null;
        selectedQuantityPrice = null;

        updatePrice();
        return;
    }

    document.querySelectorAll('.quantity-option').forEach(opt => {
        opt.classList.remove('active');
    });

    option.classList.add('active');

    selectedQuantityId = option.dataset.quantityId;
    selectedQuantityValue = option.dataset.quantityValue;
    selectedQuantityPrice = option.dataset.price;

    updatePrice();

    const quantityErrorAlert = document.getElementById('quantity-error-alert');
    if (quantityErrorAlert) {
        quantityErrorAlert.classList.add('d-none');
    }
}

function toggleCustomSize(checkbox) {
    const customSizeGroup = document.getElementById('customSizeGroup');
    const customSizeInput = document.getElementById('customSize');

    if (checkbox.checked) {
        customSizeGroup.classList.remove('d-none');
        customSizeInput.disabled = false;
        customSizeInput.focus();

        document.querySelectorAll('.size-option').forEach(item => {
            item.classList.remove('active');
        });
        selectedSize = null;

        updatePrice();
    } else {
        customSizeGroup.classList.add('d-none');
        customSizeInput.value = '';
        customSizeInput.disabled = true;
    }
}

function toggleCustomColor(checkbox) {
    const customColorGroup = document.getElementById('customColorGroup');
    const customColorInput = document.getElementById('customColor');

    if (checkbox.checked) {
        customColorGroup.classList.remove('d-none');
        customColorInput.disabled = false;
        customColorInput.focus();

        document.querySelectorAll('.color-item').forEach(item => {
            item.classList.remove('active');
        });
        selectedColor = null;
    } else {
        customColorGroup.classList.add('d-none');
        customColorInput.value = '';
        customColorInput.disabled = true;
    }
}
