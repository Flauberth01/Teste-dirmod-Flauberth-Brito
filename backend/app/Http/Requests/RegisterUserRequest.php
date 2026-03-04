<?php

namespace App\Http\Requests;

use App\Rules\ValidCpf;
use Illuminate\Foundation\Http\FormRequest;

class RegisterUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => strtolower((string) $this->input('email')),
            'cpf' => preg_replace('/\D/', '', (string) $this->input('cpf')),
            'cep' => preg_replace('/\D/', '', (string) $this->input('cep')),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'cpf' => ['required', 'string', 'size:11', 'unique:users,cpf', new ValidCpf()],
            'cep' => ['required', 'string', 'regex:/^\d{8}$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nome é obrigatório.',
            'email.required' => 'E-mail é obrigatório.',
            'email.email' => 'E-mail inválido.',
            'email.unique' => 'Este e-mail já está cadastrado.',
            'password.required' => 'Senha é obrigatória.',
            'password.min' => 'Senha deve ter no mínimo 8 caracteres.',
            'password.confirmed' => 'As senhas não coincidem.',
            'cpf.required' => 'CPF é obrigatório.',
            'cpf.size' => 'CPF deve conter 11 dígitos.',
            'cpf.unique' => 'Este CPF já está cadastrado.',
            'cep.required' => 'CEP é obrigatório.',
            'cep.regex' => 'CEP deve conter 8 dígitos.',
        ];
    }
}
