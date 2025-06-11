<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'fullname' => 'required|string|max:255',
            'password' => 'required|string|max:255',
 
            // 'gender' => 'required|in:1,0', // Assuming 1 is Male, 0 is Female
            'phone' => 'required|string|max:15',
            'email' => 'required|email|max:255|unique:user,email',
            'address' => 'nullable|string|max:500',
            // 'roles' => 'required|string|max:255',
             'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // Optional, max 2MB
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Tên người dùng là bắt buộc.',
            'fullname.required' => 'Ho ten người dùng là bắt buộc.',
            'name.string' => 'Tên người dùng phải là chuỗi ký tự.',
            'password.required' => 'Password là bắt buộc.',

            'fullname.string' => 'Họ và tên phải là chuỗi ký tự.',
            // 'gender.required' => 'Giới tính là bắt buộc.',
            // 'gender.in' => 'Giới tính không hợp lệ.',
            'phone.required' => 'Số điện thoại là bắt buộc.',
            'email.required' => 'Email là bắt buộc.',
            'email.email' => 'Định dạng email không hợp lệ.',
            'email.unique' => 'Email này đã tồn tại.',
            'address.string' => 'Địa chỉ phải là chuỗi ký tự.',
            // 'roles.required' => 'Vai trò là bắt buộc.',
            // 'roles.string' => 'Vai trò phải là chuỗi ký tự.',
            
            'thumbnail.image' => 'Ảnh đại diện phải là một hình ảnh.',
            'thumbnail.mimes' => 'Ảnh đại diện phải có định dạng jpeg, png, jpg, hoặc gif.',
            'thumbnail.max' => 'Ảnh đại diện không được vượt quá 2MB.',
        ];
    }
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'status' => false,
            'message' => 'Lỗi xác thực dữ liệu',
            'errors' => $validator->errors(),
        ], 422));  // 422 Unprocessable Entity
    }
}
