<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class RegisterBusinessRequest extends FormRequest
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
            'business_email' => 'required|email|unique:users,email',
            'business_phone' => 'nullable|string|max:15|unique:users,phone',
            'password' => 'required|min:8|confirmed',
            'password_confirmation' => 'required',
            'business_name' => 'required|string|max:255',
            'category_id' => 'required|string|max:50',
            'child_category_id' => 'required|string|max:50',
            'address_line1' => 'required|string',
            'zip_code' => 'required|numeric',
            'city' => 'required|string',
            'state' => 'required|string',
            'country' => 'required|string',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'points_per_checkin' => 'nullable|integer|min:1|max:100',
            'conversion_rate' => 'nullable|numeric|min:0.01|max:100',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'message' => 'Validation failed',
            'errors' => $validator->errors(),
        ], 422));
    }

}
