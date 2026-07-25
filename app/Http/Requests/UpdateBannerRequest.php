<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateBannerRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:150'],
            'type' => ['required', 'in:image,video'],
            'display_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],

            // Al editar solo se reemplaza UN archivo (opcional)
            'new_media' => ['nullable', 'file', 'mimes:jpeg,jpg,png,webp,mp4,webm,mov,gif', 'max:20480'],
        ];
    }

    public function messages(): array
    {
        return [
            'new_media.max' => 'El archivo no puede pesar más de 20MB.',
            'new_media.mimes' => 'Formato no permitido.',
        ];
    }
}
