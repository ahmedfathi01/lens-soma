<?php

namespace App\Services\Booking;

use App\Models\Booking;
use App\Models\User;
use App\Services\Payment\BookingTabbyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PaymentService
{
    /**
     * خدمة تابي للدفع
     */
    protected $tabbyService;

    /**
     * إنشاء كائن جديد من خدمة الدفع
     */
    public function __construct(BookingTabbyService $tabbyService)
    {
        $this->tabbyService = $tabbyService;
    }

    /**
     * تحضير بيانات الدفع وإنشاء طلب دفع جديد
     *
     * @param array $bookingData بيانات الحجز
     * @param float $amount المبلغ المطلوب دفعه
     * @param User $user بيانات المستخدم
     * @return array نتائج عملية إنشاء طلب الدفع
     */
    public function initiatePayment(array $bookingData, float $amount, User $user): array
    {
        // توليد معرف دفع فريد
        $paymentId = $bookingData['payment_id'] ?? 'PAY-' . strtoupper(Str::random(8)) . '-' . time();
        $bookingData['payment_id'] = $paymentId;

        // تحضير بيانات العميل
        $customerData = $this->tabbyService->prepareCustomerDetails([
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'address' => $user->address ?? null,
            'city' => $user->city ?? null,
            'state' => $user->state ?? null,
            'country' => 'SA'
        ]);

        // إنشاء وصف للمعاملة
        if (isset($bookingData['package_name'])) {
            $bookingData['description'] = 'Photography Session - ' . $bookingData['package_name'];
        }

        // تحضير عناصر الطلب لـ Tabby
        $items = [];
        if (isset($bookingData['package_id'])) {
            $items[] = [
                'product_id' => $bookingData['package_id'],
                'quantity' => 1,
                'unit_price' => $amount,
                'subtotal' => $amount
            ];
        }

        $bookingData['items'] = $items;

        // Add shipping address explicitly
        $bookingData['shipping_address'] = $user->address ?? 'Photography Studio Address';

        // إنشاء طلب دفع جديد عبر تابي
        $response = $this->tabbyService->createPaymentRequest($bookingData, $amount, $customerData);

        if (!empty($response['success']) && !empty($response['data']['redirect_url'])) {
            // حفظ معرف المعاملة في الجلسة
            session(['payment_transaction_id' => $response['data']['tran_ref'] ?? null]);

            return [
                'success' => true,
                'redirect_url' => $response['data']['redirect_url'],
                'transaction_id' => $response['data']['tran_ref'] ?? null,
                'payment_id' => $paymentId
            ];
        }

        // إعداد رسالة الخطأ
        $error = $response['error'] ?? ['message' => 'فشل الاتصال ببوابة الدفع'];
        Log::error('فشل في بدء عملية الدفع', [
            'booking_data' => $bookingData,
            'amount' => $amount,
            'user_id' => $user->id,
            'error' => $error
        ]);

        return [
            'success' => false,
            'message' => $error['message'] ?? 'فشل الاتصال ببوابة الدفع',
            'error' => $error
        ];
    }

    /**
     * معالجة استجابة الدفع من بوابة الدفع
     *
     * @param Request $request الطلب الحالي
     * @return array نتائج معالجة الدفع
     */
    public function processPaymentResponse(Request $request): array
    {
        // استخراج بيانات الدفع
        $paymentData = $this->tabbyService->extractPaymentData($request);

        // التحقق من حالة الدفع
        if ($paymentData['tranRef']) {
            $paymentData = $this->tabbyService->verifyPaymentStatus($paymentData);
        }

        // معالجة إضافية لدعم بيئة الاختبار
        if (config('services.tabby.is_sandbox') && !$paymentData['isSuccessful']) {
            Log::info('تجاوز التحقق في وضع الاختبار - اعتبار الدفع ناجحًا', [
                'payment_id' => $paymentData['tranRef'] ?? null
            ]);

            $paymentData['isSuccessful'] = true;
            $paymentData['isPending'] = false;
            $paymentData['message'] = 'تم الدفع بنجاح (وضع الاختبار)';
            $paymentData['status'] = 'PAID';
        }

        return $paymentData;
    }

    /**
     * البحث عن حجز موجود باستخدام معرف الدفع أو رقم المعاملة أو معرف المرجع
     *
     * @param array $paymentData بيانات الدفع
     * @return Booking|null الحجز الموجود أو null
     */
    public function findExistingBooking(array $paymentData): ?Booking
    {
        if (empty($paymentData['tranRef']) && empty($paymentData['paymentId']) && empty($paymentData['reference_id'])) {
            Log::warning('No payment reference IDs found in payment data', [
                'payment_data' => $paymentData
            ]);
            return null;
        }

        $query = Booking::query();

        // بناء استعلام مركب للبحث عن الحجز
        if (!empty($paymentData['tranRef'])) {
            Log::info('Searching for booking by transaction reference', [
                'tranRef' => $paymentData['tranRef']
            ]);
            $query->where(function($q) use ($paymentData) {
                $q->where('payment_transaction_id', $paymentData['tranRef'])
                  ->orWhere('payment_id', $paymentData['tranRef']);
            });
        }

        if (!empty($paymentData['paymentId'])) {
            Log::info('Searching for booking by payment ID', [
                'paymentId' => $paymentData['paymentId']
            ]);
            $query->orWhere(function($q) use ($paymentData) {
                $q->where('payment_id', $paymentData['paymentId'])
                  ->orWhere('payment_transaction_id', $paymentData['paymentId']);
            });
        }

        if (!empty($paymentData['reference_id'])) {
            Log::info('Searching for booking by reference ID', [
                'reference_id' => $paymentData['reference_id']
            ]);
            $query->orWhere('payment_id', $paymentData['reference_id']);
        }

        // تنفيذ الاستعلام والحصول على النتيجة
        $booking = $query->latest()->first();

        if ($booking) {
            Log::info('Found existing booking', [
                'booking_id' => $booking->id,
                'payment_id' => $booking->payment_id,
                'payment_transaction_id' => $booking->payment_transaction_id
            ]);
        } else {
            Log::warning('No booking found with payment references', [
                'tranRef' => $paymentData['tranRef'] ?? null,
                'paymentId' => $paymentData['paymentId'] ?? null,
                'reference_id' => $paymentData['reference_id'] ?? null,
                'query_sql' => $query->toSql()
            ]);
        }

        return $booking;
    }

    /**
     * تحديث حالة الحجز بناءً على نتيجة الدفع
     *
     * @param Booking $booking الحجز
     * @param array $paymentData بيانات الدفع
     * @return Booking الحجز المحدث
     */
    public function updateBookingPaymentStatus(Booking $booking, array $paymentData): Booking
    {
        // تحضير تفاصيل الدفع لحفظها
        $paymentDetails = [
            'provider' => 'tabby',
            'transaction_id' => $paymentData['tranRef'] ?? null,
            'status' => $paymentData['status'] ?? null,
            'amount' => $paymentData['amount'] ?? $booking->total_amount,
            'currency' => 'SAR',
            'payment_date' => now()->toDateTimeString()
        ];

        // إضافة البيانات الإضافية من tabby إذا كانت متوفرة
        if (!empty($paymentData['installments'])) {
            $paymentDetails['installments'] = $paymentData['installments'];
        }

        if (!empty($paymentData['downpayment'])) {
            $paymentDetails['downpayment'] = $paymentData['downpayment'];
        }

        if (!empty($paymentData['downpayment_percent'])) {
            $paymentDetails['downpayment_percent'] = $paymentData['downpayment_percent'];
        }

        if (!empty($paymentData['next_payment_date'])) {
            $paymentDetails['next_payment_date'] = $paymentData['next_payment_date'];
        }

        // تحديث الحجز مع تفاصيل الدفع المناسبة
        if ($paymentData['isSuccessful'] && $booking->status !== 'confirmed') {
            $booking->update([
                'status' => 'confirmed',
                'payment_status' => 'paid',
                'payment_transaction_id' => $paymentData['tranRef'] ?? $booking->payment_transaction_id,
                'payment_details' => json_encode($paymentDetails)
            ]);
        } elseif ($paymentData['isPending'] && $booking->status !== 'confirmed') {
            $booking->update([
                'status' => 'pending',
                'payment_status' => 'pending',
                'payment_transaction_id' => $paymentData['tranRef'] ?? $booking->payment_transaction_id,
                'payment_details' => json_encode($paymentDetails)
            ]);
        } elseif (!$paymentData['isSuccessful'] && !$paymentData['isPending']) {
            $booking->update([
                'status' => 'failed',
                'payment_status' => 'failed',
                'payment_transaction_id' => $paymentData['tranRef'] ?? $booking->payment_transaction_id,
                'payment_details' => json_encode($paymentDetails)
            ]);
        }

        Log::info('Updated booking payment status with details', [
            'booking_id' => $booking->id,
            'status' => $booking->status,
            'payment_status' => $booking->payment_status,
            'payment_details' => $paymentDetails
        ]);

        return $booking;
    }

    /**
     * إنشاء حجز جديد بناءً على بيانات الدفع
     *
     * @param array $bookingData بيانات الحجز
     * @param array $paymentData بيانات الدفع
     * @return Booking الحجز الجديد
     */
    public function createBookingFromPayment(array $bookingData, array $paymentData): Booking
    {
        $status = $paymentData['isSuccessful'] ? 'confirmed' :
                 ($paymentData['isPending'] ? 'pending' : 'failed');

        // التأكد من حفظ المعرفات الصحيحة
        // payment_id يجب أن يكون المعرف المرجعي المستخدم في نظامنا (PAY-XXXXX)
        // payment_transaction_id يجب أن يكون معرف الدفع الخاص بتابي (05208274-xxxx)
        $paymentId = $bookingData['payment_id'] ?? null;
        $transactionId = $paymentData['tranRef'] ?? null;

        Log::info('Creating booking from payment with IDs', [
            'payment_id' => $paymentId,
            'transaction_id' => $transactionId
        ]);

        // تحضير تفاصيل الدفع لحفظها
        $paymentDetails = [
            'provider' => 'tabby',
            'transaction_id' => $transactionId,
            'status' => $paymentData['status'] ?? ($paymentData['isSuccessful'] ? 'paid' : ($paymentData['isPending'] ? 'pending' : 'failed')),
            'amount' => $paymentData['amount'] ?? ($bookingData['total_amount'] ?? 0),
            'currency' => 'SAR',
            'payment_date' => now()->toDateTimeString()
        ];

        // إضافة البيانات الإضافية من tabby إذا كانت متوفرة
        if (!empty($paymentData['installments'])) {
            $paymentDetails['installments'] = $paymentData['installments'];
        }

        if (!empty($paymentData['downpayment'])) {
            $paymentDetails['downpayment'] = $paymentData['downpayment'];
        }

        if (!empty($paymentData['downpayment_percent'])) {
            $paymentDetails['downpayment_percent'] = $paymentData['downpayment_percent'];
        }

        if (!empty($paymentData['next_payment_date'])) {
            $paymentDetails['next_payment_date'] = $paymentData['next_payment_date'];
        }

        $bookingParams = array_merge($bookingData, [
            'payment_transaction_id' => $transactionId,
            'payment_id' => $paymentId,
            'payment_status' => $paymentData['status'] ?? ($paymentData['isSuccessful'] ? 'paid' : ($paymentData['isPending'] ? 'pending' : 'failed')),
            'status' => $status,
            'booking_date' => now(),
            'payment_details' => json_encode($paymentDetails)
        ]);

        // إزالة البيانات غير المطلوبة
        $addons = $bookingParams['addons'] ?? [];
        unset($bookingParams['addons']);

        // إنشاء الحجز
        $booking = Booking::create($bookingParams);

        Log::info('Created booking with payment details', [
            'booking_id' => $booking->id,
            'payment_id' => $booking->payment_id,
            'transaction_id' => $booking->payment_transaction_id,
            'status' => $booking->status
        ]);

        // إضافة الإضافات
        if (!empty($addons)) {
            foreach ($addons as $addon) {
                if (isset($addon['id'])) {
                    $booking->addons()->attach($addon['id'], [
                        'quantity' => $addon['quantity'] ?? 1,
                        'price_at_booking' => $addon['price'] ?? 0
                    ]);
                }
            }
        }

        // تسجيل استخدام الكوبون إذا كان موجوداً وكان الدفع ناجحاً
        if (($paymentData['isSuccessful'] || config('services.tabby.is_sandbox')) &&
            !empty($bookingParams['coupon_id']) &&
            !empty($bookingParams['user_id'])) {

            try {
                $coupon = \App\Models\Coupon::find($bookingParams['coupon_id']);
                if ($coupon) {
                    $coupon->recordUsageByUser($bookingParams['user_id'], $booking);
                    \Illuminate\Support\Facades\Log::info('Coupon usage recorded for booking via Tabby', [
                        'booking_id' => $booking->id,
                        'coupon_id' => $bookingParams['coupon_id'],
                        'user_id' => $bookingParams['user_id']
                    ]);
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Error recording coupon usage: ' . $e->getMessage(), [
                    'exception' => $e,
                    'trace' => $e->getTraceAsString(),
                    'booking_id' => $booking->id
                ]);
            }
        }

        return $booking;
    }

    /**
     * Capture a Tabby payment for a booking
     *
     * @param string $paymentId Tabby payment ID to capture
     * @param float|null $amount Optional amount to capture (defaults to full amount)
     * @return array Result of the capture operation
     */
    public function captureTabbyPayment(string $paymentId, float $amount = null): array
    {
        return $this->tabbyService->capturePayment($paymentId, $amount);
    }
}
