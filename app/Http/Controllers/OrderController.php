<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use App\Notifications\OrderCreated;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Auth::user()->orders()
            ->with(['items.product', 'coupon'])
            ->latest()
            ->paginate(10);

        return view('orders.index', compact('orders'));
    }

    public function show(Order $order)
    {
        $this->authorize('view', $order);

        $order->load(['items.product', 'coupon']);
        return view('orders.show', compact('order'));
    }

    public function store(Request $request)
    {
        $cart = Session::get('cart', []);
        if (empty($cart)) {
            return redirect()->route('cart.index')
                ->with('error', 'Your cart is empty.');
        }

        $validated = $request->validate([
            'shipping_address' => 'required|string|max:500',
            'phone' => 'required|string|max:20',
            'notes' => 'nullable|string|max:500'
        ]);

        try {
            DB::beginTransaction();

            $products = Product::whereIn('id', array_keys($cart))->get();
            $total = 0;

            // Check stock and calculate total
            foreach ($products as $product) {
                $quantity = $cart[$product->id];
                if ($product->stock < $quantity) {
                    throw new \Exception("Not enough stock for {$product->name}");
                }
                $total += $product->price * $quantity;
            }

            // Set the subtotal to be the same as the total initially
            $subtotal = $total;
            $discount = 0;

            // Apply coupon discount if available
            $coupon_id = Session::get('coupon_id');
            $coupon_code = Session::get('coupon_code');

            if ($coupon_id) {
                $coupon = \App\Models\Coupon::find($coupon_id);
                if ($coupon && $coupon->isValid()) {
                    $discount = $coupon->calculateDiscount($subtotal);
                    $total = $subtotal - $discount;
                }
            }

            // Create order - UUID and order_number will be auto-generated
            $order = Order::create([
                'user_id' => Auth::id(),
                'subtotal' => $subtotal,
                'discount_amount' => $discount,
                'total_amount' => $total,
                'coupon_id' => $coupon_id,
                'coupon_code' => $coupon_code,
                'shipping_address' => $validated['shipping_address'],
                'phone' => $validated['phone'],
                'notes' => $validated['notes'],
                'order_status' => Order::ORDER_STATUS_PENDING
            ]);

            // Create order items and update stock
            foreach ($products as $product) {
                $quantity = $cart[$product->id];
                $order->items()->create([
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'unit_price' => $product->price,
                    'subtotal' => $product->price * $quantity
                ]);

                // Update product stock
                $product->decrement('stock', $quantity);
            }

            // If there was a coupon, record its usage
            if ($coupon_id && isset($coupon) && $coupon) {
                $coupon->recordUsageByUser(Auth::id(), $order);
            }

            DB::commit();

            // Clear cart and coupon after successful order
            Session::forget(['cart', 'coupon_id', 'coupon_code']);

            // Send order confirmation notification - using the Notifiable trait
            // The user model includes the Notifiable trait so notify() should be available
            if (method_exists(Auth::user(), 'notify')) {
                Auth::user()->notify(new OrderCreated($order));
            }

            return redirect()->route('orders.show', $order)
                ->with('success', 'Order placed successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }
}
