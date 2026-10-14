<?php

namespace App\Http\Requests;

use App\Domain\Enum\ProductSaleType;
use App\Domain\Enum\ProductStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $productId = $this->route('id');

        return [
            'categoryId'  => ['required', 'integer', 'exists:categories,id'],
            'name' => [
                'required', 'string', 'min:3', 'max:100',
                // El mismo nombre puede repetirse en categorías distintas (ej. Hombre y Niños)
                // y cuando el otro producto ya se entregó o se eliminó
                Rule::unique('products', 'name')
                    ->ignore($productId)
                    ->where(fn($query) => $this->nombreOcupado($query)),
            ],
            'description' => ['required', 'string', 'max:2000'],
            'price'       => ['required', 'numeric', 'gt:0', 'max:10000.00'],
            'offerPrice'  => ['nullable', 'numeric', 'gt:0', 'lt:price', 'max:9900.00'],
            'saleType'    => ['required', 'string', Rule::enum(ProductSaleType::class)],
            'status'      => ['required', 'string', Rule::enum(ProductStatus::class)],

            'new_images'   => ['nullable', 'array', 'max:' . \App\Domain\Entities\Product::MAX_IMAGES],
            'new_images.*' => [
                'image',
                'mimes:jpeg,png,jpg,webp',
                'max:8192'
            ],

            'deleted_images'   => ['nullable', 'array'],
            'deleted_images.*' => ['integer'],
            'cost' => ['required', 'numeric', 'min:0', 'max:10000.00'],
        ];
    }

    public function messages(): array
    {
        return [
            'categoryId.exists' => 'La categoría seleccionada no existe en la base de datos.',
            'name.unique'       => 'Ya existe un producto con este nombre en esta categoría. Elige otro nombre o una categoría distinta.',
            'offerPrice.lt'     => 'El precio de oferta debe ser estrictamente menor que el precio regular.',
            'saleType.Illuminate\Validation\Rules\Enum' => 'El tipo de venta no es válido.',
            'status.Illuminate\Validation\Rules\Enum'   => 'El estado del producto no es válido.',
            'new_images.array'     => 'El formato de las nuevas imágenes no es válido.',
            'new_images.max'       => 'Un producto admite máximo ' . \App\Domain\Entities\Product::MAX_IMAGES . ' imágenes.',
            'new_images.*.image'   => 'Uno de los archivos subidos no es una imagen.',
            'new_images.*.mimes'   => 'Las imágenes deben ser de tipo: jpeg, png, jpg o webp.',
            'new_images.*.max'     => 'Cada imagen no debe pesar más de 8MB.',
            'deleted_images.array' => 'El formato de las imágenes eliminadas no es válido.',
            'deleted_images.*.integer' => 'El ID de la imagen a eliminar no es válido.',
            'price.max'      => 'El precio no puede ser mayor a Q10,000.00.',
            'offerPrice.max' => 'El precio de oferta no puede ser mayor a Q9,900.00.',
            'cost.max'       => 'El costo no puede ser mayor a Q10,000.00.',
        ];
    }

    // Productos que todavía "ocupan" un nombre en la categoría: los que están a la venta o
    // en un pedido activo (pendiente, confirmado, en preparación, en camino). Los eliminados
    // y los vendidos cuyo pedido ya se entregó quedan como historial y no cuentan.
    private function nombreOcupado($query)
    {
        return $query->where('category_id', $this->input('categoryId'))
            ->where('status', '!=', 'deleted')
            ->whereNot(fn($q) => $q->where('status', 'sold')->whereExists(
                fn($pedido) => $pedido->selectRaw('1')
                    ->from('order_items')
                    ->join('orders', 'orders.id', '=', 'order_items.order_id')
                    ->whereColumn('order_items.product_id', 'products.id')
                    ->where('orders.status', 'delivered')
            ));
    }
}
