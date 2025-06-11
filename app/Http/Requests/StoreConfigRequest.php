<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreConfigRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Adjust this based on your authorization logic
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'site_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phones' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'hotline' => 'required|string|max:255',
            'zalo' => 'required|string|max:255',
            'facebook' => 'required|url|max:255',
            // 'status' => 'required|boolean',
        ];
    }

    /**
     * Get custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            'site_name.required' => 'Tên trang web là bắt buộc.',
            'email.required' => 'Email là bắt buộc.',
            'email.email' => 'Email không hợp lệ.',
            'phones.required' => 'Số điện thoại là bắt buộc.',
            'phones.string' => 'Số điện thoại phải là chuỗi ký tự.',
            'address.required' => 'Địa chỉ là bắt buộc.',
            'address.string' => 'Địa chỉ phải là chuỗi ký tự.',
            'hotline.required' => 'Hotline là bắt buộc.',
            'hotline.string' => 'Hotline phải là chuỗi ký tự.',
            'zalo.required' => 'Zalo là bắt buộc.',
            'zalo.string' => 'Zalo phải là chuỗi ký tự.',
            'facebook.required' => 'Facebook là bắt buộc.',
            'facebook.url' => 'Địa chỉ Facebook không hợp lệ.',
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
