<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreOfferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'business_id' => 'required|exists:users,id',
            'title' => 'required|string|max:255',
            'discount_percentage' => 'required|numeric|min:0|max:100',
            'discount_cap' => 'nullable|numeric|min:0',
            'applicable_users' => 'required|in:new,regular,daily',
            'offer_time' => 'required|in:all_day,morning_only,evening_only',
            'campaign_start_date' => 'required|date',
            'campaign_end_date' => 'nullable|date|after_or_equal:campaign_start_date',
            'campaign_start_time' => 'required|date_format:H:i:s',
            'campaign_end_time' => 'nullable|date_format:H:i:s|after:campaign_start_time',
            'applicable_days' => 'required|in:Entire Week,Mon-Thu,Fri-Sun',
            'terms_and_conditions'=> 'nullable|string',
            'description' => 'nullable|string',
            'min_order_value' => 'required|numeric|min:0',
            'banner_image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            redirect()->back()->withErrors($validator)->withInput()
        );
    }
}