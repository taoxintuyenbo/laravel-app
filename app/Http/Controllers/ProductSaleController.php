<?php

namespace App\Http\Controllers;

use App\Models\ProductSale;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductSaleController extends Controller
{
    // List all active product sales
    public function index()
    {
        $productSales = ProductSale::where('product_sale.status', '!=', 0) // Specify table for 'status'
            ->join('product', 'product_sale.product_id', '=', 'product.id')
            ->select('product_sale.id', 'product_sale.product_id', 'product.name as product_name', 'product_sale.price_sale', 'product_sale.date_begin', 'product_sale.date_end', 'product_sale.status')
            ->get();
    
        $result = [
            'status' => true,
            'message' => 'Danh sách khuyến mãi sản phẩm',
            'product_sale' => $productSales,
        ];
    
        return response()->json($result);
    }
    

    // Show a single product sale detail
    public function show($id)
    {
        $productSale = ProductSale::where('product_sale.id', $id)
            ->join('product', 'product_sale.product_id', '=', 'product.id')
            ->select('product_sale.*', 'product.name as product_name')
            ->first();

        if (!$productSale) {
            return response()->json([
                'status' => false,
                'message' => 'Khuyến mãi không tồn tại',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Chi tiết khuyến mãi sản phẩm',
            'product_sale' => $productSale,
        ]);
    }

    // Create a new product sale
    public function store(Request $request)
    {
        $product = Product::find($request->product_id);

        if (!$product) {
            return response()->json([
                'status' => false,
                'message' => 'Mã sản phẩm không hợp lệ',
            ], 400);
        }

        $productSale = new ProductSale();
        $productSale->product_id = $request->product_id;
        $productSale->price_sale = $request->price_sale;
        $productSale->date_begin = $request->date_begin;
        $productSale->date_end = $request->date_end;
        $productSale->status = $request->status ?? 1; // Default status as active
 
        if ($productSale->save()) {
            return response()->json([
                'status' => true,
                'message' => 'Khuyến mãi sản phẩm đã được thêm thành công',
                'product_sale' => $productSale,
            ]);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Không thể thêm khuyến mãi',
            ]);
        }
    }

    // Update an existing product sale
    public function update(Request $request, $id)
    {
        $productSale = ProductSale::find($id);

        if (!$productSale) {
            return response()->json([
                'status' => false,
                'message' => 'Khuyến mãi không tồn tại',
            ], 404);
        }

        $productSale->product_id = $request->product_id;
        $productSale->price_sale = $request->price_sale;
        $productSale->date_begin = $request->date_begin;
        $productSale->date_end = $request->date_end;
        $productSale->status = $request->status ?? 1;
 

        if ($productSale->save()) {
            return response()->json([
                'status' => true,
                'message' => 'Cập nhật khuyến mãi sản phẩm thành công',
                'product_sale' => $productSale,
            ]);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Cập nhật khuyến mãi sản phẩm thất bại',
            ]);
        }
    }

    // Soft delete a product sale (move to trash)
    public function delete($id)
    {
        $productSale = ProductSale::find($id);

        if (!$productSale) {
            return response()->json([
                'status' => false,
                'message' => 'Khuyến mãi không tồn tại',
            ], 404);
        }

        $productSale->status = 0; // Move to trash
   

        if ($productSale->save()) {
            return response()->json([
                'status' => true,
                'message' => 'Khuyến mãi đã được di chuyển vào thùng rác',
            ]);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Xóa khuyến mãi thất bại',
            ]);
        }
    }

    // Restore product sale from trash
    public function restore($id)
    {
        $productSale = ProductSale::find($id);

        if (!$productSale) {
            return response()->json([
                'status' => false,
                'message' => 'Khuyến mãi không tồn tại trong thùng rác',
            ], 404);
        }

        $productSale->status = 1; // Restore
 

        if ($productSale->save()) {
            return response()->json([
                'status' => true,
                'message' => 'Khuyến mãi đã được khôi phục',
            ]);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Khôi phục khuyến mãi thất bại',
            ]);
        }
    }
// List all product sales in trash
public function trash()
{
    $trashedSales = ProductSale::where('product_sale.status', '=', 0) // Only select trashed sales
        ->join('product', 'product_sale.product_id', '=', 'product.id')
        ->select('product_sale.id', 'product_sale.product_id', 'product.name as product_name', 'product_sale.price_sale', 'product_sale.date_begin', 'product_sale.date_end', 'product_sale.status')
        ->get();

    $result = [
        'status' => true,
        'message' => 'Danh sách khuyến mãi trong thùng rác',
        'product_sale' => $trashedSales,
    ];

    return response()->json($result);
}

    // Permanently delete product sale
    public function destroy($id)
    {
        $productSale = ProductSale::find($id);

        if (!$productSale) {
            return response()->json([
                'status' => false,
                'message' => 'Khuyến mãi không tồn tại',
            ], 404);
        }

        if ($productSale->delete()) {
            return response()->json([
                'status' => true,
                'message' => 'Khuyến mãi đã được xóa vĩnh viễn',
            ]);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Xóa vĩnh viễn khuyến mãi thất bại',
            ]);
        }
    }

    // Toggle the status of a product sale
    public function status($id)
    {
        $productSale = ProductSale::find($id);

        if (!$productSale) {
            return response()->json([
                'status' => false,
                'message' => 'Khuyến mãi không tồn tại',
            ], 404);
        }

        $productSale->status = ($productSale->status == 1) ? 2 : 1; // Toggle between active and inactive
      

        if ($productSale->save()) {
            return response()->json([
                'status' => true,
                'message' => 'Cập nhật trạng thái thành công',
                'product_sale' => $productSale,
            ]);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Cập nhật trạng thái thất bại',
            ]);
        }
    }
}
