<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use Illuminate\Http\Request;
use App\Http\Requests\StoreContactRequest;
use App\Http\Requests\UpdateContactRequest;

class ContactController extends Controller
{
    // List active contacts
    public function index()
    {
        $contacts = Contact::where('status', '!=', 0)
            ->orderBy('created_at', 'DESC')
            ->select("id", "name", "email", "phone", "title", "content", "replay_id", "created_by", "updated_by", "created_at", "updated_at", "status")
            ->get();

        $result = [
            'status' => true,
            'message' => 'Danh sách liên hệ',
            'contacts' => $contacts,
        ];

        return response()->json($result);
    }

    // List contacts in trash
    public function trash()
    {
        $contacts = Contact::where('status', '=', 0)
            ->orderBy('created_at', 'DESC')
            ->select("id", "name", "email", "phone", "title", "content", "replay_id", "created_by", "updated_by", "created_at", "updated_at", "status")
            ->get();

        $result = [
            'status' => true,
            'message' => 'Danh sách liên hệ trong thùng rác',
            'contacts' => $contacts,
        ];

        return response()->json($result);
    }

    // Show contact details
    public function show($id)
    {
        $contact = Contact::find($id);
        if (!$contact) {
            return response()->json([
                'status' => false,
                'message' => 'Liên hệ không tìm thấy',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Chi tiết liên hệ',
            'contact' => $contact,
        ]);
    }

    // Store new contact
    public function store(StoreContactRequest $request)
    {
        $contact = new Contact();
        $contact->name = $request->name;
        $contact->email = $request->email;
        $contact->phone = $request->phone;
        $contact->title = $request->title;
        $contact->content = $request->content;
        $contact->replay_id = 1;
        $contact->created_by = 1; // This should be the authenticated user id
        $contact->status = 1;

        if ($contact->save()) {
            $result = [
                'status' => true,
                'message' => 'Thêm liên hệ thành công',
                'contact' => $contact,
            ];
        } else {
            $result = [
                'status' => false,
                'message' => 'Không thể thêm liên hệ',
                'contact' => null,
            ];
        }

        return response()->json($result);
    }

    // Update contact
    public function update(UpdateContactRequest $request, $id)
    {
        $contact = Contact::find($id);
        if (!$contact) {
            return response()->json([
                'status' => false,
                'message' => 'Liên hệ không tồn tại',
            ], 404);
        }

        // Update contact details
        $contact->name = $request->name;
        $contact->email = $request->email;
        $contact->phone = $request->phone;
        $contact->title = $request->title;
        $contact->content = $request->content;
        $contact->replay_id = $request->replay_id;
        $contact->updated_by = 1; // This should be the authenticated user id
        $contact->status = $request->status;
        $contact->updated_at = now();

        if ($contact->save()) {
            return response()->json([
                'status' => true,
                'message' => 'Cập nhật liên hệ thành công',
                'contact' => $contact,
            ]);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Cập nhật liên hệ thất bại',
                'contact' => null,
            ]);
        }
    }

    // Soft delete contact (Move to trash)
    public function delete($id)
    {
        $contact = Contact::find($id);
        if (!$contact) {
            return response()->json([
                'status' => false,
                'message' => 'Liên hệ không tồn tại',
            ], 404);
        }

        $contact->status = 0; // Move to trash
        $contact->updated_at = now();

        if ($contact->save()) {
            return response()->json([
                'status' => true,
                'message' => 'Xóa liên hệ thành công',
            ]);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Xóa liên hệ thất bại',
            ]);
        }
    }

    // Restore contact from trash
    public function restore($id)
    {
        $contact = Contact::find($id);
        if (!$contact) {
            return response()->json([
                'status' => false,
                'message' => 'Liên hệ không tồn tại',
            ], 404);
        }

        $contact->status = 1; // Restore contact
        $contact->updated_at = now();

        if ($contact->save()) {
            return response()->json([
                'status' => true,
                'message' => 'Khôi phục liên hệ thành công',
            ]);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Khôi phục liên hệ thất bại',
            ]);
        }
    }

    // Permanently delete contact
    public function destroy($id)
    {
        $contact = Contact::find($id);
        if (!$contact) {
            return response()->json([
                'status' => false,
                'message' => 'Liên hệ không tồn tại',
            ], 404);
        }

        if ($contact->delete()) {
            return response()->json([
                'status' => true,
                'message' => 'Xóa vĩnh viễn liên hệ thành công',
            ]);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Xóa vĩnh viễn liên hệ thất bại',
            ]);
        }
    }

    // Toggle contact status
    public function status($id)
    {
        $contact = Contact::find($id);
        if (!$contact) {
            return response()->json([
                'status' => false,
                'message' => 'Liên hệ không tồn tại',
            ], 404);
        }

        $contact->status = ($contact->status == 1) ? 2 : 1; // Toggle between active and inactive
        $contact->updated_at = now();
        $contact->save();

        return response()->json([
            'status' => true,
            'message' => 'Cập nhật trạng thái liên hệ thành công',
            'contact' => $contact,
        ]);
    }
}
