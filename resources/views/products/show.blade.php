<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $product->name }} - lens-soma</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('assets/css/customer/products-show.css') }}?t={{ time() }}">
    <link rel="stylesheet" href="{{ asset('assets/css/customer/products.css') }}?t={{ time() }}">
    <link rel="stylesheet" href="{{ asset('assets/css/customer/quantity-pricing.css') }}?t={{ time() }}">


</head>
<body class="{{ auth()->check() ? 'user-logged-in' : '' }}">
    <!-- Cart overlay for the sidebar background effect -->


    <!-- Fixed Buttons Group -->
    <div class="fixed-buttons-group">
        <button class="fixed-cart-btn" id="fixedCartBtn">
            <i class="fas fa-shopping-cart fa-lg"></i>
            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger cart-count">
                0
            </span>
        </button>
        @auth
        <a href="/dashboard" class="fixed-dashboard-btn">
            <i class="fas fa-tachometer-alt"></i>
            Dashboard
        </a>
        @endauth
    </div>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg modern-navbar sticky-top">
      <div class="container">
            <a class="navbar-brand" href="/">
               <img src="{{ asset('assets/images/logo.png') }}" alt="Madil" height="70">
            </a>
          <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
              <span class="navbar-toggler-icon"></span>
          </button>
          <div class="collapse navbar-collapse" id="navbarNav">
              <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                  <!-- الرئيسية Dropdown -->
                  <li class="nav-item dropdown">
                      <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="homeDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                          <span class="nav-icon"><i class="fas fa-home"></i></span>
                          <span class="nav-text">الرئيسية</span>
                          <i class="fas fa-angle-down dropdown-indicator"></i>
                      </a>
                      <ul class="dropdown-menu animated-dropdown" aria-labelledby="homeDropdown">
                          <li><a class="dropdown-item" href="/"><i class="fas fa-home me-2"></i> الرئيسية</a></li>
                          <li><a class="dropdown-item" href="/about"><i class="fas fa-info-circle me-2"></i> من نحن</a></li>
                          <li><a class="dropdown-item" href="/gallery"><i class="fas fa-images me-2"></i> معرض الصور</a></li>
                          <li><a class="dropdown-item" href="/services"><i class="fas fa-concierge-bell me-2"></i> الخدمات</a></li>
                      </ul>
                  </li>
                  <!-- المتجر -->
                  <li class="nav-item">
                      <a class="nav-link active d-flex align-items-center" href="/products">
                          <span class="nav-icon"><i class="fas fa-shopping-bag"></i></span>
                          <span class="nav-text">المتجر</span>
                      </a>
                  </li>
                  <!-- الحجز Dropdown -->
                  <li class="nav-item dropdown">
                      <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="bookingDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                          <span class="nav-icon"><i class="fas fa-calendar"></i></span>
                          <span class="nav-text">الحجز</span>
                          <i class="fas fa-angle-down dropdown-indicator"></i>
                      </a>
                      <ul class="dropdown-menu animated-dropdown" aria-labelledby="bookingDropdown">
                          <li><a class="dropdown-item" href="/client/book"><i class="fas fa-calendar-plus me-2"></i> حجز جديد</a></li>
                          @auth
                          <li><a class="dropdown-item" href="/client/bookings/my"><i class="fas fa-calendar-check me-2"></i> حجوزاتي</a></li>
                          @endauth
                      </ul>
                  </li>
              </ul>
              <div class="nav-buttons d-flex align-items-center">
                    <button class="cart-button" id="cartToggle">
                      <span class="cart-icon-wrapper">
                        <i class="fas fa-shopping-cart"></i>
                        <span class="cart-count-badge cart-count">0</span>
                      </span>
                    </button>
                    <div class="dropdown profile-dropdown">
                        <button class="profile-button dropdown-toggle" type="button" id="profileDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="profile-icon-wrapper">
                                <i class="fas fa-user"></i>
                            </span>
                        </button>
                        <ul class="dropdown-menu profile-dropdown-menu" aria-labelledby="profileDropdown">
                            @auth
                                <div class="dropdown-user-info">
                                    <div class="dropdown-user-name">{{ Auth::user()->name }}</div>
                                    <div class="dropdown-user-email">{{ Auth::user()->email }}</div>
                                </div>
                                <div class="dropdown-divider"></div>
                                <li><a class="dropdown-item" href="/dashboard"><i class="fas fa-tachometer-alt me-2"></i> لوحة التحكم</a></li>
                                <div class="dropdown-divider"></div>
                                <li>
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit" class="dropdown-item logout-item"><i class="fas fa-sign-out-alt me-2"></i> تسجيل الخروج</button>
                                    </form>
                                </li>
                            @else
                                <div class="dropdown-header">
                                    <i class="fas fa-user-circle auth-icon"></i>
                                    <div>تسجيل الدخول للوصول إلى حسابك</div>
                                </div>
                                <div class="dropdown-divider"></div>
                                <li><a class="dropdown-item login-item" href="/login"><i class="fas fa-sign-in-alt me-2"></i> تسجيل الدخول</a></li>
                                <li><a class="dropdown-item register-item" href="/register"><i class="fas fa-user-plus me-2"></i> إنشاء حساب</a></li>
                            @endauth
                        </ul>
                    </div>
              </div>
          </div>
      </div>
    </nav>
    <!-- Add this after navbar -->
    <div class="cart-sidebar" id="cartSidebar">
        <div class="cart-header">
            <h3>سلة التسوق</h3>
            <button class="close-cart" id="closeCart">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="cart-items" id="cartItems">
            <!-- Cart items will be dynamically added here -->
        </div>
        <div class="cart-footer">
            <div class="cart-total">
                <span>الإجمالي:</span>
                <span id="cartTotal">0 ر.س</span>
            </div>
            <a href="{{ route('checkout.index') }}" class="checkout-btn">
                <i class="fas fa-shopping-cart ml-2"></i>
                إتمام الشراء
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container py-5">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="/">الرئيسية</a></li>
                <li class="breadcrumb-item"><a href="/products">المنتجات</a></li>
                <li class="breadcrumb-item"><a href="/products?category={{ $product->category->slug }}">{{ $product->category->name }}</a></li>
                <li class="breadcrumb-item active">{{ $product->name }}</li>
            </ol>
        </nav>

        <div class="row g-5">
            <!-- Product Images -->
            <div class="col-md-6">
                <div class="product-gallery card">
                    <div class="card-body">
                        @if($product->images->count() > 0)
                            <div class="main-image-wrapper mb-3">
                                @if(isset($product->best_coupon))
                                <div class="coupon-badge copy-coupon" data-coupon-code="{{ $product->best_coupon['code'] }}" title="انقر لنسخ الكوبون">
                                    <i class="fas fa-ticket-alt"></i>
                                    <span class="coupon-code">{{ $product->best_coupon['code'] }}</span>
                                    <span class="coupon-value">
                                        @if($product->best_coupon['type'] === 'percentage')
                                            {{ $product->best_coupon['value'] }}%
                                        @else
                                            {{ $product->best_coupon['value'] }} ر.س
                                        @endif
                                    </span>
                                </div>
                                @endif
                                <img src="{{ url('storage/' . $product->primary_image->image_path) }}"
                                    alt="{{ $product->name }}"
                                    class="main-product-image"
                                    id="mainImage">
                            </div>
                            @if($product->images->count() > 1)
                                <div class="image-thumbnails">
                                    @foreach($product->images as $image)
                                        <div class="thumbnail-wrapper {{ $image->is_primary ? 'active' : '' }}"
                                            onclick="updateMainImage('{{ url('storage/' . $image->image_path) }}', this)">
                                            <img src="{{ url('storage/' . $image->image_path) }}"
                                                alt="Product thumbnail"
                                                class="thumbnail-image">
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        @else
                            <div class="no-image-placeholder">
                                <i class="fas fa-image"></i>
                                <p>لا توجد صور متاحة</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Product Details -->
            <div class="col-md-6">
                <div class="product-info">
                    <h1 class="product-title">{{ $product->name }}</h1>

                    <div class="category-badge mb-3">
                        <i class="fas fa-tag me-1"></i>
                        {{ $product->category->name }}
                    </div>

                    <!-- Product Price -->
                    <div class="price-container">
                        <div class="product-price">
                            @if($product->min_price == $product->max_price)
                                <span class="amount">{{ number_format($product->min_price, 2) }}</span>
                                <span class="currency">ر.س</span>
                                @if(isset($product->best_coupon))
                                    <div class="coupon-label copy-coupon" data-coupon-code="{{ $product->best_coupon['code'] }}" title="انقر لنسخ الكوبون">
                                        <i class="fas fa-ticket-alt"></i>
                                        <span class="coupon-code">{{ $product->best_coupon['code'] }}</span>
                                        <span class="coupon-value">
                                            @if($product->best_coupon['type'] === 'percentage')
                                                {{ $product->best_coupon['value'] }}%
                                            @else
                                                {{ $product->best_coupon['value'] }} ر.س
                                            @endif
                                        </span>
                                    </div>
                                @endif
                            @else
                                <span class="amount">{{ number_format($product->min_price, 2) }} - {{ number_format($product->max_price, 2) }}</span>
                                <span class="currency">ر.س</span>
                                @if(isset($product->best_coupon))
                                    <div class="coupon-label copy-coupon" data-coupon-code="{{ $product->best_coupon['code'] }}" title="انقر لنسخ الكوبون">
                                        <i class="fas fa-ticket-alt"></i>
                                        <span class="coupon-code">{{ $product->best_coupon['code'] }}</span>
                                        <span class="coupon-value">
                                            @if($product->best_coupon['type'] === 'percentage')
                                                {{ $product->best_coupon['value'] }}%
                                            @else
                                                {{ $product->best_coupon['value'] }} ر.س
                                            @endif
                                        </span>
                                    </div>
                                @endif
                            @endif
                        </div>
                    </div>

                    <!-- كوبونات الخصم المتاحة -->
                    @if(isset($product->all_coupons) && count($product->all_coupons) > 0)
                    <div class="available-coupons mb-4">
                        <h5 class="section-title">
                            <i class="fas fa-ticket-alt me-2"></i>
                            كوبونات الخصم المتاحة ({{ count($product->all_coupons) }})
                        </h5>
                        <div class="coupon-cards">
                            @foreach($product->all_coupons as $coupon)
                            <div class="coupon-card {{ isset($product->best_coupon) && $coupon['id'] == $product->best_coupon['id'] ? 'best-coupon' : '' }} copy-coupon" data-coupon-code="{{ $coupon['code'] }}" title="انقر لنسخ الكوبون">
                                <div class="coupon-header">
                                    <span class="coupon-code">{{ $coupon['code'] }}</span>
                                    @if(isset($product->best_coupon) && $coupon['id'] == $product->best_coupon['id'])
                                    <span class="best-coupon-badge">
                                        <i class="fas fa-crown"></i> أفضل خصم
                                    </span>
                                    @endif
                                </div>
                                <div class="coupon-body">
                                    <div class="coupon-discount">
                                        @if($coupon['type'] === 'percentage')
                                            <span class="discount-value">{{ $coupon['value'] }}%</span>
                                        @else
                                            <span class="discount-value">{{ $coupon['value'] }} ر.س</span>
                                        @endif
                                    </div>
                                    @if($coupon['min_order_amount'] > 0)
                                    <div class="coupon-condition">
                                        <small>الحد الأدنى للطلب: {{ $coupon['min_order_amount'] }} ر.س</small>
                                    </div>
                                    @endif
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    <div class="stock-info mb-4">
                        <span class="stock-badge {{ $product->stock > 0 ? 'in-stock' : 'out-of-stock' }}">
                            <i class="fas {{ $product->stock > 0 ? 'fa-check-circle' : 'fa-times-circle' }} me-1"></i>
                            {{ $product->stock > 0 ? 'متوفر' : 'غير متوفر' }}
                        </span>
                        @if($product->stock > 0)
                            <span class="stock-count">({{ $product->stock }} قطعة متوفرة)</span>
                        @endif
                    </div>

                    <div class="product-description mb-4">
                        <h5 class="section-title">
                            <i class="fas fa-info-circle me-2"></i>
                            وصف المنتج
                        </h5>
                        <p>{{ $product->description }}</p>
                    </div>

                    <!-- Product Features Guide -->
                    @if(!empty($availableFeatures))
                    <div class="features-guide mb-4">
                        <div class="alert alert-info">
                            <h6 class="alert-heading mb-3">
                                <i class="fas fa-lightbulb me-2"></i>
                                ميزات الطلب المتاحة
                            </h6>
                            <ul class="features-list mb-0">
                                @foreach($availableFeatures as $feature)
                                    <li class="mb-2">
                                        <i class="fas fa-{{ $feature['icon'] }} me-2"></i>
                                        {{ $feature['text'] }}
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                    @endif

                    <!-- Colors Section -->
                    @if($product->allow_color_selection && $product->colors->isNotEmpty())
                        <div class="colors-section mb-4">
                            <h5 class="section-title">
                                <i class="fas fa-palette me-2"></i>
                                الألوان المتاحة
                            </h5>
                            <div class="colors-grid mb-3">
                                @foreach($product->colors as $color)
                                    <div class="color-item {{ $color->is_available ? 'available' : 'unavailable' }}"
                                        data-color="{{ $color->color }}"
                                        onclick="selectColor(this)">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="color-preview" style="background-color: {{ $color->color }};"></span>
                                            <span class="color-name">{{ $color->color }}</span>
                                        </div>
                                        <span class="color-status">
                                            @if($color->is_available)
                                                <i class="fas fa-check text-success"></i>
                                            @else
                                                <i class="fas fa-times text-danger"></i>
                                            @endif
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Custom Color Input -->
                    @if($product->allow_custom_color)
                        <div class="custom-color-section mb-4">
                            <h5 class="section-title">
                                <i class="fas fa-palette me-2"></i>
                                اللون المطلوب
                            </h5>
                            <div class="input-group">
                                <input type="text" class="form-control" id="customColor" placeholder="اكتب اللون المطلوب">
                            </div>
                        </div>
                    @endif

                    <!-- Available Sizes Section -->
                    @if($product->allow_size_selection && $product->sizes->isNotEmpty())
                        <div class="available-sizes mb-4">
                            <h5 class="fw-bold mb-3">المقاسات المتاحة</h5>
                            <div class="d-flex flex-wrap gap-2">
                                @foreach($product->sizes as $size)
                                    @if($size->is_available)
                                    <button type="button"
                                        class="size-option btn"
                                        data-size="{{ $size->size }}"
                                        data-price="{{ $size->price }}"
                                        onclick="selectSize(this)">
                                        {{ $size->size }}
                                        @if($size->price != null)
                                            <span class="ms-2 badge bg-primary">{{ number_format($size->price, 2) }} ر.س</span>
                                        @endif
                                    </button>
                                    @else
                                    <button type="button" class="size-option btn disabled">
                                        {{ $size->size }} (غير متوفر)
                                    </button>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Quantity Pricing Section -->
                    @if($product->enable_quantity_pricing && $product->quantities->isNotEmpty())
                        <div class="quantity-pricing mb-4">
                            <h4>
                                <i class="fas fa-cubes"></i>
                                خيارات الكمية
                                <span class="badge bg-primary ms-2">مطلوب</span>
                            </h4>
                            <div class="alert alert-info mb-3">
                                <i class="fas fa-info-circle me-2"></i>
                                يرجى اختيار أحد خيارات الكمية المتاحة
                            </div>
                            <div id="quantity-error-alert" class="alert alert-danger d-none">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong>تنبيه:</strong> يجب عليك اختيار إحدى خيارات الكمية المتاحة قبل إضافة المنتج إلى السلة
                                <button type="button" class="btn-close float-end" onclick="this.parentElement.classList.add('d-none')"></button>
                            </div>
                            <div class="quantity-options">
                                @foreach($product->quantities as $quantity)
                                    <div class="quantity-option {{ $quantity->is_available ? 'available' : 'disabled' }}"
                                        onclick="{{ $quantity->is_available ? 'selectQuantityOption(this)' : 'return false' }}"
                                        data-quantity-id="{{ $quantity->id }}"
                                        data-quantity-value="{{ $quantity->quantity_value }}"
                                        data-price="{{ $quantity->price }}">
                                        <i class="fas fa-check"></i>
                                        <div class="quantity-details">
                                            <span class="quantity-value">{{ $quantity->quantity_value }} قطعة</span>
                                            <span class="quantity-price">{{ number_format($quantity->price, 2) }} ر.س</span>
                                            @if($quantity->description)
                                                <small class="quantity-description">{{ $quantity->description }}</small>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Custom Size Input -->
                    @if($product->allow_custom_size)
                        <div class="custom-size-input mb-4">
                            <h5 class="section-title">
                                <i class="fas fa-ruler me-2"></i>
                                المقاس المطلوب
                            </h5>
                            <div class="input-group">
                                <input type="text" class="form-control" id="customSize" placeholder="اكتب المقاس المطلوب">
                            </div>
                        </div>
                    @endif

                    <!-- Custom Measurements Option -->
                    @if($product->allow_appointment && $showStoreAppointments)
                        <div class="custom-measurements-section mb-4">
                            @auth
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>تنبيه:</strong> هذا المنتج يتطلب أخذ موعد لأخذ المقاسات
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="needsAppointment" checked disabled>
                                <label class="form-check-label" for="needsAppointment">
                                    <i class="fas fa-tape me-2"></i>
                                    أخذ موعد للمقاسات
                                </label>
                            </div>
                            <small class="text-muted d-block mt-2">
                                <i class="fas fa-info-circle me-1"></i>
                                سيتم تحديد موعد لأخذ المقاسات الخاصة بك بعد إضافة المنتج للسلة
                            </small>
                            @else
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    يجب تسجيل الدخول لتتمكن من حجز موعد لأخذ المقاسات
                                </div>
                            @endauth
                        </div>
                    @endif

                    <!-- Quantity Selector -->
                    @auth
                    <div class="quantity-selector mb-4">
                        <h5 class="section-title">
                            <i class="fas fa-shopping-basket me-2"></i>
                            الكمية
                        </h5>
                        <div class="input-group" style="width: 150px;">
                            <button class="btn btn-outline-primary" type="button" onclick="updatePageQuantity(-1)">-</button>
                            <input type="number" class="form-control text-center" id="quantity" value="1" min="1" max="{{ $product->stock }}" readonly>
                            <button class="btn btn-outline-primary" type="button" onclick="updatePageQuantity(1)">+</button>
                        </div>
                    </div>

                    <!-- Add to Cart Button -->
                    <button class="btn btn-primary btn-lg w-100 mb-4" onclick="addToCart()">
                        <i class="fas fa-shopping-cart me-2"></i>
                        أضف إلى السلة
                    </button>
                    @else
                        <!-- Login to Order Button -->
                        <button class="btn btn-primary btn-lg w-100 mb-4"
                                onclick="showLoginPrompt('{{ route('login') }}')"
                                type="button">
                            <i class="fas fa-shopping-cart me-2"></i>
                            تسجيل الدخول للطلب
                        </button>
                    @endauth

                    <!-- Error Messages -->
                    <div class="alert alert-danger d-none" id="errorMessage"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="glass-footer">
      <div class="container">
        <div class="row">
          <div class="col-lg-4">
            <div class="footer-about">
              <h5>عن الاستوديو</h5>
              <p>نقدم خدمات التصوير الفوتوغرافي والطباعة بأعلى جودة وأفضل الأسعار مع الالتزام بالمواعيد</p>
              <div class="social-links">
                <a href="https://www.instagram.com/lens_soma_studio/?igsh=d2ZvaHZqM2VoMWsw#" class="social-link" aria-label="Instagram">
                  <i class="fab fa-instagram"></i>
                </a>
                <a href="#" class="social-link" aria-label="Facebook">
                  <i class="fab fa-facebook-f"></i>
                </a>
                <a href="#" class="social-link" aria-label="Twitter">
                  <i class="fab fa-twitter"></i>
                </a>
              </div>
            </div>
          </div>
          <div class="col-lg-4">
            <div class="footer-links">
              <h5>روابط سريعة</h5>
              <ul>
                <li><a href="/">الرئيسية</a></li>
                <li><a href="/products">المنتجات</a></li>
                <li><a href="/about">من نحن</a></li>
                <li><a href="/services">خدماتنا</a></li>
                <li><a href="/client/book">حجز موعد</a></li>
              </ul>
            </div>
          </div>
          <div class="col-lg-4">
            <div class="footer-contact">
              <h5>معلومات التواصل</h5>
              <ul class="list-unstyled">
                <li class="mb-2 d-flex align-items-center">
                  <i class="fas fa-phone-alt ms-2"></i>
                  <span dir="ltr">+966561667885</span>
                </li>
                <li class="mb-2 d-flex align-items-center">
                  <i class="fas fa-envelope ms-2"></i>
                  <a href="mailto:lens_soma@outlook.sa" class="text-decoration-none">lens_soma@outlook.sa</a>
                </li>
                <li class="d-flex align-items-center">
                  <i class="fas fa-map-marker-alt ms-2"></i>
                  <span>أبها . المحالة</span>
                </li>
              </ul>
            </div>
          </div>
        </div>
      </div>
      <div class="footer-bottom">
        <div class="container">
          <p>جميع الحقوق محفوظة &copy; {{ date('Y') }} عدسة سوما</p>
        </div>
      </div>
    </footer>

    <!-- Modal for Appointment -->
    <div class="modal fade" id="appointmentModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">حجز موعد للمقاسات</h5>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning mb-4">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        يجب حجز موعد لأخذ المقاسات لإتمام الطلب. إذا أغلقت هذه النافذة بدون حجز موعد، سيتم إلغاء إضافة المنتج للسلة.
                    </div>

                    <!-- إضافة معلومات مواعيد العمل -->
                    <div class="alert alert-info mb-4">
                        <i class="fas fa-info-circle me-2"></i>
                        مواعيد العمل:
                   <ul class='mb-0'>
                      <li>من السبت إلى الخميس: <span id="studioStartTime">١٠ صباحاً</span> إلى <span id="studioEndTime">٦ مساءً</span></li>
                   </ul>
                    </div>

                    <form id="appointmentForm" class="appointment-form" data-url="{{ route('appointments.store') }}">
                        @csrf
                        <input type="hidden" name="service_type" value="new_abaya">
                        <input type="hidden" name="cart_item_id" id="cart_item_id">

                        <div class="mb-4">
                            <label for="appointment_date" class="form-label fw-bold">تاريخ الموعد</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-calendar"></i></span>
                                <input type="date" class="form-control form-control-lg" id="appointment_date"
                                       name="appointment_date" min="{{ date('Y-m-d') }}" required>
                            </div>
                            <div class="invalid-feedback" id="date-error"></div>
                            <!-- إضافة عنصر لعرض اقتراح اليوم المتاح -->
                            <div class="alert alert-info mt-2 d-none" id="dateSuggestion">
                                <i class="fas fa-info-circle me-2"></i>
                                <span id="suggestionText"></span>
                                <button type="button" class="btn btn-link p-0 ms-2" id="acceptSuggestion">
                                    اختيار هذا اليوم
                                </button>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="appointment_time" class="form-label fw-bold">وقت الموعد</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-clock"></i></span>
                                <select class="form-select form-select-lg" id="appointment_time"
                                        name="appointment_time" required disabled>
                                    <option value="">اختر التاريخ أولاً</option>
                                </select>
                            </div>
                            <div class="invalid-feedback" id="time-error"></div>
                        </div>

                        <div class="mb-4">
                            <label for="location" class="form-label fw-bold">الموقع</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-map-marker-alt"></i></span>
                                <select class="form-select form-select-lg" id="location" name="location" required>
                                    <option value="store">في المحل</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-4 d-none" id="addressField">
                            <label for="address" class="form-label fw-bold">العنوان</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-home"></i></span>
                                <textarea class="form-control" id="address" name="address"
                                          rows="3" placeholder="يرجى إدخال العنوان بالتفصيل"></textarea>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="phone" class="form-label fw-bold">رقم الهاتف</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                <input type="tel" class="form-control form-control-lg" id="phone"
                                       name="phone" value="{{ Auth::user()->phone ?? '' }}"
                                       placeholder="01xxxxxxxxx" required>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="notes" class="form-label fw-bold">ملاحظات</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-comment"></i></span>
                                <textarea class="form-control" id="notes" name="notes" rows="3"
                                          placeholder="أي ملاحظات إضافية؟"></textarea>
                            </div>
                        </div>

                        <div id="appointmentErrors" class="alert alert-danger d-none"></div>

                        <div class="d-flex gap-2 mt-4">
                            <button type="button" class="btn btn-outline-danger flex-grow-1" id="cancelAppointment">
                                <i class="fas fa-times me-1"></i>
                                إلغاء الموعد والمنتج
                            </button>
                            <button type="submit" class="btn btn-primary flex-grow-1" id="submitBtn">
                                <span class="loading-spinner d-none">
                                    <i class="fas fa-spinner fa-spin"></i>
                                </span>
                                حجز الموعد
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Login Prompt Modal -->
    <div class="modal fade" id="loginPromptModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">تسجيل الدخول مطلوب</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <i class="fas fa-user-lock fa-3x mb-3 text-primary"></i>
                    <p>يجب عليك تسجيل الدخول أولاً لتتمكن من طلب المنتج</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>
                        إلغاء
                    </button>
                    <a href="" class="btn btn-primary" id="loginButton">
                        تسجيل الدخول
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Add this hidden input for product ID -->
    <input type="hidden" id="product-id" value="{{ $product->id }}">

    <!-- Add this hidden input for original product price -->
    <input type="hidden" id="original-price" value="{{ $product->price }}">

    <!-- Add this hidden input after product-id -->
    <input type="hidden" id="appointmentsEnabled" value="{{ $showStoreAppointments ? 'true' : 'false' }}">

    @if($pendingAppointment)
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            showAppointmentModal({{ $pendingAppointment->id }});
        });
    </script>
    @endif

    <!-- Toast Notification for Coupon Copy -->
    <div class="toast-container position-fixed top-0 end-0 p-3">
        <div id="couponToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header">
                <i class="fas fa-ticket-alt me-2"></i>
                <strong class="me-auto">نسخ الكوبون</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">
                تم نسخ الكوبون بنجاح!
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Add event listeners for copying coupon codes
        document.addEventListener('DOMContentLoaded', function() {
            // Add click event to all coupon badges and cards
            document.querySelectorAll('.copy-coupon').forEach(function(couponElement) {
                couponElement.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();

                    // Get the coupon code
                    const couponCode = this.getAttribute('data-coupon-code');

                    // Create a temporary textarea element to copy the text
                    const textarea = document.createElement('textarea');
                    textarea.value = couponCode;
                    textarea.setAttribute('readonly', '');
                    textarea.style.position = 'absolute';
                    textarea.style.left = '-9999px';
                    document.body.appendChild(textarea);

                    // Select and copy the text
                    textarea.select();
                    document.execCommand('copy');

                    // Remove the textarea
                    document.body.removeChild(textarea);

                    // Show toast notification
                    const couponToast = new bootstrap.Toast(document.getElementById('couponToast'));
                    couponToast.show();
                });

                // Add cursor pointer style
                couponElement.style.cursor = 'pointer';
            });
        });
    </script>
    <script src="{{ asset('assets/js/customer/products-show.js') }}?t={{ time() }}"></script>
</body>
</html>
