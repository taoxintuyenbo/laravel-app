<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreMenuRequest extends FormRequest
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
            //  'type' => 'required|in:category,page,post,topic', // Adjust based on types
            'table_id' => 'nullable|integer',
         ];
    }

    /**
     * Custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
      
            // 'type.required' => 'Loại menu là bắt buộc.',
            // 'type.in' => 'Loại menu không hợp lệ.',
 
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
