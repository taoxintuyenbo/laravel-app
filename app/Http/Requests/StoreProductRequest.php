<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
class StoreProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'detail' => 'required|string',
            'price' => 'required|numeric|min:0',
            'description' => 'required|string',
            'category_id' => 'required|integer|exists:category,id',
            'brand_id' => 'required|integer|exists:brand,id',
            'status' => 'required',

            'thumbnail.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // For images
        ];
    }
    public function messages(): array
    {
        return [
            'name.required' => 'Tên sản phẩm là bắt buộc.',
            'detail.required' => 'Chi tiết sản phẩm là bắt buộc.',
            'price.required' => 'Giá sản phẩm là bắt buộc.',
            'description.required' => 'Mo ta sản phẩm là bắt buộc.',
            'price.numeric' => 'Giá sản phẩm phải là một số.',
            'category_id.required' => 'Danh mục là bắt buộc.',
            'brand_id.required' => 'Thương hiệu là bắt buộc.',
            'status.required' => 'Trạng thái là bắt buộc.',
            'thumbnail.image' => 'Tệp tải lên phải là một hình ảnh.',
            'thumbnail.mimes' => 'Ảnh phải có định dạng jpeg, png, jpg, hoặc gif.',
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
