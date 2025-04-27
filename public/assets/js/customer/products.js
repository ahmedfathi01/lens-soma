// Filter and Sort Functions
let activeFilters = {
    categories: [],
    minPrice: 0,
    maxPrice: 1000,
    sort: 'newest',
    has_discount: false
};

// Función para validar URLs y prevenir redirecciones abiertas
function validateRedirectUrl(url) {
    // Si no hay URL, devolver ruta predeterminada segura
    if (!url) return '/products';

    // Verificar primero rutas internas específicas (lista blanca)
    const safeInternalPaths = ['/cart', '/appointments', '/products', '/account', '/'];
    if (safeInternalPaths.includes(url)) {
        return url;
    }

    try {
        // Para URLs absolutas, verificar si pertenecen a nuestro dominio
        if (url.indexOf('://') > -1 || url.indexOf('//') === 0) {
            // Bloquear URLs absolutas externas
            console.error('Intento de redirección externa bloqueado:', url);
            return '/products';
        }
        // Para rutas relativas, permitir solo las que comienzan con una barra
        else if (!url.startsWith('/')) {
            console.error('Ruta relativa inválida bloqueada:', url);
            return '/products';
        }

        // Bloquear rutas que comienzan con doble barra
        if (url.startsWith('//')) {
            console.error('Intento de inyección de protocolo relativo bloqueado:', url);
            return '/products';
        }

        // Verificación adicional para protección contra inyección de protocolos dañinos
        const lowerUrl = url.toLowerCase().trim();
        if (lowerUrl.startsWith('javascript:') ||
            lowerUrl.startsWith('data:') ||
            lowerUrl.startsWith('vbscript:')) {
            console.error('Intento de inyección de protocolo bloqueado:', url);
            return '/products';
        }

        // La URL pasó todas las verificaciones de seguridad
        return url;
    } catch (e) {
        // Si el análisis de URL falla, devolver ruta predeterminada segura
        console.error('Error en la validación de URL:', e);
        return '/products';
    }
}

// Función para redirección segura
function safeRedirect(url) {
    // Aplicar validación de seguridad
    const safeUrl = validateRedirectUrl(url);

    // Usar método más seguro que la asignación directa a window.location.href
    if (safeUrl.startsWith('/')) {
        // Ruta relativa segura dentro de nuestra aplicación
        window.location.replace(safeUrl); // Usar replace en lugar de href
    } else {
        // Garantía adicional de seguridad, no deberíamos llegar aquí
        window.location.replace('/products');
    }
}

// Initialize when document is ready
document.addEventListener('DOMContentLoaded', function() {
    initializeFilters();

    // تحقق مما إذا كان المستخدم مسجل دخول قبل تحميل السلة
    if (document.body.classList.contains('user-logged-in')) {
        loadCartItems();
    }

    // Setup event listeners for both cart buttons
    document.getElementById('closeCart').addEventListener('click', closeCart);

    // Cart toggle in navbar
    document.getElementById('cartToggle')?.addEventListener('click', function() {
        if (!document.body.classList.contains('user-logged-in')) {
            showLoginPrompt('{{ route("login") }}');
            return;
        }

        const cartSidebar = document.getElementById('cartSidebar');
        if (cartSidebar.classList.contains('active')) {
            closeCart();
        } else {
            openCart();
        }
    });

    // Fixed cart button
    document.getElementById('fixedCartBtn')?.addEventListener('click', function() {
        if (!document.body.classList.contains('user-logged-in')) {
            showLoginPrompt('{{ route("login") }}');
            return;
        }

        const cartSidebar = document.getElementById('cartSidebar');
        if (cartSidebar.classList.contains('active')) {
            closeCart();
        } else {
            openCart();
        }
    });

    document.querySelector('.cart-overlay')?.addEventListener('click', closeCart);

    // Setup quick add to cart buttons
    document.querySelectorAll('.add-to-cart-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            quickAddToCart(this.dataset.productId);
        });
    });
});

// Initialize Filters
function initializeFilters() {
    // Initialize price range slider with debounce
    const priceRange = document.getElementById('priceRange');
    const priceValue = document.getElementById('priceValue');
    const discountFilter = document.getElementById('discountFilter');
    let priceUpdateTimeout;

    if (priceRange) {
        priceRange.addEventListener('input', function() {
            // Update display value immediately
            priceValue.textContent = Number(this.value).toLocaleString() + ' ر.س';

            // Update filter with debounce
            clearTimeout(priceUpdateTimeout);
            priceUpdateTimeout = setTimeout(() => {
                activeFilters.maxPrice = Number(this.value);
                applyFilters();
            }, 500);
        });

        // Add touchend/mouseup event
        priceRange.addEventListener('change', function() {
            clearTimeout(priceUpdateTimeout);
            activeFilters.maxPrice = Number(this.value);
            applyFilters();
        });
    }

    // Discount filter handler
    if (discountFilter) {
        discountFilter.addEventListener('change', function() {
            activeFilters.has_discount = this.checked;
            applyFilters();
        });
    }

    // Category filter handlers
    document.querySelectorAll('.form-check-input[type="checkbox"][name="categories[]"]').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const categorySlug = this.value;
            if (this.checked) {
                if (!activeFilters.categories.includes(categorySlug)) {
                    activeFilters.categories.push(categorySlug);
                }
            } else {
                activeFilters.categories = activeFilters.categories.filter(slug => slug !== categorySlug);
            }
            applyFilters();
        });
    });

    // Sort handler
    document.getElementById('sortSelect').addEventListener('change', function() {
        activeFilters.sort = this.value;
        applyFilters();
    });
}

// Apply Filters
function applyFilters() {
    // Show loading state
    const productGrid = document.getElementById('productGrid');
    const loadingSpinner = document.getElementById('loadingSpinner');
    const noProductsMessage = document.getElementById('noProductsMessage');

    if (productGrid && loadingSpinner && noProductsMessage) {
        productGrid.style.display = 'none';
        loadingSpinner.style.display = 'block';
        noProductsMessage.style.display = 'none';
    } else {
        // Fallback to old opacity transition if elements don't exist
        if (productGrid) productGrid.style.opacity = '0.5';
    }

    // Create a copy of activeFilters
    const filterData = {
        categories: activeFilters.categories,
        minPrice: Number(activeFilters.minPrice),
        maxPrice: Number(activeFilters.maxPrice),
        sort: activeFilters.sort,
        has_discount: activeFilters.has_discount
    };

    fetch(window.appConfig.routes.products.filter, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        },
        body: JSON.stringify(filterData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success === false) {
            throw new Error(data.message || 'حدث خطأ أثناء تحديث المنتجات');
        }

        // تحديث شبكة المنتجات
        updateProductGrid(data.products || []);

        // تحديث الترقيم الصفحي إذا كان موجوداً
        if (data.links) {
            updatePagination(data.links);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification(error.message || 'حدث خطأ أثناء تحديث المنتجات', 'error');
        // Show empty state when error occurs
        updateProductGrid([]);
    })
    .finally(() => {
        // Hide loading spinner
        if (loadingSpinner) {
            loadingSpinner.style.display = 'none';
        }

        // Fallback to old opacity transition if elements don't exist
        if (productGrid && !loadingSpinner) {
            productGrid.style.opacity = '1';
        }
    });
}

// Update Product Grid
function updateProductGrid(products) {
    const productGrid = document.getElementById('productGrid');
    const noProductsMessage = document.getElementById('noProductsMessage');

    if (!productGrid) return;

    // Clear the product grid
    while (productGrid.firstChild) {
        productGrid.removeChild(productGrid.firstChild);
    }

    if (!products || products.length === 0) {
        if (noProductsMessage) {
            productGrid.style.display = 'none';
            noProductsMessage.style.display = 'block';
        } else {
            // Create no products message with DOM methods
            const noProductsDiv = document.createElement('div');
            noProductsDiv.className = 'col-12 text-center py-5';

            const icon = document.createElement('i');
            icon.className = 'fas fa-box-open fa-3x mb-3 text-muted';
            noProductsDiv.appendChild(icon);

            const heading = document.createElement('h3');
            heading.textContent = 'لا توجد منتجات';
            noProductsDiv.appendChild(heading);

            const description = document.createElement('p');
            description.className = 'text-muted';
            description.textContent = 'لم يتم العثور على منتجات تطابق معايير البحث';
            noProductsDiv.appendChild(description);

            const resetButton = document.createElement('button');
            resetButton.className = 'btn btn-primary mt-3';
            resetButton.onclick = resetFilters;

            const resetIcon = document.createElement('i');
            resetIcon.className = 'fas fa-sync-alt me-2';
            resetButton.appendChild(resetIcon);

            resetButton.appendChild(document.createTextNode('إعادة ضبط الفلتر'));
            noProductsDiv.appendChild(resetButton);

            productGrid.appendChild(noProductsDiv);
            productGrid.style.display = 'block';
        }
        return;
    }

    // Show grid and hide no products message if it exists
    productGrid.style.display = 'flex';
    if (noProductsMessage) {
        noProductsMessage.style.display = 'none';
    }

    products.forEach(product => {
        // Create product card with DOM manipulation
        const productColumn = document.createElement('div');
        productColumn.className = 'col-md-6 col-lg-4';

        const productCard = document.createElement('div');
        productCard.className = 'product-card';

        // Add coupon badge if product has coupon
        if (product.has_coupon && product.best_coupon) {
            const couponBadge = document.createElement('div');
            couponBadge.className = 'coupon-badge';

            const ticketIcon = document.createElement('i');
            ticketIcon.className = 'fas fa-ticket-alt';
            couponBadge.appendChild(ticketIcon);

            const couponText = document.createTextNode(' ' + product.best_coupon.code + ' ');
            couponBadge.appendChild(couponText);

            const couponValue = product.best_coupon.type === 'percentage'
                ? `${product.best_coupon.value}%`
                : `${product.best_coupon.value} ر.س`;
            couponBadge.appendChild(document.createTextNode(couponValue));

            productCard.appendChild(couponBadge);
        }

        // Create product image wrapper
        const imageLink = document.createElement('a');
        imageLink.href = `/products/${product.slug}`;
        imageLink.className = 'product-image-wrapper';

        const productImage = document.createElement('img');
        productImage.src = product.image_url || '/storage/' + (product.images && product.images[0] ? product.images[0].image_path : '');
        productImage.alt = product.name;
        productImage.className = 'product-image';

        imageLink.appendChild(productImage);
        productCard.appendChild(imageLink);

        // Create product details
        const productDetails = document.createElement('div');
        productDetails.className = 'product-details';

        // Product category
        const productCategory = document.createElement('div');
        productCategory.className = 'product-category';
        productCategory.textContent = product.category?.name || product.category;
        productDetails.appendChild(productCategory);

        // Product title
        const titleLink = document.createElement('a');
        titleLink.href = `/products/${product.slug}`;
        titleLink.className = 'product-title text-decoration-none';

        const productTitle = document.createElement('h3');
        productTitle.textContent = product.name;
        titleLink.appendChild(productTitle);
        productDetails.appendChild(titleLink);

        // Product rating
        const ratingDiv = document.createElement('div');
        ratingDiv.className = 'product-rating';

        const starsDiv = document.createElement('div');
        starsDiv.className = 'stars';
        starsDiv.style.setProperty('--rating', product.rating || 0);
        ratingDiv.appendChild(starsDiv);

        const reviewsSpan = document.createElement('span');
        reviewsSpan.className = 'reviews';
        reviewsSpan.textContent = `(${product.reviews || 0} تقييم)`;
        ratingDiv.appendChild(reviewsSpan);

        productDetails.appendChild(ratingDiv);

        // Product price
        const priceP = document.createElement('p');
        priceP.className = 'product-price';

        let priceText;
        if (product.price_display) {
            priceText = product.price_display;
        } else if (product.price_range) {
            priceText = product.price_range.min === product.price_range.max
                ? `${product.price_range.min.toLocaleString()} ر.س`
                : `${product.price_range.min.toLocaleString()} - ${product.price_range.max.toLocaleString()} ر.س`;
        } else {
            priceText = '0 ر.س';
        }

        priceP.textContent = priceText;
        productDetails.appendChild(priceP);

        // Product actions
        const actionsDiv = document.createElement('div');
        actionsDiv.className = 'product-actions';

        const orderLink = document.createElement('a');
        orderLink.href = `/products/${product.slug}`;
        orderLink.className = 'order-product-btn';

        const cartIcon = document.createElement('i');
        cartIcon.className = 'fas fa-shopping-cart me-2';
        orderLink.appendChild(cartIcon);

        orderLink.appendChild(document.createTextNode('طلب المنتج'));
        actionsDiv.appendChild(orderLink);

        productDetails.appendChild(actionsDiv);
        productCard.appendChild(productDetails);
        productColumn.appendChild(productCard);

        productGrid.appendChild(productColumn);
    });
}

// تحديث الترقيم الصفحي
function updatePagination(links) {
    const paginationContainer = document.querySelector('.pagination');
    if (!paginationContainer) return;

    // Remove all existing pagination items
    while (paginationContainer.firstChild) {
        paginationContainer.removeChild(paginationContainer.firstChild);
    }

    // إضافة زر السابق
    if (links.prev) {
        const prevItem = document.createElement('li');
        prevItem.className = 'page-item';

        const prevLink = document.createElement('a');
        prevLink.className = 'page-link';
        prevLink.href = '#';
        prevLink.onclick = function(e) {
            e.preventDefault();
            loadPage(links.prev);
            return false;
        };

        const prevIcon = document.createElement('i');
        prevIcon.className = 'fas fa-chevron-right';
        prevLink.appendChild(prevIcon);

        prevItem.appendChild(prevLink);
        paginationContainer.appendChild(prevItem);
    }

    // إضافة الأرقام
    if (links.links) {
        links.links.forEach(link => {
            if (link.url === null) return;

            const pageItem = document.createElement('li');
            pageItem.className = `page-item ${link.active ? 'active' : ''}`;

            const pageLink = document.createElement('a');
            pageLink.className = 'page-link';
            pageLink.href = '#';
            pageLink.textContent = link.label;
            pageLink.onclick = function(e) {
                e.preventDefault();
                loadPage(link.url);
                return false;
            };

            pageItem.appendChild(pageLink);
            paginationContainer.appendChild(pageItem);
        });
    }

    // إضافة زر التالي
    if (links.next) {
        const nextItem = document.createElement('li');
        nextItem.className = 'page-item';

        const nextLink = document.createElement('a');
        nextLink.className = 'page-link';
        nextLink.href = '#';
        nextLink.onclick = function(e) {
            e.preventDefault();
            loadPage(links.next);
            return false;
        };

        const nextIcon = document.createElement('i');
        nextIcon.className = 'fas fa-chevron-left';
        nextLink.appendChild(nextIcon);

        nextItem.appendChild(nextLink);
        paginationContainer.appendChild(nextItem);
    }
}

// تحميل صفحة معينة
function loadPage(url) {
    fetch(url, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success === false) {
            throw new Error(data.message || 'حدث خطأ أثناء تحميل الصفحة');
        }
        updateProductGrid(data.products || []);
        if (data.links) {
            updatePagination(data.links);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('حدث خطأ أثناء تحميل الصفحة', 'error');
    });
}

// Reset Filters
function resetFilters() {
    // Reset checkboxes
    document.querySelectorAll('.form-check-input[type="checkbox"]').forEach(checkbox => {
        checkbox.checked = false;
    });

    // Reset price range
    const priceRangeInput = document.getElementById('priceRange');
    if (priceRangeInput) {
        priceRangeInput.value = priceRangeInput.max;
        document.getElementById('priceValue').textContent = Number(priceRangeInput.max).toLocaleString() + ' ر.س';
    }

    // Reset discount filter
    const discountFilter = document.getElementById('discountFilter');
    if (discountFilter) {
        discountFilter.checked = false;
    }

    // Reset sort
    document.getElementById('sortSelect').value = 'newest';

    // Reset active filters
    activeFilters = {
        categories: [],
        minPrice: Number(priceRangeInput?.min || 0),
        maxPrice: Number(priceRangeInput?.max || 1000),
        sort: 'newest',
        has_discount: false
    };

    // Clear URL parameters
    const url = new URL(window.location.href);
    url.searchParams.delete('category');
    url.searchParams.delete('sort');
    url.searchParams.delete('min_price');
    url.searchParams.delete('max_price');
    url.searchParams.delete('has_discount');
    window.history.replaceState({}, '', url.toString());

    // Show notification
    showNotification('تم إعادة تعيين الفلتر بنجاح', 'success');

    // Apply reset filters
    applyFilters();
}

function showNotification(message, type = 'success') {
    // Sanitize the message by creating a text node instead of using textContent directly
    const sanitizedMessage = document.createTextNode(message);

    // Validate the type parameter to only allow specific values
    const allowedTypes = ['success', 'error', 'warning', 'info'];
    const safeType = allowedTypes.includes(type) ? type : 'success';

    const notification = document.createElement('div');
    notification.className = `alert alert-${safeType} notification-toast`;
    notification.appendChild(sanitizedMessage);
    document.body.appendChild(notification);

    // تأثير ظهور الإشعار
    setTimeout(() => notification.classList.add('show'), 100);

    // إخفاء وإزالة الإشعار
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

function updateCartDisplay(data) {
    const cartItems = document.getElementById('cartItems');
    const cartTotal = document.getElementById('cartTotal');
    const cartCountElements = document.querySelectorAll('.cart-count');

    // تحديث عدد العناصر في كل أزرار السلة
    cartCountElements.forEach(element => {
        element.textContent = data.count || data.cart_count;
    });

    // تحديث الإجمالي
    cartTotal.textContent = (data.total || data.cart_total) + ' ر.س';

    // تحديث قائمة العناصر
    while (cartItems.firstChild) {
        cartItems.removeChild(cartItems.firstChild);
    }

    if (!data.items || data.items.length === 0) {
        // Create empty cart message
        const emptyCartDiv = document.createElement('div');
        emptyCartDiv.className = 'cart-empty text-center p-4';

        const cartIcon = document.createElement('i');
        cartIcon.className = 'fas fa-shopping-cart fa-3x mb-3';
        emptyCartDiv.appendChild(cartIcon);

        const emptyMessage = document.createElement('p');
        emptyMessage.className = 'mb-3';
        emptyMessage.textContent = 'السلة فارغة';
        emptyCartDiv.appendChild(emptyMessage);

        const browseLink = document.createElement('a');
        browseLink.href = '/products';
        browseLink.className = 'btn btn-primary';
        browseLink.textContent = 'تصفح المنتجات';
        emptyCartDiv.appendChild(browseLink);

        cartItems.appendChild(emptyCartDiv);
        return;
    }

    data.items.forEach(item => {
        // Create cart item with DOM manipulation
        const itemElement = document.createElement('div');
        itemElement.className = 'cart-item';
        itemElement.dataset.itemId = item.id;

        const itemInner = document.createElement('div');
        itemInner.className = 'cart-item-inner p-3 border-bottom';

        // Remove button
        const removeButton = document.createElement('button');
        removeButton.className = 'remove-btn btn btn-link text-danger';
        removeButton.onclick = function() {
            removeFromCart(this, item.id);
        };

        const removeIcon = document.createElement('i');
        removeIcon.className = 'fas fa-times';
        removeButton.appendChild(removeIcon);
        itemInner.appendChild(removeButton);

        // Item content wrapper
        const contentWrapper = document.createElement('div');
        contentWrapper.className = 'd-flex gap-3';

        // Item image
        const itemImage = document.createElement('img');
        itemImage.src = item.image;
        itemImage.alt = item.name;
        itemImage.className = 'cart-item-image';
        contentWrapper.appendChild(itemImage);

        // Item details
        const detailsDiv = document.createElement('div');
        detailsDiv.className = 'cart-item-details flex-grow-1';

        // Item title
        const titleHeading = document.createElement('h5');
        titleHeading.className = 'cart-item-title mb-2';
        titleHeading.textContent = item.name;
        detailsDiv.appendChild(titleHeading);

        // Additional info
        const additionalInfo = [];
        if (item.color) additionalInfo.push(`اللون: ${item.color}`);
        if (item.size) additionalInfo.push(`المقاس: ${item.size}`);

        if (item.needs_appointment) {
            if (item.has_appointment) {
                const appointmentSpan = document.createElement('span');
                appointmentSpan.className = 'text-success';

                const checkIcon = document.createElement('i');
                checkIcon.className = 'fas fa-check-circle';
                appointmentSpan.appendChild(checkIcon);
                appointmentSpan.appendChild(document.createTextNode(' تم حجز موعد'));

                // Safely add the DOM element to the array instead of HTML string
                additionalInfo.push(appointmentSpan);
            } else {
                const pendingSpan = document.createElement('span');
                pendingSpan.className = 'text-warning';

                const clockIcon = document.createElement('i');
                clockIcon.className = 'fas fa-clock';
                pendingSpan.appendChild(clockIcon);
                pendingSpan.appendChild(document.createTextNode(' بانتظار حجز موعد'));

                // Safely add the DOM element to the array instead of HTML string
                additionalInfo.push(pendingSpan);
            }
        }

        if (additionalInfo.length > 0) {
            const infoDiv = document.createElement('div');
            infoDiv.className = 'cart-item-info mb-2';

            const infoSmall = document.createElement('small');
            infoSmall.className = 'text-muted';

            // Create a safer approach for joining the additional info
            additionalInfo.forEach((info, index) => {
                if (index > 0) {
                    infoSmall.appendChild(document.createTextNode(' | '));
                }

                if (typeof info === 'string') {
                    infoSmall.appendChild(document.createTextNode(info));
                } else {
                    infoSmall.appendChild(info);
                }
            });

            infoDiv.appendChild(infoSmall);
            detailsDiv.appendChild(infoDiv);
        }

        // Item price
        const priceDiv = document.createElement('div');
        priceDiv.className = 'cart-item-price mb-2';
        priceDiv.textContent = item.price + ' ر.س';
        detailsDiv.appendChild(priceDiv);

        // Quantity controls
        const quantityControls = document.createElement('div');
        quantityControls.className = 'quantity-controls d-flex align-items-center gap-2';

        const decreaseBtn = document.createElement('button');
        decreaseBtn.className = 'btn btn-sm btn-outline-secondary';
        decreaseBtn.textContent = '-';
        decreaseBtn.onclick = function() {
            updateQuantity(item.id, -1);
        };
        quantityControls.appendChild(decreaseBtn);

        const quantityInput = document.createElement('input');
        quantityInput.type = 'number';
        quantityInput.value = item.quantity;
        quantityInput.min = '1';
        quantityInput.className = 'form-control form-control-sm quantity-input';
        quantityInput.onchange = function() {
            updateQuantity(item.id, 0, this.value);
        };
        quantityControls.appendChild(quantityInput);

        const increaseBtn = document.createElement('button');
        increaseBtn.className = 'btn btn-sm btn-outline-secondary';
        increaseBtn.textContent = '+';
        increaseBtn.onclick = function() {
            updateQuantity(item.id, 1);
        };
        quantityControls.appendChild(increaseBtn);

        detailsDiv.appendChild(quantityControls);

        // Item subtotal
        const subtotalDiv = document.createElement('div');
        subtotalDiv.className = 'cart-item-subtotal mt-2 text-primary';
        subtotalDiv.textContent = 'الإجمالي: ' + item.subtotal + ' ر.س';
        detailsDiv.appendChild(subtotalDiv);

        contentWrapper.appendChild(detailsDiv);
        itemInner.appendChild(contentWrapper);
        itemElement.appendChild(itemInner);

        cartItems.appendChild(itemElement);
    });
}

function updateQuantity(itemId, change, newValue = null) {
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
            // تحديث الكمية والإجمالي الفرعي للعنصر فقط
            quantityInput.value = quantity;
            const subtotalElement = cartItem.querySelector('.cart-item-subtotal');
            subtotalElement.textContent = `الإجمالي: ${data.item_subtotal} ر.س`;

            // تحديث إجمالي السلة وعدد العناصر
            const cartTotal = document.getElementById('cartTotal');
            const cartCountElements = document.querySelectorAll('.cart-count');

            cartTotal.textContent = data.cart_total + ' ر.س';
            cartCountElements.forEach(element => {
                element.textContent = data.cart_count;
            });
        } else {
            // إرجاع القيمة القديمة في حالة الخطأ
            quantityInput.value = currentValue;
            showNotification(data.message || 'فشل تحديث الكمية', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        // إرجاع القيمة القديمة في حالة الخطأ
        quantityInput.value = currentValue;
        showNotification('حدث خطأ أثناء تحديث الكمية', 'error');
    })
    .finally(() => {
        cartItem.style.opacity = '1';
    });
}

function removeFromCart(button, cartItemId) {
    event.preventDefault();

    if (!confirm('هل أنت متأكد من حذف هذا المنتج من السلة؟')) {
        return;
    }

    const cartItem = button.closest('.cart-item');
    cartItem.style.opacity = '0.5';

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
            cartItem.style.opacity = '0';
            cartItem.style.transform = 'translateX(50px)';

            // تحديث عرض السلة مباشرة
            updateCartDisplay(data);

            // إضافة تأخير قصير قبل إعادة تحميل عناصر السلة
            setTimeout(() => {
                loadCartItems();
            }, 300);

            showNotification('تم حذف المنتج من السلة بنجاح', 'success');
        } else {
            cartItem.style.opacity = '1';
            showNotification(data.message || 'حدث خطأ أثناء حذف المنتج', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        cartItem.style.opacity = '1';
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
        .then(data => updateCartDisplay(data))
        .catch(error => console.error('Error:', error));
}

function showLoginPrompt(loginUrl) {
    const currentUrl = window.location.href;
    const modal = new bootstrap.Modal(document.getElementById('loginPromptModal'));

    // Validar la URL de inicio de sesión y la URL de redirección
    const validatedLoginUrl = validateRedirectUrl(loginUrl);

    // Configurar la URL del botón de inicio de sesión con validación
    document.getElementById('loginButton').href = `${validatedLoginUrl}?redirect=${encodeURIComponent(currentUrl)}`;

    modal.show();
}
