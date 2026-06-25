<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreCategoryRequest extends FormRequest
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
            'name' => ['required', 'string', 'min:3', 'max:100'],
            'description' => ['required', 'string', 'min:5'],
            'parentCategoryId' => ['nullable', 'integer', 'exists:categories,id'],
            'isActive' => ['nullable', 'boolean']
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El campo name es obligatorio.',
            'name.min' => 'El nombre de la categoría debe tener al menos 3 caracteres.',
            'description.required' => 'La descripción es totalmente necesaria.',
            'parentCategoryId.exists' => 'La categoría padre que intentas asignar no existe en nuestra base de datos.'
        ];
    }
}
