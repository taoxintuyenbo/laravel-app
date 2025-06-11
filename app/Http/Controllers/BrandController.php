<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBrandRequest;
use App\Http\Requests\UpdateBrandRequest;
use Illuminate\Http\Request;
use App\Models\Brand;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class BrandController extends Controller
{
    // List all active brands
    public function index()
    {
        $brands = Brand::where('status', '!=', 0)
            ->orderBy('created_at', 'DESC')
            ->select("id", "name", "slug", "image", "description", "status", "created_at", "updated_at")
            ->get();

        // Generate full URL for brand images
        foreach ($brands as $brand) {
            if ($brand->image) {
                $brand->image = asset('images/brand/' . $brand->image);
            }
        }

        return response()->json([
            'status' => true,
            'message' => 'Danh sách thương hiệu',
            'brands' => $brands
        ]);
    }

    // List brands in trash
    public function trash()
    {
        $brands = Brand::where('status', '=', 0)
            ->orderBy('created_at', 'DESC')
            ->select("id", "name", "slug", "image", "description", "status", "created_at", "updated_at")
            ->get();

        foreach ($brands as $brand) {
            if ($brand->image) {
                $brand->image = asset('images/brand/' . $brand->image);
            }
        }

        return response()->json([
            'status' => true,
            'message' => 'Danh sách thương hiệu trong thùng rác',
            'brands' => $brands
        ]);
    }

    // Show a single brand by id
    public function show($id)
    {
        $brand = Brand::find($id);
        if (!$brand) {
            return response()->json([
                'status' => false,
                'message' => 'Thương hiệu không tồn tại'
            ]);
        }

        // Generate full URL for brand image
        if ($brand->image) {
            $brand->image = asset('images/brand/' . $brand->image);
        }

        return response()->json([
            'status' => true,
            'message' => 'Chi tiết thương hiệu',
            'brand' => $brand
        ]);
    }

    // Store a new brand
    public function store(StoreBrandRequest $request)
    {
        $brand = new Brand();
        $brand->name = $request->name;
        $brand->slug = Str::slug($request->name); // Automatically generate slug from name
        $brand->description = $request->description;
        $brand->created_by = 1;
        $brand->status = $request->status;

        // Handle image upload
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $extension = $file->extension();
            $imageName =$brand->slug . "." . $extension;
            $file->move(public_path('images/brand'), $imageName);
            $brand->image = $imageName;
        }

        if ($brand->save()) {
            return response()->json([
                'status' => true,
                'message' => 'Thêm thương hiệu thành công',
                'brand' => $brand
            ]);
        }

        return response()->json([
            'status' => false,
            'message' => 'Không thể thêm thương hiệu'
        ]);
    }

    // Update an existing brand
    public function update(StoreBrandRequest $request, $id)
    {
     
        \Log::info('Brand update request:', $request->all()); // Log the incoming request data
 
        $brand = Brand::find($id);
        if (!$brand) {
            return response()->json([
                'status' => false,
                'message' => 'Thương hiệu không tồn tại',
            ], 404);
        }

        // Update brand details
        $brand->name = $request->name;
        $brand->slug = Str::slug($request->name); // Slug is created from the name
        $brand->description = $request->description ?? $brand->description; // If description is not provided, keep the old one
        $brand->status = $request->status ?? $brand->status; // Keep the existing status if not provided
        // $brand->updated_by = $request->user()->id; // Assume this field is populated with the authenticated user's ID
        $brand->updated_at = now();

        // Handle image upload and remove old image if necessary
        if ($request->hasFile('image')) {
            // Remove old image if it exists
            if ($brand->image && file_exists(public_path('images/brand/' . $brand->image))) {
                unlink(public_path('images/brand/' . $brand->image));
            }

            // Process the new image
            $file = $request->file('image');
            $extension = $file->extension();
            $imageName = $brand->slug . "." . $extension;
            $file->move(public_path('images/brand'), $imageName);
            $brand->image = $imageName; // Save the new image name in the database
        }

        // Save the updated brand
        if ($brand->save()) {
            return response()->json([
                'status' => true,
                'message' => 'Cập nhật thương hiệu thành công',
                'brand' => $brand,
            ]);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Cập nhật thương hiệu thất bại',
                'brand' => null,
            ]);
        }
    }
    


    // Soft delete a brand
    public function delete($id)
    {
        $brand = Brand::find($id);
        if (!$brand) {
            return response()->json([
                'status' => false,
                'message' => 'Thương hiệu không tồn tại'
            ]);
        }

        $brand->status = 0; // Move to trash
        if ($brand->save()) {
            return response()->json([
                'status' => true,
                'message' => 'Xóa thương hiệu thành công',
                'brand' => $brand
            ]);
        }

        return response()->json([
            'status' => false,
            'message' => 'Xóa thương hiệu thất bại'
        ]);
    }

    // Restore a brand from trash
    public function restore($id)
    {
        $brand = Brand::find($id);
        if (!$brand) {
            return response()->json([
                'status' => false,
                'message' => 'Thương hiệu không tồn tại'
            ]);
        }

        $brand->status = 1; // Restore
        if ($brand->save()) {
            return response()->json([
                'status' => true,
                'message' => 'Khôi phục thương hiệu thành công',
                'brand' => $brand
            ]);
        }

        return response()->json([
            'status' => false,
            'message' => 'Khôi phục thương hiệu thất bại'
        ]);
    }

    // Permanently delete a brand
    public function destroy($id)
    {
        $brand = Brand::find($id);
        if (!$brand) {
            return response()->json([
                'status' => false,
                'message' => 'Thương hiệu không tồn tại'
            ]);
        }

        // Delete the brand image from storage if it exists
        if ($brand->image && file_exists(public_path('images/brand/' . $brand->image))) {
            unlink(public_path('images/brand/' . $brand->image));
        }

        if ($brand->delete()) {
            return response()->json([
                'status' => true,
                'message' => 'Xóa vĩnh viễn thương hiệu thành công'
            ]);
        }

        return response()->json([
            'status' => false,
            'message' => 'Xóa vĩnh viễn thương hiệu thất bại'
        ]);
    }

    // Toggle brand status
    public function status($id)
    {
        $brand = Brand::find($id);
        if (!$brand) {
            return response()->json([
                'status' => false,
                'message' => 'Thương hiệu không tồn tại'
            ]);
        }

        $brand->status = ($brand->status == 1) ? 0 : 1; // Toggle between active (1) and inactive (0)
        $brand->updated_at = now();
        if ($brand->save()) {
            return response()->json([
                'status' => true,
                'message' => 'Cập nhật trạng thái thương hiệu thành công',
                'brand' => $brand
            ]);
        }

        return response()->json([
            'status' => false,
            'message' => 'Cập nhật trạng thái thương hiệu thất bại'
        ]);
    }
}
