<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Http\Requests\MomoRequest;
use App\Http\Requests\VnpayRequest;
use App\Models\Orderdetail;

use Illuminate\Support\Str;

class OrderController extends Controller
{
    // List active orders
    public function index()
    {
        $orders = Order::where('status', '!=', 0)
             ->select("*")
            ->get();

        $result = [
            'status' => true,
            'message' => 'Danh sách đơn hàng',
            'orders' => $orders,
        ];

        return response()->json($result);
    }

    // List orders in trash
    public function trash()
    {
        $orders = Order::where('status', '=', 0)
            ->orderBy('created_at', 'DESC')
            ->select("id", "user_id", "name", "email", "phone", "address", "created_by", "updated_by", "created_at", "updated_at", "status")
            ->get();

        $result = [
            'status' => true,
            'message' => 'Danh sách đơn hàng trong thùng rác',
            'orders' => $orders,
        ];

        return response()->json($result);
    }

    // Show order details
    public function show($id)
    {
        $order = Order::find($id);
        if (!$order) {
            return response()->json([
                'status' => false,
                'message' => 'Đơn hàng không tìm thấy',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Chi tiết đơn hàng',
            'order' => $order,
        ]);
    }
    public function getUserOrders($userId)
    {
        try {
            $orders = Order::where('user_id', $userId)
                ->with('orderDetails')  
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'status' => true,
                'message' => 'Orders fetched successfully',
                'data' => $orders,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch orders',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    // Store new order
    public function store(StoreOrderRequest $request)
    {
        $order = new Order();
        $order->name = $request->name;
        $order->user_id = $request->user_id;
        $order->email = $request->email;
        $order->phone = $request->phone;
        $order->address = $request->address;
        // $order->created_by = 1;
        $order->status = 1;

        if ($order->save()) {
            $result = [
                'status' => true,
                'message' => 'Thêm đơn hàng thành công',
                'order' => $order,
            ];
        } else {
            $result = [
                'status' => false,
                'message' => 'Không thể thêm đơn hàng',
                'order' => null,
            ];
        }

        return response()->json($result);
    }

    // Update order
    public function update(UpdateOrderRequest $request, $id)
    {
        $order = Order::find($id);
        if (!$order) {
            return response()->json([
                'status' => false,
                'message' => 'Đơn hàng không tồn tại',
            ], 404);
        }

        // Update order details
        $order->name = $request->name;
        $order->user_id = $request->user_id;
        $order->email = $request->email;
        $order->phone = $request->phone;
        $order->address = $request->address;
        $order->updated_by = $request->updated_by;
        $order->status = $request->status;
        $order->updated_at = now();

        if ($order->save()) {
            return response()->json([
                'status' => true,
                'message' => 'Cập nhật đơn hàng thành công',
                'order' => $order,
            ]);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Cập nhật đơn hàng thất bại',
                'order' => null,
            ]);
        }
    }

    // Soft delete order (Move to trash)
    public function delete($id)
    {
        $order = Order::find($id);
        if (!$order) {
            return response()->json([
                'status' => false,
                'message' => 'Đơn hàng không tồn tại',
            ], 404);
        }

        $order->status = 0; // Move to trash
        $order->updated_at = now();

        if ($order->save()) {
            return response()->json([
                'status' => true,
                'message' => 'Xóa đơn hàng thành công',
            ]);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Xóa đơn hàng thất bại',
            ]);
        }
    }

    // Restore order from trash
    public function restore($id)
    {
        $order = Order::find($id);
        if (!$order) {
            return response()->json([
                'status' => false,
                'message' => 'Đơn hàng không tồn tại',
            ], 404);
        }

        $order->status = 1; // Restore order
        $order->updated_at = now();

        if ($order->save()) {
            return response()->json([
                'status' => true,
                'message' => 'Khôi phục đơn hàng thành công',
            ]);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Khôi phục đơn hàng thất bại',
            ]);
        }
    }

    // Permanently delete order
    public function destroy($id)
    {
        $order = Order::find($id);
        if (!$order) {
            return response()->json([
                'status' => false,
                'message' => 'Đơn hàng không tồn tại',
            ], 404);
        }

        if ($order->delete()) {
            return response()->json([
                'status' => true,
                'message' => 'Xóa vĩnh viễn đơn hàng thành công',
            ]);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Xóa vĩnh viễn đơn hàng thất bại',
            ]);
        }
    }

    // Toggle order status
    public function status($id)
    {
        $order = Order::find($id);
        if (!$order) {
            return response()->json([
                'status' => false,
                'message' => 'Đơn hàng không tồn tại',
            ], 404);
        }

        $order->status = ($order->status == 1) ? 2 : 1; // Toggle between 1 (Processed) and 2 (Unprocessed)
        $order->updated_at = now();
        $order->save();

        return response()->json([
            'status' => true,
            'message' => 'Cập nhật trạng thái đơn hàng thành công',
            'order' => $order,
        ]);
    }
    
    public function momoPayment(MomoRequest $request)
    {
        // Validate dữ liệu đầu vào
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            'address' => 'required|string|max:500',
            'note' => 'nullable|string|max:1000',
            'carts' => 'required|array|min:1',
            'carts.*.id' => 'required|integer',
            'carts.*.qty' => 'required|integer|min:1',
            'carts.*.price' => 'required|numeric|min:0',
        ]);
    
        $carts = $request->carts;
    
        // Tính tổng tiền đơn hàng
        $totalMoney = 0;
        foreach ($carts as $cart) {
            $totalMoney += $cart['qty'] * $cart['price'];
        }
    
        // Tạo đơn hàng trong DB
        $order = new Order();
        $order->user_id = $request->user_id ?? null; // hoặc lấy từ Auth::user()
        $order->name = $request->name;
        $order->email = $request->email;
        $order->phone = $request->phone;
        $order->address = $request->address;
        $order->payment = "Momo";
        $order->note = $request->note;
        $order->status = 1; // trạng thái "Đang chuẩn bị"
    
        if (!$order->save()) {
            return response()->json(['status' => false, 'message' => 'Tạo đơn hàng thất bại'], 500);
        }
    
        // Tạo chi tiết đơn hàng
        foreach ($carts as $cart) {
            $orderDetail = new Orderdetail();
            $orderDetail->order_id = $order->id;
            $orderDetail->product_id = $cart['id'];
            $orderDetail->qty = $cart['qty'];
            $orderDetail->price = $cart['price'];
            $orderDetail->status = 1;
            $orderDetail->save();
        }
    
        // Thiết lập dữ liệu gửi tới MoMo
        $endpoint = "https://test-payment.momo.vn/v2/gateway/api/create";
    
        $partnerCode = 'MOMOBKUN20180529';
        $accessKey = 'klm05TvNBzhg7h7j';
        $secretKey = 'at67qH6mk8w5Y1nAyMoYKMWACiEi2bsa';
        $orderInfo = "Thanh toán đơn hàng #" . $order->id;
        $amount = (string) ceil($totalMoney);
    
        // Tạo orderId duy nhất: nối id đơn hàng với timestamp
        $uniqueOrderId = $order->id . '_' . time();
    
        $redirectUrl = "https://ambitious-grass-0e16f9200.6.azurestaticapps.net/thanh-toan-thanh-cong"; 
        $ipnUrl = "http://yourbackend.com/api/momo/ipn"; // URL backend nhận callback IPN (cần thay thành URL thật)
    
        $extraData = "";
    
        $requestId = time() . "";
        $requestType = "payWithATM";
    
        // Chuẩn bị chuỗi ký
        $rawHash = "accessKey=$accessKey&amount=$amount&extraData=$extraData&ipnUrl=$ipnUrl&orderId=$uniqueOrderId&orderInfo=$orderInfo&partnerCode=$partnerCode&redirectUrl=$redirectUrl&requestId=$requestId&requestType=$requestType";
    
        // Tạo chữ ký theo thuật toán HMAC SHA256
        $signature = hash_hmac("sha256", $rawHash, $secretKey);
    
        // Dữ liệu gửi request
        $data = [
            'partnerCode' => $partnerCode,
            'partnerName' => "Test",
            'storeId' => "MomoTestStore",
            'requestId' => $requestId,
            'amount' => $amount,
            'orderId' => $uniqueOrderId,
            'orderInfo' => $orderInfo,
            'redirectUrl' => $redirectUrl,
            'ipnUrl' => $ipnUrl,
            'lang' => 'vi',
            'extraData' => $extraData,
            'requestType' => $requestType,
            'signature' => $signature,
        ];
    
        \Log::info('MoMo request data:', $data);
    
        // Khởi tạo CURL gửi request đến MoMo
        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen(json_encode($data))
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    
        $result = curl_exec($ch);
    
        if (curl_errno($ch)) {
            $curlError = curl_error($ch);
            \Log::error("Lỗi CURL gọi MoMo: $curlError");
            curl_close($ch);
            return response()->json(['status' => false, 'message' => "Lỗi gọi MoMo: $curlError"], 500);
        }
    
        curl_close($ch);
    
        \Log::info('MoMo response:', json_decode($result, true));
    
        $jsonResult = json_decode($result, true);
    
        if (isset($jsonResult['payUrl'])) {
            // Có thể lưu lại $uniqueOrderId hoặc trạng thái đơn hàng tại đây nếu cần
    
            return response()->json([
                'status' => true,
                'message' => 'Tạo liên kết thanh toán MoMo thành công',
                'payUrl' => $jsonResult['payUrl']
            ]);
        }
    
        return response()->json(['status' => false, 'message' => 'Tạo liên kết thanh toán MoMo thất bại'], 500);
    }
    
    public function vnpayPayment(VnpayRequest $request)
    {
        \Log::info('VNPAY Request data:', $request->all());
        date_default_timezone_set('Asia/Ho_Chi_Minh');
    
        $carts = $request->carts;
    
        // Tính tổng tiền đơn hàng
        $totalMoney = 0;
        foreach ($carts as $cart) {
            $totalMoney += $cart['qty'] * $cart['price'];
        }
    
        // Tạo đơn hàng trong DB
        $order = new Order();
        $order->user_id = $request->user_id ?? null; // Hoặc lấy từ Auth::user()
        $order->name = $request->name;
        $order->email = $request->email;
        $order->phone = $request->phone;
        $order->address = $request->address;
        $order->payment = "VNPAY";
        $order->note = $request->note;
        $order->status = 1; // trạng thái "Đang chuẩn bị"
    
        if (!$order->save()) {
            return response()->json(['status' => false, 'message' => 'Tạo đơn hàng thất bại'], 500);
        }
    
        // Tạo chi tiết đơn hàng
        foreach ($carts as $cart) {
            $orderDetail = new Orderdetail();
            $orderDetail->order_id = $order->id;
            $orderDetail->product_id = $cart['id'];
            $orderDetail->qty = $cart['qty'];
            $orderDetail->price = $cart['price'];
            $orderDetail->status = 1;
            $orderDetail->save();
        }
    
        // Thông tin VNPAY
        $tmnCode = "K9C7AVE4"; // Mã TMN code VNPAY thật của bạn
        $secretKey = "688IM7YB5KQDKIUWN0XJ53F00Y6R2RUG"; // Chuỗi bí mật thật
        $vnpUrl = "https://sandbox.vnpayment.vn/paymentv2/vpcpay.html";
        $returnUrl = "https://ambitious-grass-0e16f9200.6.azurestaticapps.net/thanh-toan-thanh-cong"; // URL nhận kết quả thanh toán
    
        $locale = "vn"; // Viết thường
        $currCode = "VND";
    
        $date = new \DateTime();
        $createDate = $date->format('YmdHis');
        $expireDate = $date->modify('+15 minutes')->format('YmdHis');
    
        $ipAddr = $request->ip() ?? $_SERVER['REMOTE_ADDR'];
    
        // Tạo mã đơn hàng duy nhất (không trùng lặp trong ngày)
        $orderId = 'ORDER' . $order->id . time();
    
        $vnp_Params = [
            "vnp_Version" => "2.1.0",
            "vnp_Command" => "pay",
            "vnp_TmnCode" => $tmnCode,
            "vnp_Locale" => $locale,
            "vnp_CurrCode" => $currCode,
            "vnp_TxnRef" => $orderId,
            "vnp_OrderInfo" => "Payment for order " . $orderId,
            "vnp_OrderType" => "other",
            "vnp_Amount" => $totalMoney * 100, // nhân 100 (đơn vị nhỏ nhất)
            "vnp_ReturnUrl" => $returnUrl,
            "vnp_IpAddr" => $ipAddr,
            "vnp_CreateDate" => $createDate,
            "vnp_ExpireDate" => $expireDate,
            // Có thể thêm nếu có bank_code từ frontend
            // "vnp_BankCode" => $request->bank_code ?? '',
        ];
    
        // Loại bỏ tham số rỗng (nếu có)
        $vnp_Params = array_filter($vnp_Params, function ($value) {
            return $value !== null && $value !== '';
        });
    
        // Sắp xếp tham số theo tên key
        ksort($vnp_Params);
    
        // Tạo chuỗi hash data
        $hashData = '';
        $i = 0;
        foreach ($vnp_Params as $key => $value) {
            $encodedValue = str_replace('%20', '+', rawurlencode($value));
            if ($i == 1) {
                $hashData .= '&' . $key . '=' . $encodedValue;
            } else {
                $hashData .= $key . '=' . $encodedValue;
                $i = 1;
            }
        }
        \Log::info('VNPAY Hash Data: ' . $hashData);
    
        // Tạo chữ ký bảo mật theo chuẩn VNPAY
        $vnpSecureHash = hash_hmac('sha512', $hashData, $secretKey);
    
        // Tạo query string với encode chuẩn
        $query = http_build_query($vnp_Params, '', '&', PHP_QUERY_RFC3986);
    
        // Hoàn chỉnh URL thanh toán
        $paymentUrl = $vnpUrl . '?' . $query . '&vnp_SecureHash=' . $vnpSecureHash;
    
        \Log::info('VNPAY Secure Hash: ' . $vnpSecureHash);
        \Log::info('VNPAY payment URL: ' . $paymentUrl);
    
        return response()->json([
            'status' => true,
            'paymentUrl' => $paymentUrl,
        ]);
    }
    
    /**
     * Nếu muốn giữ hàm sortParams để sử dụng riêng
     */
    private function sortParams(array $params)
    {
        ksort($params);
        return $params;
    }
    
 

// public function vnpayPayment(VnpayRequest $request)
// {
//     date_default_timezone_set('Asia/Ho_Chi_Minh');

//     // Tính tổng tiền
//     $totalMoney = 0;
//     foreach ($request->carts as $cart) {
//         $totalMoney += $cart['qty'] * $cart['price'];
//     }

//     $tmnCode = "7JBRUH2N"; // TMN Code chính xác
//     $secretKey = "MU0YRHED8QKHIIMQ3U38GLJQZ4V83ZU0"; // Chuỗi bí mật đúng
//     $vnpUrl = "https://sandbox.vnpayment.vn/paymentv2/vpcpay.html";
//     $returnUrl = "https://localhost/vnpay_php/vnpay_return.php";

//     $orderId = "ORDER" . time();
//     $ipAddr = $request->ip() ?? $_SERVER['REMOTE_ADDR'];

//     $vnp_Params = [
//         "vnp_Version" => "2.1.0",
//         "vnp_Command" => "pay",
//         "vnp_TmnCode" => $tmnCode,
//         "vnp_Locale" => "vn",
//         "vnp_CurrCode" => "VND",
//         "vnp_TxnRef" => $orderId,
//         "vnp_OrderInfo" => "Thanh toan hoa don",
//         "vnp_OrderType" => "other",
//         "vnp_Amount" => $totalMoney * 100,
//         "vnp_ReturnUrl" => $returnUrl,
//         "vnp_IpAddr" => $ipAddr,
//         "vnp_CreateDate" => date('YmdHis'),
//         "vnp_ExpireDate" => date('YmdHis', strtotime('+15 minutes')),
//         "vnp_BankCode" => "NCB",
//     ];

//     ksort($vnp_Params);

//     $hashData = '';
//     $i = 0;
//     foreach ($vnp_Params as $key => $value) {
//         if ($i == 1) {
//             $hashData .= '&' . $key . '=' . str_replace('%20', '+', rawurlencode($value));
//         } else {
//             $hashData .= $key . '=' . str_replace('%20', '+', rawurlencode($value));
//             $i = 1;
//         }
//     }

//     \Log::info('VNPAY Hash Data: ' . $hashData);

//     $vnpSecureHash = hash_hmac('sha512', $hashData, $secretKey);

//     $query = http_build_query($vnp_Params, '', '&', PHP_QUERY_RFC3986);

//     $paymentUrl = $vnpUrl . '?' . $query . '&vnp_SecureHash=' . $vnpSecureHash;

//     \Log::info('VNPAY Secure Hash: ' . $vnpSecureHash);
//     \Log::info('VNPAY payment URL: ' . $paymentUrl);

//     return response()->json([
//         'status' => true,
//         'paymentUrl' => $paymentUrl,
//     ]);
// }

    

}
