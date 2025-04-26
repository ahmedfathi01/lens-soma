@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card booking-details-main-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">
                        <i class="fas fa-calendar-check me-2"></i>
                        تفاصيل الحجز #{{ $booking->booking_number }}
                    </h3>
                    <div>
                        <a href="{{ route('admin.bookings.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-right me-1"></i>
                            عودة للحجوزات
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- معلومات العميل -->
                        <div class="col-md-6">
                            <div class="card info-card client-card">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-user me-2"></i>معلومات العميل</h5>
                                </div>
                                <div class="card-body">
                                    <div class="info-item">
                                        <span class="info-label"><i class="fas fa-user-tag me-2"></i>الاسم:</span>
                                        <span class="info-value">{{ $booking->user->name }}</span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label"><i class="fas fa-envelope me-2"></i>البريد الإلكتروني:</span>
                                        <span class="info-value">{{ $booking->user->email }}</span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label"><i class="fas fa-phone-alt me-2"></i>رقم الهاتف:</span>
                                        <span class="info-value">{{ $booking->user->phone }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- معلومات الحجز -->
                        <div class="col-md-6">
                            <div class="card info-card booking-card">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>معلومات الحجز</h5>
                                </div>
                                <div class="card-body">
                                    <div class="booking-info-grid">
                                        <div class="service-info info-grid-item">
                                            <div class="info-icon"><i class="fas fa-camera"></i></div>
                                            <div class="info-content">
                                                <span class="info-label">نوع الجلسة</span>
                                                <span class="info-value">{{ $booking->service->name }}</span>
                                            </div>
                                        </div>

                                        <div class="package-info info-grid-item">
                                            <div class="info-icon"><i class="fas fa-box-open"></i></div>
                                            <div class="info-content">
                                                <span class="info-label">الباقة</span>
                                                <span class="info-value">{{ $booking->package->name }}</span>
                                            </div>
                                        </div>

                                        <div class="date-info info-grid-item">
                                            <div class="info-icon"><i class="fas fa-calendar-day"></i></div>
                                            <div class="info-content">
                                                <span class="info-label">تاريخ الجلسة</span>
                                                <span class="info-value">{{ $booking->session_date->format('Y-m-d') }}</span>
                                            </div>
                                        </div>

                                        <div class="time-info info-grid-item">
                                            <div class="info-icon"><i class="fas fa-clock"></i></div>
                                            <div class="info-content">
                                                <span class="info-label">وقت الجلسة</span>
                                                <span class="info-value">{{ $booking->session_time->format('H:i') }}</span>
                                            </div>
                                        </div>

                                        <div class="status-info info-grid-item">
                                            <div class="info-icon"><i class="fas fa-info-circle"></i></div>
                                            <div class="info-content">
                                                <span class="info-label">حالة الحجز</span>
                                                <span class="info-value">
                                                    <span class="badge bg-{{ $booking->status == 'pending' ? 'warning' : ($booking->status == 'confirmed' ? 'success' : ($booking->status == 'completed' ? 'info' : 'danger')) }} status-badge">
                                                        {{ $booking->status == 'pending' ? 'قيد الانتظار' : ($booking->status == 'confirmed' ? 'مؤكد' : ($booking->status == 'completed' ? 'مكتمل' : 'ملغي')) }}
                                                    </span>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- معلومات المولود -->
                        @if($booking->baby_name || $booking->baby_birth_date || $booking->gender)
                        <div class="col-md-6 mt-4">
                            <div class="card info-card baby-card">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-baby me-2"></i>معلومات المولود</h5>
                                </div>
                                <div class="card-body">
                                    @if($booking->baby_name)
                                        <div class="info-item">
                                            <span class="info-label"><i class="fas fa-signature me-2"></i>الاسم:</span>
                                            <span class="info-value">{{ $booking->baby_name }}</span>
                                        </div>
                                    @endif
                                    @if($booking->baby_birth_date)
                                        <div class="info-item">
                                            <span class="info-label"><i class="fas fa-birthday-cake me-2"></i>تاريخ الميلاد:</span>
                                            <span class="info-value">{{ $booking->baby_birth_date->format('Y-m-d') }}</span>
                                        </div>
                                    @endif
                                    @if($booking->gender)
                                        <div class="info-item">
                                            <span class="info-label"><i class="fas fa-venus-mars me-2"></i>الجنس:</span>
                                            <span class="info-value">{{ $booking->gender == 'male' ? 'ذكر' : 'أنثى' }}</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endif

                        <!-- الإضافات -->
                        @if($booking->addons->count() > 0)
                        <div class="col-md-6 mt-4">
                            <div class="card info-card addons-card">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-plus-circle me-2"></i>الإضافات المختارة</h5>
                                </div>
                                <div class="card-body">
                                    <ul class="addons-list">
                                        @foreach($booking->addons as $addon)
                                            <li class="addon-item">
                                                <div class="addon-name">
                                                    <i class="fas fa-check-circle me-2"></i>
                                                    {{ $addon->name }}
                                                </div>
                                                <div class="addon-price">
                                                    {{ $addon->pivot->quantity }} × {{ $addon->pivot->price_at_booking }} درهم
                                                </div>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </div>
                        @endif

                        <!-- معلومات الدفع -->
                        <div class="col-md-6 mt-4">
                            <div class="card info-card payment-card">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-credit-card me-2"></i>معلومات الدفع</h5>
                                </div>
                                <div class="card-body">
                                    <div class="payment-status">
                                        <span class="status-label"><i class="fas fa-cash-register me-2"></i>حالة الدفع:</span>
                                        <span class="status-value">
                                            <span class="badge {{ $booking->payment_status == 'success' ? 'bg-success' : ($booking->payment_status == 'pending' ? 'bg-warning' : 'bg-danger') }} status-badge">
                                                {{ $booking->payment_status == 'success' ? 'تم الدفع' : ($booking->payment_status == 'pending' ? 'قيد الانتظار' : 'فشل الدفع') }}
                                            </span>
                                        </span>
                                    </div>

                                    <div class="payment-details">
                                        <div class="original-price">
                                            <i class="fas fa-tag me-2"></i>
                                            <strong>المبلغ الأصلي:</strong> <span>{{ $booking->original_amount }} درهم</span>
                                        </div>

                                        @if($booking->discount_amount > 0)
                                        <div class="discount-amount">
                                            <i class="fas fa-percentage me-2"></i>
                                            <strong>قيمة الخصم:</strong> <span class="text-success fw-bold">- {{ $booking->discount_amount }} درهم</span>

                                            @if($booking->coupon_code)
                                            <div class="coupon-badge">
                                                <span class="badge bg-primary">
                                                    <i class="fas fa-ticket-alt me-1"></i>
                                                    كود الكوبون: {{ $booking->coupon_code }}
                                                </span>
                                            </div>
                                            @endif
                                        </div>
                                        @endif

                                        <div class="final-price">
                                            <i class="fas fa-money-bill-wave me-2"></i>
                                            <strong>المبلغ النهائي:</strong> <span class="text-primary fw-bold fs-5">{{ $booking->total_amount }} درهم</span>
                                        </div>
                                    </div>

                                    @if($booking->payment_transaction_id)
                                        <div class="transaction-info info-item">
                                            <span class="info-label"><i class="fas fa-receipt me-2"></i>رقم المعاملة:</span>
                                            <span class="info-value">{{ $booking->payment_transaction_id }}</span>
                                        </div>
                                    @endif
                                    @if($booking->payment_id)
                                        <div class="transaction-info info-item">
                                            <span class="info-label"><i class="fas fa-fingerprint me-2"></i>معرف الدفع:</span>
                                            <span class="info-value">{{ $booking->payment_id }}</span>
                                        </div>
                                    @endif

                                    @if($booking->payment_method)
                                        <div class="payment-method info-item">
                                            <span class="info-label"><i class="fas fa-money-check me-2"></i>طريقة الدفع:</span>
                                            <span class="info-value">
                                                <span class="badge {{ $booking->payment_method == 'cod' ? 'bg-secondary' : 'bg-info' }} payment-method-badge">
                                                    <i class="{{ $booking->payment_method == 'cod' ? 'fas fa-hand-holding-usd' : 'fas fa-credit-card' }} me-1"></i>
                                                    {{ $booking->payment_method == 'cod' ? 'الدفع عند الاستلام' : 'دفع إلكتروني' }}
                                                </span>
                                            </span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- تحديث الحالة -->
                        <div class="col-12 mt-4">
                            <div class="card info-card status-update-card">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-edit me-2"></i>تحديث حالة الحجز</h5>
                                </div>
                                <div class="card-body">
                                    <form action="{{ route('admin.bookings.update-status', $booking->uuid) }}"
                                        method="POST" class="d-flex align-items-center">
                                        @csrf
                                        @method('PATCH')
                                        <div class="row g-3 align-items-center w-100">
                                            <div class="col-md-8">
                                                <select name="status" class="form-select status-select">
                                                    <option value="pending" {{ $booking->status == 'pending' ? 'selected' : '' }}>
                                                        قيد الانتظار
                                                    </option>
                                                    <option value="confirmed" {{ $booking->status == 'confirmed' ? 'selected' : '' }}>
                                                        مؤكد
                                                    </option>
                                                    <option value="completed" {{ $booking->status == 'completed' ? 'selected' : '' }}>
                                                        مكتمل
                                                    </option>
                                                    <option value="cancelled" {{ $booking->status == 'cancelled' ? 'selected' : '' }}>
                                                        ملغي
                                                    </option>
                                                </select>
                                            </div>
                                            <div class="col-md-4">
                                                <button type="submit" class="btn btn-primary w-100 update-status-btn">
                                                    <i class="fas fa-save me-1"></i>
                                                    تحديث الحالة
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- إضافة قسم لعرض رسائل النجاح -->
                        @if(session('success'))
                        <div class="col-12 mt-4">
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="fas fa-check-circle me-1"></i>
                                {{ session('success') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/admin/bookings.css') }}?t={{ time() }}">
@endsection
