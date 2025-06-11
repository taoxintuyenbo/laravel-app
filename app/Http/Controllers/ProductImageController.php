<?php

namespace App\Http\Controllers;

use App\Models\ProductImage;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductImageController extends Controller
{
    // List all product images
    public function index()
    {
        $productImages = ProductImage::with('product')
            ->orderBy('created_at', 'DESC')
            ->select("id", "product_id", "thumbnail", "created_at", "updated_at")
            ->get();
        
        foreach ($productImages as $image) {
            $image->thumbnail = asset('images/product/' . $image->thumbnail); // Generate full URL for the thumbnail
        }

        return response()->json([
            'status' => true,
            'message' => 'Danh sách hình ảnh sản phẩm',
            'productImages' => $productImages,
        ]);
    }

    // Show a specific product image
    public function show($id)
    {
        $productImage = ProductImage::find($id);

        if (!$productImage) {
            return response()->json([
                'status' => false,
                'message' => 'Hình ảnh không tồn tại',
            ], 404);
        }

        $productImage->thumbnail = asset('images/product/' . $productImage->thumbnail); // Generate full URL

        return response()->json([
            'status' => true,
            'message' => 'Chi tiết hình ảnh sản phẩm',
            'productImage' => $productImage,
        ]);
    }

    // Add new product image
    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:product,id',
            'thumbnail' => 'required|file|mimes:jpeg,png,jpg,gif',
        ]);

        $productImage = new ProductImage();
        $productImage->product_id = $request->product_id;

        if ($request->hasFile('thumbnail')) {
            $file = $request->file('thumbnail');
            $exten = $file->extension();
            $imageName = time() . '.' . $exten; // Generate a unique name
            $file->move(public_path('images/product'), $imageName);
            $productImage->thumbnail = $imageName;
        }

        $productImage->save();

        return response()->json([
            'status' => true,
            'message' => 'Thêm hình ảnh sản phẩm thành công',
            'productImage' => $productImage,
        ]);
    }

     
    public function update(Request $request, $id)
    {
    $productImage = ProductImage::find($id);

    if (!$productImage) {
        return response()->json([
            'status' => false,
            'message' => 'Hình ảnh không tồn tại',
        ], 404);
    }

    // Validate that the file is an image and is optional
    $request->validate([
        'thumbnail' => 'nullable|file|mimes:jpeg,png,jpg,gif',
    ]);

    // Check if a new thumbnail image is uploaded
    if ($request->hasFile('thumbnail')) {
        // Delete the old image if it exists
        if ($productImage->thumbnail && file_exists(public_path('images/product/' . $productImage->thumbnail))) {
            unlink(public_path('images/product/' . $productImage->thumbnail)); // Delete old image
        }

        $file = $request->file('thumbnail');
        $exten = $file->extension();

        // Use the original name without extension
        $originalName = pathinfo($productImage->thumbnail, PATHINFO_FILENAME);

        // Save the new file with the same original name but with the new extension
        $imageName = $originalName . '.' . $exten;
        $file->move(public_path('images/product'), $imageName);
        
        // Update the thumbnail field with the new file name
        $productImage->thumbnail = $imageName;
    }

    $productImage->save();

    return response()->json([
        'status' => true,
        'message' => 'Cập nhật hình ảnh sản phẩm thành công',
        'productImage' => $productImage,
    ]);
}


    

    // Restore product image from trash
    public function restore($id)
    {
        $productImage = ProductImage::find($id);

        if (!$productImage) {
            return response()->json([
                'status' => false,
                'message' => 'Hình ảnh không tồn tại trong thùng rác',
            ], 404);
        }

        $productImage->status = 1;  // Restore status
        $productImage->save();

        return response()->json([
            'status' => true,
            'message' => 'Hình ảnh đã được khôi phục',
        ]);
    }

    // Permanently delete product image
    public function destroy($id)
    {
        $productImage = ProductImage::find($id);

        if (!$productImage) {
            return response()->json([
                'status' => false,
                'message' => 'Hình ảnh không tồn tại',
            ], 404);
        }

        if ($productImage->thumbnail && file_exists(public_path('images/product/' . $productImage->thumbnail))) {
            unlink(public_path('images/product/' . $productImage->thumbnail)); // Delete file from server
        }

        $productImage->delete();

        return response()->json([
            'status' => true,
            'message' => 'Hình ảnh đã được xóa vĩnh viễn',
        ]);
    }
}
