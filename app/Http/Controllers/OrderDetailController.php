<?php

namespace App\Http\Controllers;

use App\Models\OrderDetail;
use Illuminate\Http\Request;

class OrderDetailController extends Controller
{
    // List all order details for a specific order
    public function index($orderId)
    {
        $orderDetails = OrderDetail::where('order_id', $orderId)
            ->select("*")
            ->get();

        if ($orderDetails->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'Chi tiết đơn hàng không tìm thấy',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Danh sách chi tiết đơn hàng',
            'orderDetails' => $orderDetails,
        ]);
    }

    // Show a specific order detail
    public function show($id)
    {
        $orderDetail = OrderDetail::find($id);
        if (!$orderDetail) {
            return response()->json([
                'status' => false,
                'message' => 'Chi tiết đơn hàng không tồn tại',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Chi tiết đơn hàng',
            'orderDetail' => $orderDetail,
        ]);
    }

    // Store a new order detail
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'order_id' => 'required|exists:order,id',
            'product_id' => 'required|exists:product,id',
            'qty' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
        ]);

        $orderDetail = new OrderDetail();
        $orderDetail->order_id = $validatedData['order_id'];
        $orderDetail->product_id = $validatedData['product_id'];
        $orderDetail->qty = $validatedData['qty'];
        $orderDetail->price = $validatedData['price'];
        $orderDetail->created_by = 1;
        $orderDetail->updated_by = 1;
        $orderDetail->status = 1;

        if ($orderDetail->save()) {
            return response()->json([
                'status' => true,
                'message' => 'Thêm chi tiết đơn hàng thành công',
                'orderDetail' => $orderDetail,
            ]);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Thêm chi tiết đơn hàng thất bại',
            ]);
        }
    }

    // Update an existing order detail
    public function update(Request $request, $id)
    {
        $orderDetail = OrderDetail::find($id);
        if (!$orderDetail) {
            return response()->json([
                'status' => false,
                'message' => 'Chi tiết đơn hàng không tồn tại',
            ], 404);
        }

        $validatedData = $request->validate([
            'qty' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
        ]);

        $orderDetail->qty = $validatedData['qty'];
        $orderDetail->price = $validatedData['price'];
        $orderDetail->updated_by = 1;

        if ($orderDetail->save()) {
            return response()->json([
                'status' => true,
                'message' => 'Cập nhật chi tiết đơn hàng thành công',
                'orderDetail' => $orderDetail,
            ]);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Cập nhật chi tiết đơn hàng thất bại',
            ]);
        }
    }

    // Soft delete (move to trash) an order detail
    public function delete($id)
    {
        $orderDetail = OrderDetail::find($id);
        if (!$orderDetail) {
            return response()->json([
                'status' => false,
                'message' => 'Chi tiết đơn hàng không tồn tại',
            ], 404);
        }

        $orderDetail->status = 0; // Move to trash
        $orderDetail->updated_by = auth()->user()->id;
        $orderDetail->updated_at = now();

        if ($orderDetail->save()) {
            return response()->json([
                'status' => true,
                'message' => 'Xóa chi tiết đơn hàng thành công',
            ]);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Xóa chi tiết đơn hàng thất bại',
            ]);
        }
    }

    // Permanently delete an order detail
    public function destroy($id)
    {
        $orderDetail = OrderDetail::find($id);
        if (!$orderDetail) {
            return response()->json([
                'status' => false,
                'message' => 'Chi tiết đơn hàng không tồn tại',
            ], 404);
        }

        if ($orderDetail->delete()) {
            return response()->json([
                'status' => true,
                'message' => 'Xóa vĩnh viễn chi tiết đơn hàng thành công',
            ]);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Xóa vĩnh viễn chi tiết đơn hàng thất bại',
            ]);
        }
    }

    // Restore an order detail from trash
    public function restore($id)
    {
        $orderDetail = OrderDetail::find($id);
        if (!$orderDetail) {
            return response()->json([
                'status' => false,
                'message' => 'Chi tiết đơn hàng không tồn tại',
            ], 404);
        }

        $orderDetail->status = 1; // Restore
        $orderDetail->updated_by = auth()->user()->id;
        $orderDetail->updated_at = now();

        if ($orderDetail->save()) {
            return response()->json([
                'status' => true,
                'message' => 'Khôi phục chi tiết đơn hàng thành công',
                'orderDetail' => $orderDetail,
            ]);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Khôi phục chi tiết đơn hàng thất bại',
            ]);
        }
    }
}
