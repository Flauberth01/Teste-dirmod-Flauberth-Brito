<?php

namespace App\Http\Requests;

use App\Rules\ValidCpf;
use Illuminate\Foundation\Http\FormRequest;

class ValidateCpfRealtimeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'cpf' => preg_replace('/\D/', '', (string) $this->route('cpf')),
        ]);
    }

    public function rules(): array
    {
        return [
            'cpf' => ['required', 'string', 'size:11', new ValidCpf()],
        ];
    }
}
