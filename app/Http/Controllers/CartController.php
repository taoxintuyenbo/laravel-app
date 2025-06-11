<?php
namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Orderdetail;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    public function index()
    {
        $list_cart = session('carts', []);
        return response()->json($list_cart);
    }

    public function addToCart(Request $request)
    {
        $productid = $request->product_id;
        $qty = $request->qty;
        $product = Product::find($productid);

        if (!$product) {
            return response()->json(['error' => 'Product not found'], 404);
        }

        $cartItem = [
            'id' => $productid,
            'image' => $product->image,
            'name' => $product->name,
            'qty' => $qty,
            'price' => $product->pricesale > 0 ? $product->pricesale : $product->price,
        ];

        $carts = session('carts', []);
        $found = false;

        foreach ($carts as $key => &$cart) {
            if ($cart['id'] == $productid) {
                $cart['qty'] += $qty;
                $found = true;
                break;
            }
        }

        if (!$found) {
            $carts[] = $cartItem;
        }

        session(['carts' => $carts]);
        return response()->json($carts);
    }

    public function updateCart(Request $request)
    {
        $carts = session('carts', []);
        $list_qty = $request->qty;

        foreach ($carts as $key => &$cart) {
            if (isset($list_qty[$cart['id']])) {
                $cart['qty'] = $list_qty[$cart['id']];
            }
        }

        session(['carts' => $carts]);
        return response()->json($carts);
    }

    public function removeFromCart($id)
    {
        $carts = session('carts', []);
        $carts = array_filter($carts, fn($cart) => $cart['id'] != $id);

        session(['carts' => $carts]);
        return response()->json($carts);
    }

    public function checkout(Request $request)
    {
        $user = Auth::user();
        $carts = session('carts', []);

        if (count($carts) == 0) {
            return response()->json(['error' => 'Cart is empty'], 400);
        }

        $order = new Order();
        $order->user_id = $user->id;
        $order->name = $request->name;
        $order->email = $request->email;
        $order->phone = $request->phone;
        $order->address = $request->address;
        $order->created_at = now();
        $order->updated_at = now();
        $order->status = 1;

        if ($order->save()) {
            foreach ($carts as $cart) {
                $orderDetail = new Orderdetail();
                $orderDetail->order_id = $order->id;
                $orderDetail->product_id = $cart['id'];
                $orderDetail->qty = $cart['qty'];
                $orderDetail->price = $cart['price'];
                $orderDetail->created_at = now();
                $orderDetail->updated_at = now();
                $orderDetail->status = 1;
                $orderDetail->save();
            }

            session(['carts' => []]);
            return response()->json(['message' => 'Checkout completed']);
        }

        return response()->json(['error' => 'Checkout failed'], 500);
    }

    

}
