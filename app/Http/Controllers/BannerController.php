<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\StoreBannerRequest;
use App\Http\Requests\UpdateBannerRequest;
use Illuminate\Support\Str;
class BannerController extends Controller
{
    // List active banners
    public function index()
    {
        $banners = Banner::where('status', '!=', 0)
            ->orderBy('sort_order', 'ASC')
            ->select("id", "name", "link", "image", "description", "position", "sort_order", "created_by", "updated_by", "created_at", "updated_at", "status")
            ->get();

        // Generate full URL for banner images
        foreach ($banners as $banner) {
            if ($banner->image) {
                $banner->image = asset('images/banner/' . $banner->image);
            }
        }

        return response()->json([
            'status' => true,
            'message' => 'Danh sách banner',
            'banners' => $banners,
        ]);
    }

    public function bannerFe()
    {
        $banners = Banner::where('status', '=', 1)
            // ->orderBy('sort_order', 'ASC')
            ->select("id", "name", "link", "image", "description", "position", "sort_order", "created_by", "updated_by", "created_at", "updated_at", "status")
            ->get();

        // Generate full URL for banner images
        foreach ($banners as $banner) {
            if ($banner->image) {
                $banner->image = asset('images/banner/' . $banner->image);
            }
        }

        return response()->json([
            'status' => true,
            'message' => 'Danh sách banner',
            'banners' => $banners,
        ]);
    }
    // List banners in trash
    public function trash()
    {
        $banners = Banner::where('status', '=', 0)
            ->orderBy('sort_order', 'ASC')
            ->select("id", "name", "link", "image", "description", "position", "sort_order", "created_by", "updated_by", "created_at", "updated_at", "status")
            ->get();

        foreach ($banners as $banner) {
            if ($banner->image) {
                $banner->image = asset('images/banner/' . $banner->image);
            }
        }

        return response()->json([
            'status' => true,
            'message' => 'Danh sách banner trong thùng rác',
            'banners' => $banners,
        ]);
    }

    // Show details of a specific banner
    public function show($id)
    {
        $banner = Banner::find($id);
        if (!$banner) {
            return response()->json([
                'status' => false,
                'message' => 'Banner không tìm thấy',
            ], 404);
        }

        if ($banner->image) {
            $banner->image = asset('images/banner/' . $banner->image);
        }

        return response()->json([
            'status' => true,
            'message' => 'Chi tiết banner',
            'banner' => $banner,
        ]);
    }

    // Store a new banner
    public function store(StoreBannerRequest $request)
    {
        $banner = new Banner();
        $banner->name = $request->name;
        $banner->link = Str::of($request->name)->slug('-');
        $banner->description = $request->description;
        $banner->position = $request->position;
        $banner->sort_order =1; // Assuming you pass this value from the request

        $banner->created_by =1; // Assuming you pass this value from the request
        $banner->status = $request->status;

        // Handle image upload
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $exten = $file->extension();
            $imageName = date('YmdHis') . "." . $exten;
            $file->move(public_path('images/banner'), $imageName);
            $banner->image = $imageName;
        }

        if ($banner->save()) {
            return response()->json([
                'status' => true,
                'message' => 'Thêm banner thành công',
                'banner' => $banner,
            ]);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Không thể thêm banner',
            ]);
        }
    }

    // Update an existing banner
  // Update an existing banner
public function update(UpdateBannerRequest $request, $id)
{
    $banner = Banner::find($id);
    
    if (!$banner) {
        return response()->json([
            'status' => false,
            'message' => 'Banner không tồn tại',
        ], 404);
    }

    // Update banner details
    $banner->name = $request->name;
    $banner->link = Str::of($request->name)->slug('-');
    $banner->description = $request->description;
    $banner->position = $request->position;
    $banner->sort_order = $request->sort_order ?? 1; // Set sort_order to 1 if not provided
    $banner->updated_by = $request->updated_by ?? 1; // Assuming updated_by is passed in the request
    $banner->status = $request->status;

    // Handle image upload and remove old image if necessary
    if ($request->hasFile('image')) {
        // Check if there is an existing image and remove it
        if ($banner->image && file_exists(public_path('images/banner/' . $banner->image))) {
            unlink(public_path('images/banner/' . $banner->image));
        }

        // Process the new image
        $file = $request->file('image');
        $exten = $file->extension();
        $imageName = date('YmdHis') . "." . $exten;
        $file->move(public_path('images/banner'), $imageName);
        $banner->image = $imageName;
    }

    // Save the updated banner data
    if ($banner->save()) {
        return response()->json([
            'status' => true,
            'message' => 'Cập nhật banner thành công',
            'banner' => $banner,
        ]);
    } else {
        return response()->json([
            'status' => false,
            'message' => 'Cập nhật banner thất bại',
        ]);
    }
}


    // Soft delete (move to trash)
    public function delete($id)
    {
        $banner = Banner::find($id);
        if (!$banner) {
            return response()->json([
                'status' => false,
                'message' => 'Banner không tồn tại',
            ], 404);
        }

        $banner->status = 0; // Move to trash
        $banner->updated_at = now();

        if ($banner->save()) {
            return response()->json([
                'status' => true,
                'message' => 'Xóa banner thành công',
            ]);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Xóa banner thất bại',
            ]);
        }
    }

    // Restore a banner from trash
    public function restore($id)
    {
        $banner = Banner::find($id);
        if (!$banner) {
            return response()->json([
                'status' => false,
                'message' => 'Banner không tồn tại',
            ], 404);
        }

        $banner->status = 1; // Restore banner
        $banner->updated_at = now();

        if ($banner->save()) {
            return response()->json([
                'status' => true,
                'message' => 'Khôi phục banner thành công',
            ]);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Khôi phục banner thất bại',
            ]);
        }
    }

    // Permanently delete a banner
    public function destroy($id)
    {
        $banner = Banner::find($id);
        if (!$banner) {
            return response()->json([
                'status' => false,
                'message' => 'Banner không tồn tại',
            ], 404);
        }

        // Delete the banner image from storage if it exists
        if ($banner->image && file_exists(public_path('images/banner/' . $banner->image))) {
            unlink(public_path('images/banner/' . $banner->image));
        }

        if ($banner->delete()) {
            return response()->json([
                'status' => true,
                'message' => 'Xóa vĩnh viễn banner thành công',
            ]);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Xóa vĩnh viễn banner thất bại',
            ]);
        }
    }

    // Toggle banner status
    public function status($id)
    {
        $banner = Banner::find($id);
        if (!$banner) {
            return response()->json([
                'status' => false,
                'message' => 'Banner không tồn tại',
            ], 404);
        }

        $banner->status = ($banner->status == 1) ? 0 : 1; // Toggle between active (1) and inactive (0)
        $banner->updated_at = now();
        $banner->save();

        return response()->json([
            'status' => true,
            'message' => 'Cập nhật trạng thái banner thành công',
            'banner' => $banner,
        ]);
    }
}
