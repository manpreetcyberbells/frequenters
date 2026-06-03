<?php

namespace App\Http\Requests\B2B;

use Illuminate\Foundation\Http\FormRequest;

class CreateCollabPostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'business';
    }

    public function rules(): array
    {
        return [
            'post_type'          => 'required|in:request,offer',
            'headline'           => 'required|string|max:255',
            'description'        => 'required|string|max:2000',
            'target_industries'  => 'required|array|min:1',
            'target_industries.*'=> 'string|max:100',
            'duration_days'      => 'required|in:3,7,15',
            'contact_type'       => 'required|in:in_app,direct',
            // Contact fields required only if contact_type = direct
            'contact_name'       => 'required_if:contact_type,direct|nullable|string|max:255',
            'contact_email'      => 'required_if:contact_type,direct|nullable|email',
            'contact_phone'      => 'required_if:contact_type,direct|nullable|string|max:20',
        ];
    }

    public function messages(): array
    {
        return [
            'contact_name.required_if'  => 'Contact name is required when sharing direct contact info.',
            'contact_email.required_if' => 'Contact email is required when sharing direct contact info.',
        ];
    }
}