<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreTopicRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            // 'slug' => 'required|string|max:255|unique:topics,slug',
            // 'sort_order' => 'nullable|integer',
            'description' => 'nullable|string',
            // 'status' => 'required|boolean',
            // 'created_by' => 'required|integer',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Tên chủ đề là bắt buộc.',
            // 'slug.required' => 'Slug là bắt buộc.',
            // 'slug.unique' => 'Slug đã tồn tại, vui lòng chọn slug khác.',
            'status.required' => 'Trạng thái là bắt buộc.',
            // 'created_by.required' => 'Người tạo là bắt buộc.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'status' => false,
            'message' => 'Validation errors',
            'errors' => $validator->errors(),
        ]));
    }
}
