<?php

namespace App\Http\Controllers;

use App\Models\Config;
use Illuminate\Http\Request;
use App\Http\Requests\StoreConfigRequest;
use App\Http\Requests\UpdateConfigRequest;

class ConfigController extends Controller
{
 
    public function index()
    {
        $configs = Config::where('status', '!=', 0)
             ->select("id", "site_name", "email", "phones", "address", "hotline", "zalo", "facebook",  "status")
            ->get();

        $result = [
            'status' => true,
            'message' => 'Danh sách cấu hình',
            'configs' => $configs,
        ];
        return response()->json($result);
    }

    // List configs in trash
    public function trash()
    {
        $configs = Config::where('status', '=', 0)
     
            ->select("id", "site_name", "email", "phones", "address", "hotline", "zalo", "facebook",  "status")
            ->get();

        $result = [
            'status' => true,
            'message' => 'Danh sách cấu hình trong thùng rác',
            'configs' => $configs,
        ];

        return response()->json($result);
    }

    // Show config details
    public function show($id)
    {
        $config = Config::find($id);
        if (!$config) {
            return response()->json([
                'status' => false,
                'message' => 'Cấu hình không tìm thấy',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Chi tiết cấu hình',
            'config' => $config,
        ]);
    }

    // Store new config
    public function store(StoreConfigRequest $request)
    {
        $config = new Config();
        $config->site_name = $request->site_name;
        $config->email = $request->email;
        $config->phones = $request->phones;
        $config->address = $request->address;
        $config->hotline = $request->hotline;
        $config->zalo = $request->zalo;
        $config->facebook = $request->facebook;
 
        $config->status = $request->status;

        if ($config->save()) {
            $result = [
                'status' => true,
                'message' => 'Thêm cấu hình thành công',
                'config' => $config,
            ];
        } else {
            $result = [
                'status' => false,
                'message' => 'Không thể thêm cấu hình',
                'config' => null,
            ];
        }

        return response()->json($result);
    }

    // Update config
    public function update(UpdateConfigRequest $request, $id)
    {
        $config = Config::find($id);
        if (!$config) {
            return response()->json([
                'status' => false,
                'message' => 'Cấu hình không tồn tại',
            ], 404);
        }
        $config->site_name = $request->site_name;
        $config->email = $request->email;
        $config->phones = $request->phones;
        $config->address = $request->address;
        $config->hotline = $request->hotline;
        $config->zalo = $request->zalo;
        $config->facebook = $request->facebook;
        $config->status = $request->status;
        if ($config->save()) {
            return response()->json([
                'status' => true,
                'message' => 'Cập nhật cấu hình thành công',
                'config' => $config,
            ]);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Cập nhật cấu hình thất bại',
                'config' => null,
            ]);
        }
    }

    // Soft delete config (Move to trash)
    public function delete($id)
    {
        $config = Config::find($id);
        if (!$config) {
            return response()->json([
                'status' => false,
                'message' => 'Cấu hình không tồn tại',
            ], 404);
        }

        $config->status = 0; // Move to trash
 

        if ($config->save()) {
            return response()->json([
                'status' => true,
                'message' => 'Xóa cấu hình thành công',
            ]);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Xóa cấu hình thất bại',
            ]);
        }
    }

    // Restore config from trash
    public function restore($id)
    {
        $config = Config::find($id);
        if (!$config) {
            return response()->json([
                'status' => false,
                'message' => 'Cấu hình không tồn tại',
            ], 404);
        }

        $config->status = 1; // Restore config
 

        if ($config->save()) {
            return response()->json([
                'status' => true,
                'message' => 'Khôi phục cấu hình thành công',
            ]);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Khôi phục cấu hình thất bại',
            ]);
        }
    }

    // Permanently delete config
    public function destroy($id)
    {
        $config = Config::find($id);
        if (!$config) {
            return response()->json([
                'status' => false,
                'message' => 'Cấu hình không tồn tại',
            ], 404);
        }

        if ($config->delete()) {
            return response()->json([
                'status' => true,
                'message' => 'Xóa vĩnh viễn cấu hình thành công',
            ]);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Xóa vĩnh viễn cấu hình thất bại',
            ]);
        }
    }

    // Toggle config status
    public function status($id)
    {
        $config = Config::find($id);
        if (!$config) {
            return response()->json([
                'status' => false,
                'message' => 'Cấu hình không tồn tại',
            ], 404);
        }

        $config->status = ($config->status == 1) ? 2 : 1;  
  
        $config->save();

        return response()->json([
            'status' => true,
            'message' => 'Cập nhật trạng thái cấu hình thành công',
            'config' => $config,
        ]);
    }
}
