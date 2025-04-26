@extends('layouts.customer')

@section('title', 'سلة التسوق')

@section('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
<link rel="stylesheet" href="{{ asset('assets/css/customer/cart.css') }}?t={{ time() }}">
<style>
  .appointment-alert {
    border-right: 4px solid #ffc107;
    background-color: #fff8e1;
  }

  .alert-warning {
    border-right: 4px solid #ffc107;
  }

  .alert-warning .alert-heading {
    color: #856404;
    font-size: 1.1rem;
  }
</style>
@endsection

@section('content')
<div class="container-fluid py-4">
  <div id="alerts-container"></div>

  <div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="page-title mb-0">سلة التسوق</h2>
    <span class="text-muted">{{ $cart_items_count ?? 0 }} منتجات</span>
  </div>

  <div class="cart-container">
    @if(isset($cart_items) && count($cart_items) > 0)
    <div class="row">
      <div class="col-lg-8">
        @foreach($cart_items as $item)
        @php
          $needsAppointment = $item->needs_appointment && !$item->appointment()->exists();
          $itemPrice = $item->unit_price;
          $itemSubtotal = $item->subtotal;
        @endphp
        <div class="cart-item d-flex gap-3" data-item-id="{{ $item->id }}">
          @php
          // Get any available image for the product, not just primary
          $productImage = $item->product->images->first();
          $imagePath = $productImage ? url('storage/' . $productImage->image_path) : url('images/no-image.png');
          @endphp
          <img src="{{ $imagePath }}" alt="{{ $item->product->name }}" class="cart-item-image">
          <div class="cart-item-details">
            <div class="d-flex justify-content-between align-items-start">
              <div>
                <h5 class="cart-item-title">{{ $item->product->name }}</h5>
                <div class="cart-item-meta">
                  @if($item->product->category)
                  <span class="me-2">{{ $item->product->category->name }}</span>
                  @endif
                  @if($item->size)
                  <span class="me-2">المقاس: {{ $item->size }}</span>
                  @endif
                  @if($item->color)
                  <span>اللون: {{ $item->color }}</span>
                  @endif
                  @if($needsAppointment)
                  <div class="alert alert-warning mt-2 mb-0 appointment-alert">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <strong>مطلوب حجز موعد:</strong> يرجى الضغط على الزر أدناه لحجز موعد لأخذ المقاسات
                    <a href="{{ route('products.show', $item->product) }}?pending_appointment={{ $item->id }}"
                       class="btn btn-warning ms-2 mt-2 d-block">
                      <i class="bi bi-calendar-plus me-1"></i>
                      اضغط هنا لحجز موعد
                    </a>
                  </div>
                  @endif
                </div>
              </div>
              <button type="button" class="remove-item" onclick="removeCartItem({{ $item->id }})">
                <i class="bi bi-x-circle"></i>
              </button>
            </div>
            <div class="d-flex justify-content-between align-items-center mt-3">
              <div class="quantity-control">
                <button type="button" class="quantity-btn decrease" onclick="updateQuantity({{ $item->id }}, -1)">
                  <i class="bi bi-dash"></i>
                </button>
                <input type="number" value="{{ $item->quantity }}" min="1" class="quantity-input"
                       onchange="updateQuantity({{ $item->id }}, 0, this.value)">
                <button type="button" class="quantity-btn increase" onclick="updateQuantity({{ $item->id }}, 1)">
                  <i class="bi bi-plus"></i>
                </button>
              </div>
              <div class="cart-item-price">
                <div class="unit-price">{{ number_format($itemPrice, 2) }} ريال</div>
                <div class="subtotal" id="price-{{ $item->id }}">
                  {{ number_format($itemSubtotal, 2) }} ريال
                </div>
              </div>
            </div>
          </div>
        </div>
        @endforeach
      </div>

      <div class="col-lg-4">
        @php
          $hasItemsNeedingAppointment = $cart_items->contains(function($item) {
              return $item->needs_appointment && !$item->appointment()->exists();
          });
        @endphp
        @if($hasItemsNeedingAppointment)
          <div class="alert alert-warning mb-4">
            <h5 class="alert-heading d-flex align-items-center">
              <i class="bi bi-exclamation-triangle me-2"></i>
              تنبيه هام
            </h5>
            <p class="mb-0">يوجد منتجات تحتاج لحجز موعد لأخذ المقاسات. يرجى حجز المواعيد أولاً قبل متابعة الشراء</p>
            <button class="btn btn-warning mt-3" onclick="scrollToAppointmentItem()">
              <i class="bi bi-arrow-down me-1"></i>
              عرض المنتج المطلوب له موعد
            </button>
          </div>
          <script>
            function scrollToAppointmentItem() {
              const appointmentAlert = document.querySelector('.appointment-alert');
              if (appointmentAlert) {
                appointmentAlert.scrollIntoView({ behavior: 'smooth' });
                appointmentAlert.querySelector('.btn-warning').focus();
              }
            }
          </script>
        @endif
        <div class="cart-summary">
          <h4 class="mb-4">ملخص الطلب</h4>
          <div class="summary-item">
            <span class="summary-label">إجمالي المنتجات</span>
            <span class="summary-value" id="subtotal">{{ number_format($subtotal, 2) }} ريال</span>
          </div>
          <div class="summary-item">
            <span class="summary-label">الإجمالي الكلي</span>
            <span class="total-amount" id="total">{{ number_format($total, 2) }} ريال</span>
          </div>
          @if($hasItemsNeedingAppointment)
            <button class="btn btn-primary checkout-btn w-100" disabled>
              متابعة الشراء
            </button>
            <div class="text-danger text-center mt-2">
              <small>يجب حجز جميع المواعيد المطلوبة قبل متابعة الشراء</small>
            </div>
          @else
            <a href="{{ route('checkout.index') }}" class="btn btn-primary checkout-btn w-100">
              متابعة الشراء
            </a>
          @endif
          <div class="continue-shopping mt-3">
            <a href="{{ route('products.index') }}">
              <i class="bi bi-arrow-right"></i>
              متابعة التسوق
            </a>
          </div>
        </div>
      </div>
    </div>
    @else
    <div class="empty-cart">
      <div class="empty-cart-icon">
        <i class="bi bi-cart-x"></i>
      </div>
      <h3>السلة فارغة</h3>
      <p>لم تقم بإضافة أي منتجات إلى سلة التسوق بعد</p>
      <a href="{{ route('products.index') }}" class="btn btn-primary">
        تصفح المنتجات
      </a>
    </div>
    @endif
  </div>
</div>

@endsection

@section('scripts')
<script src="{{ asset('assets/js/customer/products-show.js') }}?t={{ time() }}"></script>
<script>
function showAlert(message, type = 'success') {
    const alertsContainer = document.getElementById('alerts-container');
    const alert = document.createElement('div');
    alert.className = `alert alert-${type} alert-dismissible fade show`;
    alert.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    alertsContainer.appendChild(alert);

    // Auto hide after 3 seconds
    setTimeout(() => {
        alert.classList.remove('show');
        setTimeout(() => alert.remove(), 150);
    }, 3000);
}

function formatPrice(price) {
    return new Intl.NumberFormat('ar-SA', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(price) + ' ريال';
}

function updateQuantity(itemId, change, newValue = null) {
    const input = document.querySelector(`[data-item-id="${itemId}"] .quantity-input`);
    const currentValue = parseInt(input.value);
    let quantity = newValue !== null ? parseInt(newValue) : currentValue + change;

    if (quantity < 1) return;

    const cartItem = document.querySelector(`[data-item-id="${itemId}"]`);
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
            input.value = quantity;

            // تحديث السعر الفرعي للمنتج
            document.getElementById(`price-${itemId}`).textContent = formatPrice(data.item_subtotal);

            // تحديث إجمالي السلة
            document.getElementById('total').textContent = formatPrice(data.cart_total);
            document.getElementById('subtotal').textContent = formatPrice(data.cart_total);

            showAlert('تم تحديث الكمية بنجاح');
        } else {
            input.value = currentValue;
            showAlert(data.message, 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        input.value = currentValue;
        showAlert('حدث خطأ أثناء تحديث الكمية', 'danger');
    })
    .finally(() => {
        cartItem.style.opacity = '1';
    });
}

function removeCartItem(itemId) {
    if (!confirm('هل أنت متأكد من حذف هذا المنتج من السلة؟')) {
        return;
    }

    const cartItem = document.querySelector(`[data-item-id="${itemId}"]`);
    cartItem.style.opacity = '0.5';

    fetch(`/cart/items/${itemId}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            cartItem.style.transform = 'translateX(100px)';
            cartItem.style.opacity = '0';

            setTimeout(() => {
                cartItem.remove();

                // تحديث إجمالي السلة
                document.getElementById('total').textContent = formatPrice(data.cart_total);
                document.getElementById('subtotal').textContent = formatPrice(data.cart_total);

                // إذا أصبحت السلة فارغة
                if (data.cart_count === 0) {
                    location.reload();
                }
            }, 300);

            showAlert('تم حذف المنتج من السلة بنجاح');
        } else {
            cartItem.style.opacity = '1';
            showAlert(data.message, 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        cartItem.style.opacity = '1';
        showAlert('حدث خطأ أثناء حذف المنتج', 'danger');
    });
}
</script>
@endsection
