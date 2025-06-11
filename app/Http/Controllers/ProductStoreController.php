<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ProductStore;
use App\Models\Product;

use App\Http\Requests\StoreProductStoreRequest;
use App\Http\Requests\UpdateProductStoreRequest;

use Illuminate\Support\Facades\Auth;

class ProductStoreController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $productstores = ProductStore::where('product_store.status', '!=', 0)
            ->select("*")
            ->get();

        $result = [
            'status' => true,
            'message' => 'Tải dữ liệu thành công',
            'productstores' => $productstores,
            'total' => count($productstores),
        ];

        return response()->json($result);
    }

    public function trash()
    {
         $trashedProducts = ProductStore::where('product_store.status', '=', 0)
            ->orderBy('product_store.created_at', 'DESC')
            ->select("*")
            ->get();
    
        $result = [
            'status' => true,
            'message' => 'Danh sách sản phẩm trong thùng rác',
            'productstores' => $trashedProducts,
        ];
    
        // Return the result as JSON
        return response()->json($result);
    }
    
    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProductStoreRequest $request)
    {
        $product = Product::find($request->product_id);
        if ($product == null) {
            $result = [
                'status' => false,
                'message' => 'Mã sản phẩm không hợp lệ',
                'productsale' => null
            ];

            return response()->json($result);
        }

        $productstore = new ProductStore();
        $productstore->product_id = $request->product_id;
        $productstore->type = $request->type;
        $productstore->price_root = $request->price_root;
        $productstore->qty = $request->qty;
        $productstore->created_at = now(); // Use Laravel's helper for current time
        $productstore->status = 1;

        if ($productstore->save()) {
            $result = [
                'status' => true,
                'message' => 'Thêm thành công',
                'productstore' => $productstore
            ];
        } else {
            $result = [
                'status' => false,
                'message' => 'Không thể thêm',
                'productstore' => null
            ];
        }
        return response()->json($result);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $productstore = ProductStore::find($id);

        if (!$productstore) {
            return response()->json([
                'status' => false,
                'message' => 'Sản phẩm không tồn tại'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'productstore' => $productstore
        ]);
    }

 
    public function update(UpdateProductStoreRequest $request, $id)
{
    $productStore = ProductStore::find($id); // Assuming you're using ProductStore model
    if (!$productStore) {
        return response()->json([
            'status' => false,
            'message' => 'Sản phẩm lưu kho không tồn tại',
        ], 404);
    }

    // Update fields based on the request data
    $productStore->product_id = $request->product_id;
    $productStore->type = $request->type;
    $productStore->price_root = $request->price_root;
    $productStore->qty = $request->qty;
    $productStore->updated_by = Auth::id() ?? 1; // Update by the authenticated user, default to 1 (admin)
    $productStore->updated_at = now(); // Update timestamp
    $productStore->status = $request->status;

    // Save and return a success/failure response
    if ($productStore->save()) {
        return response()->json([
            'status' => true,
            'message' => 'Cập nhật sản phẩm lưu kho thành công',
            'productStore' => $productStore,
        ]);
    } else {
        return response()->json([
            'status' => false,
            'message' => 'Cập nhật sản phẩm lưu kho thất bại',
            'productStore' => null,
        ]);
    }
}


    public function status(string $id)
    {
        $product = ProductStore::find($id);
        if ($product == null) {
            return response()->json([
                'status' => false,
                'message' => 'The item does not exist.'
            ], 404);
        }
    
        $product->status = ($product->status == 2) ? 1 : 2;
        $product->updated_at = now(); // Use Laravel's helper for current time
        $product->save();
    
        return response()->json([
            'status' => true,
            'message' => 'Product status updated successfully.',
            'product' => $product
        ]);
    }
    /**
     * Remove the specified resource from storage.
     */

    public function delete($id)
    {
        // Find the product by its ID
        $productstore = ProductStore::find($id);
        if (!$productstore) {
            return response()->json([
                'status' => false,
                'message' => 'Sản phẩm không tồn tại',
            ], 404);
        }
        $productstore->status = 0;
        $productstore->updated_at = date('Y-m-d H:i:s');
        $productstore->save();

        return response()->json([
            'status' => true,
            'message' => 'Sản phẩm đã được di chuyển vào thùng rác',
        ]);
    }

    public function restore($id)
    {
        $productstore = ProductStore::find($id);
        if (!$productstore) {
            return response()->json([
                'status' => false,
                'message' => 'Sản phẩm không tồn tại trong thùng rác',
            ], 404);
        }
        $productstore->status = 2;  
        $productstore->updated_at = date('Y-m-d H:i:s');
        $productstore->save();
        return response()->json([
            'status' => true,
            'message' => 'Sản phẩm đã được khôi phục',
        ]);
    }
    public function destroy(string $id)
    {
        $productstore = ProductStore::find($id);

        if (!$productstore) {
            return response()->json([
                'status' => false,
                'message' => 'Sản phẩm không tồn tại'
            ], 404);
        }

        if ($productstore->delete()) {
            return response()->json([
                'status' => true,
                'message' => 'Xóa thành công'
            ]);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Không thể xóa'
            ]);
        }
    }
}
