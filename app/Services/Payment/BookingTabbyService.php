<?php

namespace App\Services\Payment;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BookingTabbyService
{
    protected $apiUrl;
    protected $secretKey;
    protected $publicKey;
    protected $merchantCode;

    public function __construct()
    {
        $this->apiUrl = 'https://api.tabby.ai';
        $this->secretKey = config('services.tabby.secret_key');
        $this->publicKey = config('services.tabby.public_key');
        $this->merchantCode = config('services.tabby.merchant_code');
    }

    public function prepareCustomerDetails(array $customerData): array
    {
        $names = explode(' ', $customerData['name'], 2);

        // Format phone number - remove 966 if it starts with it to avoid duplicate country code
        $phone = $customerData['phone'];
        if (substr($phone, 0, 3) === '966') {
            $phone = substr($phone, 3);
        }

        return [
            'first_name' => $names[0] ?? '',
            'last_name' => $names[1] ?? '',
            'phone' => $phone,
            'email' => $customerData['email'],
        ];
    }

    public function createPaymentRequest(array $bookingData, float $amount, array $customerData): array
    {
        try {
            Log::info('Starting Tabby booking payment request', [
                'booking_id' => $bookingData['payment_id'],
                'amount' => $amount
            ]);

            $items = [];
            if (!empty($bookingData['addons'])) {
                foreach ($bookingData['addons'] as $addon) {
                    $items[] = [
                        'title' => 'Addon #' . $addon['id'],
                        'description' => 'Quantity: ' . $addon['quantity'],
                        'quantity' => (int)$addon['quantity'],
                        'unit_price' => (string)$addon['price'],
                        'reference_id' => (string)$addon['id'],
                        'category' => 'service'
                    ];
                }
            }

            // Calculate package price by subtracting addons total from the total amount
            $packagePrice = $amount;
            if (isset($bookingData['addons_total']) && $bookingData['addons_total'] > 0) {
                $packagePrice = $amount - $bookingData['addons_total'];
            }

            $items[] = [
                'title' => $bookingData['package_name'] ?? 'Package Booking',
                'description' => 'Session on ' . $bookingData['session_date'],
                'quantity' => 1,
                'unit_price' => (string)$packagePrice,
                'reference_id' => 'package-' . ($bookingData['package_id'] ?? 'unknown'),
                'category' => 'service'
            ];

            $testCredentials = config('services.tabby.is_sandbox');

            $phone = $customerData['phone'];
            $email = $customerData['email'];

            if ($testCredentials) {
                if (session()->has('tabby_test_phone')) {
                    $phone = session('tabby_test_phone');
                    session()->forget('tabby_test_phone');
                } else if (!empty($bookingData['user_id'])) {
                    // Get the user's actual phone from the database
                    $user = \App\Models\User::find($bookingData['user_id']);
                    if ($user && $user->phone) {
                        $phone = $user->phone;
                    }
                }

                Log::info('Using user phone in test mode for Tabby', [
                    'email' => $email,
                    'phone' => $phone
                ]);
            }

            // Calculate loyalty level based on user's previous successful bookings and orders
            $loyaltyLevel = 0;
            $orderHistory = [];

            if (!empty($bookingData['user_id'])) {
                $userId = $bookingData['user_id'];

                // Count previous successful bookings
                $previousBookingsCount = \App\Models\Booking::where('user_id', $userId)
                    ->where('status', 'confirmed')
                    ->count();

                // Count previous successful orders
                $previousOrdersCount = \App\Models\Order::where('user_id', $userId)
                    ->where('payment_status', 'paid')
                    ->count();

                // Set loyalty level based on total successful transactions
                $loyaltyLevel = $previousBookingsCount + $previousOrdersCount;

                // Get previous bookings for order_history
                $previousBookings = \App\Models\Booking::where('user_id', $userId)
                    ->where('status', 'confirmed')
                    ->orderBy('created_at', 'desc')
                    ->take(2)
                    ->get();

                // Get previous orders for order_history
                $previousOrders = \App\Models\Order::where('user_id', $userId)
                    ->where('payment_status', 'paid')
                    ->orderBy('created_at', 'desc')
                    ->take(1)
                    ->get();

                // Add bookings to order history
                foreach ($previousBookings as $prevBooking) {
                    $bookingItems = [];
                    // Add main package
                    $bookingItems[] = [
                        'reference_id' => 'package-' . ($prevBooking->package_id ?? 'unknown'),
                        'title' => $prevBooking->package_name ?? 'Photography Package',
                        'description' => 'Session on ' . $prevBooking->session_date,
                        'quantity' => 1,
                        'unit_price' => (float) $prevBooking->amount,
                        'category' => 'service'
                    ];

                    // Add any addons
                    if ($prevBooking->addons()->count() > 0) {
                        foreach ($prevBooking->addons as $addon) {
                            $bookingItems[] = [
                                'reference_id' => 'addon-' . $addon->id,
                                'title' => $addon->name ?? 'Addon',
                                'description' => 'Quantity: ' . $addon->pivot->quantity,
                                'quantity' => (int) $addon->pivot->quantity,
                                'unit_price' => (float) $addon->pivot->price_at_booking,
                                'category' => 'service'
                            ];
                        }
                    }

                    $orderHistory[] = [
                        'purchased_at' => $prevBooking->created_at->toIso8601String(),
                        'amount' => (float) $prevBooking->total_amount,
                        'payment_method' => $prevBooking->payment_method ?? 'card',
                        'status' => 'complete',
                        'items' => $bookingItems
                    ];
                }

                // Add orders to order history
                foreach ($previousOrders as $prevOrder) {
                    $orderItems = $prevOrder->items->map(function($item) {
                        return [
                            'reference_id' => (string) $item->product_id,
                            'title' => 'Product #' . $item->product_id,
                            'description' => 'Quantity: ' . $item->quantity,
                            'quantity' => (int) $item->quantity,
                            'unit_price' => (float) $item->unit_price,
                            'category' => 'physical'
                        ];
                    })->toArray();

                    $orderHistory[] = [
                        'purchased_at' => $prevOrder->created_at->toIso8601String(),
                        'amount' => (float) $prevOrder->total_amount,
                        'payment_method' => $prevOrder->payment_method,
                        'status' => 'complete',
                        'items' => $orderItems
                    ];
                }
            }

            $payload = [
                "payment" => [
                    "amount" => $amount,
                    "currency" => "SAR",
                    "description" => $bookingData['description'] ?? 'Booking #' . $bookingData['payment_id'],
                    "buyer" => [
                        "phone" => $phone,
                        "email" => $email,
                        "name" => $customerData['first_name'] . ' ' . $customerData['last_name']
                    ],
                    "shipping_address" => [
                        "city" => $customerData['city'] ?? 'Riyadh',
                        "address" => $bookingData['shipping_address'] ?? $customerData['address'] ?? 'N/A',
                        "zip" => "00000",
                    ],
                    "order" => [
                        "tax_amount" => "0.00",
                        "shipping_amount" => "0.00",
                        "discount_amount" => isset($bookingData['discount_amount']) ? (string)$bookingData['discount_amount'] : "0.00",
                        "updated_at" => now()->toIso8601String(),
                        "reference_id" => $bookingData['payment_id'],
                        "items" => $items
                    ],
                    "buyer_history" => [
                        "registered_since" => now()->subDays(30)->toIso8601String(),
                        "loyalty_level" => $loyaltyLevel,
                    ],
                    "order_history" => $orderHistory,
                ],
                "lang" => "ar",
                "merchant_code" => $this->merchantCode,
                "merchant_urls" => [
                    "success" => route('client.bookings.payment.return') . '?payment_id=' . $bookingData['payment_id'],
                    "cancel" => route('client.bookings.create'),
                    "failure" => route('client.bookings.create'),
                ]
            ];

            Log::info('Booking Tabby request payload', ['payload' => $payload]);

            $response = Http::withToken($this->secretKey)
                ->withHeaders([
                    'Content-Type' => 'application/json'
                ])
                ->post($this->apiUrl . '/api/v2/checkout', $payload);

            $statusCode = $response->status();
            $responseBody = $response->body();
            $responseData = $response->json() ?: [];

            Log::info('Booking Tabby API raw response', [
                'status' => $statusCode,
                'body' => $responseBody,
                'parsed' => $responseData
            ]);

            if ($response->successful()) {
                if (!empty($responseData['id'])) {
                    $redirectUrl = null;

                    if (isset($responseData['configuration']) &&
                        isset($responseData['configuration']['available_products']) &&
                        isset($responseData['configuration']['available_products']['installments']) &&
                        isset($responseData['configuration']['available_products']['installments'][0]['web_url'])) {
                        $redirectUrl = $responseData['configuration']['available_products']['installments'][0]['web_url'];
                    }

                    if (!$redirectUrl && isset($responseData['checkout_url'])) {
                        $redirectUrl = $responseData['checkout_url'];
                    }

                    if ($redirectUrl) {
                        Log::info('Booking Tabby payment created successfully', [
                            'id' => $responseData['id'],
                            'redirect_url' => $redirectUrl
                        ]);

                        return [
                            'success' => true,
                            'data' => [
                                'tran_ref' => $responseData['id'],
                                'redirect_url' => $redirectUrl
                            ]
                        ];
                    }
                }

                Log::warning('Booking Tabby response missing expected fields', ['response' => $responseData]);
                return [
                    'success' => false,
                    'error' => [
                        'message' => 'Invalid response format from Tabby',
                        'details' => $responseData
                    ]
                ];
            }

            $errorMsg = $responseData['message'] ?? 'Unknown error';
            $logLevel = 'error';
            $userMessage = '';

            switch ($statusCode) {
                case 400:
                    $userMessage = 'Invalid request format: ' . $errorMsg;
                    break;
                case 401:
                    $userMessage = 'Authentication failed. Please check your Tabby API keys.';
                    break;
                case 403:
                    $userMessage = 'Access forbidden. Your API key may not have the correct permissions.';
                    break;
                case 404:
                    $userMessage = 'API endpoint not found. Please check the API URL and endpoint path.';
                    break;
                case 405:
                    $userMessage = 'Method not allowed. Please check the API endpoint and method.';
                    break;
                case 422:
                    $userMessage = 'Validation failed: ' . $errorMsg;
                    break;
                case 429:
                    $userMessage = 'Too many requests. Please try again later.';
                    break;
                case 500:
                case 502:
                case 503:
                case 504:
                    $userMessage = 'Tabby service is currently unavailable. Please try again later.';
                    break;
                default:
                    $userMessage = 'Failed to connect to Tabby: ' . $errorMsg;
            }

            Log::$logLevel('Booking Tabby API error', [
                'status' => $statusCode,
                'message' => $errorMsg,
                'response' => $responseData
            ]);

            return [
                'success' => false,
                'error' => [
                    'message' => $userMessage,
                    'details' => $responseData,
                    'status_code' => $statusCode
                ]
            ];
        } catch (\Exception $e) {
            Log::error('Exception in Booking Tabby payment request', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => [
                    'message' => 'Error processing Booking Tabby payment: ' . $e->getMessage()
                ]
            ];
        }
    }

    public function extractPaymentData(Request $request): array
    {
        // يمكن أن يأتي payment_id إما من الـquery string أو من الـbody
        $paymentId = $request->get('payment_id') ?? $request->input('id');
        $merchantReferenceId = $request->get('merchant_reference_id') ??
                               $request->input('order.reference_id') ??
                               $request->input('payment.order.reference_id');

        Log::info('Extracting booking payment data', [
            'payment_id' => $paymentId,
            'merchant_reference_id' => $merchantReferenceId,
            'request' => $request->all()
        ]);

        // إذا لم نستطع العثور على معرف الدفع
        if (empty($paymentId)) {
            return [
                'isSuccessful' => false,
                'isPending' => false,
                'tranRef' => null,
                'paymentId' => null,
                'amount' => 0,
                'message' => 'No payment ID received'
            ];
        }

        // في وضع الاختبار، لا نحتاج للتحقق من Tabby
        if (config('services.tabby.is_sandbox')) {
            return [
                'tranRef' => $paymentId, // الـtranRef هو معرف الدفع من tabby
                'paymentId' => $merchantReferenceId, // الـpaymentId هو معرف الدفع الداخلي (PAY-XXXXX)
                'reference_id' => $merchantReferenceId, // إضافة reference_id بشكل صريح
                'amount' => 400,
                'isPending' => false,
                'isSuccessful' => true,
                'message' => 'Payment successful (Test Mode)'
            ];
        }

        // في البيئة الفعلية
        return [
            'tranRef' => $paymentId, // معرف المعاملة من Tabby
            'paymentId' => $merchantReferenceId, // معرف الدفع الداخلي
            'reference_id' => $merchantReferenceId, // إضافة reference_id بشكل صريح
            'amount' => 0,
            'isPending' => true,
            'isSuccessful' => false,
            'message' => 'Payment status pending verification'
        ];
    }

    public function verifyPaymentStatus(array $paymentData): array
    {
        try {
            if (empty($paymentData['tranRef'])) {
                return array_merge($paymentData, [
                    'isSuccessful' => false,
                    'isPending' => false,
                    'message' => 'No transaction reference to verify'
                ]);
            }

            if (config('services.tabby.is_sandbox') && $paymentData['isSuccessful']) {
                Log::info('Booking payment already verified as successful in test mode', ['tranRef' => $paymentData['tranRef']]);
                return $paymentData;
            }

            Log::info('Verifying Booking Tabby payment status', ['tranRef' => $paymentData['tranRef']]);

            $response = Http::withToken($this->secretKey)
                ->withHeaders([
                    'Content-Type' => 'application/json'
                ])
                ->get($this->apiUrl . '/api/v2/checkout/' . $paymentData['tranRef']);

            $statusCode = $response->status();
            $responseBody = $response->body();
            $responseData = $response->json() ?: [];

            Log::info('Booking Tabby payment verification response', [
                'status' => $statusCode,
                'body' => $responseBody,
                'parsed' => $responseData
            ]);

            if (!$response->successful()) {
                if (config('services.tabby.is_sandbox')) {
                    Log::info('Booking Tabby verification failed in sandbox mode, assuming success', [
                        'status' => $statusCode,
                        'response' => $responseData
                    ]);

                    return array_merge($paymentData, [
                        'isSuccessful' => true,
                        'isPending' => false,
                        'message' => 'Payment successful (Test Mode)'
                    ]);
                }

                Log::error('Booking Tabby payment verification failed', [
                    'status' => $statusCode,
                    'response' => $responseData
                ]);

                return array_merge($paymentData, [
                    'isSuccessful' => false,
                    'isPending' => false,
                    'message' => 'Failed to verify payment: ' . ($responseData['message'] ?? 'Unknown error')
                ]);
            }

            $amount = 0;
            if (isset($responseData['payment']) && isset($responseData['payment']['amount'])) {
                $amount = $responseData['payment']['amount'];
            }

            $status = $responseData['payment']['status'] ?? 'UNKNOWN';
            Log::info('Booking Tabby payment status', ['status' => $status, 'amount' => $amount]);

            $isSuccessful = in_array($status, ['AUTHORIZED', 'CLOSED', 'CAPTURED', 'COMPLETED']);
            $isPending = in_array($status, ['CREATED', 'PENDING']);

            return array_merge($paymentData, [
                'isSuccessful' => $isSuccessful,
                'isPending' => $isPending,
                'amount' => $amount,
                'message' => $isSuccessful ? 'Payment successful' : ($isPending ? 'Payment pending' : 'Payment failed: ' . $status)
            ]);
        } catch (\Exception $e) {
            Log::error('Exception in Booking Tabby payment verification', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return array_merge($paymentData, [
                'isSuccessful' => false,
                'isPending' => false,
                'message' => 'Error verifying payment: ' . $e->getMessage()
            ]);
        }
    }

    public function capturePayment(string $paymentId, float $amount = null): array
    {
        try {
            Log::info('Capturing Booking Tabby payment', ['payment_id' => $paymentId]);

            // التحقق أولاً من تفاصيل الدفع لمعرفة المبلغ الإجمالي إذا لم يتم تحديده
            if ($amount === null) {
                // الحصول على تفاصيل الدفع
                $paymentDetails = $this->refreshPaymentStatus($paymentId);
                if (isset($paymentDetails['amount']) && $paymentDetails['amount'] > 0) {
                    $amount = (float) $paymentDetails['amount'];
                } else {
                    // إذا لم نستطع الحصول على المبلغ، نضع قيمة افتراضية
                    $amount = 1.0; // المبلغ الافتراضي
                }
            }

            // تحضير البيانات المرسلة وفق المطلوب من Tabby API
            $payload = [
                'amount' => number_format($amount, 2, '.', ''), // تنسيق المبلغ بدقة رقمين عشريين
                'reference_id' => 'booking-capture-' . $paymentId . '-' . time() // إضافة معرف مرجعي فريد للتتبع
            ];

            Log::info('Booking Tabby capture payload', ['payload' => $payload]);

            $response = Http::withToken($this->secretKey)
                ->withHeaders([
                    'Content-Type' => 'application/json'
                ])
                ->post($this->apiUrl . '/api/v2/payments/' . $paymentId . '/captures', $payload);

            $statusCode = $response->status();
            $responseBody = $response->body();
            $responseData = $response->json() ?: [];

            Log::info('Booking Tabby capture response', [
                'status' => $statusCode,
                'body' => $responseBody,
                'parsed' => $responseData
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $responseData,
                    'message' => 'Payment captured successfully'
                ];
            }

            Log::error('Booking Tabby payment capture failed', [
                'status' => $statusCode,
                'response' => $responseData
            ]);

            return [
                'success' => false,
                'error' => [
                    'message' => 'Failed to capture payment: ' . ($responseData['error'] ?? 'Unknown error'),
                    'details' => $responseData,
                    'status_code' => $statusCode
                ]
            ];
        } catch (\Exception $e) {
            Log::error('Exception in Booking Tabby payment capture', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => [
                    'message' => 'Error capturing payment: ' . $e->getMessage()
                ]
            ];
        }
    }

    /**
     * Refresh payment status by making a direct API call to Tabby
     * This can be used to manually check payment status if webhooks fail
     *
     * @param string $paymentId Tabby payment ID or checkout ID
     * @return array Payment status details
     */
    public function refreshPaymentStatus(string $paymentId): array
    {
        try {
            Log::info('Refreshing Booking Tabby payment status', ['payment_id' => $paymentId]);

            // First try to get payment by checkout ID
            $endpoint = '/api/v2/checkout/' . $paymentId;
            $response = Http::withToken($this->secretKey)
                ->withHeaders([
                    'Content-Type' => 'application/json'
                ])
                ->get($this->apiUrl . $endpoint);

            // If not found, try to get payment directly
            if ($response->status() === 404) {
                $endpoint = '/api/v2/payments/' . $paymentId;
                $response = Http::withToken($this->secretKey)
                    ->withHeaders([
                        'Content-Type' => 'application/json'
                    ])
                    ->get($this->apiUrl . $endpoint);
            }

            $statusCode = $response->status();
            $responseData = $response->json() ?: [];

            Log::info('Booking Tabby payment status refresh response', [
                'endpoint' => $endpoint,
                'status' => $statusCode,
                'response' => $responseData
            ]);

            if (!$response->successful()) {
                if (config('services.tabby.is_sandbox')) {
                    Log::info('Booking Tabby status refresh failed in sandbox mode, returning default success', [
                        'status' => $statusCode,
                        'response' => $responseData
                    ]);

                    return [
                        'isSuccessful' => true,
                        'isPending' => false,
                        'status' => 'AUTHORIZED',
                        'amount' => 0,
                        'message' => 'Payment successful (Test Mode)'
                    ];
                }

                return [
                    'isSuccessful' => false,
                    'isPending' => false,
                    'status' => 'ERROR',
                    'amount' => 0,
                    'message' => 'Failed to refresh payment status: ' . ($responseData['message'] ?? 'Unknown error')
                ];
            }

            $amount = 0;
            $status = 'UNKNOWN';
            $installmentDetails = null;
            $productInfo = null;

            // استخراج المعلومات من استجابة checkout
            if (isset($responseData['payment']) && isset($responseData['payment']['status'])) {
                $status = $responseData['payment']['status'];
                if (isset($responseData['payment']['amount'])) {
                    $amount = $responseData['payment']['amount'];
                }

                // استخراج معلومات المنتج والأقساط من كائن payment
                if (isset($responseData['payment']['product'])) {
                    $productInfo = $responseData['payment']['product'];
                }
            }
            // استخراج المعلومات من استجابة payment
            else if (isset($responseData['status'])) {
                $status = $responseData['status'];
                if (isset($responseData['amount'])) {
                    $amount = $responseData['amount'];
                }

                // استخراج معلومات المنتج والأقساط من كائن الاستجابة الرئيسي
                if (isset($responseData['product'])) {
                    $productInfo = $responseData['product'];
                }
            }

            // استخراج معلومات تفصيلية عن الأقساط من استجابة checkout
            if (isset($responseData['configuration']) && isset($responseData['configuration']['available_products']) && isset($responseData['configuration']['available_products']['installments'])) {
                $installmentDetails = $responseData['configuration']['available_products']['installments'][0] ?? null;
            }

            $isSuccessful = in_array($status, ['AUTHORIZED', 'CLOSED', 'CAPTURED', 'COMPLETED']);
            $isPending = in_array($status, ['CREATED', 'PENDING']);

            // تجهيز المعلومات التفصيلية عن خطة التقسيط
            $installmentPlan = [];

            if ($installmentDetails) {
                $installmentPlan = [
                    'downpayment' => $installmentDetails['downpayment'] ?? null,
                    'downpayment_percent' => $installmentDetails['downpayment_percent'] ?? null,
                    'installments' => $installmentDetails['installments'] ?? [],
                    'next_payment_date' => $installmentDetails['next_payment_date'] ?? null,
                    'installments_count' => $installmentDetails['installments_count'] ?? count($installmentDetails['installments'] ?? []),
                    'pay_per_installment' => $installmentDetails['pay_per_installment'] ?? null,
                    'amount_to_pay' => $installmentDetails['amount_to_pay'] ?? null,
                    'downpayment_total' => $installmentDetails['downpayment_total'] ?? null,
                ];
            } elseif ($productInfo && isset($productInfo['installments_count']) && $productInfo['installments_count'] > 0) {
                // إذا لم تتوفر تفاصيل الأقساط، نحاول بناء خطة تقسيط تقريبية
                $installments_count = $productInfo['installments_count'];
                $total_amount = (float) $amount;

                // نستخدم 25% كقيمة افتراضية للدفعة الأولى في نظام التقسيط على 4 دفعات
                $downpayment_percent = $installments_count == 3 ? 25 : (100 / ($installments_count + 1));
                $downpayment = $total_amount * ($downpayment_percent / 100);

                $installment_amount = ($total_amount - $downpayment) / $installments_count;
                $installments = [];
                $next_month = now()->addMonth();

                for ($i = 0; $i < $installments_count; $i++) {
                    $due_date = $next_month->copy()->addMonths($i)->format('Y-m-d');
                    $installments[] = [
                        'due_date' => $due_date,
                        'amount' => number_format($installment_amount, 2, '.', ''),
                        'principal' => number_format($installment_amount, 2, '.', ''),
                        'service_fee' => '0.00'
                    ];
                }

                $installmentPlan = [
                    'downpayment' => number_format($downpayment, 2, '.', ''),
                    'downpayment_percent' => $downpayment_percent,
                    'installments' => $installments,
                    'next_payment_date' => $next_month->format('Y-m-d\TH:i:s\Z'),
                    'installments_count' => $installments_count,
                    'pay_per_installment' => number_format($installment_amount, 2, '.', ''),
                    'amount_to_pay' => number_format($total_amount - $downpayment, 2, '.', ''),
                    'downpayment_total' => number_format($downpayment, 2, '.', '')
                ];
            }

            return [
                'isSuccessful' => $isSuccessful,
                'isPending' => $isPending,
                'status' => $status,
                'amount' => $amount,
                'message' => $isSuccessful ? 'Payment successful' : ($isPending ? 'Payment pending' : 'Payment failed: ' . $status),
                'response' => $responseData,
                'installment_plan' => $installmentPlan ?: null,
                'product' => $productInfo
            ];
        } catch (\Exception $e) {
            Log::error('Exception in Booking Tabby payment status refresh', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'isSuccessful' => false,
                'isPending' => false,
                'status' => 'ERROR',
                'amount' => 0,
                'message' => 'Error refreshing payment status: ' . $e->getMessage()
            ];
        }
    }
}
