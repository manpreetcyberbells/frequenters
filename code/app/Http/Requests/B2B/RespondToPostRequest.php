<?php

namespace App\Http\Requests\B2B;

use Illuminate\Foundation\Http\FormRequest;

class RespondToPostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'business';
    }

    public function rules(): array
    {
        return [
            'message' => 'required|string|max:1000',
        ];
    }
}