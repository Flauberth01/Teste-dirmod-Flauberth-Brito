<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'currency' => strtoupper((string) $this->input('currency')),
        ]);
    }

    public function rules(): array
    {
        return [
            'amount_original' => ['required', 'numeric', 'min:0.01', 'regex:/^\d{1,13}(\.\d{1,2})?$/'],
            'currency' => ['required', 'string', 'size:3', 'regex:/^[A-Z]{3}$/'],
        ];
    }
}
