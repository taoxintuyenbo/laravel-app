<?php

namespace App\Http\Controllers;

use App\Models\Topic;
use Illuminate\Http\Request;
use App\Http\Requests\StoreTopicRequest;
use App\Http\Requests\UpdateTopicRequest;
use Illuminate\Support\Str;

class TopicController extends Controller
{
    // List active topics
    public function index()
    {
        $topics = Topic::where('status', '!=', 0)
            ->orderBy('created_at', 'DESC')
            ->select("id", "name", "slug", "sort_order", "description", "created_by", "updated_by", "created_at", "updated_at", "status")
            ->get();

        $result = [
            'status' => true,
            'message' => 'Danh sách chủ đề',
            'topics' => $topics,
        ];

        return response()->json($result);
    }

    // List topics in trash
    public function trash()
    {
        $topics = Topic::where('status', '=', 0)
            ->orderBy('created_at', 'DESC')
            ->select("id", "name", "slug", "sort_order", "description", "created_by", "updated_by", "created_at", "updated_at", "status")
            ->get();

        $result = [
            'status' => true,
            'message' => 'Danh sách chủ đề trong thùng rác',
            'topics' => $topics,
        ];

        return response()->json($result);
    }

    // Show topic details
    public function show($id)
    {
        $topic = Topic::find($id);
        if (!$topic) {
            return response()->json([
                'status' => false,
                'message' => 'Chủ đề không tìm thấy',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Chi tiết chủ đề',
            'topic' => $topic,
        ]);
    }

    // Store new topic
    public function store(StoreTopicRequest $request)
    {
        $topic = new Topic();
        $topic->name = $request->name;
        $topic->slug = Str::of($request->name)->slug('-');
        $topic->sort_order = $request->sort_order ?? 0;
        $topic->description = $request->description;
        $topic->created_by = 1;  
        $topic->status = $request->status;
        if ($topic->save()) {
            $result = [
                'status' => true,
                'message' => 'Thêm chủ đề thành công',
                'topic' => $topic,
            ];
        } else {
            $result = [
                'status' => false,
                'message' => 'Không thể thêm chủ đề',
                'topic' => null,
            ];
        }

        return response()->json($result);
    }

    // Update topic
    public function update(UpdateTopicRequest $request, $id)
    {
        $topic = Topic::find($id);
        if (!$topic) {
            return response()->json([
                'status' => false,
                'message' => 'Chủ đề không tồn tại',
            ], 404);
        }

        // Update topic details
        $topic->name = $request->name;
        $topic->slug = Str::of($request->name)->slug('-');
        $topic->sort_order = $request->sort_order ?? 0;
        $topic->description = $request->description;
        $topic->updated_by = $request->updated_by; // Replace with actual user ID
        $topic->status = $request->status;
        $topic->updated_at = now();

        if ($topic->save()) {
            return response()->json([
                'status' => true,
                'message' => 'Cập nhật chủ đề thành công',
                'topic' => $topic,
            ]);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Cập nhật chủ đề thất bại',
                'topic' => null,
            ]);
        }
    }

    // Soft delete topic (Move to trash)
    public function delete($id)
    {
        $topic = Topic::find($id);
        if (!$topic) {
            return response()->json([
                'status' => false,
                'message' => 'Chủ đề không tồn tại',
            ], 404);
        }

        $topic->status = 0; // Move to trash
        $topic->updated_at = now();

        if ($topic->save()) {
            return response()->json([
                'status' => true,
                'message' => 'Xóa chủ đề thành công',
            ]);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Xóa chủ đề thất bại',
            ]);
        }
    }

    // Restore topic from trash
    public function restore($id)
    {
        $topic = Topic::find($id);
        if (!$topic) {
            return response()->json([
                'status' => false,
                'message' => 'Chủ đề không tồn tại',
            ], 404);
        }

        $topic->status = 1; // Restore topic
        $topic->updated_at = now();

        if ($topic->save()) {
            return response()->json([
                'status' => true,
                'message' => 'Khôi phục chủ đề thành công',
            ]);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Khôi phục chủ đề thất bại',
            ]);
        }
    }

    // Permanently delete topic
    public function destroy($id)
    {
        $topic = Topic::find($id);
        if (!$topic) {
            return response()->json([
                'status' => false,
                'message' => 'Chủ đề không tồn tại',
            ], 404);
        }

        if ($topic->delete()) {
            return response()->json([
                'status' => true,
                'message' => 'Xóa vĩnh viễn chủ đề thành công',
            ]);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Xóa vĩnh viễn chủ đề thất bại',
            ]);
        }
    }

    // Toggle topic status
    public function status($id)
    {
        $topic = Topic::find($id);
        if (!$topic) {
            return response()->json([
                'status' => false,
                'message' => 'Chủ đề không tồn tại',
            ], 404);
        }

        $topic->status = ($topic->status == 1) ? 2 : 1; // Toggle between 1 and 2
        $topic->updated_at = now();
        $topic->save();

        return response()->json([
            'status' => true,
            'message' => 'Cập nhật trạng thái chủ đề thành công',
            'topic' => $topic,
        ]);
    }
}
