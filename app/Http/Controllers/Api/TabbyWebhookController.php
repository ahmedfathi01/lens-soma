<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Order;
use App\Services\FirebaseNotificationService;
use App\Services\Payment\StoreTabbyService;
use App\Services\Payment\BookingTabbyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;

class TabbyWebhookController extends Controller
{
    protected $storeTabbyService;
    protected $bookingTabbyService;

    public function __construct(StoreTabbyService $storeTabbyService, BookingTabbyService $bookingTabbyService)
    {
        $this->storeTabbyService = $storeTabbyService;
        $this->bookingTabbyService = $bookingTabbyService;
    }

    public function handleWebhook(Request $request)
    {
        $payload = $request->all();

        Log::info('Tabby webhook received', [
            'payload' => $payload,
            'headers' => $request->headers->all()
        ]);

        if (!$this->verifySourceIp($request->ip())) {
            Log::warning('Tabby webhook received from unauthorized IP', [
                'ip' => $request->ip()
            ]);
        }

        $paymentId = $payload['id'] ??
                     $payload['payment']['id'] ??
                     null;

        $referenceId = $payload['order']['reference_id'] ??
                       $payload['payment']['order']['reference_id'] ??
                       null;

        $status = $payload['status'] ??
                  $payload['payment']['status'] ??
                  null;

        Log::info('Extracted data from Tabby webhook', [
            'payment_id' => $paymentId,
            'reference_id' => $referenceId,
            'status' => $status
        ]);

        if (!$paymentId || !$status) {
            Log::error('Missing required fields in Tabby webhook', ['payload' => $payload]);
            return response()->json(['error' => 'Missing required fields'], 400);
        }

        try {
            $result = $this->processPaymentStatusChange($paymentId, $referenceId, $status, $payload);

            if ($result['success']) {
                return response()->json(['message' => $result['message']], 200);
            } else {
                return response()->json(['error' => $result['message']], 400);
            }
        } catch (\Exception $e) {
            Log::error('Error processing Tabby webhook', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'payload' => $payload
            ]);

            return response()->json(['error' => 'Internal server error'], 500);
        }
    }

    protected function verifySourceIp($ip)
    {
        $allowedIps = [
            '34.166.36.90',
            '34.166.35.211',
            '34.166.34.222',
            '34.166.37.207',
            '34.93.76.191'
        ];

        return in_array($ip, $allowedIps);
    }

    protected function verifySignature($payload, $signature)
    {
        Log::info('Tabby webhook signature verification skipped - not implemented by Tabby');
        return true;
    }

    protected function processPaymentStatusChange($paymentId, $referenceId, $status, $payload)
    {
        Log::info('Processing Tabby payment status change with data:', [
            'payment_id' => $paymentId,
            'reference_id' => $referenceId,
            'status' => $status
        ]);

        $order = null;
        $booking = null;

        if ($referenceId) {
            Log::info('Searching for order/booking by reference_id', ['reference_id' => $referenceId]);
            $order = Order::where('payment_id', $referenceId)->first();
            $booking = Booking::where('payment_id', $referenceId)->first();
        }

        if (!$order && !$booking && $paymentId) {
            Log::info('Searching for order/booking by paymentId', ['payment_id' => $paymentId]);
            $order = Order::where('payment_transaction_id', $paymentId)->first();
            $booking = Booking::where('payment_transaction_id', $paymentId)->first();
        }

        if (!$order && !$booking && $paymentId) {
            Log::info('Searching for order/booking by paymentId in payment_id field', ['payment_id' => $paymentId]);
            $order = Order::where('payment_id', $paymentId)->first();
            $booking = Booking::where('payment_id', $paymentId)->first();
        }

        if (!$order && !$booking) {
            $bookingQuery = Booking::query();
            if ($referenceId) {
                $bookingQuery->orWhere('payment_id', $referenceId);
            }
            if ($paymentId) {
                $bookingQuery->orWhere('payment_transaction_id', $paymentId);
            }

            $orderQuery = Order::query();
            if ($referenceId) {
                $orderQuery->orWhere('payment_id', $referenceId);
            }
            if ($paymentId) {
                $orderQuery->orWhere('payment_transaction_id', $paymentId);
            }

            Log::error('No order or booking found for Tabby webhook', [
                'payment_id' => $paymentId,
                'reference_id' => $referenceId,
                'debug_queries' => [
                    'booking_queries' => [
                        'by_payment_id' => $referenceId ? "Booking::where('payment_id', '{$referenceId}')" : null,
                        'by_transaction_id' => $paymentId ? "Booking::where('payment_transaction_id', '{$paymentId}')" : null
                    ],
                    'order_queries' => [
                        'by_payment_id' => $referenceId ? "Order::where('payment_id', '{$referenceId}')" : null,
                        'by_transaction_id' => $paymentId ? "Order::where('payment_transaction_id', '{$paymentId}')" : null
                    ]
                ]
            ]);

            return [
                'success' => false,
                'message' => 'No order or booking found with the provided reference'
            ];
        }

        if ($order) {
            return $this->updateOrderStatus($order, $status, $paymentId, $payload);
        }

        if ($booking) {
            return $this->updateBookingStatus($booking, $status, $paymentId, $payload);
        }

        return [
            'success' => false,
            'message' => 'Failed to process payment update'
        ];
    }

    protected function updateOrderStatus($order, $status, $paymentId, $payload)
    {
        $oldStatus = $order->payment_status;
        $newStatus = $this->mapTabbyStatusToLocalStatus($status);

        $order->payment_transaction_id = $paymentId;
        $order->payment_status = $newStatus;

        if (strtoupper($status) === 'AUTHORIZED') {
            Log::info('Attempting to capture Tabby payment for order', [
                'order_id' => $order->id,
                'payment_id' => $paymentId
            ]);

            $paymentDetails = $this->storeTabbyService->refreshPaymentStatus($paymentId);
            Log::info('Retrieved complete payment details for order', [
                'order_id' => $order->id,
                'payment_id' => $paymentId,
                'details' => $paymentDetails
            ]);

            $captureResult = $this->storeTabbyService->capturePayment($paymentId);

            if ($captureResult['success']) {
                Log::info('Successfully captured Tabby payment', [
                    'order_id' => $order->id,
                    'payment_id' => $paymentId,
                    'result' => $captureResult
                ]);

                if (!empty($captureResult['data'])) {
                    $payload = array_merge($payload, $captureResult['data']);
                }
            } else {
                Log::error('Failed to capture Tabby payment', [
                    'order_id' => $order->id,
                    'payment_id' => $paymentId,
                    'error' => $captureResult['error'] ?? 'Unknown error'
                ]);
            }
        }

        if ($newStatus === 'paid' && $oldStatus !== 'paid') {
            $order->order_status = Order::ORDER_STATUS_PROCESSING;

            $paymentAmount = $payload['amount'] ??
                            ($payload['payment']['amount'] ??
                            ($payload['order']['amount'] ??
                            $order->total_amount));

            $paymentDetails = [
                'provider' => 'tabby',
                'transaction_id' => $paymentId,
                'status' => $status,
                'amount' => $paymentAmount,
                'currency' => $payload['currency'] ??
                             ($payload['payment']['currency'] ??
                             ($payload['order']['currency'] ?? 'SAR')),
                'payment_date' => now()->toDateTimeString()
            ];

            if (isset($payload['configuration']['available_products']['installments'][0])) {
                $installmentData = $payload['configuration']['available_products']['installments'][0];
                $paymentDetails['downpayment'] = $installmentData['downpayment'] ?? null;
                $paymentDetails['downpayment_percent'] = $installmentData['downpayment_percent'] ?? null;
                $paymentDetails['installments'] = $installmentData['installments'] ?? [];
                $paymentDetails['next_payment_date'] = $installmentData['next_payment_date'] ?? null;
                $paymentDetails['installments_count'] = $installmentData['installments_count'] ?? count($installmentData['installments'] ?? []);
            } elseif (isset($payload['payment']['product'])) {
                $paymentDetails['product'] = $payload['payment']['product'];
                if (isset($payload['payment']['product']['installments_count'])) {
                    $paymentDetails['installments_count'] = $payload['payment']['product']['installments_count'];
                }

                if (isset($payload['payment']['product']['installments_count']) && $payload['payment']['product']['installments_count'] > 0) {
                    $this->addCalculatedInstallments($paymentDetails, $payload['payment']['product']['installments_count'], $paymentAmount);
                }
            } elseif (isset($payload['product'])) {
                $paymentDetails['product'] = $payload['product'];
                if (isset($payload['product']['installments_count'])) {
                    $paymentDetails['installments_count'] = $payload['product']['installments_count'];

                    if ($payload['product']['installments_count'] > 0) {
                        $this->addCalculatedInstallments($paymentDetails, $payload['product']['installments_count'], $paymentAmount);
                    }
                }
            }

            if (empty($paymentDetails['installments']) && empty($paymentDetails['downpayment'])) {
                $detailedPayment = $this->storeTabbyService->refreshPaymentStatus($paymentId);

                if (!empty($detailedPayment['raw_response'])) {
                    if (isset($detailedPayment['raw_response']['product'])) {
                        $product = $detailedPayment['raw_response']['product'];
                        $paymentDetails['product'] = $product;

                        if (isset($product['installments_count']) && $product['installments_count'] > 0) {
                            $this->addCalculatedInstallments($paymentDetails, $product['installments_count'], $paymentAmount, 25);
                        }
                    }
                }
            }

            $order->payment_details = json_encode($paymentDetails);

            Log::info('Payment details for order', [
                'order_id' => $order->id,
                'payment_details' => $paymentDetails
            ]);
        } elseif ($newStatus === 'failed' && $oldStatus !== 'failed') {
            $order->order_status = Order::ORDER_STATUS_FAILED;
        }

        $order->save();

        Log::info('Order payment status updated from webhook', [
            'order_id' => $order->id,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'tabby_status' => $status
        ]);

        try {
            if ($order->user && $order->user->fcm_token) {
                $notificationService = new FirebaseNotificationService();

                if ($newStatus === 'paid') {
                    $notificationService->sendNotification(
                        $order->user->fcm_token,
                        'تم تأكيد الدفع',
                        'تم تأكيد الدفع لطلبك رقم #' . $order->id . ' وسيتم معالجته قريبًا',
                        '/orders/' . $order->id
                    );
                } elseif ($newStatus === 'failed') {
                    $notificationService->sendNotification(
                        $order->user->fcm_token,
                        'فشل في الدفع',
                        'للأسف، فشل الدفع لطلبك رقم #' . $order->id . '. يرجى المحاولة مرة أخرى أو استخدام طريقة دفع أخرى',
                        '/orders/' . $order->id
                    );
                }
            }

            if ($newStatus === 'paid') {
                $notificationService = new FirebaseNotificationService();
                $notificationService->sendNotificationToAdmins(
                    'تم الدفع لطلب جديد',
                    'تم استلام دفعة جديدة لطلب رقم #' . $order->id,
                    $order->id,
                    '/admin/orders/{uuid}'
                );
            }
        } catch (\Exception $e) {
            Log::error('Failed to send order payment notification', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);
        }

        return [
            'success' => true,
            'message' => "Order #{$order->id} payment status updated to {$newStatus}"
        ];
    }

    private function addCalculatedInstallments(&$paymentDetails, $installments_count, $total_amount, $downpayment_percent = null)
    {
        if ($downpayment_percent === null) {
            $downpayment_percent = 100 / ($installments_count + 1);
        }

        $downpayment = $total_amount * ($downpayment_percent / 100);

        $paymentDetails['downpayment'] = number_format($downpayment, 2, '.', '');
        $paymentDetails['downpayment_percent'] = $downpayment_percent;

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

        $paymentDetails['installments'] = $installments;
        $paymentDetails['next_payment_date'] = $next_month->format('Y-m-d\TH:i:s\Z');

        return $paymentDetails;
    }

    protected function updateBookingStatus($booking, $status, $paymentId, $payload)
    {
        $oldStatus = $booking->payment_status;
        $newStatus = $this->mapTabbyStatusToLocalStatus($status);

        $booking->payment_transaction_id = $paymentId;
        $booking->payment_status = $newStatus;

        if (strtoupper($status) === 'AUTHORIZED') {
            Log::info('Attempting to capture Tabby payment for booking', [
                'booking_id' => $booking->id,
                'payment_id' => $paymentId
            ]);

            $paymentDetails = $this->bookingTabbyService->refreshPaymentStatus($paymentId);
            Log::info('Retrieved complete payment details for booking', [
                'booking_id' => $booking->id,
                'payment_id' => $paymentId,
                'details' => $paymentDetails
            ]);

            $captureResult = $this->bookingTabbyService->capturePayment($paymentId);

            if ($captureResult['success']) {
                Log::info('Successfully captured Tabby payment for booking', [
                    'booking_id' => $booking->id,
                    'payment_id' => $paymentId,
                    'result' => $captureResult
                ]);

                if (!empty($captureResult['data'])) {
                    $payload = array_merge($payload, $captureResult['data']);
                }
            } else {
                Log::error('Failed to capture Tabby payment for booking', [
                    'booking_id' => $booking->id,
                    'payment_id' => $paymentId,
                    'error' => $captureResult['error'] ?? 'Unknown error'
                ]);
            }
        }

        if ($newStatus === 'paid' && $oldStatus !== 'paid') {
            $booking->status = 'confirmed';

            $paymentAmount = $payload['amount'] ??
                            ($payload['payment']['amount'] ??
                            ($payload['order']['amount'] ??
                            $booking->total_amount));

            $paymentDetails = [
                'provider' => 'tabby',
                'transaction_id' => $paymentId,
                'status' => $status,
                'amount' => $paymentAmount,
                'currency' => $payload['currency'] ??
                             ($payload['payment']['currency'] ??
                             ($payload['order']['currency'] ?? 'SAR')),
                'payment_date' => now()->toDateTimeString()
            ];

            if (isset($payload['configuration']['available_products']['installments'][0])) {
                $installmentData = $payload['configuration']['available_products']['installments'][0];
                $paymentDetails['downpayment'] = $installmentData['downpayment'] ?? null;
                $paymentDetails['downpayment_percent'] = $installmentData['downpayment_percent'] ?? null;
                $paymentDetails['installments'] = $installmentData['installments'] ?? [];
                $paymentDetails['next_payment_date'] = $installmentData['next_payment_date'] ?? null;
                $paymentDetails['installments_count'] = $installmentData['installments_count'] ?? count($installmentData['installments'] ?? []);
            } elseif (isset($payload['payment']['product'])) {
                $paymentDetails['product'] = $payload['payment']['product'];
                if (isset($payload['payment']['product']['installments_count'])) {
                    $paymentDetails['installments_count'] = $payload['payment']['product']['installments_count'];
                }

                if (isset($payload['payment']['product']['installments_count']) && $payload['payment']['product']['installments_count'] > 0) {
                    $installments_count = $payload['payment']['product']['installments_count'];
                    $total_amount = (float)$paymentAmount;

                    $downpayment_percent = 100 / ($installments_count + 1);
                    $downpayment = $total_amount * ($downpayment_percent / 100);

                    $paymentDetails['downpayment'] = number_format($downpayment, 2, '.', '');
                    $paymentDetails['downpayment_percent'] = $downpayment_percent;

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

                    $paymentDetails['installments'] = $installments;
                    $paymentDetails['next_payment_date'] = $next_month->format('Y-m-d\TH:i:s\Z');
                }
            } elseif (isset($payload['product'])) {
                $paymentDetails['product'] = $payload['product'];
                if (isset($payload['product']['installments_count'])) {
                    $paymentDetails['installments_count'] = $payload['product']['installments_count'];

                    if ($payload['product']['installments_count'] > 0) {
                        $installments_count = $payload['product']['installments_count'];
                        $total_amount = (float)$paymentAmount;

                        $downpayment_percent = 100 / ($installments_count + 1);
                        $downpayment = $total_amount * ($downpayment_percent / 100);

                        $paymentDetails['downpayment'] = number_format($downpayment, 2, '.', '');
                        $paymentDetails['downpayment_percent'] = $downpayment_percent;

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

                        $paymentDetails['installments'] = $installments;
                        $paymentDetails['next_payment_date'] = $next_month->format('Y-m-d\TH:i:s\Z');
                    }
                }
            }

            if (empty($paymentDetails['installments']) && empty($paymentDetails['downpayment'])) {
                $detailedPayment = $this->bookingTabbyService->refreshPaymentStatus($paymentId);

                if (isset($detailedPayment['response']['product'])) {
                    $product = $detailedPayment['response']['product'];
                    $paymentDetails['product'] = $product;

                    if (isset($product['installments_count']) && $product['installments_count'] > 0) {
                        $paymentDetails['installments_count'] = $product['installments_count'];

                        $installments_count = $product['installments_count'];
                        $total_amount = (float)$paymentAmount;

                        $downpayment_percent = 25;
                        $downpayment = $total_amount * ($downpayment_percent / 100);

                        $paymentDetails['downpayment'] = number_format($downpayment, 2, '.', '');
                        $paymentDetails['downpayment_percent'] = $downpayment_percent;

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

                        $paymentDetails['installments'] = $installments;
                        $paymentDetails['next_payment_date'] = $next_month->format('Y-m-d\TH:i:s\Z');
                    }
                }
            }

            $booking->payment_details = json_encode($paymentDetails);

            Log::info('Payment details for booking', [
                'booking_id' => $booking->id,
                'payment_details' => $paymentDetails
            ]);
        } elseif ($newStatus === 'failed' && $oldStatus !== 'failed') {
            $booking->status = 'failed';
        }

        $booking->save();

        Log::info('Booking payment status updated from webhook', [
            'booking_id' => $booking->id,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'tabby_status' => $status
        ]);

        try {
            if ($booking->user && $booking->user->fcm_token) {
                $notificationService = new FirebaseNotificationService();

                if ($newStatus === 'paid') {
                    $notificationService->sendNotification(
                        $booking->user->fcm_token,
                        'تم تأكيد الحجز',
                        'تم تأكيد الدفع لحجزك رقم #' . $booking->id . ' وتم تأكيد موعد الجلسة',
                        '/bookings/' . $booking->id
                    );
                } elseif ($newStatus === 'failed') {
                    $notificationService->sendNotification(
                        $booking->user->fcm_token,
                        'فشل في الدفع',
                        'للأسف، فشل الدفع لحجزك رقم #' . $booking->id . '. يرجى المحاولة مرة أخرى أو استخدام طريقة دفع أخرى',
                        '/bookings/' . $booking->id
                    );
                }
            }

            if ($newStatus === 'paid') {
                $notificationService = new FirebaseNotificationService();
                $notificationService->sendNotificationToAdmins(
                    'تم تأكيد حجز جديد',
                    'تم استلام دفعة جديدة لحجز جلسة تصوير رقم #' . $booking->id,
                    $booking->id,
                    '/admin/bookings/{uuid}'
                );
            }
        } catch (\Exception $e) {
            Log::error('Failed to send booking payment notification', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage()
            ]);
        }

        return [
            'success' => true,
            'message' => "Booking #{$booking->id} payment status updated to {$newStatus}"
        ];
    }

    protected function mapTabbyStatusToLocalStatus($tabbyStatus)
    {
        switch (strtoupper($tabbyStatus)) {
            case 'AUTHORIZED':
            case 'CLOSED':
            case 'CAPTURED':
            case 'COMPLETED':
                return 'paid';
            case 'REJECTED':
            case 'EXPIRED':
            case 'CANCELED':
                return 'failed';
            case 'CREATED':
            case 'PENDING':
                return 'pending';
            default:
                return 'unknown';
        }
    }
}
