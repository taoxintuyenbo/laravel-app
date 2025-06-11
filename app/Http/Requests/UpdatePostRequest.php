<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdatePostRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Allow all users to make requests (adjust if necessary)
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'topic_id' => 'required',
            'content' => 'required|string',
            'description' => 'nullable|string|max:500',
            'thumbnail' => 'nullable|image|mimes:jpeg,png|max:2048',
            'type' => 'required|',
  
        ];
    }

    /**
     * Custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Tiêu đề bài viết không được để trống.',
            'topic_id.required' => 'Chủ đề bài viết là bắt buộc.',
            'topic_id.exists' => 'Chủ đề không hợp lệ.',
            'content.required' => 'Nội dung bài viết không được để trống.',
            'thumbnail.image' => 'Thumbnail phải là một hình ảnh hợp lệ.',
            'thumbnail.mimes' => 'Thumbnail phải có định dạng jpeg hoặc png.',
            'type.required' => 'Loại bài viết là bắt buộc.',
           
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'status' => false,
            'message' => 'Validation errors',
            'errors' => $validator->errors()
        ], 422));
    }
}
