@extends('layouts.customer')

@section('title', 'تفاصيل الطلب #' . $order->order_number)

@section('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
<link rel="stylesheet" href="{{ asset('assets/css/customer/orders.css') }}?t={{ time() }}">
<style>
    .price-summary {
        background-color: #ffe5f1;
        border-radius: 15px;
        padding: 20px;
        margin-top: 25px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
    }

    .price-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .price-column {
        flex: 1;
        padding: 0 15px;
        text-align: center;
        position: relative;
    }

    .price-column:not(:last-child):after {
        content: "";
        position: absolute;
        left: 0;
        top: 50%;
        transform: translateY(-50%);
        height: 80%;
        width: 1px;
        background-color: #FF99C2;
    }

    .price-label {
        font-size: 1.1rem;
        font-weight: 700;
        color: #FF1493;
        margin-bottom: 15px;
    }

    .price-value {
        font-size: 1.7rem;
        font-weight: 700;
        color: #FF1493;
    }

    .discount-column .price-value {
        color: #0ca678;
    }
</style>
@endsection

@section('content')
<header class="header-container">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="page-title">تفاصيل الطلب #{{ $order->order_number }}</h2>
            <a href="{{ route('orders.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-right"></i>
                العودة للطلبات
            </a>
        </div>
    </div>
</header>

<main class="container py-4">
    <div class="order-card">
        <div class="order-header">
            <div class="status-section">
                <h3 class="section-title">حالة الطلب</h3>
                <span class="status-badge status-{{ $order->order_status }}">
                    {{ match($order->order_status) {
                        'completed' => 'مكتمل',
                        'cancelled' => 'ملغي',
                        'processing' => 'قيد المعالجة',
                        'pending' => 'قيد الانتظار',
                        'out_for_delivery' => 'جاري التوصيل',
                        'on_the_way' => 'في الطريق',
                        'delivered' => 'تم التوصيل',
                        'returned' => 'مرتجع',
                        default => 'غير معروف'
                    } }}
                </span>
            </div>
            <div class="order-info mt-3">
                <p class="order-date">تاريخ الطلب: {{ $order->created_at->format('Y/m/d') }}</p>
            </div>
            @if($order->notes)
            <div class="order-notes mt-3">
                <h4>ملاحظات:</h4>
                <p>{{ $order->notes }}</p>
            </div>
            @endif
        </div>

        <div class="order-details">
            <div class="row">
                <!-- معلومات الشحن -->
                <div class="col-md-6">
                    <div class="info-group">
                        <h3 class="section-title">معلومات الشحن</h3>
                        <div class="shipping-info">
                            <div class="info-item">
                                <span class="info-label">العنوان:</span>
                                <span class="info-value">{{ $order->shipping_address }}</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">رقم الهاتف:</span>
                                <span class="info-value">{{ $order->phone }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ملخص الطلب -->
                <div class="col-md-6">
                    <div class="info-group">
                        <h3 class="section-title">ملخص الطلب</h3>
                        <div class="order-items">
                            @foreach($order->items as $item)
                            <div class="order-item">
                                @if($item->product->images->first())
                                <img src="{{ url('storage/' . $item->product->images->first()->image_path) }}"
                                    alt="{{ $item->product->name }}"
                                    class="item-image">
                                @endif
                                <div class="item-details">
                                    <h4 class="item-name">{{ $item->product->name }}</h4>
                                    <p class="item-price">
                                        {{ $item->unit_price }} ريال × {{ $item->quantity }}
                                    </p>
                                    @if($item->color || $item->size)
                                    <p class="item-options">
                                        @if($item->color)
                                        <span class="item-color">اللون: {{ $item->color }}</span>
                                        @endif
                                        @if($item->size)
                                        <span class="item-size">المقاس: {{ $item->size }}</span>
                                        @endif
                                    </p>
                                    @endif
                                    <p class="item-subtotal">
                                        الإجمالي: {{ $item->subtotal }} ريال
                                    </p>
                                </div>
                            </div>
                            @endforeach
                        </div>

                        <div class="price-summary">
                            <div class="price-row">
                                <div class="price-column">
                                    <div class="price-label">الإجمالي بعد الخصم:</div>
                                    <div class="price-value">{{ $order->total_amount }} ريال</div>
                                </div>

                                <div class="price-column discount-column">
                                    <div class="price-label">الخصم:</div>
                                    <div class="price-value">{{ $order->discount_amount ?? 0 }} ريال</div>
                                </div>

                                <div class="price-column">
                                    <div class="price-label">الإجمالي الفرعي:</div>
                                    <div class="price-value">{{ $order->subtotal ?? $order->total_amount }} ريال</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- تتبع الطلب -->
        <div class="order-tracking mt-5 p-4">
            <h3 class="tracking-title text-center mb-4">تتبع الطلب</h3>

            <div class="tracking-stepper">
                <div class="tracking-step {{ $order->order_status != 'pending' ? 'completed' : '' }}">
                    <div class="step-icon">
                        <i class="bi bi-check-circle-fill"></i>
                    </div>
                    <div class="step-line"></div>
                    <div class="step-content">
                        <h4>تم استلام الطلب</h4>
                        <p>تم استلام طلبك وهو قيد المراجعة</p>
                    </div>
                </div>

                <div class="tracking-step {{ in_array($order->order_status, ['processing', 'out_for_delivery', 'on_the_way', 'delivered', 'completed']) ? 'completed' : '' }}">
                    <div class="step-icon">
                        <i class="bi bi-gear-fill"></i>
                    </div>
                    <div class="step-line"></div>
                    <div class="step-content">
                        <h4>قيد المعالجة</h4>
                        <p>جاري تجهيز طلبك</p>
                    </div>
                </div>

                <div class="tracking-step {{ in_array($order->order_status, ['out_for_delivery', 'on_the_way', 'delivered', 'completed']) ? 'completed' : '' }}">
                    <div class="step-icon">
                        <i class="bi bi-box-seam-fill"></i>
                    </div>
                    <div class="step-line"></div>
                    <div class="step-content">
                        <h4>جاري التوصيل</h4>
                        <p>تم تجهيز طلبك للتوصيل</p>
                    </div>
                </div>

                <div class="tracking-step {{ in_array($order->order_status, ['on_the_way', 'delivered', 'completed']) ? 'completed' : '' }}">
                    <div class="step-icon">
                        <i class="bi bi-truck"></i>
                    </div>
                    <div class="step-line"></div>
                    <div class="step-content">
                        <h4>في الطريق</h4>
                        <p>المندوب في طريقه إليك</p>
                    </div>
                </div>

                <div class="tracking-step {{ in_array($order->order_status, ['delivered', 'completed']) ? 'completed' : '' }}">
                    <div class="step-icon">
                        <i class="bi bi-house-check-fill"></i>
                    </div>
                    <div class="step-content">
                        <h4>تم التوصيل</h4>
                        <p>تم توصيل طلبك بنجاح</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
@endsection
