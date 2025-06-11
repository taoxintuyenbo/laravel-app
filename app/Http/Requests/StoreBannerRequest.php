<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreBannerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Change this based on authorization logic
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            // 'link' => 'required', // Link is required and must be a valid URL
            'description' => 'required|string|max:1000', // Description is required
            'position' => 'required|string|in:slideshow,ads', // Position must be either 'slideshow' or 'ads'
            'status' => 'required|integer', // 1 or 0 for active/inactive
            'image' => 'required|mimes:jpeg,png,jpg,gif,avif,webp,|max:2048', // Image validation
        ];
    }

    /**
     * Get custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Tên banner là bắt buộc.',
            'name.string' => 'Tên banner phải là chuỗi ký tự.',
            'name.max' => 'Tên banner không được vượt quá 255 ký tự.',
            // 'link.required' => 'Link banner là bắt buộc.',
             'description.required' => 'Mô tả là bắt buộc.',
            'description.string' => 'Mô tả phải là chuỗi ký tự.',
            'description.max' => 'Mô tả không được vượt quá 1000 ký tự.',
            'position.required' => 'Vị trí là bắt buộc.',
            'position.in' => 'Vị trí phải là "slideshow" hoặc "ads".',
            'status.required' => 'Trạng thái là bắt buộc.',
            'image.required' => 'Hinh anh là bắt buộc.',

             'image.mimes' => 'Ảnh phải có định dạng jpeg, png, jpg, hoặc gif.',
            'image.max' => 'Kích thước ảnh tối đa là 2MB.',
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'status' => false,
            'message' => 'Lỗi xác thực dữ liệu',
            'errors' => $validator->errors(),
        ], 422));  // 422 Unprocessable Entity
    }
}
