<?php

namespace App\Services\Payment;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class StorePaytabsService extends PaytabsService
{
    /**
     * تهيئة المتغيرات
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * تهيئة بيانات العميل لاستخدامها في الدفع
     *
     * @param array $customerData
     * @return array
     */
    public function prepareCustomerDetails(array $customerData): array
    {
        return [
            'name' => $customerData['name'] ?? '',
            'email' => $customerData['email'] ?? '',
            'phone' => $customerData['phone'] ?? '',
            'street1' => $customerData['address'] ?? '',
            'city' => $customerData['city'] ?? '',
            'state' => $customerData['state'] ?? '',
            'country' => $customerData['country'] ?? 'SA',
            'zip' => $customerData['zip'] ?? '00000'
        ];
    }

    /**
     * إنشاء طلب دفع جديد للمتجر
     *
     * يتجاوز هذا الدالة الموجودة في الفئة الأب لتتناسب مع متطلبات المتجر
     *
     * @param array $orderData بيانات الطلب
     * @param float $amount المبلغ
     * @param array $customerData بيانات العميل
     * @return array
     */
    public function createPaymentRequest(array $orderData, float $amount, array $customerData): array
    {
        $paymentId = $orderData['payment_id'];
        $description = $orderData['description'] ?? 'Order Payment';

        $reference = [
            'transaction' => $paymentId,
            'order' => $paymentId
        ];

        $returnUrl = route('checkout.payment.return');

        $payload = [
            'profile_id' => $this->profileId,
            'tran_type' => 'sale',
            'tran_class' => 'ecom',
            'cart_id' => $paymentId,
            'cart_description' => $description,
            'cart_currency' => $this->currency,
            'cart_amount' => $amount,
            'hide_shipping' => true,
            'callback' => $returnUrl,
            'return' => $returnUrl,
            'customer_details' => [
                'name' => $customerData['name'],
                'email' => $customerData['email'],
                'phone' => $customerData['phone'],
                'street1' => $customerData['street1'] ?? $customerData['address'] ?? '',
                'city' => $customerData['city'] ?? '',
                'state' => $customerData['state'] ?? '',
                'country' => $customerData['country'] ?? 'SA',
                'zip' => $orderData['zip'] ?? '00000'
            ],
            'shipping_details' => [
                'name' => $customerData['name'],
                'email' => $customerData['email'],
                'phone' => $customerData['phone'],
                'street1' => $customerData['street1'] ?? $customerData['address'] ?? '',
                'city' => $customerData['city'] ?? '',
                'state' => $customerData['state'] ?? '',
                'country' => $customerData['country'] ?? 'SA',
                'zip' => $orderData['zip'] ?? '00000'
            ],
            'user_defined' => [
                'custom1' => 'store_order',
                'custom2' => json_encode($reference),
                'udf9' => 'store_order'
            ]
        ];

        // تسجيل الطلب قبل الإرسال
        Log::info('Creating PayTabs store payment request', [
            'order_id' => $orderData['payment_id'],
            'amount' => $amount,
            'payload' => $payload
        ]);

        try {
            // إرسال الطلب لبوابة الدفع باستخدام الدالة المحمية من الفئة الأب
            $response = $this->sendRequest('/payment/request', $payload);

            if (!empty($response['success']) && !empty($response['data']['redirect_url'])) {
                Log::info('PayTabs store payment request successful', [
                    'order_id' => $orderData['payment_id'],
                    'tran_ref' => $response['data']['tran_ref'] ?? null,
                    'redirect_url' => $response['data']['redirect_url']
                ]);

                return [
                    'success' => true,
                    'data' => [
                        'tran_ref' => $response['data']['tran_ref'] ?? null,
                        'redirect_url' => $response['data']['redirect_url']
                    ]
                ];
            }

            // في حالة الخطأ
            Log::error('PayTabs store payment request failed', [
                'order_id' => $orderData['payment_id'],
                'response' => $response
            ]);

            return [
                'success' => false,
                'error' => [
                    'message' => $response['message'] ?? 'Failed to connect to payment gateway',
                    'details' => $response
                ]
            ];
        } catch (\Exception $e) {
            Log::error('Exception during PayTabs store payment request', [
                'order_id' => $orderData['payment_id'],
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => [
                    'message' => 'Payment gateway connection failed: ' . $e->getMessage(),
                    'details' => []
                ]
            ];
        }
    }

    /**
     * استخراج بيانات الدفع من طلب callback
     *
     * @param Request $request
     * @return array
     */
    public function extractPaymentData(Request $request): array
    {
        $data = $request->all();
        $query = $request->query();

        // تحسين سجلات التتبع
        Log::info('PayTabs store extracting payment data', [
            'all_data' => $data,
            'query_params' => $query,
            'method' => $request->method(),
            'headers' => $request->headers->all(),
            'payment_id' => session('payment_transaction_id')
        ]);

        // استخراج المعرفات المهمة بطرق مختلفة
        $tranRef = $data['tran_ref'] ??
                  $data['payment_reference'] ??
                  $query['tranRef'] ??
                  $query['tran_ref'] ??
                  $query['payment_reference'] ??
                  session('payment_transaction_id');

        $cartId = $data['cart_id'] ?? $query['cart_id'] ?? $data['order_id'] ?? null;
        $respCode = $data['respcode'] ?? $data['response_code'] ?? $query['respCode'] ?? null;
        $respStatus = $data['respstatus'] ?? $data['status'] ?? $data['response_status'] ?? $query['respStatus'] ?? null;
        $respMessage = $data['respmsg'] ?? $data['message'] ?? $data['response_message'] ?? null;
        $amount = $data['cart_amount'] ?? $data['amount'] ?? 0;

        // تحقق من وجود معلومات في payment_result
        $paymentResult = $data['payment_result'] ?? [];
        if (!empty($paymentResult) && is_array($paymentResult)) {
            $respCode = $respCode ?: ($paymentResult['response_code'] ?? null);
            $respStatus = $respStatus ?: ($paymentResult['response_status'] ?? null);
            $respMessage = $respMessage ?: ($paymentResult['response_message'] ?? null);
        }

        Log::info('PayTabs store payment data extracted', [
            'tran_ref' => $tranRef,
            'cart_id' => $cartId,
            'resp_code' => $respCode,
            'resp_status' => $respStatus,
            'resp_message' => $respMessage
        ]);

        $udf2 = $data['user_defined']['udf2'] ?? null;
        $paymentId = null;

        if ($udf2) {
            $reference = json_decode($udf2, true);
            $paymentId = $reference['order'] ?? null;
        } else {
            $paymentId = $cartId;
        }

        // تحديد حالة الدفع
        $isSuccess = $this->isSuccessfulStatus($respStatus);
        $isPending = $this->isPendingStatus($respStatus);

        return [
            'tranRef' => $tranRef,
            'paymentId' => $paymentId,
            'cartId' => $cartId,
            'amount' => (float) $amount,
            'isSuccessful' => $isSuccess,
            'isPending' => $isPending,
            'responseCode' => $respCode,
            'responseStatus' => $respStatus,
            'responseMessage' => $respMessage,
            'rawData' => $data
        ];
    }

    /**
     * التحقق ما إذا كانت حالة الدفع ناجحة
     *
     * @param string|null $status
     * @return bool
     */
    protected function isSuccessfulStatus($status): bool
    {
        return in_array($status, ['A', 'H', 'APPROVED', 'CAPTURED', 'AUTHORIZED']);
    }

    /**
     * التحقق ما إذا كانت حالة الدفع معلقة
     *
     * @param string|null $status
     * @return bool
     */
    protected function isPendingStatus($status): bool
    {
        return in_array($status, ['P', 'PENDING', 'PENDING_PAYMENT', 'PENDING_PROCESS']);
    }

    /**
     * التحقق من حالة الدفع
     *
     * @param array $paymentData
     * @return array
     */
    public function verifyPaymentStatus(array $paymentData): array
    {
        if (empty($paymentData['tranRef'])) {
            return $paymentData;
        }

        try {
            $payload = [
                'profile_id' => $this->profileId,
                'tran_ref' => $paymentData['tranRef']
            ];

            Log::info('Verifying PayTabs store payment status', [
                'tran_ref' => $paymentData['tranRef'],
                'payment_id' => $paymentData['paymentId'] ?? ''
            ]);

            $response = $this->sendRequest('/payment/query', $payload);

            Log::info('PayTabs store payment verification response', [
                'tran_ref' => $paymentData['tranRef'],
                'payment_id' => $paymentData['paymentId'] ?? '',
                'response' => $response
            ]);

            // تحديث بيانات الدفع
            if (!empty($response['success']) && !empty($response['data'])) {
                $result = $response['data'];
                $paymentData['responseCode'] = $result['payment_result']['response_code'] ?? $paymentData['responseCode'];
                $paymentData['responseStatus'] = $result['payment_result']['response_status'] ?? $paymentData['responseStatus'];
                $paymentData['responseMessage'] = $result['payment_result']['response_message'] ?? $paymentData['responseMessage'];
                $paymentData['amount'] = !empty($result['cart_amount']) ? (float) $result['cart_amount'] : $paymentData['amount'];

                // التحقق من حالة الدفع باستخدام الدوال المحمية من الفئة الأب
                $paymentData['isSuccessful'] = $this->isSuccessfulStatus($paymentData['responseStatus']);
                $paymentData['isPending'] = $this->isPendingStatus($paymentData['responseStatus']);

                $paymentData['rawData'] = array_merge($paymentData['rawData'] ?? [], $response);
            }
        } catch (\Exception $e) {
            Log::error('Exception during PayTabs store payment verification', [
                'tran_ref' => $paymentData['tranRef'],
                'payment_id' => $paymentData['paymentId'] ?? '',
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }

        return $paymentData;
    }
}
