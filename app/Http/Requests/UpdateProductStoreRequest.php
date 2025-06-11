<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateProductStoreRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'product_id' => 'required|integer',
            'type' => 'required|string|max:255',
            'price_root' => 'required|numeric|min:0',
            'qty' => 'required|integer|min:0',
            'status' => 'required|integer',
        ];
    }

    /**
     * Custom validation messages.
     */
    public function messages(): array
    {
        return [
            'product_id.required'=> 'Sản phẩm là bắt buộc',
             'type.required' => 'Loại sản phẩm là bắt buộc.',
            'type.string' => 'Loại sản phẩm phải là chuỗi ký tự.',
            'price_root.required' => 'Giá gốc là bắt buộc.',
            'price_root.numeric' => 'Giá gốc phải là một số hợp lệ.',
            'qty.required' => 'Số lượng là bắt buộc.',
            'qty.integer' => 'Số lượng phải là một số nguyên.',   
            'status.required' => 'Trạng thái là bắt buộc.',
            'status.integer' => 'Trạng thái phải là giá trị hợp lệ (1 hoặc 2).',
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
        ], 422));
    }
}
