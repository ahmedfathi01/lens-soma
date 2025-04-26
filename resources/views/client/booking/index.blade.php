<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="احجز جلسة تصوير مع عدسة سوما - باقات متنوعة لتصوير المواليد والأطفال والعائلات. خدمة احترافية وأسعار مناسبة في أبها، حي المحالة. احجز موعدك الآن!">
    <meta name="keywords" content="حجز تصوير، تصوير مواليد، تصوير أطفال، تصوير عائلي، استوديو تصوير، عدسة سوما، حجز موعد، أبها، محالة">
    <meta name="author" content="عدسة سوما">
    <meta name="robots" content="index, follow">
    <meta name="googlebot" content="index, follow">
    <meta name="theme-color" content="#ffffff">
    <meta name="msapplication-TileColor" content="#ffffff">

    <!-- Open Graph Meta Tags -->
    <meta property="og:site_name" content="عدسة سوما">
    <meta property="og:title" content="احجز جلسة تصوير مع عدسة سوما | تصوير مواليد وأطفال في أبها">
    <meta property="og:description" content="احجز جلسة تصوير مع عدسة سوما - باقات متنوعة لتصوير المواليد والأطفال والعائلات. خدمة احترافية وأسعار مناسبة في أبها، حي المحالة. احجز موعدك الآن!">
    <meta property="og:image" content="/assets/images/logo.png" loading="lazy">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:type" content="website">
    <meta property="og:locale" content="ar_SA">

    <!-- Twitter Card Meta Tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="احجز جلسة تصوير مع عدسة سوما | تصوير مواليد وأطفال في أبها">
    <meta name="twitter:description" content="احجز جلسة تصوير مع عدسة سوما - باقات متنوعة لتصوير المواليد والأطفال والعائلات. خدمة احترافية وأسعار مناسبة في أبها، حي المحالة. احجز موعدك الآن!">
    <meta name="twitter:image" content="{{ asset('assets/images/logo.png') }}">

    <!-- Canonical URL -->
    <link rel="canonical" href="{{ url()->current() }}">

    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>احجز جلسة تصوير مع عدسة سوما | تصوير مواليد وأطفال في أبها، حي المحالة</title>
    <!-- Bootstrap RTL CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="{{ asset('assets/css/studio-client/style.css') }}?t={{ time() }}">
    <link rel="stylesheet" href="{{ asset('assets/css/studio-client/booking.css') }}?t={{ time() }}">


    <!-- Tabby Scripts -->
    <script src="https://checkout.tabby.ai/tabby-promo.js"></script>
    <script>
        // تجهيز متغيرات للتابي
        window.packageData = {
            price: 0
        };
    </script>


</head>
<body>
    @include('parts.navbar')

    <div class="container py-4">
        <!-- Error and Success Messages -->
        @if(session('error') || session('success') || $errors->any())
        <div class="messages-container mb-4">
            @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            @endif

            @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            @endif

            @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <h6 class="alert-heading mb-2"><i class="fas fa-exclamation-triangle me-2"></i>يوجد أخطاء في النموذج:</h6>
                <ul class="mb-0 ps-3">
                    @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            @endif

            @if(session('retry_payment'))
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <i class="fas fa-info-circle me-2"></i>يمكنك متابعة عملية الحجز مع إعادة محاولة الدفع. تم الاحتفاظ ببياناتك السابقة.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            @endif

            <!-- رابط إعادة ضبط الصفحة -->
            <div class="alert alert-light border mt-2">
                <i class="fas fa-sync-alt me-2"></i>
                إذا واجهت أي مشكلة في عرض الصفحة،
                <a href="{{ route('client.bookings.create', ['reset_session' => 1]) }}" class="alert-link">اضغط هنا لإعادة تحميل الصفحة</a>
            </div>
        </div>
        @endif

        <!-- Gallery Carousel -->
        <div id="galleryCarousel" class="carousel slide gallery-carousel animate-fadeInUp" data-bs-ride="carousel">
            <div class="carousel-indicators">
                @foreach($galleryImages as $key => $image)
                <button type="button" data-bs-target="#galleryCarousel" data-bs-slide-to="{{ $key }}"
                        class="{{ $key === 0 ? 'active' : '' }}" aria-current="{{ $key === 0 ? 'true' : 'false' }}"
                        aria-label="Slide {{ $key + 1 }}"></button>
                @endforeach
            </div>

            <div class="carousel-inner">
                @foreach($galleryImages as $key => $image)
                <div class="carousel-item {{ $key === 0 ? 'active' : '' }}">
                    <img src="{{ url('storage/' . $image->image_url) }}"
                         class="d-block w-100"
                         alt="Gallery Image"
                         loading="{{ $key === 0 ? 'eager' : 'lazy' }}">
                </div>
                @endforeach
            </div>

            <button class="carousel-control-prev" type="button" data-bs-target="#galleryCarousel" data-bs-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="visually-hidden">السابق</span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#galleryCarousel" data-bs-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="visually-hidden">التالي</span>
            </button>
        </div>

        <!-- Authentication Notice -->
        @guest
        <div class="auth-notice animate-fadeInUp mb-4">
            <div class="alert alert-booking">
                <div class="alert-icon">
                    <i class="fas fa-user-lock"></i>
                </div>
                <div class="alert-content">
                    <h5>تنبيه هام</h5>
                    <p>قبل البدء في تعبئة نموذج الحجز، يجب عليك <a href="{{ route('login') }}">تسجيل الدخول</a> إلى حسابك أو <a href="{{ route('register') }}">إنشاء حساب جديد</a> إذا لم يكن لديك حساب مسبقاً.</p>
                </div>
            </div>
        </div>
        @endguest

        <!-- Booking Form -->
        <div class="booking-form animate-fadeInUp">
            <h2>حجز جلسة تصوير</h2>

            <form action="{{ route('client.bookings.store') }}" method="POST" id="checkout-form">
                @csrf
                <input type="hidden" name="intended_route" value="{{ url()->current() }}">

                <!-- Add hidden inputs for data needed by JS -->
                <input type="hidden" id="services-data" value="{{ json_encode($services) }}">
                <input type="hidden" id="packages-data" value="{{ json_encode($packages) }}">
                <input type="hidden" id="addons-data" value="{{ json_encode($addons) }}">
                <input type="hidden" id="bookings-data" value="{{ json_encode($currentBookings) }}">
                <input type="hidden" id="old-session-time" value="{{ old('session_time', '') }}">
                <input type="hidden" id="old-package-id" value="{{ old('package_id', 0) }}">

                <!-- Service Selection -->
                <div class="mb-4">
                    <label class="form-label">نوع الجلسة</label>
                    <select name="service_id" class="form-select" required>
                        <option value="">اختر نوع الجلسة</option>
                        @foreach($services as $service)
                            <option value="{{ $service->id }}" {{ old('service_id') == $service->id ? 'selected' : '' }}>{{ $service->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Package Selection -->
                <div class="mb-4">
                    <label class="form-label">الباقة</label>
                    <div class="row">
                        @foreach($packages as $package)
                            <div class="col-md-6">
                                <div class="package-card {{ old('package_id') == $package->id ? 'selected' : '' }}">
                                    <input type="radio" name="package_id" value="{{ $package->id }}"
                                           class="form-check-input package-select" required
                                           {{ old('package_id') == $package->id ? 'checked' : '' }}>
                                    <h5>{{ $package->name }}</h5>
                                    <p class="text-muted">{{ $package->description }}</p>
                                    <ul class="list-unstyled">
                                        <li><i class="fas fa-clock me-2"></i>المدة:
                                            @if($package->duration >= 60)
                                                {{ floor($package->duration / 60) }} ساعة
                                                @if($package->duration % 60 > 0)
                                                    و {{ $package->duration % 60 }} دقيقة
                                                @endif
                                            @else
                                                {{ $package->duration }} دقيقة
                                            @endif
                                        </li>
                                        <li><i class="fas fa-images me-2"></i>عدد الصور: {{ $package->num_photos }}</li>
                                        <li><i class="fas fa-palette me-2"></i>عدد الثيمات: {{ $package->themes_count }}</li>
                                        <li><i class="fas fa-tag me-2"></i>السعر: {{ $package->base_price }} ريال</li>
                                        @if(isset($package->best_coupon))
                                        <li class="coupon-info">
                                            <i class="fas fa-ticket-alt me-2 coupon-icon"></i>
                                            كوبون خصم:
                                            <span class="coupon-code">{{ $package->best_coupon->code }}</span>
                                            <span class="discount-value">({{ $package->discount_text }})</span>
                                        </li>
                                        @endif
                                    </ul>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Addons Selection -->
                <div class="mb-4" id="addons-section">
                    <label class="form-label">الإضافات المتاحة</label>
                    <div class="row">
                        @foreach($addons as $addon)
                            <div class="col-md-4 mb-3">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <div class="form-check">
                                            <input type="checkbox" name="addons[{{ $addon->id }}][id]"
                                                   value="{{ $addon->id }}"
                                                   class="form-check-input addon-checkbox"
                                                   id="addon-{{ $addon->id }}"
                                                   {{ old('addons.'.$addon->id.'.id') ? 'checked' : '' }}>
                                            <input type="hidden" name="addons[{{ $addon->id }}][quantity]" value="1">
                                            <label class="form-check-label" for="addon-{{ $addon->id }}">
                                                <h6>{{ $addon->name }}</h6>
                                                <p class="text-muted small mb-2">{{ $addon->description }}</p>
                                                <span class="badge bg-primary">{{ $addon->price }} ريال</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Date and Time -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <label class="form-label">تاريخ الجلسة</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-calendar"></i></span>
                            <input type="date" name="session_date" class="form-control" required
                                   min="{{ date('Y-m-d', strtotime('+1 day')) }}"
                                   max="{{ date('Y-m-d', strtotime('+30 days')) }}"
                                   value="{{ old('session_date') }}">
                        </div>
                        <small class="text-muted mt-1">
                            <i class="fas fa-info-circle"></i>
                            يمكنك اختيار موعد من الغد وحتى 30 يوم قادمة
                        </small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">وقت الجلسة</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-clock"></i></span>
                            <select name="session_time" class="form-select" required id="sessionTime" disabled>
                                <option value="">يرجى اختيار الباقة والتاريخ أولاً</option>
                            </select>
                        </div>
                        <small class="text-muted mt-1" id="timeNote">
                            <i class="fas fa-info-circle"></i>
                            سيتم عرض المواعيد المتاحة بعد اختيار الباقة والتاريخ
                        </small>
                    </div>
                </div>

                <!-- Baby Information -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <label class="form-label">اسم المولود</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-baby"></i></span>
                            <input type="text" name="baby_name" class="form-control" value="{{ old('baby_name') }}">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">تاريخ الميلاد</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-birthday-cake"></i></span>
                            <input type="date" name="baby_birth_date" class="form-control" value="{{ old('baby_birth_date') }}">
                        </div>
                    </div>
                </div>

                <!-- Gender -->
                <div class="mb-4">
                    <label class="form-label">الجنس</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-venus-mars"></i></span>
                        <select name="gender" class="form-select">
                            <option value="">اختر الجنس</option>
                            <option value="ذكر" {{ old('gender') == 'ذكر' ? 'selected' : '' }}>ذكر</option>
                            <option value="أنثى" {{ old('gender') == 'أنثى' ? 'selected' : '' }}>أنثى</option>
                        </select>
                    </div>
                </div>

                <!-- Notes -->
                <div class="mb-4">
                    <label class="form-label">ملاحظات إضافية</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-comment"></i></span>
                        <textarea name="notes" class="form-control" rows="3">{{ old('notes') }}</textarea>
                    </div>
                </div>

                <!-- Consent Checkboxes -->
                <div class="mb-4">
                    <div class="mb-3">
                        <label class="form-label">الموافقة على عرض الصور</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-camera"></i></span>
                            <select name="image_consent" class="form-select" id="imageConsent">
                                <option value="1" {{ old('image_consent') == '1' ? 'selected' : '' }}>نعم، أوافق على عرض الصور في معرض الاستوديو ومواقع التواصل الاجتماعي</option>
                                <option value="0" {{ old('image_consent') == '0' ? 'selected' : '' }}>لا، لا أوافق على عرض الصور</option>
                            </select>
                        </div>
                        <div class="mt-2">
                            <small class="text-success">
                                <i class="fas fa-gift me-1"></i>
                                في حالة الموافقة على عرض الصور، ستحصل على ثيم إضافي مجاناً كهدية شكر
                            </small>
                        </div>
                    </div>

                    <div class="form-check">
                        <input type="checkbox" name="terms_consent" class="form-check-input" id="termsConsent"
                               value="1" required {{ old('terms_consent') ? 'checked' : '' }}>
                        <label class="form-check-label" for="termsConsent">
                            أوافق على <a href="{{ route('policy') }}">الشروط والسياسات</a> الخاصة بالاستوديو <span class="text-danger">*</span>
                        </label>
                    </div>
                </div>

                <!-- تعليمات الحجز والدفع -->
                @auth
                <div class="mb-4 text-center">
                    <div class="row justify-content-center">
                        <div class="col-md-8">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6 class="mb-3">تعليمات الحجز والدفع:</h6>
                                    <ul class="list-unstyled text-start mb-4">
                                        <li class="mb-2">
                                            <i class="fas fa-check-circle text-success me-2"></i>
                                            سيتم تحويلك إلى بوابة الدفع الإلكتروني بعد تأكيد الحجز
                                        </li>
                                        <li class="mb-2">
                                            <i class="fas fa-credit-card text-success me-2"></i>
                                            يمكنك الدفع باستخدام بطاقة مدى أو فيزا أو ماستركارد
                                        </li>
                                        <li class="mb-2">
                                            <i class="fas fa-lock text-success me-2"></i>
                                            جميع عمليات الدفع آمنة ومشفرة بواسطة PayTabs
                                        </li>
                                        <li class="mb-2">
                                            <i class="fas fa-check-circle text-success me-2"></i>
                                            سيتم تأكيد الحجز تلقائيًا بعد نجاح عملية الدفع
                                        </li>
                                        <li>
                                            <i class="fas fa-check-circle text-success me-2"></i>
                                            يمكنك متابعة حالة حجزك من صفحة حجوزاتي
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endauth

                <!-- Coupon Code Section -->
                <div class="coupon-container">
                    <div class="coupon-header">
                        <div class="coupon-icon">
                            <i class="fas fa-ticket-alt"></i>
                        </div>
                        <h5>كود الخصم</h5>
                    </div>
                    <div class="coupon-body">
                        <div class="coupon-input-wrapper">
                            <div class="coupon-input-container">
                                <i class="fas fa-tag input-icon"></i>
                                <input type="text" name="coupon_code" id="coupon_code" class="coupon-input" placeholder="أدخل كود الخصم إذا كان متوفراً لديك">
                                <button type="button" id="check-coupon" class="verify-btn">
                                    <span class="verify-text">تحقق</span>
                                    <i class="fas fa-check"></i>
                                </button>
                            </div>
                            <div id="coupon-message" class="coupon-message"></div>
                        </div>
                        <div id="coupon-details" class="coupon-details d-none">
                            <div class="applied-coupon">
                                <div class="coupon-badge">
                                    <i class="fas fa-check-circle"></i>
                                    <span id="coupon-code-display"></span>
                                </div>
                                <div id="coupon-discount-display" class="discount-text"></div>
                                <button type="button" id="remove-coupon" class="remove-coupon-btn">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Price Breakdown Section -->
                <div class="price-breakdown-container" id="price-breakdown-section" style="display:none;">
                    <div class="price-breakdown-header">
                        <div class="price-icon-wrapper">
                            <i class="fas fa-tags"></i>
                        </div>
                        <h5>تفاصيل السعر</h5>
                    </div>

                    <div class="price-breakdown-body">
                        <div class="price-row">
                            <div class="price-label">السعر الأصلي</div>
                            <div class="price-value" id="original-price-display">0 ريال</div>
                        </div>

                        <div class="price-row discount-row">
                            <div class="price-label">
                                <i class="fas fa-percentage pulse-icon"></i>
                                قيمة الخصم
                            </div>
                            <div class="price-value discount-value" id="discount-amount-display">0 ريال</div>
                        </div>

                        <div class="price-row total-row">
                            <div class="price-label">السعر النهائي بعد الخصم</div>
                            <div class="price-value final-price" id="final-price-display">0 ريال</div>
                        </div>

                        <div class="total-savings">
                            <div class="savings-badge">
                                <i class="fas fa-coins me-2"></i>
                                <span>وفرت</span>
                                <span id="savings-percentage">0%</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payment Method Section -->
                @auth
                <div class="payment-methods-section mb-4">
                    <h4 class="mb-3">اختر طريقة الدفع</h4>

                    <!-- إضافة تنبيه توضيحي عن وضع الاختبار لتابي -->
                    <div class="alert alert-info mb-4">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>ملاحظة هامة:</strong> حالياً، خدمة الدفع عبر تابي تعمل في وضع الاختبار فقط ولن يتم خصم أي مبالغ من بطاقتك.
                    </div>

                    <div class="payment-methods-container">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="payment-method-card">
                                    <input type="radio" name="payment_method" id="payment_tabby" value="tabby" checked>
                                    <label for="payment_tabby" class="payment-method-label tabby-shimmer">
                                        <div class="payment-icon">
                                            <img src="https://th.bing.com/th/id/OIP.MYBQ1iOEIlhyysL0Y3eh4wHaFG?rs=1&pid=ImgDetMain" alt="Tabby" style="height: 30px;">
                                        </div>
                                        <div class="payment-details">
                                            <h5>التقسيط بدون فوائد</h5>
                                            <p>قسّم على 4 دفعات شهرية بدون فوائد مع تابي</p>
                                        </div>
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="payment-method-card">
                                    <input type="radio" name="payment_method" id="payment_cod" value="cod">
                                    <label for="payment_cod" class="payment-method-label">
                                        <div class="payment-icon">
                                            <i class="fas fa-money-bill-wave"></i>
                                        </div>
                                        <div class="payment-details">
                                            <h5>الدفع عند الاستلام</h5>
                                            <p>ادفع نقداً بعد حضور الجلسة</p>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tabby Container -->
                    <div id="tabby-container" style="display: none;">
                        <div class="tabby-promo">
                            <img src="https://th.bing.com/th/id/OIP.MYBQ1iOEIlhyysL0Y3eh4wHaFG?rs=1&pid=ImgDetMain" alt="Tabby" class="tabby-logo">
                            <h4>تقسيط بدون فوائد أو رسوم إضافية</h4>
                        </div>
                        <div class="tabby-info">
                            <p>مع تابي، يمكنك دفع ربع المبلغ الآن والباقي على 3 أشهر بدون فوائد أو رسوم إضافية.
                            كل ما تحتاجه هو بطاقة مدى أو بطاقة ائتمان (فيزا/ماستركارد) سعودية.</p>
                            <p>سيتم تحويلك إلى موقع تابي لإتمام عملية التقسيط بعد تأكيد الحجز.</p>
                        </div>

                        <!-- Tabby Product Widget - للمنتج الحالي -->
                        <div id="tabby-product-widget"></div>

                        <div class="tabby-disclaimer">
                            <p class="small text-muted">يرجى التأكد من إدخال بيانات دقيقة وصحيحة لإتمام عملية الدفع بنجاح.</p>
                        </div>
                        <figure class="tabby-example">
                            <img src="https://mintlify.s3.us-west-1.amazonaws.com/tabby-5f40add6/images/tabby-payment-method.png" alt="شاشة تابي" />
                            <figcaption class="small">شكل شاشة تابي عند الدفع</figcaption>
                        </figure>
                    </div>
                </div>
                @endauth

                <!-- Submit Button -->
                <div class="text-center">
                    @auth
                        <button type="submit" class="btn btn-primary btn-lg" id="submitBtn">
                            <i class="fas fa-credit-card me-2"></i>متابعة للدفع
                        </button>
                        <div class="mt-3">
                            <small class="text-muted">
                                سيتم تحويلك إلى صفحة الدفع الآمنة بعد تأكيد الحجز
                            </small>
                        </div>
                    @else
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            يرجى <a href="{{ route('login') }}">تسجيل الدخول</a> أو <a href="{{ route('register') }}">إنشاء حساب جديد</a> للمتابعة
                        </div>
                    @endauth
                </div>

            </form>
        </div>
    </div>

    <!-- Terms Modal -->
    <div class="modal fade" id="termsModal" tabindex="-1" aria-labelledby="termsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="termsModalLabel">الشروط والسياسات</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <h6>سياسة عرض الصور:</h6>
                    <ul>
                        <li>يحتفظ الاستوديو بحق عرض الصور المختارة في معرض الصور الخاص به.</li>
                        <li>سيتم استخدام الصور في وسائل التواصل الاجتماعي والمواد التسويقية للاستوديو.</li>
                        <li>نحن نحترم خصوصيتكم ولن نستخدم الصور بطريقة غير لائقة.</li>
                    </ul>

                    <h6 class="mt-4">الشروط العامة:</h6>
                    <ul>
                        <li>يجب الحضور في الموعد المحدد بدقة.</li>
                        <li>في حالة الرغبة في إلغاء الحجز، يجب إخطارنا قبل 24 ساعة على الأقل.</li>
                        <li>سيتم تسليم الصور النهائية خلال أسبوع من تاريخ الجلسة.</li>
                        <li>يتم دفع 50% من قيمة الحجز مقدماً لتأكيد الموعد.</li>
                    </ul>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Bootstrap and Custom JavaScript files -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('assets/js/client/booking-core.js') }}?t={{ time() }}"></script>
    <script src="{{ asset('assets/js/client/booking-coupon.js') }}?t={{ time() }}"></script>
    <script src="{{ asset('assets/js/client/booking-payment.js') }}?t={{ time() }}"></script>
</body>
</html>
