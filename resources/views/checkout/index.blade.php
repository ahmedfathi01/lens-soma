<!DOCTYPE html>
<html lang="en" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('Checkout') }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="{{ url('assets/css/customer/checkout.css') }}">

    <!-- Tabby Scripts -->
    <script src="https://checkout-web-components.checkout.com/index.js"></script>
    <script src="https://checkout.tabby.ai/tabby-promo.js"></script>

    <!-- تجهيز بيانات الكوبون والسلة -->
    <script>
        // تجهيز متغيرات السلة والكوبون لتكون متاحة للملف الخارجي
        window.cartData = {
            subtotal: {{ $cart->total_amount }},
            totalAmount: {{ $cart->total_amount }}
        };

        @if(session('coupon'))
        window.sessionCoupon = {
            coupon: {
                code: "{{ session('coupon')->code }}",
                type: "{{ session('coupon')->discount_type }}",
                value: "{{ session('coupon')->discount_value }}"
            },
            discount_amount: {{ session('coupon')->calculateDiscount($cart->total_amount) }},
            message: "تم تطبيق كود الخصم",
            valid: true
        };
        @else
        window.sessionCoupon = null;
        @endif
    </script>

    <!-- JavaScript للتعامل مع الدفع والكوبونات -->
    <script src="{{ asset('assets/js/customer/checkout.js') }}?t={{ time() }}"></script>

    <style>
        /* Coupon Section Styles */
        .coupon-section {
            margin-top: 20px;
            margin-bottom: 20px;
            border: 1px dashed #ddd;
            border-radius: 8px;
            padding: 15px;
            background-color: #f9f9f9;
        }

        .coupon-form {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .coupon-input {
            flex-grow: 1;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-family: 'Tajawal', sans-serif;
            font-size: 14px;
        }

        .apply-coupon-btn {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
            transition: background-color 0.3s;
            font-family: 'Tajawal', sans-serif;
        }

        .apply-coupon-btn:hover {
            background-color: #45a049;
        }

        .coupon-error {
            color: #d9534f;
            margin-top: 8px;
            font-size: 14px;
            display: none;
        }

        .coupon-success {
            color: #4CAF50;
            margin-top: 8px;
            font-size: 14px;
            display: none;
        }

        .applied-coupon {
            margin-top: 10px;
            padding: 10px;
            background-color: #e8f5e9;
            border-radius: 5px;
            border-right: 3px solid #4CAF50;
            display: none;
        }

        .applied-coupon .coupon-details {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .applied-coupon .coupon-code {
            font-weight: bold;
            color: #4CAF50;
        }

        .applied-coupon .coupon-discount {
            font-weight: bold;
        }

        .applied-coupon .remove-coupon {
            color: #d9534f;
            cursor: pointer;
            margin-right: 10px;
            font-size: 14px;
        }

        .price-breakdown {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px dashed #ddd;
        }

        .price-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }

        .price-row.total {
            font-weight: bold;
            font-size: 16px;
            margin-top: 8px;
            padding-top: 8px;
            border-top: 1px solid #ddd;
        }

        .discount-value {
            color: #d9534f;
        }

        .loading .apply-coupon-btn {
            opacity: 0.7;
            pointer-events: none;
        }

        .loading .apply-coupon-btn::after {
            content: "...";
            display: inline-block;
            animation: loading-dots 1s infinite;
        }

        @keyframes loading-dots {
            0%, 20% { content: "."; }
            40% { content: ".."; }
            60%, 100% { content: "..."; }
        }
    </style>
</head>
<body class="checkout-container">
    <!-- Header -->
    <header class="checkout-header">
        <div class="container">
            <div class="header-content">
                <h2>{{ __('إتمام الطلب') }}</h2>
                <a href="{{ route('cart.index') }}" class="back-to-cart-btn">
                    العودة إلى السلة
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <div class="checkout-content">
        <div class="container">
            <div class="checkout-wrapper">
                <!-- Tabby Top Banner -->


                <form action="{{ route('checkout.store') }}" method="POST" id="checkout-form">
                    @csrf

                    @if ($errors->any())
                    <div class="error-container">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li class="error-message">{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                    @endif

                    <div class="checkout-grid">
                        <!-- Bank Information -->
                        <div class="bank-info-section" id="bank-info-section">
                            <div class="bank-info-header">
                                <i class="fas fa-info-circle"></i>
                                <h3>معلومات الدفع</h3>
                            </div>
                            <div class="bank-info-content">
                                <div class="bank-logo">
                                    <i class="fas fa-university"></i>
                                    <h4>البنك الأهلي السعودي</h4>
                                </div>
                                <div class="account-details">
                                    <div class="detail-item">
                                        <span class="detail-label">رقم الحساب</span>
                                        <span class="detail-value">18900000406701</span>
                                        <button class="copy-btn" data-clipboard="18900000406701">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">الآيبان</span>
                                        <span class="detail-value">SA8710000018900000406701</span>
                                        <button class="copy-btn" data-clipboard="SA8710000018900000406701">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">السويفت</span>
                                        <span class="detail-value">NCBKSAJE</span>
                                        <button class="copy-btn" data-clipboard="NCBKSAJE">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">رقم الواتساب للتواصل</span>
                                        <span class="detail-value">+966561667885</span>
                                        <button class="copy-btn" data-clipboard="+966561667885">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="payment-notice">
                                    <p class="important-note">ملاحظة هامة: يجب دفع المبلغ كاملاً لتأكيد الطلب</p>
                                </div>
                                <div class="payment-steps">
                                    <div class="step">
                                        <span class="step-number">1</span>
                                        <span class="step-text">حول المبلغ للحساب</span>
                                    </div>
                                    <div class="step">
                                        <span class="step-number">2</span>
                                        <span class="step-text">أرسل صورة الإيصال عبر الواتساب</span>
                                    </div>
                                    <div class="step">
                                        <span class="step-number">3</span>
                                        <span class="step-text">انتظر تأكيد الطلب</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Order Summary -->
                        <div class="order-summary">
                            <h3>ملخص الطلب</h3>
                            <div class="order-items">
                                @if(Auth::check() && isset($cart))
                                    @foreach($cart->items as $item)
                                    <div class="order-item">
                                        <div class="product-info">
                                            <div class="product-image">
                                                <x-product-image :product="$item->product" size="16" />
                                            </div>
                                            <div class="product-details">
                                                <h4>{{ $item->product->name }}</h4>
                                                <p>الكمية: {{ $item->quantity }}</p>
                                            </div>
                                        </div>
                                        <p class="item-price">{{ $item->unit_price }} ريال × {{ $item->quantity }}</p>
                                        <p class="item-subtotal">الإجمالي: {{ $item->subtotal }} ريال</p>
                                    </div>
                                    @endforeach
                                @else
                                    @foreach($products as $product)
                                    <div class="order-item">
                                        <div class="product-info">
                                            <div class="product-image">
                                                @if($product->primary_image)
                                                    <img src="{{ url('storage/' . $product->primary_image->image_path) }}"
                                                        alt="{{ $product->name }}">
                                                @else
                                                    <div class="placeholder-image">
                                                        <svg viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                        </svg>
                                                    </div>
                                                @endif
                                            </div>
                                            <div class="product-details">
                                                <h4>{{ $product->name }}</h4>
                                                <p>الكمية: {{ $sessionCart[$product->id] }}</p>
                                            </div>
                                        </div>
                                        <p class="item-price">{{ $product->price }} ريال × {{ $sessionCart[$product->id] }}</p>
                                        <p class="item-subtotal">الإجمالي: {{ $product->price * $sessionCart[$product->id] }} ريال</p>
                                    </div>
                                    @endforeach
                                @endif

                                <div class="d-flex justify-content-between">
                                    <h4>الإجمالي الكلي:</h4>
                                    <span class="total-amount">{{ $cart->total_amount }} ريال</span>
                                </div>

                                <!-- Coupon Section -->
                                <div class="coupon-section">
                                    <h4>هل لديك كوبون خصم؟</h4>
                                    <div class="coupon-form" id="coupon-form">
                                        <input type="text" name="coupon_code" id="coupon-input" class="coupon-input" placeholder="أدخل كود الخصم" value="{{ session('coupon_code') }}">
                                        <button type="button" class="apply-coupon-btn" id="apply-coupon-btn">تطبيق</button>
                                    </div>
                                    <p class="coupon-error" id="coupon-error">كود الخصم غير صالح، الرجاء التأكد من الكود وإعادة المحاولة.</p>
                                    <p class="coupon-success" id="coupon-success"></p>

                                    <div class="coupon-not-applicable" id="coupon-not-applicable" @if(session('coupon_error') === 'هذا الكوبون لا ينطبق على المنتجات الموجودة في السلة') style="display: flex;" @else style="display: none;" @endif>
                                        <div class="coupon-not-applicable-icon">
                                            <i class="fas fa-exclamation-circle"></i>
                                        </div>
                                        <div class="coupon-not-applicable-text">
                                            هذا الكوبون لا ينطبق على المنتجات الموجودة في السلة
                                        </div>
                                        <div class="coupon-not-applicable-close" id="close-not-applicable">
                                            <i class="fas fa-times"></i>
                                        </div>
                                    </div>

                                    <div class="applied-coupon" id="applied-coupon">
                                        <div class="coupon-details">
                                            <div>
                                                <span>الكوبون المطبق:</span>
                                                <span class="coupon-code" id="applied-coupon-code"></span>
                                            </div>
                                            <div>
                                                <span>قيمة الخصم:</span>
                                                <span class="coupon-discount" id="coupon-discount-value"></span>
                                            </div>
                                            <div>
                                                <span class="remove-coupon" id="remove-coupon">إلغاء الكوبون <i class="fas fa-times"></i></span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="price-breakdown" id="price-breakdown">
                                        <div class="price-row">
                                            <span>المجموع الفرعي:</span>
                                            <span id="subtotal-value">{{ number_format($cart->total_amount, 2) }} ريال</span>
                                        </div>
                                        <div class="price-row" id="discount-row" style="display: none;">
                                            <span>الخصم:</span>
                                            <span class="discount-value" id="discount-amount">- 0 ريال</span>
                                        </div>
                                        <div class="price-row total">
                                            <span>الإجمالي النهائي:</span>
                                            <span id="final-price">{{ number_format($cart->total_amount, 2) }} ريال</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- معلومات التقسيط البارزة -->
                                <div class="order-summary-installment-notice">
                                    <i class="fas fa-tags"></i>
                                    <p>قسّمها على 4. بدون أي فوائد، أو رسوم. <strong>{{ number_format($cart->total_amount / 4, 2) }} ريال</strong> فقط كل شهر مع <strong>تابي</strong></p>
                                </div>

                                <!-- Tabby Widget - بيان التقسيط -->
                                <div class="tabby-widget-container">
                                    <div id="tabby-promotional-widget"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Shipping Information -->
                        <div class="shipping-info">
                            <h3>معلومات الشحن</h3>
                            <div class="form-groups">
                                <div class="form-group">
                                    <label for="shipping_address" class="form-label">
                                        عنوان الشحن
                                    </label>
                                    <textarea name="shipping_address" id="shipping_address" rows="4"
                                        class="form-input"
                                        placeholder="أدخل عنوان الشحن الكامل"
                                        required>{{ old('shipping_address', Auth::user()->address ?? '') }}</textarea>
                                    @error('shipping_address')
                                    <p class="error-message">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="phone" class="form-label">
                                        رقم الهاتف
                                    </label>
                                    <input type="tel" name="phone" id="phone"
                                        value="{{ old('phone', Auth::user()->phone ?? '') }}"
                                        class="form-input"
                                        placeholder="05xxxxxxxx"
                                        required>
                                    @error('phone')
                                    <p class="error-message">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Payment Method -->
                                <div class="form-group">
                                    <label class="form-label">
                                        طريقة الدفع
                                    </label>
                                    <div class="payment-methods">
                                        <div class="payment-method-option">
                                            <input type="radio" name="payment_method" id="payment_cash" value="cash"
                                                {{ old('payment_method') == 'cash' ? 'checked' : '' }} checked>
                                            <label for="payment_cash" class="payment-method-label">
                                                <span class="payment-icon"><i class="fas fa-money-bill-wave"></i></span>
                                                <span class="payment-label">الدفع عند الاستلام</span>
                                            </label>
                                        </div>
                                        <div class="payment-method-option">
                                            <input type="radio" name="payment_method" id="payment_tabby" value="tabby"
                                                {{ old('payment_method') == 'tabby' ? 'checked' : '' }}>
                                            <label for="payment_tabby" class="payment-method-label tabby-shimmer">
                                                <span class="payment-icon"><i class="fas fa-shopping-bag"></i></span>
                                                <span class="payment-label">
                                                    <span class="new-badge">تجريبي!</span>
                                                    قسّمها على 4. بدون أي فوائد، أو رسوم.
                                                </span>
                                                <div class="payment-cards">
                                                    <img src="https://th.bing.com/th/id/OIP.MYBQ1iOEIlhyysL0Y3eh4wHaFG?rs=1&pid=ImgDetMain" alt="Tabby" style="height: 30px;">
                                                </div>
                                            </label>
                                        </div>
                                        <div class="payment-method-option">
                                            <input type="radio" name="payment_method" id="payment_paytabs" value="paytabs"
                                                {{ old('payment_method') == 'paytabs' ? 'checked' : '' }}>
                                            <label for="payment_paytabs" class="payment-method-label">
                                                <span class="payment-icon"><i class="fas fa-credit-card"></i></span>
                                                <span class="payment-label">
                                                    الدفع الإلكتروني
                                                </span>
                                                <div class="payment-cards">
                                                    <img src="https://cdn.nooncdn.com/s/app/com/noon/design-system/payment-methods-v2/cards/visa.svg" alt="Visa" style="height: 24px; margin-right: 5px;">
                                                    <img src="https://cdn.nooncdn.com/s/app/com/noon/design-system/payment-methods-v2/cards/mastercard.svg" alt="MasterCard" style="height: 24px; margin-right: 5px;">
                                                    <img src="https://cdn.nooncdn.com/s/app/com/noon/design-system/payment-methods-v2/cards/mada.svg" alt="Mada" style="height: 24px;">
                                                </div>
                                            </label>
                                        </div>
                                    </div>

                                    <!-- Tabby Container -->
                                    <div id="tabby-container">
                                        <div class="tabby-promo">
                                            <img src="https://th.bing.com/th/id/OIP.MYBQ1iOEIlhyysL0Y3eh4wHaFG?rs=1&pid=ImgDetMain" alt="Tabby" class="tabby-logo">
                                            <p>قسّمها على 4. بدون أي فوائد، أو رسوم.</p>
                                        </div>
                                        <div class="tabby-info">
                                            <h4>كيف يعمل Pay in 4:</h4>
                                            <ul>
                                                <li>ادفع ربع المبلغ الآن ({{ number_format($cart->total_amount / 4, 2) }} ريال)</li>
                                                <li>ادفع الباقي على 3 أقساط شهرية ({{ number_format($cart->total_amount / 4, 2) }} ريال كل شهر)</li>
                                                <li>لا توجد فوائد أو رسوم إضافية</li>
                                                <li>لا تحتاج إلى بطاقة ائتمان</li>
                                            </ul>
                                            <p>سيتم تحويلك إلى موقع تابي لإتمام عملية الدفع بأمان</p>
                                        </div>

                                        <!-- Tabby Product Widget - للمنتج الحالي -->
                                        <div id="tabby-product-widget"></div>

                                        <div class="tabby-disclaimer">
                                            <strong>ملاحظة هامة:</strong> خدمة التقسيط حالياً في المرحلة التجريبية ولن يتم سحب أي مبالغ من حسابك. إذا كنت ترغب في استلام المنتج بشكل مؤكد، نرجو اختيار الدفع عند الاستلام.
                                        </div>

                                        <figure class="tabby-example">
                                            <img src="https://mintlify.s3.us-west-1.amazonaws.com/tabby-5f40add6/images/tabby-payment-method.png" alt="شاشة تابي" />
                                            <figcaption>صورة توضيحية لشاشة الدفع عبر تابي - ستجد خطوات سهلة ومباشرة في عملية الدفع</figcaption>
                                        </figure>
                                    </div>

                                    @error('payment_method')
                                    <p class="error-message">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="notes" class="form-label">
                                        ملاحظات الطلب (اختياري)
                                    </label>
                                    <textarea name="notes" id="notes" rows="4"
                                        class="form-input"
                                        placeholder="أي ملاحظات إضافية للطلب">{{ old('notes') }}</textarea>
                                    @error('notes')
                                    <p class="error-message">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- إضافة حقل الموافقة على السياسة -->
                                <div class="form-group">
                                    <div class="policy-agreement">
                                        <input type="checkbox"
                                               name="policy_agreement"
                                               id="policy_agreement"
                                               class="form-checkbox"
                                               {{ old('policy_agreement') ? 'checked' : '' }}
                                               required>
                                        <label for="policy_agreement" class="form-label">
                                            أوافق على <a href="{{ route('policy') }}" target="_blank">سياسة الشركة وشروط الخدمة</a>
                                        </label>
                                    </div>
                                    @error('policy_agreement')
                                    <p class="error-message">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Hidden Appointment ID field -->
                    <input type="hidden" name="appointment_id" value="{{ session('appointment_id') }}">

                    <!-- Submit button section -->
                    <div class="checkout-actions">
                        <button type="submit" class="place-order-btn" id="submitBtn">
                            تأكيد الطلب
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
