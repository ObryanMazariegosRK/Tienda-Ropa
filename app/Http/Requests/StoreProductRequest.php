<?php

namespace App\Http\Requests;
use App\Domain\Enum\ProductSaleType;
use App\Domain\Enum\ProductStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
//Para las reglas de validación los que usaremos con los Enums
use Illuminate\Validation\Rule;

//al heredar FormRequest podemos interceptar peticiones, validar datos
//y lanzar errores
class StoreProductRequest extends FormRequest
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
            //Verificamos que la categoría exista en tu tabla 'categories'
            'categoryId'  => ['required', 'integer', 'exists:categories,id'],
            
            'name'        => ['required', 'string', 'max:200'],
            'description' => ['required', 'string', 'max:2000'],
            
            //El precio debe ser un número mayor a 0 (Greater Than= Mayor que)
            'price'       => ['required', 'numeric', 'gt:0'],
            
            //El precio de oferta es opcional, pero si viene, debe ser menor que el precio normal
            'offerPrice'  => ['nullable', 'numeric', 'gt:0', 'lt:price'],
            
            //Validamos que los strings coincidan exactamente con los Enums
            'saleType'    => ['required', 'string', Rule::enum(ProductSaleType::class)],
            'status'      => ['required', 'string', Rule::enum(ProductStatus::class)],
        ];
    }

    /**
     * Mensajes de error personalizados (Opcional, pero muy recomendado)
     */
    public function messages(): array
    {
        return [
            'categoryId.exists' => 'La categoría seleccionada no existe en la base de datos.',
            'offerPrice.lt'     => 'El precio de oferta debe ser estrictamente menor que el precio regular.',
            'saleType.Illuminate\Validation\Rules\Enum' => 'El tipo de venta no es válido.',
            'status.Illuminate\Validation\Rules\Enum'=>'El estado del producto no es válido.',
        ];
    }
}
