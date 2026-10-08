<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterUserRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email'], // No ponemos 'unique:users' aquí para no acoplar la BD. El Caso de Uso ya lo valida.
            'password' => [
                'required',
                'string',
                Password::min(8)
                    ->mixedCase() //mayúscula + minúscula
                    ->numbers() //al menos un número
                    ->symbols(), //al menos un símbolo (@#$%&...)
            ],
            'phone' => ['required', 'string', 'regex:/^[0-9]{8}$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'      => 'El nombre es obligatorio.',
            'last_name.required' => 'El apellido es obligatorio.',
            'email.required'     => 'El correo electrónico es obligatorio.',
            'email.email'        => 'El formato del correo no es válido.',
            'password.required'  => 'La contraseña es obligatoria.',
            'password.min'       => 'La contraseña debe tener al menos 8 caracteres.',
            'password.mixed'     => 'La contraseña debe incluir mayúsculas y minúsculas.',
            'password.numbers'   => 'La contraseña debe incluir al menos un número.',
            'password.symbols'   => 'La contraseña debe incluir al menos un símbolo (@, #, $, %, &...).',
            'phone.required'     => 'El teléfono es obligatorio.',
            'phone.min'          => 'El teléfono debe tener al menos 8 caracteres.',
            'phone.regex' => 'El teléfono debe contener 8 dígitos numéricos.',
            'phone.required' => 'El teléfono es obligatorio.',
        ];
    }
}
