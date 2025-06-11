<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use Illuminate\Support\Str;
class CategoryController extends Controller
{
    // List active categories
    public function index()
    {
        $categories = Category::where('status', '!=', 0)
            ->orderBy('created_at', 'DESC')
            ->select("id", "name", "slug", "parent_id", "sort_order", "image", "description", "created_by", "updated_by", "created_at", "updated_at", "status")
            ->get();

        // Generate full URL for category images
        foreach ($categories as $category) {
            if ($category->image) {
                $category->image = asset('images/categories/' . $category->image);
            }
        }

        $result = [
            'status' => true,
            'message' => 'Danh sách danh mục',
            'categories' => $categories,
        ];

        return response()->json($result);
    }

    // List categories in trash
    public function trash()
    {
        $categories = Category::where('status', '=', 0)
            ->orderBy('created_at', 'DESC')
            ->select("id", "name", "slug", "parent_id", "sort_order", "image", "description", "created_by", "updated_by", "created_at", "updated_at", "status")
            ->get();

        foreach ($categories as $category) {
            if ($category->image) {
                $category->image = asset('images/categories/' . $category->image);
            }
        }

        $result = [
            'status' => true,
            'message' => 'Danh sách danh mục trong thùng rác',
            'categories' => $categories,
        ];

        return response()->json($result);
    }

    // Show category details
    public function show($id)
    {
        $category = Category::find($id);
        if (!$category) {
            return response()->json([
                'status' => false,
                'message' => 'Danh mục không tìm thấy',
            ], 404);
        }

        // Generate full URL for the image
        if ($category->image) {
            $category->image = asset('images/categories/' . $category->image);
        }

        return response()->json([
            'status' => true,
            'message' => 'Chi tiết danh mục',
            'category' => $category,
        ]);
    }

    // Store new category
    public function store(StoreCategoryRequest $request)
    {
        $category = new Category();
        $category->name = $request->name;
        $category->slug = Str::of($request->name)->slug('-');
        $category->parent_id = $request->parent_id ?? 0;
        $category->sort_order = $request->sort_order ?? 0;
        if ($request->hasFile('image')) {
            $file = $request->file('image');  // Corrected line
            $exten = $file->extension();  // Get the image extension
            $imageName = $category->slug . "." . $exten;  // Generate a unique image name using the slug
            $file->move(public_path('images/categories'), $imageName);  // Move the image to 'images
            $category->image =  $imageName;  // Save the image path in the database
        }
        $category->description = $request->description;
        $category->created_by = 1;
        $category->status = $request->status;

        if ($category->save()) {
            $result = [
                'status' => true,
                'message' => 'Thêm danh mục thành công',
                'category' => $category,
            ];
        } else {
            $result = [
                'status' => false,
                'message' => 'Không thể thêm danh mục',
                'category' => null,
            ];
        }

        return response()->json($result);
    }

    // Update category
    public function update(UpdateCategoryRequest $request, $id)
{
    $category = Category::find($id);
    if (!$category) {
        return response()->json([
            'status' => false,
            'message' => 'Danh mục không tồn tại',
        ], 404);
    }

    // Update category details
    $category->name = $request->name;
    $category->slug = Str::of($request->name)->slug('-');
    $category->parent_id = $request->parent_id ?? 0;
    $category->sort_order = $request->sort_order ?? 0;

 
    if ($request->hasFile('image')) {
         if ($category->image && file_exists(public_path('images/categories/' . $category->image))) {
            unlink(public_path('images/categories/' . $category->image));
        }

         $file = $request->file('image');
        $exten = $file->extension();
        $imageName = $category->slug . "." . $exten;
        $file->move(public_path('images/categories'), $imageName);
        $category->image = $imageName; // Save the new image name in the database
    }
 
    $category->description = $request->description;
    $category->updated_by = $request->updated_by;
    $category->status = $request->status;
    $category->updated_at = now();

    if ($category->save()) {
        return response()->json([
            'status' => true,
            'message' => 'Cập nhật danh mục thành công',
            'category' => $category,
        ]);
    } else {
        return response()->json([
            'status' => false,
            'message' => 'Cập nhật danh mục thất bại',
            'category' => null,
        ]);
    }
}


    // Soft delete category (Move to trash)
    public function delete($id)
    {
        $category = Category::find($id);
        if (!$category) {
            return response()->json([
                'status' => false,
                'message' => 'Danh mục không tồn tại',
            ], 404);
        }

        $category->status = 0; // Move to trash
        $category->updated_at = now();

        if ($category->save()) {
            return response()->json([
                'status' => true,
                'message' => 'Xóa danh mục thành công',
            ]);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Xóa danh mục thất bại',
            ]);
        }
    }

    // Restore category from trash
    public function restore($id)
    {
        $category = Category::find($id);
        if (!$category) {
            return response()->json([
                'status' => false,
                'message' => 'Danh mục không tồn tại',
            ], 404);
        }

        $category->status = 1; // Restore category
        $category->updated_at = now();

        if ($category->save()) {
            return response()->json([
                'status' => true,
                'message' => 'Khôi phục danh mục thành công',
            ]);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Khôi phục danh mục thất bại',
            ]);
        }
    }

    // Permanently delete category
    public function destroy($id)
    {
        $category = Category::find($id);
        if (!$category) {
            return response()->json([
                'status' => false,
                'message' => 'Danh mục không tồn tại',
            ], 404);
        }

        if ($category->delete()) {
            return response()->json([
                'status' => true,
                'message' => 'Xóa vĩnh viễn danh mục thành công',
            ]);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Xóa vĩnh viễn danh mục thất bại',
            ]);
        }
    }

    // Toggle category status
    public function status($id)
    {
        $category = Category::find($id);
        if (!$category) {
            return response()->json([
                'status' => false,
                'message' => 'Danh mục không tồn tại',
            ], 404);
        }

        $category->status = ($category->status == 1) ? 2 : 1; // Toggle between 1 and 2
        $category->updated_at = now();
        $category->save();

        return response()->json([
            'status' => true,
            'message' => 'Cập nhật trạng thái danh mục thành công',
            'category' => $category,
        ]);
    }
}
