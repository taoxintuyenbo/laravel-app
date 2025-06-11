<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePostRequest;
use App\Http\Requests\UpdatePostRequest;
use Illuminate\Http\Request;
use App\Models\Post;
use Illuminate\Support\Str;

class PostController extends Controller
{

    // Get all active posts
    public function index()
    {
        $posts = Post::where('status', '!=', 0)
            ->orderBy('created_at', 'DESC')
            ->select('id', 'title', 'topic_id', 'description', 'thumbnail', 'type', 'status', 'created_at', 'updated_at')
            ->get();

        // Generate full URL for post thumbnails
        foreach ($posts as $post) {
            if ($post->thumbnail) {
                $post->thumbnail = asset('images/posts/' . $post->thumbnail);
            }
        }

        return response()->json([
            'status' => true,
            'message' => 'Danh sách bài viết',
            'posts' => $posts,
        ]);
    }


    public function trash()
    {
        $posts = Post::where('status', '=', 0)
            ->orderBy('created_at', 'DESC')
            ->select('id', 'title', 'topic_id', 'description', 'thumbnail', 'type', 'status', 'created_at', 'updated_at')
            ->get();

        // Generate full URL for post thumbnails
        foreach ($posts as $post) {
            if ($post->thumbnail) {
                $post->thumbnail = asset('images/posts/' . $post->thumbnail);
            }
        }

        return response()->json([
            'status' => true,
            'message' => 'Danh sách bài viết',
            'posts' => $posts,
        ]);
    }
    // Show a specific post
    public function show($id)
    {
        $post = Post::find($id);

        if (!$post) {
            return response()->json([
                'status' => false,
                'message' => 'Bài viết không tồn tại',
            ], 404);
        }

        // Generate full URL for the thumbnail
        if ($post->thumbnail) {
            $post->thumbnail = asset('images/posts/' . $post->thumbnail);
        }

        return response()->json([
            'status' => true,
            'message' => 'Chi tiết bài viết',
            'post' => $post,
        ]);
    }

    // Store a new post
    public function store(StorePostRequest $request)
    {
        $post = new Post();
        $post->title = $request->title;
        $post->slug = Str::slug($request->title);
        $post->topic_id = $request->topic_id;
        $post->content = $request->content;
        $post->description = $request->description;
        $post->type = $request->type;
        $post->created_by = 1;
        $post->status = $request->status;

        // Handle file upload for thumbnail
        if ($request->hasFile('thumbnail')) {
            $file = $request->file('thumbnail');
            $extension = $file->extension();
            $thumbnailName = $post->title . "." . $extension;
            $file->move(public_path('images/posts'), $thumbnailName);
            $post->thumbnail = $thumbnailName;
        }

        if ($post->save()) {
            return response()->json([
                'status' => true,
                'message' => 'Bài viết đã được tạo thành công',
                'post' => $post,
            ], 201);
        }

        return response()->json([
            'status' => false,
            'message' => 'Không thể tạo bài viết',
        ], 500);
    }

    // Update an existing post
    public function update(UpdatePostRequest $request, $id)
    {
        $post = Post::find($id);

        if (!$post) {
            return response()->json([
                'status' => false,
                'message' => 'Bài viết không tồn tại',
            ], 404);
        }

        $post->title = $request->title;
        // $post->slug = Str::slug($request->title);
        $post->topic_id = $request->topic_id;
        $post->content = $request->content;
        $post->description = $request->description;
        $post->type = $request->type;
        $post->updated_by = 1;
        $post->status = $request->status;

        // Handle file upload for thumbnail
        if ($request->hasFile('thumbnail')) {
            // Delete the old image if it exists
            if ($post->thumbnail && file_exists(public_path('images/posts/' . $post->thumbnail))) {
                unlink(public_path('images/posts/' . $post->thumbnail));
            }

            $file = $request->file('thumbnail');
            $extension = $file->extension();
            $thumbnailName = $post->title . "." . $extension;
            $file->move(public_path('images/posts'), $thumbnailName);
            $post->thumbnail = $thumbnailName;
        }

        if ($post->save()) {
            return response()->json([
                'status' => true,
                'message' => 'Bài viết đã được cập nhật thành công',
                'post' => $post,
            ]);
        }

        return response()->json([
            'status' => false,
            'message' => 'Không thể cập nhật bài viết',
        ], 500);
    }

    // Soft delete a post (Move to trash)
    public function delete($id)
    {
        $post = Post::find($id);

        if (!$post) {
            return response()->json([
                'status' => false,
                'message' => 'Bài viết không tồn tại',
            ], 404);
        }

        $post->status = 0; // Soft delete
        $post->updated_at = now();
        $post->save();

        return response()->json([
            'status' => true,
            'message' => 'Bài viết đã được xóa thành công',
        ]);
    }

    // Restore a soft-deleted post
    public function restore($id)
    {
        $post = Post::find($id);

        if (!$post) {
            return response()->json([
                'status' => false,
                'message' => 'Bài viết không tồn tại',
            ], 404);
        }

        $post->status = 1; // Restore post
        $post->updated_at = now();
        $post->save();

        return response()->json([
            'status' => true,
            'message' => 'Bài viết đã được khôi phục thành công',
        ]);
    }

    // Permanently delete a post
    public function destroy($id)
    {
        $post = Post::find($id);

        if (!$post) {
            return response()->json([
                'status' => false,
                'message' => 'Bài viết không tồn tại',
            ], 404);
        }

        // Delete the image file if it exists
        if ($post->thumbnail && file_exists(public_path('images/posts/' . $post->thumbnail))) {
            unlink(public_path('images/posts/' . $post->thumbnail));
        }

        $post->delete(); // Permanently delete the post

        return response()->json([
            'status' => true,
            'message' => 'Bài viết đã được xóa vĩnh viễn',
        ]);
    }

    // Toggle post status
    public function status($id)
    {
        $post = Post::find($id);
        if (!$post) {
            return response()->json([
                'status' => false,
                'message' => 'Bài viết không tồn tại',
            ], 404);
        }

        $post->status = ($post->status == 1) ? 2 : 1;  
        $post->updated_at = now();
        $post->save();

        return response()->json([
            'status' => true,
            'message' => 'Cập nhật trạng thái bài viết thành công',
            'post' => $post,
        ]);
    }
    public function singlepage($slug)
    {
        $post = Post::where('slug', $slug)->first();

        if (!$post) {
            return response()->json([
                'status' => false,
                'message' => 'Bài viết không tồn tạiii',
            ], 404);
        }
  
        if ($post->thumbnail) {
            $post->thumbnail = asset('images/posts/' . $post->thumbnail);
        }
   
        return response()->json([
            'status' => true,
            'message' => 'Load bài viết thành công',
            'post' => $post,
        ]);
    }
    public function getAllPosts()
    {
        $posts = Post::where('status', 1)
        ->where('type', 'post')
        ->orderBy('created_at', 'DESC')
        ->get();
    
        foreach ($posts as $post) {
            if ($post->thumbnail) {
                $post->thumbnail = asset('images/posts/' . $post->thumbnail);
            }
        }
        return response()->json([
            'status' => true,
            'message' => 'Load bài viết thành công',
            'post' => $posts,
        ]);
    }

  
    
    
}
