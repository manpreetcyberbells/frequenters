<?php

namespace App\Http\Requests\B2B;

use Illuminate\Foundation\Http\FormRequest;

class SendB2BMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'business';
    }

    public function rules(): array
    {
        return [
            'body' => 'required|string|max:2000',
        ];
    }
}