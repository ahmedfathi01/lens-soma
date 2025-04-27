<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\CartItem;
use App\Models\Product;
use App\Services\Customer\Products\CartService;
use App\Services\Customer\Products\ProductService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductController extends Controller
{
    protected $productService;
    protected $cartService;

    public function __construct(ProductService $productService, CartService $cartService)
    {
        $this->productService = $productService;
        $this->cartService = $cartService;
    }

    public function index(Request $request)
    {
        $products = $this->productService->getFilteredProducts($request);
        $categories = $this->productService->getCategories();
        $priceRange = $this->productService->getPriceRange();

        foreach ($products as $product) {
            $productDetails = $this->productService->getProductDetails($product);
            if (isset($productDetails['has_coupon']) && $productDetails['has_coupon']) {
                $product->has_coupon = true;
                $product->best_coupon = $productDetails['best_coupon'];
                $product->all_coupons = $productDetails['all_coupons'];
            }
        }

        if ($request->ajax()) {
            return response()->json([
                'products' => $this->productService->formatProductsForJson($products),
                'pagination' => [
                    'current_page' => $products->currentPage(),
                    'last_page' => $products->lastPage(),
                    'per_page' => $products->perPage(),
                    'total' => $products->total()
                ]
            ]);
        }

        return view('products.index', compact('products', 'categories', 'priceRange'));
    }

    public function show(Product $product)
    {
        if (!$product->is_available) {
            abort(404, 'المنتج غير متوفر حالياً');
        }

        $product->load(['category', 'images', 'colors', 'sizes']);

        $productDetails = $this->productService->getProductDetails($product);
        if (isset($productDetails['has_coupon']) && $productDetails['has_coupon']) {
            $product->has_coupon = true;
            $product->best_coupon = $productDetails['best_coupon'];
            $product->all_coupons = $productDetails['all_coupons'];
            $product->discounted_price = $productDetails['discounted_price'];
        }

        $availableFeatures = $this->productService->getAvailableFeatures($product);
        $relatedProducts = $this->productService->getRelatedProducts($product);
        $showStoreAppointments = \App\Models\Setting::getBool('show_store_appointments', true);

        $pendingAppointment = null;
        if (Auth::check()) {
            $pendingAppointment = CartItem::whereHas('cart', function($query) {
                $query->where('user_id', Auth::id());
            })
            ->where('needs_appointment', true)
            ->whereHas('product', function($query) use ($product) {
                $query->where('id', $product->id);
            })
            ->whereDoesntHave('appointment')
            ->first();
        }

        return view('products.show', compact(
            'product',
            'relatedProducts',
            'availableFeatures',
            'pendingAppointment',
            'showStoreAppointments'
        ));
    }

    public function filter(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'categories' => 'nullable|array',
                'minPrice' => 'nullable|numeric|min:0',
                'maxPrice' => 'nullable|numeric|min:0',
                'sort' => 'nullable|string|in:newest,price-low,price-high',
                'has_discount' => 'nullable|boolean'
            ]);

            $request->merge([
                'max_price' => $validatedData['maxPrice'] ?? null,
                'category' => !empty($validatedData['categories']) ? $validatedData['categories'][0] : null,
                'sort' => $validatedData['sort'] ?? 'newest',
                'has_discount' => isset($validatedData['has_discount']) ? (bool)$validatedData['has_discount'] : null
            ]);

            $products = $this->productService->getFilteredProducts($request);

            foreach ($products as $product) {
                $productDetails = $this->productService->getProductDetails($product);
                if (isset($productDetails['has_coupon']) && $productDetails['has_coupon']) {
                    $product->has_coupon = true;
                    $product->best_coupon = $productDetails['best_coupon'];
                    $product->all_coupons = $productDetails['all_coupons'];
                }
            }

            return response()->json([
                'success' => true,
                'products' => $this->productService->formatProductsForFilter($products),
                'pagination' => [
                    'current_page' => $products->currentPage(),
                    'last_page' => $products->lastPage(),
                    'per_page' => $products->perPage(),
                    'total' => $products->total()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث المنتجات',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getProductDetails($productId)
    {
        try {
            // Find product by ID instead of using route model binding
            // This allows the route to work regardless of authentication status
            $product = Product::findOrFail($productId);

            if (!$product->is_available) {
                return response()->json([
                    'success' => false,
                    'message' => 'المنتج غير متوفر حالياً'
                ], 404);
            }

            $product->load(['category', 'images', 'colors', 'sizes']);

            $productDetails = $this->productService->getProductDetails($product);

            // Ensure we return features even if empty
            if (!isset($productDetails['features'])) {
                $productDetails['features'] = [];
            }

            return response()->json($productDetails);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب تفاصيل المنتج',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function addToCart(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'color' => 'nullable|string|max:50',
            'size' => 'nullable|string|max:50',
            'needs_appointment' => 'nullable|boolean'
        ]);

        if (!$request->has('needs_appointment')) {
            $request->merge(['needs_appointment' => false]);
        }

        $result = $this->cartService->addToCart($request);

        if (!$result['success']) {
            return response()->json([
                'success' => $result['success'],
                'message' => $result['message']
            ], $result['status']);
        }

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
            'cart_count' => $result['cart_count'],
            'cart_total' => $result['cart_total'],
            'show_appointment' => $result['show_appointment'],
            'product_name' => $result['product_name'],
            'product_id' => $result['product_id'],
            'cart_item_id' => $result['cart_item_id'],
            'show_modal' => $result['show_modal']
        ]);
    }

    public function getCartItems(Request $request)
    {
        return response()->json($this->cartService->getCartItems($request));
    }

    public function updateCartItem(Request $request, CartItem $cartItem)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1'
        ]);

        $result = $this->cartService->updateCartItem($cartItem, $request->quantity);

        return response()->json($result);
    }

    public function removeCartItem(CartItem $cartItem)
    {
        try {
            $result = $this->cartService->removeCartItem($cartItem);

            if (!$result['success']) {
                return response()->json([
                    'success' => $result['success'],
                    'message' => $result['message']
                ], $result['status'] ?? 403);
            }

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء حذف المنتج'
            ], 500);
        }
    }
}
