<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Cart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Exceptions\CheckoutException;
use App\Models\Appointment;
use App\Models\Coupon;
use App\Notifications\OrderCreated;
use App\Services\Store\StorePaymentService;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CheckoutController extends Controller
{
  protected $paymentService;

  public function __construct(StorePaymentService $paymentService)
  {
    $this->paymentService = $paymentService;
  }

  public function index()
  {
    if (!Auth::check()) {
      return redirect()->route('login')
        ->with('error', 'يجب تسجيل الدخول لإتمام عملية الشراء');
    }

    $cart = Cart::with(['items.product', 'items.appointment'])
      ->where('user_id', Auth::id())
      ->first();

    if (!$cart || $cart->items->isEmpty()) {
      return redirect()->route('products.index')
        ->with('error', 'السلة فارغة');
    }

    $itemNeedsAppointment = $cart->items
      ->filter(function ($item) {
        $appointment = Appointment::where('cart_item_id', $item->id)->first();
        return $item->product->needs_appointment && is_null($appointment);
      })
      ->first();

    if ($itemNeedsAppointment) {
      return redirect()->route('appointments.create', [
        'cart_item_id' => $itemNeedsAppointment->id
      ])->with('info', 'يجب حجز موعد للمقاسات أولاً');
    }

    $coupon = null;
    $couponCode = session('coupon_code');
    if ($couponCode) {
      $coupon = Coupon::where('code', $couponCode)->first();
      if ($coupon && !$coupon->isValid()) {
        session()->forget('coupon_code');
        $coupon = null;
      }
    }

    return view('checkout.index', compact('cart', 'coupon'));
  }

  public function applyCoupon(Request $request)
  {
    $validated = $request->validate([
      'coupon_code' => ['required', 'string', 'max:50']
    ]);

    $couponCode = strtoupper($validated['coupon_code']);
    $coupon = Coupon::where('code', $couponCode)->first();

    if (!$coupon) {
      return back()->withErrors(['coupon_code' => 'كود الكوبون غير صالح']);
    }

    if (!$coupon->isValid()) {
      return back()->withErrors(['coupon_code' => 'كود الكوبون غير صالح أو منتهي الصلاحية']);
    }

    if (!Auth::check()) {
      return redirect()->route('login')
        ->with('error', 'يجب تسجيل الدخول لتطبيق الكوبون');
    }

    if ($coupon->hasBeenUsedByCurrentUser('App\\Models\\Order')) {
      return back()->withErrors(['coupon_code' => 'لقد قمت باستخدام هذا الكوبون مسبقًا']);
    }

    $cart = Cart::with(['items.product'])
      ->where('user_id', Auth::id())
      ->first();

    if (!$cart || $cart->items->isEmpty()) {
      return back()->withErrors(['coupon_code' => 'لا يمكن تطبيق الكوبون على سلة فارغة']);
    }

    if (!$coupon->applies_to_all_products) {
      $canApply = false;
      foreach ($cart->items as $item) {
        if ($coupon->appliesTo($item->product_id)) {
          $canApply = true;
          break;
        }
      }

      if (!$canApply) {
        return back()->withErrors(['coupon_code' => 'هذا الكوبون لا ينطبق على المنتجات الموجودة في السلة']);
      }
    }

    if (floatval($cart->total_amount) < floatval($coupon->min_order_amount)) {
      return back()->withErrors([
        'coupon_code' => sprintf(
          'الحد الأدنى للطلب لتطبيق هذا الكوبون هو %s ر.س',
          number_format($coupon->min_order_amount, 2)
        )
      ]);
    }

    session(['coupon_code' => $couponCode]);

    return back()->with('success', 'تم تطبيق الكوبون بنجاح');
  }

  public function removeCoupon()
  {
    session()->forget('coupon_code');
    return back()->with('success', 'تم إزالة الكوبون');
  }

  public function validateCouponApi(Request $request)
  {
    $code = $request->input('coupon_code');
    if (!$code) {
      return response()->json([
        'valid' => false,
        'message' => 'يرجى إدخال كود الكوبون'
      ]);
    }

    $couponCode = strtoupper($code);
    $coupon = Coupon::where('code', $couponCode)->first();

    if (!$coupon) {
      return response()->json([
        'valid' => false,
        'message' => 'كود الكوبون غير صالح'
      ]);
    }

    if (!$coupon->isValid()) {
      return response()->json([
        'valid' => false,
        'message' => 'كود الكوبون غير صالح أو منتهي الصلاحية'
      ]);
    }

    if (!Auth::check()) {
      return response()->json([
        'valid' => false,
        'message' => 'يجب تسجيل الدخول لاستخدام الكوبون'
      ]);
    }

    if ($coupon->hasBeenUsedByCurrentUser('App\\Models\\Order')) {
      return response()->json([
        'valid' => false,
        'message' => 'لقد قمت باستخدام هذا الكوبون مسبقًا'
      ]);
    }

    $cart = Cart::with(['items.product'])
      ->where('user_id', Auth::id())
      ->first();

    if (!$cart || $cart->items->isEmpty()) {
      return response()->json([
        'valid' => false,
        'message' => 'لا يمكن تطبيق الكوبون على سلة فارغة'
      ]);
    }

    if (!$coupon->applies_to_all_products) {
      $canApply = false;
      foreach ($cart->items as $item) {
        if ($coupon->appliesTo($item->product_id)) {
          $canApply = true;
          break;
        }
      }

      if (!$canApply) {
        return response()->json([
          'valid' => false,
          'message' => 'هذا الكوبون لا ينطبق على المنتجات الموجودة في السلة'
        ]);
      }
    }

    \Illuminate\Support\Facades\Log::info('Coupon validation debug', [
      'cart_subtotal' => $cart->total_amount,
      'coupon_min_amount' => $coupon->min_order_amount,
      'comparison' => ($cart->total_amount >= $coupon->min_order_amount)
    ]);

    if (floatval($cart->total_amount) < floatval($coupon->min_order_amount)) {
      return response()->json([
        'valid' => false,
        'message' => sprintf(
          'الحد الأدنى للطلب لتطبيق هذا الكوبون هو %s ر.س',
          number_format($coupon->min_order_amount, 2)
        )
      ]);
    }

    $discountAmount = $coupon->calculateDiscount($cart->total_amount);

    session(['coupon_code' => $couponCode]);

    return response()->json([
      'valid' => true,
      'coupon' => [
        'id' => $coupon->id,
        'code' => $coupon->code,
        'type' => $coupon->type,
        'value' => $coupon->value
      ],
      'discount_amount' => $discountAmount,
      'message' => 'تم تطبيق الكوبون بنجاح'
    ]);
  }

  public function store(Request $request)
  {
    try {
      if (!Auth::check()) {
        throw new CheckoutException('يجب تسجيل الدخول لإتمام عملية الشراء');
      }

      $cart = Cart::where('user_id', Auth::id())
        ->with(['items.product'])
        ->first();

      if (!$cart || $cart->items->isEmpty()) {
        throw new CheckoutException('السلة فارغة');
      }

      $itemNeedsAppointment = $cart->items
        ->filter(function ($item) {
          $appointment = Appointment::where('cart_item_id', $item->id)->first();
          return $item->product->needs_appointment && is_null($appointment);
        })
        ->first();

      if ($itemNeedsAppointment) {
        throw new CheckoutException('يجب حجز موعد للمقاسات أولاً');
      }

      $validated = $request->validate([
        'shipping_address' => ['required', 'string', 'max:500'],
        'phone' => ['required', 'string', 'max:20', 'regex:/^([0-9\s\-\+\(\)]*)$/'],
        'notes' => ['nullable', 'string', 'max:1000'],
        'payment_method' => ['required', 'in:cash,online,tabby,paytabs'],
        'policy_agreement' => ['required', 'accepted']
      ]);

      return DB::transaction(function () use ($request, $validated, $cart) {
        foreach ($cart->items as $item) {
          if ($item->product->stock < $item->quantity) {
            throw new CheckoutException("الكمية المطلوبة غير متوفرة من {$item->product->name}");
          }
        }

        $totalAmount = floatval($cart->total_amount);
        $discountAmount = 0;
        $coupon = null;
        $couponId = null;
        $couponCode = null;

        $couponCode = session('coupon_code');
        if ($couponCode) {
          $coupon = Coupon::where('code', $couponCode)->first();
          if ($coupon && $coupon->isValid() && floatval($totalAmount) >= floatval($coupon->min_order_amount)) {
            $discountAmount = floatval($coupon->calculateDiscount($totalAmount));
            $totalAmount -= $discountAmount;
            $couponId = $coupon->id;
          } else {
            $coupon = null;
            $couponCode = null;
            session()->forget('coupon_code');
          }
        }

        if ($validated['payment_method'] === 'cash') {
          $orderData = [
            'user_id' => Auth::id(),
            'total_amount' => floatval($totalAmount),
            'subtotal' => floatval($cart->total_amount),
            'discount_amount' => floatval($discountAmount),
            'coupon_id' => $couponId,
            'coupon_code' => $couponCode,
            'shipping_address' => $validated['shipping_address'],
            'phone' => $validated['phone'],
            'payment_method' => 'cash',
            'payment_status' => Order::PAYMENT_STATUS_PENDING,
            'order_status' => Order::ORDER_STATUS_PENDING,
            'notes' => $validated['notes'] ?? null,
            'policy_agreement' => true,
            'amount_paid' => 0
          ];

          $order = Order::create($orderData);

          foreach ($cart->items as $item) {
            $appointment = Appointment::where('cart_item_id', $item->id)->first();

            $orderItem = $order->items()->create([
              'product_id' => $item->product_id,
              'quantity' => $item->quantity,
              'unit_price' => $item->unit_price,
              'subtotal' => $item->subtotal,
              'appointment_id' => $appointment ? $appointment->id : null,
              'color' => $item->color,
              'size' => $item->size
            ]);

            if ($appointment) {
              $appointment->update([
                'status' => Appointment::STATUS_PENDING,
                'order_item_id' => $orderItem->id
              ]);
            }
          }

          $cart->items()->delete();
          $cart->delete();

          if ($coupon) {
            $coupon->recordUsageByUser(Auth::id(), $order);
            session()->forget('coupon_code');
          }

          $order->user->notify(new OrderCreated($order));

          return redirect()->route('orders.show', $order)
            ->with('success', 'تم إنشاء الطلب بنجاح');
        }

        $paymentId = 'ORDER-' . strtoupper(Str::random(8)) . '-' . time();

        $orderItems = [];
        foreach ($cart->items as $item) {
          $appointment = Appointment::where('cart_item_id', $item->id)->first();
          $orderItems[] = [
            'product_id' => $item->product_id,
            'quantity' => $item->quantity,
            'unit_price' => $item->unit_price,
            'subtotal' => $item->subtotal,
            'appointment_id' => $appointment ? $appointment->id : null,
            'color' => $item->color,
            'size' => $item->size
          ];
        }

        // إنشاء الطلب مباشرة قبل إرسال الطلب إلى تابي
        $orderData = [
          'user_id' => Auth::id(),
          'total_amount' => floatval($totalAmount),
          'subtotal' => floatval($cart->total_amount),
          'discount_amount' => floatval($discountAmount),
          'coupon_id' => $couponId,
          'coupon_code' => $couponCode,
          'shipping_address' => $validated['shipping_address'],
          'phone' => $validated['phone'],
          'payment_method' => $validated['payment_method'],
          'payment_status' => Order::PAYMENT_STATUS_PENDING,
          'order_status' => Order::ORDER_STATUS_PENDING,
          'notes' => $validated['notes'] ?? null,
          'payment_id' => $paymentId,
          'policy_agreement' => true,
          'amount_paid' => 0
        ];

        // إنشاء الطلب في قاعدة البيانات مباشرة
        $order = Order::create($orderData);

        // إنشاء عناصر الطلب
        foreach ($cart->items as $item) {
          $appointment = Appointment::where('cart_item_id', $item->id)->first();

          $orderItem = $order->items()->create([
            'product_id' => $item->product_id,
            'quantity' => $item->quantity,
            'unit_price' => $item->unit_price,
            'subtotal' => $item->subtotal,
            'appointment_id' => $appointment ? $appointment->id : null,
            'color' => $item->color,
            'size' => $item->size
          ]);

          if ($appointment) {
            $appointment->update([
              'status' => Appointment::STATUS_PENDING,
              'order_item_id' => $orderItem->id
            ]);
          }
        }

        // حفظ معلومات الطلب في الجلسة للاستخدام لاحقاً
        $paymentRequestData = [
          'user_id' => Auth::id(),
          'payment_id' => $paymentId,
          'total_amount' => floatval($totalAmount),
          'subtotal' => floatval($cart->total_amount),
          'discount_amount' => floatval($discountAmount),
          'coupon_id' => $couponId,
          'coupon_code' => $couponCode,
          'shipping_address' => $validated['shipping_address'],
          'phone' => $validated['phone'],
          'payment_method' => $validated['payment_method'],
          'notes' => $validated['notes'] ?? null,
          'items' => $orderItems
        ];

        // تخزين معرف الطلب لاستخدامه لاحقاً
        session(['pending_order_id' => $order->id]);
        session(['pending_order' => $paymentRequestData]);

        $paymentResult = $this->paymentService->initiatePayment($paymentRequestData, floatval($totalAmount), Auth::user());

        if ($paymentResult['success'] && !empty($paymentResult['redirect_url'])) {
          // حذف العناصر من السلة بعد إنشاء الطلب
          $cart->items()->delete();
          $cart->delete();

          // تسجيل استخدام الكوبون إذا تم استخدامه
          if ($coupon) {
            $coupon->recordUsageByUser(Auth::id(), $order);
            session()->forget('coupon_code');
          }

          // تخزين معرف المعاملة في الجلسة
          session(['payment_transaction_id' => $paymentResult['transaction_id']]);

          // تحديث معرف المعاملة في سجل الطلب
          $order->update(['payment_transaction_id' => $paymentResult['transaction_id']]);

          // إعادة التوجيه إلى بوابة الدفع
          return redirect($paymentResult['redirect_url']);
        }

        // في حالة الفشل، حذف الطلب المنشأ
        $order->delete();

        throw new CheckoutException('فشل الاتصال ببوابة الدفع: ' . ($paymentResult['message'] ?? 'خطأ غير معروف'));
      });
    } catch (ValidationException $e) {
      return back()->withErrors($e->errors())->withInput();
    } catch (CheckoutException $e) {
      return back()
        ->withInput()
        ->withErrors(['error' => $e->getMessage()]);
    } catch (\Exception $e) {
      return back()
        ->withInput()
        ->withErrors(['error' => 'حدث خطأ غير متوقع. الرجاء المحاولة مرة أخرى أو الاتصال بالدعم الفني.']);
    }
  }

  public function paymentCallback(Request $request)
  {
    try {
      $paymentData = $this->paymentService->processPaymentResponse($request);

      // إذا كان هناك معرف طلب معلق في الجلسة، استخدمه للعثور على الطلب
      $order = null;
      $orderId = session('pending_order_id');

      if ($orderId) {
        $order = Order::find($orderId);
      }

      // إذا لم يتم العثور على الطلب من خلال معرف الجلسة، نحاول العثور عليه عن طريق معرف الدفع
      if (!$order) {
        $order = $this->paymentService->findExistingOrder($paymentData);
      }

      if ($order) {
        $this->paymentService->updateOrderPaymentStatus($order, $paymentData);

        // مسح معلومات الجلسة بعد معالجة الدفع
        session()->forget(['pending_order', 'pending_order_id', 'payment_transaction_id', 'coupon_code']);

        return redirect()->route('orders.show', $order)
          ->with('success', 'تم تأكيد الدفع بنجاح!');
      }

      if (!$paymentData['isSuccessful'] && !$paymentData['isPending']) {
        session()->forget(['pending_order', 'pending_order_id', 'payment_transaction_id', 'coupon_code']);

        return redirect()->route('checkout.index')
          ->with('error', 'فشل الدفع: ' . ($paymentData['message'] ?: 'خطأ غير معروف'));
      }

      // هذا القسم لن يتم تنفيذه عادة لأن الطلب تم إنشاؤه بالفعل
      $orderData = session('pending_order');
      if (!$orderData) {
        return redirect()->route('checkout.index')
          ->with('error', 'خطأ في الدفع - لم يتم العثور على بيانات الطلب');
      }

      $order = $this->paymentService->createOrderFromPayment($orderData, $paymentData);

      // مسح معلومات الجلسة
      session()->forget(['pending_order', 'pending_order_id', 'payment_transaction_id', 'coupon_code']);

      $order->user->notify(new OrderCreated($order));

      $message = $paymentData['isSuccessful']
        ? 'تم تأكيد الدفع وإنشاء الطلب بنجاح!'
        : 'تم إنشاء الطلب، جارٍ التحقق من حالة الدفع...';

      return redirect()->route('orders.show', $order)
        ->with('success', $message);

    } catch (\Exception $e) {
      Log::error('Error processing payment callback: ' . $e->getMessage(), [
        'exception' => $e,
        'request_data' => $request->all()
      ]);

      return redirect()->route('checkout.index')
        ->with('error', 'حدث خطأ أثناء معالجة الدفع. الرجاء الاتصال بالدعم الفني.');
    }
  }

  public function paymentReturn(Request $request)
  {
    try {
      Log::info('PayTabs payment return received', [
        'data' => $request->all(),
        'query' => $request->query(),
        'headers' => $request->header(),
        'method' => $request->method(),
        'transaction_id' => session('payment_transaction_id')
      ]);

      // تمرير الطلب للتعامل مع كلا نوعي الاستجابة (redirect و webhook)
      return $this->paymentCallback($request);
    } catch (\Exception $e) {
      Log::error('Error processing payment return', [
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
        'data' => $request->all()
      ]);

      return redirect()->route('checkout.index')
        ->with('error', 'حدث خطأ أثناء معالجة الدفع. الرجاء الاتصال بالدعم الفني.');
    }
  }

  public function paytabsWebhook(Request $request)
  {
    try {
      Log::info('PayTabs webhook received', ['data' => $request->all()]);

      // تخزين الطلب في الجلسة مؤقتًا للرجوع إليه في حالة فشل المعالجة
      session(['paytabs_webhook_data' => $request->all()]);

      $paymentData = $this->paymentService->processPaymentResponse($request);

      $order = $this->paymentService->findExistingOrder($paymentData);

      if (!$order) {
        return response()->json(['status' => 'error', 'message' => 'Order not found'], 404);
      }

      $this->paymentService->updateOrderPaymentStatus($order, $paymentData);

      return response()->json(['status' => 'success']);
    } catch (\Exception $e) {
      Log::error('Error processing PayTabs webhook', [
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
        'data' => $request->all()
      ]);

      return response()->json(['status' => 'error', 'message' => 'Server error'], 500);
    }
  }

  public function tabbyWebhook(Request $request)
  {
    try {
        Log::info('Tabby webhook received', ['data' => $request->all()]);

        $paymentId = $request->input('id');
        $status = $request->input('status');
        $merchantReference = $request->input('merchant_reference_id');

        if (empty($paymentId) || empty($status)) {
            return response()->json(['status' => 'error', 'message' => 'Invalid webhook data'], 400);
        }

        $order = Order::where('payment_transaction_id', $paymentId)
            ->orWhere('payment_id', $merchantReference)
            ->first();

        if (!$order) {
            return response()->json(['status' => 'error', 'message' => 'Order not found'], 404);
        }

        switch ($status) {
            case 'AUTHORIZED':
            case 'CLOSED':
            case 'CAPTURED':
                $order->update([
                    'payment_status' => Order::PAYMENT_STATUS_PAID,
                    'order_status' => Order::ORDER_STATUS_PROCESSING,
                    'amount_paid' => $request->input('amount') ?? $order->total_amount
                ]);

                // Make sure coupon details are recorded
                if ($order->coupon_id && !$order->discount_amount) {
                    $coupon = Coupon::find($order->coupon_id);
                    if ($coupon) {
                        $discount = $coupon->calculateDiscount($order->subtotal);
                        $order->update([
                            'discount_amount' => $discount
                        ]);
                    }
                }

                break;
            case 'CANCELED':
            case 'REJECTED':
            case 'EXPIRED':
                $order->update([
                    'payment_status' => Order::PAYMENT_STATUS_FAILED,
                    'order_status' => Order::ORDER_STATUS_FAILED
                ]);
                break;
            default:
                break;
        }

        return response()->json(['status' => 'success']);
    } catch (\Exception $e) {
        Log::error('Error processing Tabby webhook', [
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json(['status' => 'error', 'message' => 'Server error'], 500);
    }
  }

  public function tabbyTest(Request $request)
  {
    $testType = $request->get('test_type', 'success');

    switch ($testType) {
      case 'rejection':
        session(['tabby_test_email' => 'otp.rejected@tabby.ai']);
        session(['tabby_test_phone' => '+966500000001']);
        return redirect()->route('checkout.index')
          ->with('info', 'Testing Tabby rejection flow. Complete checkout to test.');

      case 'pre_scoring_reject':
        session(['tabby_test_email' => 'otp.success@tabby.ai']);
        session(['tabby_test_phone' => '+966500000002']);
        return redirect()->route('checkout.index')
          ->with('info', 'Testing Tabby pre-scoring rejection flow. Complete checkout to test.');

      case 'success':
      default:
        session(['tabby_test_email' => 'otp.success@tabby.ai']);
        session(['tabby_test_phone' => '+966500000001']);
        return redirect()->route('checkout.index')
          ->with('info', 'Testing Tabby success flow. Complete checkout to test.');
    }
  }
}
