<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Log;
use App\Models\Menu;
use App\Models\Category;

use App\Models\Brand;
use App\Models\Post;
use App\Models\Topic;

use Illuminate\Http\Request;
use App\Http\Requests\StoreMenuRequest;
use App\Http\Requests\UpdateMenuRequest;
use Illuminate\Support\Str;

class MenuController extends Controller
{
 
    public function index()
    {
        $menus = Menu::where('status', '!=', 0)
            ->orderBy('created_at', 'DESC')
            ->select("id", "name", "link", "type", "table_id", "created_by", "updated_by", "created_at", "updated_at", "status")
            ->get();

        $result = [
            'status' => true,
            'message' => 'Danh sách menu',
            'menus' => $menus,
        ];

        return response()->json($result);
    }
    public function parentMenu($id)
{
    // Ensure that 'table' is the correct column name
    $menus = Menu::where('status', '!=', 0)
        ->where('table_id', '=', 0) // Assuming 'table_id' is the correct column
        ->where('id', '!=', $id) // Exclude the provided menu ID
        ->orderBy('created_at', 'DESC')
        ->select("id", "name", "link", "type", "table_id", "created_by", "updated_by", "created_at", "updated_at", "status")
        ->get();

    $result = [
        'status' => true,
        'message' => 'Danh sách menu',
        'menus' => $menus,
    ];

    return response()->json($result);
}

    public function trash()
    {
        $menus = Menu::where('status', '=', 0)
            ->orderBy('created_at', 'DESC')
            ->select("id", "name", "link", "type", "table_id", "created_by", "updated_by", "created_at", "updated_at", "status")
            ->get();

        $result = [
            'status' => true,
            'message' => 'Danh sách menu trong thùng rác',
            'menus' => $menus,
        ];

        return response()->json($result);
    }

    // Show menu details
    public function show($id)
    {
        $menu = Menu::find($id);
        if (!$menu) {
            return response()->json([
                'status' => false,
                'message' => 'Menu không tìm thấy',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Chi tiết menu',
            'menu' => $menu,
        ]);
    }

    // Store new menu
    public function store(StoreMenuRequest $request)
    {
        $menu = new Menu();
        Log::info('User update request data:', $request->all());
        if ($request->filled('category_id')) {
            // Handle Category Menu creation
            $category = Category::find($request->category_id);
            if ($category) {
                $menu->name = $category->name;
                $menu->link = 'danh-muc/' . $category->slug;
                $menu->position = $request->position;
                $menu->table_id = 0;
                $menu->type = 'category';
            }
        } elseif ($request->filled('brand_id')) {
            // Handle Brand Menu creation
            $brand = Brand::find($request->brand_id);
            if ($brand) {
                $menu->name = $brand->name;
                $menu->link = 'thuong-hieu/' . $brand->slug;
                $menu->position = $request->position;
                $menu->table_id = 0;
                $menu->type = 'brand';
            }
        } elseif ($request->filled('topic_id')) {
            // Handle Topic Menu creation
            $topic = Topic::find($request->topic_id);
            if ($topic) {
                $menu->name = $topic->name;
                $menu->link = 'chu-de/' . $topic->slug;
                $menu->position = $request->position;
                $menu->table_id = 0;
                $menu->type = 'topic';
            }
        } elseif ($request->filled('page_id')) {
            // Handle Page Menu creation
            $page = Post::find($request->page_id);
            if ($page) {
                $menu->name = $page->title;
                $menu->link = 'trang-don/' . $page->slug;
                $menu->position = $request->position;
                $menu->table_id = 0;
                $menu->type = 'page';
            }
        } elseif ($request->filled('name') && $request->filled('link')) {
            // Handle Custom Link Menu creation
            $menu->name = $request->name;
            $menu->link = $request->link;
            $menu->position = $request->position;
            $menu->type = 'custom';
            $menu->table_id = 0;  // No table id for custom links
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Missing necessary data for menu creation'
            ], 400);
        }
    
        $menu->created_by =   1;  
        $menu->status = $request->status;
    
        if ($menu->save()) {
            return response()->json([
                'status' => true,
                'message' => 'Thêm menu thành công',
                'menu' => $menu,
            ]);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Không thể thêm menu',
                'menu' => null,
            ], 500);
        }
    }
    

    // Update menu
    public function update(UpdateMenuRequest $request, $id)
    {
        $menu = Menu::find($id);
        if (!$menu) {
            return response()->json([
                'status' => false,
                'message' => 'Menu không tồn tại',
            ], 404);
        }

        // Update menu details
        $menu->name = $request->name;
        $menu->link = $request->link;
        $menu->type = $request->type;
        $menu->table_id = $request->table_id ?? 0;
        $menu->updated_by = $request->updated_by;
        $menu->status = $request->status;
        $menu->updated_at = now();

        if ($menu->save()) {
            return response()->json([
                'status' => true,
                'message' => 'Cập nhật menu thành công',
                'menu' => $menu,
            ]);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Cập nhật menu thất bại',
                'menu' => null,
            ]);
        }
    }

    // Soft delete menu (Move to trash)
    public function delete($id)
    {
        $menu = Menu::find($id);
        if (!$menu) {
            return response()->json([
                'status' => false,
                'message' => 'Menu không tồn tại',
            ], 404);
        }

        $menu->status = 0; // Move to trash
        $menu->updated_at = now();

        if ($menu->save()) {
            return response()->json([
                'status' => true,
                'message' => 'Xóa menu thành công',
            ]);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Xóa menu thất bại',
            ]);
        }
    }

    // Restore menu from trash
    public function restore($id)
    {
        $menu = Menu::find($id);
        if (!$menu) {
            return response()->json([
                'status' => false,
                'message' => 'Menu không tồn tại',
            ], 404);
        }

        $menu->status = 1; // Restore menu
        $menu->updated_at = now();

        if ($menu->save()) {
            return response()->json([
                'status' => true,
                'message' => 'Khôi phục menu thành công',
            ]);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Khôi phục menu thất bại',
            ]);
        }
    }

    // Permanently delete menu
    public function destroy($id)
    {
        $menu = Menu::find($id);
        if (!$menu) {
            return response()->json([
                'status' => false,
                'message' => 'Menu không tồn tại',
            ], 404);
        }

        if ($menu->delete()) {
            return response()->json([
                'status' => true,
                'message' => 'Xóa vĩnh viễn menu thành công',
            ]);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Xóa vĩnh viễn menu thất bại',
            ]);
        }
    }

    // Toggle menu status
    public function status($id)
    {
        $menu = Menu::find($id);
        if (!$menu) {
            return response()->json([
                'status' => false,
                'message' => 'Menu không tồn tại',
            ], 404);
        }

        $menu->status = ($menu->status == 1) ? 2 : 1; // Toggle between 1 and 2
        $menu->updated_at = now();
        $menu->save();

        return response()->json([
            'status' => true,
            'message' => 'Cập nhật trạng thái menu thành công',
            'menu' => $menu,
        ]);
    }

    public function mainMenu()
    {
        $menus = Menu::where([
            ['status', '=', 1],
            ['table_id', '=', 0], 
            ['position', '=', "mainmenu"], 
        ])->with('children')  
         ->get();
         $result = [
            'status' => true,
            'message' => 'Danh sách menu',
            'menus' => $menus,
        ];

        return response()->json($result);
    }
    public function footerMenu()
    {
        $menus = Menu::where([
            ['status', '=', 1],
            ['table_id', '=', 0], 
            ['position', '=', "footermenu"], 
        ])  
         ->get();
         $result = [
            'status' => true,
            'message' => 'Danh sách menu',
            'menus' => $menus,
        ];

        return response()->json($result);
    } 
}
