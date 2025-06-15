<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Log;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use Google_Client; 
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Http;  // <-- Thêm dòng này

class UserController extends Controller
{
    // List active users
    public function index()
    {
        $users = User::where('status', '!=', 0)
            ->orderBy('created_at', 'DESC')
            ->select("id", "name", "fullname", "email", "phone", "gender", "thumbnail", "roles", "created_by","login_method", "updated_by", "created_at", "updated_at", "status")
            ->get();

        foreach ($users as $user) {
            if ($user->thumbnail) {
                $user->thumbnail = asset('images/users/' . $user->thumbnail);
            }
        }

        $result = [
            'status' => true,
            'message' => 'Danh sách người dùng',
            'users' => $users,
        ];

        return response()->json($result);
    }

    // List users in trash
    public function trash()
    {
        $users = User::where('status', '=', 0)
            ->orderBy('created_at', 'DESC')
            ->select("id", "name", "fullname", "email", "phone", "gender", "thumbnail", "roles", "created_by", "login_method","updated_by", "created_at", "updated_at", "status")
            ->get();

        foreach ($users as $user) {
            if ($user->thumbnail) {
                $user->thumbnail = asset('images/users/' . $user->thumbnail);
            }
        }

        $result = [
            'status' => true,
            'message' => 'Danh sách người dùng trong thùng rác',
            'users' => $users,
        ];

        return response()->json($result);
    }

    // Show user details
    public function show($id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Người dùng không tìm thấy',
            ], 404);
        }

        // Generate full URL for the thumbnail
        if ($user->thumbnail) {
            $user->thumbnail = asset('images/users/' . $user->thumbnail);
        }

        return response()->json([
            'status' => true,
            'message' => 'Chi tiết người dùng',
            'user' => $user,
        ]);
    }

    // Store new user
    public function store(StoreUserRequest $request)
    {
         

        $user = new User();
        $user->name = $request->name;
        $user->fullname = $request->fullname;
        $user->email = $request->email;
        $user->phone = $request->phone;
        $user->gender = $request->gender;
        $user->address = $request->address;
        $user->status = 1;
        // $user->roles = $request->roles;
        $user->password = Hash::make($request->password); // Hash the password
        $user->created_by = 1; // This should be the ID of the authenticated admin

        if ($request->hasFile('thumbnail')) {
            $file = $request->file('thumbnail');
            $exten = $file->extension();
            $thumbnailName = Str::slug($user->name) . "." . $exten;
            $file->move(public_path('images/users'), $thumbnailName);
            $user->thumbnail = $thumbnailName;
        }
        if ($user->save()) {
            $result = [
                'status' => true,
                'message' => 'Thêm người dùng thành công',
                'user' => $user,
            ];
        } else {
            $result = [
                'status' => false,
                'message' => 'Không thể thêm người dùng',
                'user' => null,
            ];
        }

        return response()->json($result);
    }

    // Update user
    public function update(UpdateUserRequest $request, $id)
    {
        Log::info('User update request data:', $request->all());

        $user = User::find($id);
        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Người dùng không tồn tại',
            ], 404);
        }

        $user->name = $request->name;
        $user->fullname = $request->fullname;
        $user->email = $request->email;
        $user->phone = $request->phone;
        $user->gender = $request->gender;
        $user->address = $request->address;
        if ($request->roles) {
         $user->roles = $request->roles;
        }
 
        if ($request->password && $request->password !== 'undefined' && $request->password !== '') {
            // Hash and update password only if it's provided and valid
            $user->password = Hash::make($request->password);
        }
        
        $user->status = 1;

        if ($request->hasFile('thumbnail')) {
            if ($user->thumbnail && file_exists(public_path('images/users/' . $user->thumbnail))) {
                unlink(public_path('images/users/' . $user->thumbnail));
            }
            $file = $request->file('thumbnail');
            $exten = $file->extension();
            $thumbnailName = Str::slug($user->name) . "." . $exten;
            $file->move(public_path('images/users'), $thumbnailName);
            $user->thumbnail = $thumbnailName;
        }
         if ($user->save()) {
            return response()->json([
                'status' => true,
                'message' => 'Cập nhật người dùng thành công',
                'user' => $user,
            ]);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Cập nhật người dùng thất bại',
                'user' => null,
            ]);
        }
    }

    // Soft delete user (Move to trash)
    public function delete($id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Người dùng không tồn tại',
            ], 404);
        }

        $user->status = 0; // Move to trash
        $user->updated_at = now();

        if ($user->save()) {
            return response()->json([
                'status' => true,
                'message' => 'Xóa người dùng thành công',
            ]);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Xóa người dùng thất bại',
            ]);
        }
    }

    // Restore user from trash
    public function restore($id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Người dùng không tồn tại',
            ], 404);
        }

        $user->status = 1; // Restore user
        $user->updated_at = now();

        if ($user->save()) {
            return response()->json([
                'status' => true,
                'message' => 'Khôi phục người dùng thành công',
            ]);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Khôi phục người dùng thất bại',
            ]);
        }
    }

    // Permanently delete user
    public function destroy($id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Người dùng không tồn tại',
            ], 404);
        }

        if ($user->delete()) {
            return response()->json([
                'status' => true,
                'message' => 'Xóa vĩnh viễn người dùng thành công',
            ]);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Xóa vĩnh viễn người dùng thất bại',
            ]);
        }
    }

    // Toggle user status
    public function status($id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Người dùng không tồn tại',
            ], 404);
        }

        $user->status = ($user->status == 1) ? 2 : 1; // Toggle between 1 (active) and 2 (inactive)
        $user->updated_at = now();
        $user->save();

        return response()->json([
            'status' => true,
            'message' => 'Cập nhật trạng thái người dùng thành công',
            'user' => $user,
        ]);
    }

    public function login(Request $request)
    {
        // Validate input with custom messages in Vietnamese
        $validator = \Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ], [
            'email.required' => 'Trường địa chỉ email là bắt buộc.',
            'email.email' => 'Địa chỉ email không hợp lệ.',
            'password.required' => 'Mật khẩu là bắt buộc.',
        ]);
    
        // Check if validation fails
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $validator->errors(),
            ], 422);
        }
        // Attempt to find user by email
        $user = User::where('email', $request->email)->first();

        // Check if user exists and password is correct
        if ($user && Hash::check($request->password, $user->password)) {
            // Return success response with token
            return response()->json([
                'status' => true,
                'message' => 'Đăng nhập thành công',
"pass"=>$user->password,
                'user' => $user
            ], 200);
        }

        // Return error if login fails
        return response()->json([
            'status' => false,
            'message' => 'Thông tin đăng nhập không chính xác',
        ], 401);
    }


//     public function loginWithGoogle(Request $request)
// {
//     $request->validate([
//         'token' => 'required|string',
//     ], [
//         'token.required' => 'Token Google là bắt buộc',
//     ]);

//     $client = new Google_Client(['client_id' => env('GOOGLE_CLIENT_ID')]);
//     $payload = $client->verifyIdToken($request->token);
//     \Log::info('Google login payload:', $payload);

//     if (!$payload) {
//         return response()->json([
//             'status' => false,
//             'message' => 'Token Google không hợp lệ',
//         ], 401);
//     }

//     $googleId = $payload['sub'];       // Google user ID
//     $email = $payload['email'] ?? null;
//     $fullname = $payload['name'] ?? 'Người dùng Google';
//     $name = $payload['given_name'] ?? 'Người dùng Google';
//     $thumbnail=$payload['picture'];
//     if (!$email) {
//         return response()->json([
//             'status' => false,
//             'message' => 'Email từ Google không tồn tại',
//         ], 400);
//     }

//     // Tìm hoặc tạo user
//     $user = User::where('email', $email)->first();

//     if (!$user) {
//         $user = new User();
//         $user->name = $name;
//         $user->fullname = $fullname;
//         $user->email = $email;
//         $user->google_id = $googleId;
//         $user->password = Hash::make(Str::random(16)); // mật khẩu ngẫu nhiên
//         $user->login_method = 'google';
//         $user->status = 1;
//         $user->roles = 'customer';    
//         $user->thumbnail = $thumbnail;
//         $user->save();
//     } else {
//          $update = false;
//         if (!$user->google_id) {
//             $user->google_id = $googleId;
//             $update = true;
//         }
//         if ($user->login_method !== 'google') {
//             $user->login_method = 'google';
//             $update = true;
//         }
//         if ($update) {
//             $user->save();
//         }
//     }

//     // Trả về dữ liệu giống hàm login hiện tại
//     return response()->json([
//         'status' => true,
//         'message' => 'Đăng nhập thành công bằng Google',
//         'user' => $user,
//     ], 200);
// }

// public function loginWithFacebook(Request $request)
// {
//     $request->validate([
//         'access_token' => 'required|string',
//     ], [
//         'access_token.required' => 'Token Facebook là bắt buộc',
//     ]);

//     $accessToken = $request->access_token;

//     // Gọi Facebook Graph API lấy thông tin user
//     $fbResponse = Http::get('https://graph.facebook.com/me', [
//         'fields' => 'id,name,email,first_name,picture',
//         'access_token' => $accessToken,
//     ]);
//     \Log::info('Facebook login payload:', $fbResponse->json());

//     if ($fbResponse->failed()) {
//         return response()->json([
//             'status' => false,
//             'message' => 'Token Facebook không hợp lệ hoặc hết hạn',
//         ], 401);
//     }

//     $fbUser = $fbResponse->json();

//     $facebookId = $fbUser['id'] ?? null;
//     $email = $fbUser['email'] ?? null;
//     $fullname = $fbUser['name'] ?? 'Người dùng Facebook';
//     $name = $fbUser['first_name'] ?? 'Người dùng Facebook';
//     $thumbnail = $fbUser['picture']['data']['url'] ?? null;

//     if (!$email) {
//         return response()->json([
//             'status' => false,
//             'message' => 'Email từ Facebook không tồn tại',
//         ], 400);
//     }

//     // Tìm hoặc tạo user
//     $user = User::where('email', $email)->first();

//     if (!$user) {
//         $user = new User();
//         $user->name = $name;
//         $user->fullname = $fullname;
//         $user->email = $email;
//         $user->facebook_id = $facebookId;
//         $user->password = Hash::make(Str::random(16)); // mật khẩu ngẫu nhiên
//         $user->login_method = 'facebook';
//         $user->status = 1;
//         $user->roles = 'customer';    
//         $user->thumbnail = $thumbnail;
//         $user->save();
//     } else {
//         $update = false;
//         if (!$user->facebook_id) {
//             $user->facebook_id = $facebookId;
//             $update = true;
//         }
//         if ($user->login_method !== 'facebook') {
//             $user->login_method = 'facebook';
//             $update = true;
//         }
//         if ($update) {
//             $user->save();
//         }
//     }

//     return response()->json([
//         'status' => true,
//         'message' => 'Đăng nhập thành công bằng Facebook',
//         'user' => $user,
//     ], 200);
// }
public function loginWithGoogle(Request $request)
{
    $request->validate([
        'token' => 'required|string',
    ], [
        'token.required' => 'Token Google là bắt buộc',
    ]);

    $client = new Google_Client(['client_id' => env('GOOGLE_CLIENT_ID')]);
    $payload = $client->verifyIdToken($request->token);
    \Log::info('Google login payload:', $payload);

    if (!$payload) {
        return response()->json([
            'status' => false,
            'message' => 'Token Google không hợp lệ',
        ], 401);
    }

    $googleId = $payload['sub'];       // Google user ID
    $email = $payload['email'] ?? null;
    $fullname = $payload['name'] ?? 'Người dùng Google';
    $name = $payload['given_name'] ?? 'Người dùng Google';
    $thumbnail = $payload['picture'];

    if (!$email) {
        return response()->json([
            'status' => false,
            'message' => 'Email từ Google không tồn tại',
        ], 400);
    }

    // Tìm hoặc tạo user
    $user = User::where('email', $email)->first();

    if (!$user) {
        // Create a new user if not found
        $user = new User();
        $user->name = $name;
        $user->fullname = $fullname;
        $user->email = $email;
        $user->google_id = $googleId;
        $user->password = Hash::make(Str::random(16)); // Random password
        $user->login_method = 'google';
        $user->status = 1;
        $user->roles = 'customer';    
        $user->thumbnail = $thumbnail;
        $user->save();
    } else {
        // User exists, so update if necessary
        $update = false;
        
        // Update fields if they are different
        if ($user->name !== $fullname) {
            $user->name = $fullname;
            $update = true;
        }
        
        if ($user->thumbnail !== $thumbnail) {
            $user->thumbnail = $thumbnail;
            $update = true;
        }

        if ($user->login_method !== 'google') {
            $user->login_method = 'google';
            $update = true;
        }

        if ($update) {
            $user->save();
        }
    }

    return response()->json([
        'status' => true,
        'message' => 'Đăng nhập thành công bằng Google',
        'user' => $user,
    ], 200);
}


public function loginWithFacebook(Request $request)
{
    $request->validate([
        'access_token' => 'required|string',
    ], [
        'access_token.required' => 'Token Facebook là bắt buộc',
    ]);

    $accessToken = $request->access_token;

    // Gọi Facebook Graph API lấy thông tin user
    $fbResponse = Http::get('https://graph.facebook.com/me', [
        'fields' => 'id,name,email,first_name,picture',
        'access_token' => $accessToken,
    ]);
    \Log::info('Facebook login payload:', $fbResponse->json());

    if ($fbResponse->failed()) {
        return response()->json([
            'status' => false,
            'message' => 'Token Facebook không hợp lệ hoặc hết hạn',
        ], 401);
    }

    $fbUser = $fbResponse->json();

    $facebookId = $fbUser['id'] ?? null;
    $email = $fbUser['email'] ?? null;
    $fullname = $fbUser['name'] ?? 'Người dùng Facebook';
    $name = $fbUser['first_name'] ?? 'Người dùng Facebook';
    $thumbnail = $fbUser['picture']['data']['url'] ?? null;

    if (!$email) {
        return response()->json([
            'status' => false,
            'message' => 'Email từ Facebook không tồn tại',
        ], 400);
    }

    // Tìm user qua email đã đăng ký
    $user = User::where('email', $email)->first();

    if (!$user) {
        // Create a new user if not found
        $user = new User();
        $user->name = $name;
        $user->fullname = $fullname;
        $user->email = $email;
        $user->facebook_id = $facebookId;
        $user->password = Hash::make(Str::random(16)); // Random password
        $user->login_method = 'facebook';
        $user->status = 1;
        $user->roles = 'customer';    
        $user->thumbnail = $thumbnail;
        $user->save();
    } else {
        // User exists, so update if necessary
        $update = false;
        
        // Update fields if they are different
        if ($user->name !== $fullname) {
            $user->name = $fullname;
            $update = true;
        }
        
        if ($user->thumbnail !== $thumbnail) {
            $user->thumbnail = $thumbnail;
            $update = true;
        }

        if ($user->login_method !== 'facebook') {
            $user->login_method = 'facebook';
            $update = true;
        }

        if ($update) {
            $user->save();
        }
    }

    return response()->json([
        'status' => true,
        'message' => 'Đăng nhập thành công bằng Facebook',
        'user' => $user,
    ], 200);
}

}
