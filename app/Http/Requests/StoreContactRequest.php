<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreContactRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Allow all users to create contacts; adjust this if necessary.
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255', // Contact name is required
            'email' => 'required|email|max:255', // Email must be valid
            'phone' => 'required|string|max:15', // Phone is optional but must be a string and up to 15 characters
            'title' => 'required|string|max:255', // Title is required
            'content' => 'required|string|max:2000', // Content is required with a max of 2000 characters
            // 'status' => 'required|boolean', // Status must be true (1) or false (0)
        ];
    }

    /**
     * Get custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Tên liên hệ là bắt buộc.',
            'email.required' => 'Email là bắt buộc.',
            'email.email' => 'Email phải là địa chỉ email hợp lệ.',
            'phone.string' => 'Số điện thoại phải là chuỗi ký tự.',
            'phone.required' => 'Số điện thoại là bắt buộc.',

            'title.required' => 'Tiêu đề là bắt buộc.',
            'content.required' => 'Nội dung là bắt buộc.',
            // 'status.required' => 'Trạng thái là bắt buộc.',
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
