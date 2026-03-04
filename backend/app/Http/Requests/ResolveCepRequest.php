<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResolveCepRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'cep' => preg_replace('/\D/', '', (string) $this->route('cep')),
        ]);
    }

    public function rules(): array
    {
        return [
            'cep' => ['required', 'string', 'regex:/^\d{8}$/'],
        ];
    }
}
