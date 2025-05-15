<?php

namespace App\Services\Payment;

use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaytabsService
{
    protected $profileId;
    protected $serverKey;
    protected $currency;
    protected $isSandbox;
    protected $maxRetries = 3;
    protected $apiUrl = 'https://secure-egypt.paytabs.com';

    public function __construct()
    {
        $this->profileId = config('services.paytabs.profile_id');
        $this->serverKey = config('services.paytabs.server_key');
        $this->currency = config('services.paytabs.currency');
        $this->isSandbox = config('services.paytabs.is_sandbox');
    }

    /**
     * إنشاء طلب دفع جديد
     *
     * @param array $orderData بيانات الطلب/الحجز
     * @param float $amount المبلغ
     * @param array $customerData بيانات العميل
     * @return array
     */
    public function createPaymentRequest(array $orderData, float $amount, array $customerData): array
    {
        $paymentId = $orderData['payment_id'];
        $description = $orderData['description'] ?? 'Photography Session Booking';

        $paymentData = [
            "profile_id" => $this->profileId,
            "tran_type" => "sale",
            "tran_class" => "ecom",
            "cart_id" => $paymentId,
            "cart_description" => $description,
            "cart_currency" => $this->currency,
            "cart_amount" => $amount,
            "callback" => route('client.bookings.payment.callback'),
            "return" => route('client.bookings.payment.return'),
            "customer_details" => $customerData,
            "hide_shipping" => true,
            "framed" => true,
            "is_sandbox" => $this->isSandbox,
            "is_hosted" => true
        ];

        return $this->sendRequest('/payment/request', $paymentData);
    }

    public function queryTransaction(string $tranRef): array
    {
        $data = [
            'profile_id' => $this->profileId,
            'tran_ref' => $tranRef
        ];

        return $this->sendRequest('/payment/query', $data);
    }

    protected function sendRequest(string $endpoint, array $data): array
    {
        $attempt = 1;
        $lastError = null;

        while ($attempt <= $this->maxRetries) {
            try {
                $response = Http::timeout(30)
                    ->withHeaders([
                        'Authorization' => $this->serverKey,
                        'Content-Type' => 'application/json'
                    ])
                    ->withOptions([
                        'verify' => !app()->environment('local'),
                        'connect_timeout' => 30
                    ])
                    ->post($this->apiUrl . $endpoint, $data);

                if ($response->successful()) {
                    return [
                        'success' => true,
                        'data' => $response->json() ?? [],
                        'status_code' => $response->status()
                    ];
                }

                $lastError = [
                    'status' => $response->status(),
                    'body' => $response->json() ?? $response->body(),
                ];
            } catch (\Exception $e) {
                $lastError = [
                    'message' => $e->getMessage(),
                    'type' => get_class($e)
                ];

                Log::error('PayTabs API Error', [
                    'endpoint' => $endpoint,
                    'error' => $lastError,
                    'attempt' => $attempt
                ]);
            }

            if ($attempt < $this->maxRetries) {
                sleep(pow(2, $attempt - 1));
            }
            $attempt++;
        }

        return [
            'success' => false,
            'error' => $lastError,
            'message' => 'فشل الاتصال بخدمة الدفع بعد عدة محاولات'
        ];
    }

    public function extractPaymentData(Request $request): array
    {
        $requestData = $request->all();

        Log::info('Trying to extract PayTabs payment data', [
            'request_data' => $requestData,
            'query_params' => $request->query(),
            'session_transaction_id' => session('payment_transaction_id'),
            'method' => $request->method()
        ]);

        // محاولة اكتشاف مختلف أشكال البيانات التي قد ترسلها PayTabs
        $tranRef = $request->input('tran_ref') ??
                   $request->input('payment_reference') ??
                   $request->query('tranRef') ??
                   $request->query('payment_reference') ??
                   session('payment_transaction_id');

        $status = $request->input('respStatus') ??
                  $request->input('response_status') ??
                  $request->input('status') ??
                  $request->query('respStatus') ??
                  $request->query('status');

        $respCode = $request->input('respCode') ??
                    $request->input('respcode') ??
                    $request->input('response_code') ??
                    $request->query('respCode');

        $message = $request->input('respMessage') ??
                   $request->input('response_message') ??
                   $request->input('message') ??
                   $request->query('respMessage');

        $paymentId = $request->input('payment_id') ??
                     $request->input('cart_id') ??
                     session('pending_booking.payment_id');

        // التحقق من وجود معلومات إضافية في payment_result
        $paymentResult = $request->input('payment_result', []);
        if (!empty($paymentResult)) {
            if (empty($status) && !empty($paymentResult['response_status'])) {
                $status = $paymentResult['response_status'];
            }
            if (empty($respCode) && !empty($paymentResult['response_code'])) {
                $respCode = $paymentResult['response_code'];
            }
            if (empty($message) && !empty($paymentResult['response_message'])) {
                $message = $paymentResult['response_message'];
            }
        }

        $amount = $request->input('cart_amount') ??
                  $request->input('amount') ??
                  null;

        $paymentData = [
            'status' => $status,
            'tranRef' => $tranRef,
            'paymentId' => $paymentId,
            'message' => $message,
            'responseCode' => $respCode,
            'amount' => $amount,
            'currency' => $this->currency,
            'rawData' => $requestData
        ];

        Log::info('Extracted PayTabs payment data', [
            'payment_data' => $paymentData
        ]);

        return $paymentData;
    }

    public function verifyPaymentStatus(array $paymentData): array
    {
        if (empty($paymentData['tranRef'])) {
            Log::warning('No transaction reference to verify PayTabs payment', [
                'payment_data' => $paymentData
            ]);

            // في بيئة الاختبار، نعتبر الدفع ناجحاً إذا لم نستطع التحقق منه
            if ($this->isSandbox) {
                Log::info('In sandbox mode: Considering payment successful without verification');
                return array_merge($paymentData, [
                    'isSuccessful' => true,
                    'isPending' => false,
                    'message' => 'تم اعتبار الدفع ناجحاً في وضع الاختبار',
                    'status' => 'A',
                ]);
            }

            return array_merge($paymentData, [
                'isSuccessful' => false,
                'isPending' => false,
            ]);
        }

        try {
            $queryResponse = $this->queryTransaction($paymentData['tranRef']);

            Log::info('PayTabs payment verification response', [
                'tran_ref' => $paymentData['tranRef'],
                'response' => $queryResponse
            ]);

            if (!empty($queryResponse['success']) && !empty($queryResponse['data'])) {
                $result = $queryResponse['data'];

                // تحميل معلومات الدفع من نتيجة الاستعلام
                $responseStatus = $result['payment_result']['response_status'] ?? $paymentData['status'] ?? null;
                $responseMessage = $result['payment_result']['response_message'] ?? $paymentData['message'] ?? null;
                $responseCode = $result['payment_result']['response_code'] ?? null;
                $amount = $result['cart_amount'] ?? $result['tran_total'] ?? $paymentData['amount'] ?? null;
                $currency = $result['tran_currency'] ?? $result['cart_currency'] ?? $this->currency;

                // تحديث بيانات الدفع بالمعلومات الجديدة
                $paymentData['status'] = $responseStatus;
                $paymentData['message'] = $responseMessage;
                $paymentData['responseCode'] = $responseCode;
                $paymentData['amount'] = $amount;
                $paymentData['currency'] = $currency;
                $paymentData['verificationData'] = $result;
            }
            else if ($this->isSandbox) {
                // في وضع الاختبار، نعتبر الدفع ناجحاً إذا فشل الاستعلام
                Log::info('In sandbox mode: Considering payment successful after failed verification', [
                    'tran_ref' => $paymentData['tranRef']
                ]);

                $paymentData['status'] = 'A';
                $paymentData['message'] = 'تم اعتبار الدفع ناجحاً في وضع الاختبار';
            }
        } catch (\Exception $e) {
            Log::error('Exception during PayTabs payment verification', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'tran_ref' => $paymentData['tranRef']
            ]);

            // في وضع الاختبار، لا نريد أن تفشل العملية بسبب خطأ في الاستعلام
            if ($this->isSandbox) {
                Log::info('In sandbox mode: Ignoring verification error');
                $paymentData['status'] = 'A';
                $paymentData['message'] = 'تم تجاوز خطأ التحقق في وضع الاختبار';
            }
        }

        // تحديد نجاح أو فشل الدفع بناءً على الحالة
        $paymentData['isSuccessful'] = $this->isSuccessfulStatus($paymentData['status']);
        $paymentData['isPending'] = $this->isPendingStatus($paymentData['status']);

        // إضافة رسالة مناسبة إذا لم تكن موجودة
        if (empty($paymentData['message'])) {
            if ($paymentData['isSuccessful']) {
                $paymentData['message'] = 'تم الدفع بنجاح';
            } else if ($paymentData['isPending']) {
                $paymentData['message'] = 'الدفع قيد المعالجة';
            } else {
                $paymentData['message'] = 'فشل الدفع';
            }
        }

        Log::info('Final PayTabs payment status', [
            'tran_ref' => $paymentData['tranRef'],
            'is_successful' => $paymentData['isSuccessful'],
            'is_pending' => $paymentData['isPending'],
            'status' => $paymentData['status'],
            'message' => $paymentData['message']
        ]);

        return $paymentData;
    }

    protected function isSuccessfulStatus($status): bool
    {
        $successStatuses = [
            'A', 'H', 'P', 'V', 'success', 'SUCCESS', '1', 1, 'CAPTURED',
            '100', 'Authorised', 'Captured', 'Approved'
        ];

        return in_array($status, $successStatuses, true);
    }

    protected function isPendingStatus($status): bool
    {
        $pendingStatuses = [
            'PENDING', 'pending', 'H', 'P', '2', 'PROCESSING'
        ];

        return in_array($status, $pendingStatuses, true);
    }

    public function prepareCustomerDetails(array $user): array
    {
        return [
            "name" => $user['name'] ?? 'Customer',
            "email" => $user['email'] ?? '',
            "phone" => $user['phone'] ?? '',
            "street1" => $user['address'] ?? 'Client Address',
            "city" => $user['city'] ?? 'City',
            "state" => $user['state'] ?? 'State',
            "country" => $user['country'] ?? 'EG',
            "zip" => $user['zip'] ?? '00000'
        ];
    }
}
