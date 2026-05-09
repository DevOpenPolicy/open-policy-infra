<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OnboardingRequest extends FormRequest
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
            'name' => 'required|string',
            'company_size' => 'required|string',
            'country' => 'required|string',
            'industries' => 'required|array',
            'use_cases' => 'required|array',
            'policy_interests' => 'required|array',
            'alert_preference' => 'required|string',
        ];
    }
}
