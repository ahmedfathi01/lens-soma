@extends('layouts.admin')

@section('title', 'تفاصيل الطلب #' . $order->order_number)
@section('page_title', 'تفاصيل الطلب #' . $order->order_number)

@section('content')
<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid px-0">
            <div class="row mx-0">
                <div class="col-12 px-0">
                    <div class="orders-container">
                        <!-- Header Actions -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="card border-0 shadow-sm">
                                    <div class="card-body d-flex justify-content-between align-items-center">
                                        <div>
                                            <h5 class="card-title mb-1 d-flex align-items-center">
                                                <span class="icon-circle bg-primary text-white me-2">
                                                    <i class="fas fa-info-circle"></i>
                                                </span>
                                                تفاصيل الطلب #{{ $order->order_number }}
                                            </h5>
                                            <p class="text-muted mb-0 fs-sm">عرض تفاصيل الطلب والمنتجات والمواعيد</p>
                </div>
                                        <div class="actions d-flex gap-2">
                                            <a href="{{ route('admin.orders.index') }}" class="btn btn-light-secondary">
                                                <i class="fas fa-arrow-right me-2"></i>
                                                عودة للطلبات
                </a>
                                            <button onclick="window.print()" class="btn btn-light-primary">
                                                <i class="fas fa-print me-2"></i>
                    طباعة الطلب
                </button>
            </div>
                            </div>
                        </div>
                    </div>
                </div>

                        <!-- Order Stats -->
                        <div class="row g-4 mb-4">
                            <div class="col-md-3">
                                <div class="card border-0 shadow-sm stat-card bg-gradient-primary h-100">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <div class="icon-circle bg-white text-primary me-3">
                                                <i class="fas fa-shopping-cart fa-lg"></i>
                        </div>
                                            <div>
                                                <h6 class="text-white mb-1">رقم الطلب</h6>
                                                <h3 class="text-white mb-0">#{{ $order->order_number }}</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
                            <div class="col-md-3">
                                <div class="card border-0 shadow-sm stat-card bg-gradient-success h-100">
                <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <div class="icon-circle bg-white text-success me-3">
                                                <i class="fas fa-box-open fa-lg"></i>
                                    </div>
                                    <div>
                                                <h6 class="text-white mb-1">عدد المنتجات</h6>
                                                <h3 class="text-white mb-0">{{ $order->items->count() }}</h3>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border-0 shadow-sm stat-card bg-gradient-info h-100">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <div class="icon-circle bg-white text-info me-3">
                                                <i class="fas fa-money-bill-wave fa-lg"></i>
                                            </div>
                                            <div>
                                                <h6 class="text-white mb-1">إجمالي الطلب</h6>
                                                <h3 class="text-white mb-0">{{ number_format($order->total_amount) }} ريال</h3>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border-0 shadow-sm stat-card bg-gradient-warning h-100">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <div class="icon-circle bg-white text-warning me-3">
                                                <i class="fas fa-clock fa-lg"></i>
                                    </div>
                                    <div>
                                                <h6 class="text-white mb-1">تاريخ الطلب</h6>
                                                <h3 class="text-white mb-0">{{ $order->created_at->format('Y/m/d') }}</h3>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Price Details Card -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="card border-0 shadow-sm">
                                    <div class="card-body">
                                        <h5 class="card-title mb-4 d-flex align-items-center">
                                            <span class="icon-circle bg-primary text-white me-2">
                                                <i class="fas fa-calculator"></i>
                                            </span>
                                            تفاصيل السعر
                                        </h5>
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="price-detail-item d-flex justify-content-between align-items-center py-2 border-bottom">
                                                    <span class="text-muted">إجمالي المنتجات</span>
                                                    <span class="fw-bold">{{ number_format($order->subtotal, 2) }} ريال</span>
                                                </div>
                                                @if($order->discount_amount > 0)
                                                <div class="price-detail-item d-flex justify-content-between align-items-center py-2 border-bottom">
                                                    <span class="text-muted">الخصم
                                                        @if($order->coupon_code)
                                                            <small>({{ $order->coupon_code }})</small>
                                                        @endif
                                                    </span>
                                                    <span class="fw-bold text-danger">- {{ number_format($order->discount_amount, 2) }} ريال</span>
                                                </div>
                                                @endif
                                                <div class="price-detail-item d-flex justify-content-between align-items-center py-2">
                                                    <span class="text-muted fw-bold">الإجمالي النهائي</span>
                                                    <span class="fw-bold fs-5">{{ number_format($order->total_amount, 2) }} ريال</span>
                                                </div>
                                            </div>
                                            <div class="col-md-8">
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="payment-method-details border rounded p-3 h-100">
                                                            <h6 class="mb-3">
                                                                <i class="fas fa-credit-card text-primary me-2"></i>
                                                                طريقة الدفع
                                                            </h6>
                                                            <p class="mb-2">
                                                                <span class="text-muted">الطريقة:</span>
                                                                <span class="fw-bold">{{ $order->payment_method === 'cash' ? 'كاش' : 'بطاقة' }}</span>
                                                            </p>
                                                            <p class="mb-2">
                                                                <span class="text-muted">الحالة:</span>
                                                                <span class="badge bg-{{ $order->payment_status === 'paid' ? 'success' : ($order->payment_status === 'pending' ? 'warning' : 'danger') }}-subtle
                                                                 text-{{ $order->payment_status === 'paid' ? 'success' : ($order->payment_status === 'pending' ? 'warning' : 'danger') }} rounded-pill">
                                                                    {{ $order->payment_status === 'paid' ? 'تم الدفع' : ($order->payment_status === 'pending' ? 'قيد الانتظار' : 'فشل الدفع') }}
                                                                </span>
                                                            </p>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        @if($order->payment_status === 'paid')
                                                        <div class="payment-details border rounded p-3 h-100">
                                                            <h6 class="mb-3">
                                                                <i class="fas fa-money-check text-primary me-2"></i>
                                                                تفاصيل الدفع
                                                            </h6>
                                                            <p class="mb-2">
                                                                <span class="text-muted">تاريخ الدفع:</span>
                                                                <span class="fw-bold">{{ $order->updated_at->format('Y/m/d') }}</span>
                                                            </p>
                                                            @if($order->payment_transaction_id)
                                                            <p class="mb-2">
                                                                <span class="text-muted">رقم العملية:</span>
                                                                <span class="fw-bold">{{ $order->payment_transaction_id }}</span>
                                                            </p>
                                                            @endif
                                                        </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- تفاصيل التقسيط عبر تابي -->
                        @if($order->payment_method == 'tabby' && $order->payment_details)
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="card border-0 shadow-sm">
                                    <div class="card-body">
                                        <h5 class="card-title mb-4 d-flex align-items-center">
                                            <span class="icon-circle bg-primary text-white me-2">
                                                <i class="fas fa-credit-card"></i>
                                            </span>
                                            تفاصيل التقسيط عبر تابي
                                        </h5>

                                        @php
                                            $paymentDetails = json_decode($order->payment_details, true);
                                            $hasInstallments = isset($paymentDetails['installments']) && !empty($paymentDetails['installments']);
                                            $downpayment = $paymentDetails['downpayment'] ?? null;
                                            $downpaymentPercent = $paymentDetails['downpayment_percent'] ?? null;
                                        @endphp

                                        <div class="row mb-4">
                                            <div class="col-md-3">
                                                @if ($downpayment)
                                                <div class="card border bg-light mb-3">
                                                    <div class="card-body text-center">
                                                        <h6 class="card-title mb-3">
                                                            <i class="fas fa-money-bill-wave text-primary me-1"></i>
                                                            الدفعة المقدمة
                                                        </h6>
                                                        <h5 class="mb-2">{{ $downpayment }} ريال</h5>
                                                        <span class="badge bg-info">{{ $downpaymentPercent }}%</span>
                                                    </div>
                                                </div>
                                                @endif
                                            </div>
                                            <div class="col-md-3">
                                                @if (isset($paymentDetails['installments_count']))
                                                <div class="card border bg-light mb-3">
                                                    <div class="card-body text-center">
                                                        <h6 class="card-title mb-3">
                                                            <i class="fas fa-calculator text-primary me-1"></i>
                                                            عدد الأقساط
                                                        </h6>
                                                        <h5 class="mb-0">{{ $paymentDetails['installments_count'] }}</h5>
                                                    </div>
                                                </div>
                                                @endif
                                            </div>
                                            <div class="col-md-3">
                                                @if (isset($paymentDetails['next_payment_date']))
                                                <div class="card border bg-light mb-3">
                                                    <div class="card-body text-center">
                                                        <h6 class="card-title mb-3">
                                                            <i class="fas fa-calendar-day text-primary me-1"></i>
                                                            موعد الدفعة التالية
                                                        </h6>
                                                        <h5 class="mb-0">{{ \Carbon\Carbon::parse($paymentDetails['next_payment_date'])->format('Y/m/d') }}</h5>
                                                    </div>
                                                </div>
                                                @endif
                                            </div>
                                            <div class="col-md-3">
                                                <div class="card border bg-light mb-3">
                                                    <div class="card-body text-center">
                                                        <h6 class="card-title mb-3">
                                                            <i class="fas fa-coins text-primary me-1"></i>
                                                            إجمالي المبلغ
                                                        </h6>
                                                        <h5 class="mb-0">{{ $order->total_amount }} ريال</h5>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        @if ($hasInstallments)
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-striped table-hover">
                                                <thead class="table-primary">
                                                    <tr>
                                                        <th style="width: 60px;" class="text-center">#</th>
                                                        <th class="text-center">تاريخ الاستحقاق</th>
                                                        <th class="text-center">المبلغ</th>
                                                        <th class="text-center">الحالة</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <!-- صف الدفعة المقدمة -->
                                                    @if ($downpayment)
                                                    <tr class="table-success">
                                                        <td class="text-center"><i class="fas fa-check-circle text-success"></i></td>
                                                        <td class="text-center">{{ $order->created_at->format('Y-m-d') }}</td>
                                                        <td class="text-center">{{ $downpayment }} ريال</td>
                                                        <td class="text-center">
                                                            <span class="badge bg-success">
                                                                <i class="fas fa-check me-1"></i> تم الدفع
                                                            </span>
                                                        </td>
                                                    </tr>
                                                    @endif

                                                    <!-- صفوف الأقساط -->
                                                    @foreach($paymentDetails['installments'] as $index => $installment)
                                                    @php
                                                        $dueDate = \Carbon\Carbon::parse($installment['due_date']);
                                                        $isPaid = false; // يمكنك تعديل هذا لاحقًا بناءً على بيانات الدفع الفعلية
                                                        $isDue = $dueDate->isPast() && !$isPaid;
                                                        $isPending = $dueDate->isFuture();
                                                    @endphp
                                                    <tr class="{{ $isPaid ? 'table-success' : ($isDue ? 'table-danger' : '') }}">
                                                        <td class="text-center">{{ $index + 1 }}</td>
                                                        <td class="text-center">{{ $dueDate->format('Y-m-d') }}</td>
                                                        <td class="text-center">{{ $installment['amount'] }} ريال</td>
                                                        <td class="text-center">
                                                            @if($isPaid)
                                                                <span class="badge bg-success">
                                                                    <i class="fas fa-check me-1"></i> تم الدفع
                                                                </span>
                                                            @elseif($isDue)
                                                                <span class="badge bg-danger">
                                                                    <i class="fas fa-exclamation-circle me-1"></i> متأخر
                                                                </span>
                                                            @else
                                                                <span class="badge bg-warning">
                                                                    <i class="fas fa-clock me-1"></i> قيد الانتظار
                                                                </span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                    @endforeach
                                                </tbody>
                                                <tfoot class="table-primary">
                                                    <tr>
                                                        <td class="text-start fw-bold" colspan="2">إجمالي المبلغ</td>
                                                        <td class="text-center fw-bold" colspan="2">{{ $order->total_amount }} ريال</td>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif

                        <!-- Order Details -->
                        <div class="row g-4">
                            <!-- Order Info -->
                            <div class="col-md-6">
                                <div class="card border-0 shadow-sm h-100">
                                    <div class="card-body">
                                        <h5 class="card-title mb-4 d-flex align-items-center">
                                            <span class="icon-circle bg-primary text-white me-2">
                                                <i class="fas fa-info-circle"></i>
                                            </span>
                                            معلومات الطلب
                                        </h5>
                                        <div class="info-list">
                                            <div class="info-item d-flex justify-content-between py-2">
                                                <span class="text-muted">حالة الطلب</span>
                                                <div>
                                                    <select name="order_status" class="form-select form-select-sm d-inline-block w-auto me-2"
                                                            onchange="this.form.submit()" form="update-status-form">
                                                        <option value="pending" {{ $order->order_status === 'pending' ? 'selected' : '' }}>قيد الانتظار</option>
                                                        <option value="processing" {{ $order->order_status === 'processing' ? 'selected' : '' }}>قيد المعالجة</option>
                                                        <option value="out_for_delivery" {{ $order->order_status === 'out_for_delivery' ? 'selected' : '' }}>جاري التوصيل</option>
                                                        <option value="on_the_way" {{ $order->order_status === 'on_the_way' ? 'selected' : '' }}>في الطريق</option>
                                                        <option value="delivered" {{ $order->order_status === 'delivered' ? 'selected' : '' }}>تم التوصيل</option>
                                                        <option value="completed" {{ $order->order_status === 'completed' ? 'selected' : '' }}>مكتمل</option>
                                                        <option value="returned" {{ $order->order_status === 'returned' ? 'selected' : '' }}>مرتجع</option>
                                                        <option value="cancelled" {{ $order->order_status === 'cancelled' ? 'selected' : '' }}>ملغي</option>
                                                    </select>
                                                    <span class="badge bg-{{ $order->status_color }}-subtle text-{{ $order->status_color }} rounded-pill">
                                                        {{ $order->status_text }}
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="info-item d-flex justify-content-between py-2">
                                                <span class="text-muted">طريقة الدفع</span>
                                                <span>{{ $order->payment_method === 'cash' ? 'كاش' : 'بطاقة' }}</span>
                                            </div>
                                            <div class="info-item d-flex justify-content-between py-2">
                                                <span class="text-muted">حالة الدفع</span>
                                                <div>
                                                    <select name="payment_status" class="form-select form-select-sm d-inline-block w-auto me-2"
                                                            onchange="this.form.submit()" form="update-payment-status-form">
                                                        <option value="pending" {{ $order->payment_status === 'pending' ? 'selected' : '' }}>قيد الانتظار</option>
                                                        <option value="paid" {{ $order->payment_status === 'paid' ? 'selected' : '' }}>تم الدفع</option>
                                                        <option value="failed" {{ $order->payment_status === 'failed' ? 'selected' : '' }}>فشل الدفع</option>
                                                    </select>
                                                    <span class="badge bg-{{ $order->payment_status === 'paid' ? 'success' : ($order->payment_status === 'pending' ? 'warning' : 'danger') }}-subtle
                                                                 text-{{ $order->payment_status === 'paid' ? 'success' : ($order->payment_status === 'pending' ? 'warning' : 'danger') }} rounded-pill">
                                                        {{ $order->payment_status === 'paid' ? 'تم الدفع' : ($order->payment_status === 'pending' ? 'قيد الانتظار' : 'فشل الدفع') }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                    </div>
                </div>
                            </div>

                            <!-- Customer Info -->
                            <div class="col-md-6">
                                <div class="card border-0 shadow-sm h-100">
                                    <div class="card-body">
                                        <h5 class="card-title mb-4 d-flex align-items-center">
                                            <span class="icon-circle bg-primary text-white me-2">
                                                <i class="fas fa-user"></i>
                                            </span>
                                            معلومات العميل
                                        </h5>
                                        <div class="customer-info">
                                            <div class="d-flex align-items-center mb-4">
                                                <div class="avatar-circle bg-primary text-white me-3">
                                                    {{ substr($order->user->name, 0, 1) }}
                                                </div>
                                                <div>
                                                    <h6 class="mb-1">{{ $order->user->name }}</h6>
                                                    <p class="text-muted mb-0">{{ $order->user->email }}</p>
                                                </div>
                                            </div>
                                            <div class="info-list">
                                                <div class="info-item d-flex align-items-center py-2">
                                                    <i class="fas fa-phone text-primary me-3"></i>
                                                    <span>{{ $order->phone }}</span>
                                                </div>
                                                <div class="info-item d-flex align-items-center py-2">
                                                    <i class="fas fa-map-marker-alt text-primary me-3"></i>
                                                    <span>{{ $order->shipping_address }}</span>
                                                </div>
                                                @if($order->notes)
                                                <div class="info-item d-flex align-items-center py-2">
                                                    <i class="fas fa-sticky-note text-primary me-3"></i>
                                                    <span>{{ $order->notes }}</span>
            </div>
            @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                </div>

                            <!-- Products List -->
                            <div class="col-12">
                                <div class="card border-0 shadow-sm mb-4">
                                    <div class="card-body">
                                        <h5 class="card-title mb-4 d-flex align-items-center">
                                            <span class="icon-circle bg-primary text-white me-2">
                                                <i class="fas fa-shopping-bag"></i>
                                            </span>
                                            منتجات الطلب
                                        </h5>

                                        <!-- Products with Appointments -->
                                        @if($itemsWithAppointments->isNotEmpty())
                                        <div class="table-responsive mb-4">
                                            <h6 class="mb-3">
                                                <i class="fas fa-calendar-check text-primary me-2"></i>
                                                المنتجات مع مواعيد
                                            </h6>
                                            <table class="table table-hover align-middle">
                                                <thead class="bg-light">
                                                    <tr>
                                                        <th class="border-0 text-center" style="width: 60px">#</th>
                                                        <th class="border-0" style="min-width: 250px">المنتج</th>
                                                        <th class="border-0 text-center" style="width: 100px">الكمية</th>
                                                        <th class="border-0" style="width: 100px">اللون</th>
                                                        <th class="border-0" style="width: 100px">المقاس</th>
                                                        <th class="border-0" style="width: 150px">سعر الوحدة</th>
                                                        <th class="border-0" style="width: 150px">الإجمالي</th>
                                                        <th class="border-0" style="width: 250px">الموعد</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($itemsWithAppointments as $item)
                                                    <tr>
                                                        <td class="text-center fw-bold">{{ $loop->iteration }}</td>
                                                        <td>
                                                            <div class="d-flex align-items-center">
                                                                <div class="flex-shrink-0">
                                                                    @if($item->product->image)
                                                                        <img src="{{ asset($item->product->image) }}"
                                                                             class="product-image border"
                                                                             width="60" height="60"
                                                                             alt="{{ $item->product->name }}">
                                                                    @else
                                                                        <div class="product-image border d-flex align-items-center justify-content-center bg-light">
                                                                            <i class="fas fa-box text-muted fa-lg"></i>
                                                                        </div>
                                                                    @endif
                                                                </div>
                                                                <div class="ms-3">
                                                                    <h6 class="mb-1">{{ $item->product->name }}</h6>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td class="text-center">{{ $item->quantity }}</td>
                                                        <td>{{ $item->color ?? '-' }}</td>
                                                        <td>{{ $item->size ?? '-' }}</td>
                                                        <td>{{ number_format($item->unit_price, 2) }} ريال</td>
                                                        <td>{{ number_format($item->subtotal, 2) }} ريال</td>
                                                        <td>
                                                            @if($item->appointment)
                                                                <div class="d-flex align-items-center">
                                                                    <div class="icon-circle bg-light-primary text-primary me-2">
                                                                        <i class="fas fa-calendar-alt"></i>
                                                                    </div>
                                                                    <div>
                                                                        @if($item->appointment->appointment_date)
                                                                            <p class="mb-0">{{ $item->appointment->appointment_date->format('Y/m/d') }}</p>
                                                                        @else
                                                                            <p class="mb-0">تاريخ غير محدد</p>
                                                                        @endif
                                                                        @if($item->appointment->appointment_time)
                                                                            <p class="mb-0 small text-muted">{{ $item->appointment->appointment_time->format('g:i A') }}</p>
                                                                        @else
                                                                            <p class="mb-0 small text-muted">وقت غير محدد</p>
                                                                        @endif
                                                                    </div>
                                                                </div>
                                                            @else
                                                                <span class="text-muted">-</span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                        @endif

                                        <!-- Products without Appointments -->
                                        @if($itemsWithoutAppointments->isNotEmpty())
                                        <div class="table-responsive">
                                            <h6 class="mb-3">
                                                <i class="fas fa-shopping-bag text-primary me-2"></i>
                                                المنتجات بدون مواعيد
                                            </h6>
                                            <table class="table table-hover align-middle">
                                                <thead class="bg-light">
                                                    <tr>
                                                        <th class="border-0 text-center" style="width: 60px">#</th>
                                                        <th class="border-0" style="min-width: 250px">المنتج</th>
                                                        <th class="border-0 text-center" style="width: 100px">الكمية</th>
                                                        <th class="border-0" style="width: 100px">اللون</th>
                                                        <th class="border-0" style="width: 100px">المقاس</th>
                                                        <th class="border-0" style="width: 150px">سعر الوحدة</th>
                                                        <th class="border-0" style="width: 150px">الإجمالي</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($itemsWithoutAppointments as $item)
                                                    <tr>
                                                        <td class="text-center fw-bold">{{ $loop->iteration }}</td>
                                                        <td>
                                                            <div class="d-flex align-items-center">
                                                                <div class="flex-shrink-0">
                                                                    @if($item->product->image)
                                                                        <img src="{{ asset($item->product->image) }}"
                                                                             class="product-image border"
                                                                             width="60" height="60"
                                                                             alt="{{ $item->product->name }}">
                                                                    @else
                                                                        <div class="product-image border d-flex align-items-center justify-content-center bg-light">
                                                                            <i class="fas fa-box text-muted fa-lg"></i>
                                                                        </div>
                                                                    @endif
                                                                </div>
                                                                <div class="ms-3">
                                                                    <h6 class="mb-1">{{ $item->product->name }}</h6>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td class="text-center">{{ $item->quantity }}</td>
                                                        <td>{{ $item->color ?? '-' }}</td>
                                                        <td>{{ $item->size ?? '-' }}</td>
                                                        <td>{{ number_format($item->unit_price, 2) }} ريال</td>
                                                        <td>{{ number_format($item->subtotal, 2) }} ريال</td>
                                                    </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                        @endif

                                        @if($itemsWithAppointments->isEmpty() && $itemsWithoutAppointments->isEmpty())
                                        <div class="text-center text-muted py-4">
                                            <i class="fas fa-shopping-cart mb-2 fa-2x"></i>
                                            <p class="mb-0">لا توجد منتجات في هذا الطلب</p>
                                        </div>
                                        @endif

                                        <!-- Order Summary -->
                                        <div class="order-summary mt-4">
                                            <div class="card border-0 shadow-sm bg-light">
                                                <div class="card-body">
                                                    <h5 class="card-title mb-4 d-flex align-items-center">
                                                        <span class="icon-circle bg-primary text-white me-2">
                                                            <i class="fas fa-calculator"></i>
                                                        </span>
                                                        ملخص الطلب
                                                    </h5>
                                                    <div class="table-responsive">
                                                        <table class="table table-borderless">
                                                            <tbody>
                                                                <tr>
                                                                    <td class="text-start fw-bold">الإجمالي</td>
                                                                    <td class="text-start fw-bold">{{ number_format($order->subtotal, 2) }} ريال</td>
                                                                </tr>
                                                                @if($order->discount_amount > 0)
                                                                <tr>
                                                                    <td class="text-start fw-bold">الخصم
                                                                        @if($order->coupon_code)
                                                                        <small class="text-muted">({{ $order->coupon_code }})</small>
                                                                        @endif
                                                                    </td>
                                                                    <td class="text-start fw-bold text-danger">- {{ number_format($order->discount_amount, 2) }} ريال</td>
                                                                </tr>
                                                                @endif
                                                                <tr class="border-top">
                                                                    <td class="text-start fw-bold fs-5">الإجمالي النهائي</td>
                                                                    <td class="text-start fw-bold fs-5">{{ number_format($order->total_amount, 2) }} ريال</td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Additional Contact Information -->
                        <div class="col-12">
                            <div class="card border-0 shadow-sm">
                                <div class="card-body">
                                    <h5 class="card-title mb-4 d-flex align-items-center">
                                        <span class="icon-circle bg-primary text-white me-2">
                                            <i class="fas fa-address-book"></i>
                                        </span>
                                        معلومات الاتصال الإضافية
                                    </h5>

                                    @if($additionalAddresses->isNotEmpty())
                                    <div class="mb-4">
                                        <h6 class="mb-3">العناوين الإضافية</h6>
                                        <div class="row g-3">
                                            @foreach($additionalAddresses as $address)
                                            <div class="col-md-6">
                                                <div class="address-card bg-light p-3 rounded">
                                                    <div class="d-flex align-items-center mb-2">
                                                        <i class="fas fa-map-marker-alt text-primary me-2"></i>
                                                        <span class="fw-bold">{{ $address->type_text }}</span>
                                                    </div>
                                                    <p class="mb-0">{{ $address->full_address }}</p>
                                                </div>
                                            </div>
                                            @endforeach
                                        </div>
                                    </div>
                                    @endif

                                    @if($additionalPhones->isNotEmpty())
                                    <div>
                                        <h6 class="mb-3">أرقام الهواتف الإضافية</h6>
                                        <div class="row g-3">
                                            @foreach($additionalPhones as $phone)
                                            <div class="col-md-4">
                                                <div class="phone-card bg-light p-3 rounded">
                                                    <div class="d-flex align-items-center">
                                                        <i class="fas fa-phone text-primary me-2"></i>
                                                        <div>
                                                            <div class="fw-bold">{{ $phone->phone }}</div>
                                                            <small class="text-muted">{{ $phone->type_text }}</small>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            @endforeach
                                        </div>
                                    </div>
                                    @endif

                                    @if($additionalAddresses->isEmpty() && $additionalPhones->isEmpty())
                                    <div class="text-center text-muted py-4">
                                        <i class="fas fa-info-circle mb-2 fa-2x"></i>
                                        <p class="mb-0">لا توجد معلومات اتصال إضافية</p>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Hidden Forms for Status Updates -->
<form id="update-status-form" action="{{ route('admin.orders.update-status', $order) }}" method="POST" class="d-none">
    @csrf
    @method('PUT')
</form>

<form id="update-payment-status-form" action="{{ route('admin.orders.update-payment-status', $order) }}" method="POST" class="d-none">
    @csrf
    @method('PUT')
</form>
@endsection

@section('styles')
<link rel="stylesheet" href="{{ asset('assets/css/admin/orders.css') }}?t={{ time() }}">
@endsection
