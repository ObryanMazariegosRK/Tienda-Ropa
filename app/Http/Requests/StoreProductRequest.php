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
            'categoryId'  => ['required', 'integer', 'exists:categories,id'],
            'name' => [
                'required', 'string', 'min:3', 'max:100',
                Rule::unique('products', 'name')->where(function ($query) {
                    return $query->where('status', '!=', 'deleted');
                }),
            ],
            'description' => ['required', 'string', 'max:2000'],
            'price'       => ['required', 'numeric', 'gt:0', 'max:10000.00'],
            'offerPrice'  => ['nullable', 'numeric', 'gt:0', 'lt:price', 'max:9900.00', 'prohibited_if:saleType,auction'],
            'saleType'    => ['required', 'string', Rule::enum(ProductSaleType::class)],
            'status'      => ['required', 'string', Rule::enum(ProductStatus::class)],

            'auctionDurationAmount' => ['required_if:saleType,auction', 'nullable', 'integer', 'min:1'],
            'auctionDurationUnit'   => ['required_if:saleType,auction', 'nullable', 'string', Rule::in(['hours', 'days', 'weeks'])],
            'auctionMinIncrement'   => ['nullable', 'numeric', 'gt:0', 'max:500.00'],

            'images'   => ['nullable', 'array'],
            'images.*' => ['image', 'mimes:jpeg,png,jpg,webp', 'max:8192'],
            'cost' => ['required', 'numeric', 'gt:0', 'max:10000.00'],
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
            'images.array'    => 'El formato de las imágenes no es válido.',
            'images.*.image'  => 'Uno de los archivos subidos no es una imagen.',
            'images.*.mimes'  => 'Las imágenes deben ser de tipo: jpeg, png, jpg o webp.',
            'images.*.max'    => 'Cada imagen no debe pesar más de 8MB.',
            'name.unique' => 'Ya existe un producto con este nombre. Por favor, elige otro para evitar duplicados',
            'auctionDurationAmount.required_if' => 'Debes indicar cuánto durará la subasta.',
            'auctionDurationUnit.required_if'   => 'Debes indicar la unidad de tiempo de la subasta (horas, días o semanas).',
            'offerPrice.prohibited_if'          => 'No puedes asignar un precio de oferta a un producto en subasta.',
            'price.max'              => 'El precio no puede ser mayor a Q10,000.00.',
            'offerPrice.max'         => 'El precio de oferta no puede ser mayor a Q9,900.00.',
            'cost.max'               => 'El costo no puede ser mayor a Q10,000.00.',
            'auctionMinIncrement.max' => 'El incremento mínimo no puede ser mayor a Q500.00.',
            'cost.required' => 'El costo del producto es obligatorio.',
            'cost.gt'       => 'El costo debe ser mayor a cero.',
            'offerPrice.gt' => 'El precio de oferta debe ser mayor a cero.',
        ];
    }
}
