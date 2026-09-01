<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'requirements_pdf' => ['required', 'file', 'mimes:pdf', 'max:10240'],
            'hourly_rate' => ['nullable', 'numeric', 'min:1', 'max:999999.99'],
            'country' => ['required', 'string', Rule::in(array_keys(config('estimator.countries', [])))],
        ];
    }
}
