<?php

namespace App\Http\Requests;

use App\Domain\Enum\ProductSaleType;
use App\Domain\Enum\ProductStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
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
            'categoryId'  => ['required', 'integer', 'exists:categories,id'],
            'name'        => ['required', 'string', 'max:200'],
            'description' => ['required', 'string', 'max:2000'],
            'price'       => ['required', 'numeric', 'gt:0'],
            'offerPrice'  => ['nullable', 'numeric', 'gt:0', 'lt:price'],
            'saleType'    => ['required', 'string', Rule::enum(ProductSaleType::class)],
            'status'      => ['required', 'string', Rule::enum(ProductStatus::class)],
            
            // Validamos los arreglos nuevos
            'new_images'   => ['nullable', 'array'],
            'new_images.*' => [
                'image', 
                'mimes:jpeg,png,jpg,webp', 
                'max:2048' 
            ],
            
            'deleted_images'   => ['nullable', 'array'],
            'deleted_images.*' => ['integer'], // Nos aseguramos de que solo envíen IDs numéricos
        ];
    }

    public function messages(): array
    {
        return [
            'categoryId.exists' => 'La categoría seleccionada no existe en la base de datos.',
            'offerPrice.lt'     => 'El precio de oferta debe ser estrictamente menor que el precio regular.',
            'saleType.Illuminate\Validation\Rules\Enum' => 'El tipo de venta no es válido.',
            'status.Illuminate\Validation\Rules\Enum'   => 'El estado del producto no es válido.',
            'new_images.array'     => 'El formato de las nuevas imágenes no es válido.',
            'new_images.*.image'   => 'Uno de los archivos subidos no es una imagen.',
            'new_images.*.mimes'   => 'Las imágenes deben ser de tipo: jpeg, png, jpg o webp.',
            'new_images.*.max'     => 'Cada imagen no debe pesar más de 2MB.',
            'deleted_images.array' => 'El formato de las imágenes eliminadas no es válido.',
            'deleted_images.*.integer' => 'El ID de la imagen a eliminar no es válido.'
        ];
    }
}
