<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;

class UpdateCategoryRequest extends FormRequest
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
        // Obtenemos el ID de la URL (asumiendo que tu ruta es /api/categories/{id})
        $categoryId = $this->route('id');

        return [
            'name' => ['required', 'string', 'min:3', 'max:100'],
            'description' => ['required', 'string', 'min:5'],
            'isActive' => ['nullable', 'boolean'],
            'parentCategoryId' => [
                'nullable', 
                'integer', 
                'exists:categories,id',
                
                //Evitar que sea padre de sí misma (Bucle infinito)
                function ($attribute, $value, $fail) use ($categoryId) {
                    if ($value == $categoryId) {
                        $fail('Una categoría no puede ser subcategoría de sí misma.');
                    }
                },

                //Evitar jerarquía de 3 niveles (Padre -> Hija -> Nieta)
                function ($attribute, $value, $fail) use ($categoryId) {
                    // Si el usuario está intentando asignarle un padre ($value no es nulo)
                    if ($value !== null) {
                        // Verificamos si esta categoría ya tiene hijas en la BD
                        $tieneHijas = DB::table('categories')
                            ->where('parent_category_id', $categoryId)
                            ->exists();
                            
                        if ($tieneHijas) {
                            $fail('Esta categoría ya tiene subcategorías. No puedes convertirla en subcategoría de otra.');
                        }
                    }
                }
            ],
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