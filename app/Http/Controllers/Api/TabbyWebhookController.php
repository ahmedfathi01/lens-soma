<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Order;
use App\Services\FirebaseNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;

class TabbyWebhookController extends Controller
{
    /**
     * Handle incoming Tabby webhooks
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function handleWebhook(Request $request)
    {
        $payload = $request->all();
        $signature = $request->header('X-Tabby-Signature');

        Log::info('Tabby webhook received', [
            'payload' => $payload,
            'signature' => $signature
        ]);

        // Verify webhook signature
        if (!$this->verifySignature($payload, $signature)) {
            Log::warning('Invalid Tabby webhook signature');
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        // Extract payment details from webhook
        $paymentId = $payload['payment']['id'] ?? null;
        $referenceId = $payload['payment']['order']['reference_id'] ?? null;
        $status = $payload['payment']['status'] ?? null;

        if (!$paymentId || !$referenceId || !$status) {
            Log::error('Missing required fields in Tabby webhook', ['payload' => $payload]);
            return response()->json(['error' => 'Missing required fields'], 400);
        }

        try {
            // Process the webhook based on payment status
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

    /**
     * Verify Tabby webhook signature
     *
     * @param array $payload
     * @param string $signature
     * @return bool
     */
    protected function verifySignature($payload, $signature)
    {
        if (empty($signature)) {
            return false;
        }

        $webhookSecret = config('services.tabby.webhook_secret');

        if (empty($webhookSecret)) {
            Log::warning('Tabby webhook secret not configured');
            return false;
        }

        // Convert payload to JSON string
        $payloadString = json_encode($payload);

        // Calculate expected signature
        $expectedSignature = hash_hmac('sha256', $payloadString, $webhookSecret);

        // Use constant time comparison to prevent timing attacks
        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Process payment status change
     *
     * @param string $paymentId
     * @param string $referenceId
     * @param string $status
     * @param array $payload
     * @return array
     */
    protected function processPaymentStatusChange($paymentId, $referenceId, $status, $payload)
    {
        // Look for order or booking with this reference ID
        $order = Order::where('payment_id', $referenceId)->first();
        $booking = Booking::where('payment_id', $referenceId)->first();

        // If neither found, check by transaction ID
        if (!$order && !$booking) {
            $order = Order::where('payment_transaction_id', $paymentId)->first();
            $booking = Booking::where('payment_transaction_id', $paymentId)->first();
        }

        if (!$order && !$booking) {
            Log::error('No order or booking found for Tabby webhook', [
                'payment_id' => $paymentId,
                'reference_id' => $referenceId
            ]);
            return [
                'success' => false,
                'message' => 'No order or booking found with the provided reference'
            ];
        }

        // Process order payment
        if ($order) {
            return $this->updateOrderStatus($order, $status, $paymentId, $payload);
        }

        // Process booking payment
        if ($booking) {
            return $this->updateBookingStatus($booking, $status, $paymentId, $payload);
        }

        return [
            'success' => false,
            'message' => 'Failed to process payment update'
        ];
    }

    /**
     * Update order status based on Tabby webhook
     *
     * @param \App\Models\Order $order
     * @param string $status
     * @param string $paymentId
     * @param array $payload
     * @return array
     */
    protected function updateOrderStatus($order, $status, $paymentId, $payload)
    {
        $oldStatus = $order->payment_status;
        $newStatus = $this->mapTabbyStatusToLocalStatus($status);

        // Update order with new payment status
        $order->payment_transaction_id = $paymentId;
        $order->payment_status = $newStatus;

        // If payment is successful and status was not paid before
        if ($newStatus === 'paid' && $oldStatus !== 'paid') {
            // Additional logic for successful payment
            $order->status = 'processing';

            // Record payment details
            $paymentAmount = $payload['payment']['amount'] ?? $order->total_amount;
            $order->payment_details = json_encode([
                'provider' => 'tabby',
                'transaction_id' => $paymentId,
                'status' => $status,
                'amount' => $paymentAmount,
                'currency' => $payload['payment']['currency'] ?? 'SAR',
                'payment_date' => now()->toDateTimeString(),
                'raw_response' => $payload
            ]);
        } elseif ($newStatus === 'failed' && $oldStatus !== 'failed') {
            // Handle failed payment
            $order->status = 'payment_failed';
        }

        $order->save();

        Log::info('Order payment status updated from webhook', [
            'order_id' => $order->id,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'tabby_status' => $status
        ]);

        // Attempt to notify the customer about status change
        try {
            // Notify customer
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

            // Notify admins
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

    /**
     * Update booking status based on Tabby webhook
     *
     * @param \App\Models\Booking $booking
     * @param string $status
     * @param string $paymentId
     * @param array $payload
     * @return array
     */
    protected function updateBookingStatus($booking, $status, $paymentId, $payload)
    {
        $oldStatus = $booking->payment_status;
        $newStatus = $this->mapTabbyStatusToLocalStatus($status);

        // Update booking with new payment status
        $booking->payment_transaction_id = $paymentId;
        $booking->payment_status = $newStatus;

        // If payment is successful and status was not confirmed before
        if ($newStatus === 'paid' && $oldStatus !== 'paid') {
            // Set booking as confirmed
            $booking->status = 'confirmed';

            // Record payment details
            $paymentAmount = $payload['payment']['amount'] ?? $booking->total_amount;
            $booking->payment_details = json_encode([
                'provider' => 'tabby',
                'transaction_id' => $paymentId,
                'status' => $status,
                'amount' => $paymentAmount,
                'currency' => $payload['payment']['currency'] ?? 'SAR',
                'payment_date' => now()->toDateTimeString(),
                'raw_response' => $payload
            ]);
        } elseif ($newStatus === 'failed' && $oldStatus !== 'failed') {
            // Handle failed payment
            $booking->status = 'failed';
        }

        $booking->save();

        Log::info('Booking payment status updated from webhook', [
            'booking_id' => $booking->id,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'tabby_status' => $status
        ]);

        // Attempt to notify the customer about status change
        try {
            // Notify customer
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

            // Notify admins
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

    /**
     * Map Tabby payment status to our local payment status
     *
     * @param string $tabbyStatus
     * @return string
     */
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
