<?php

namespace App\Http\Requests\Banner;

use Illuminate\Foundation\Http\FormRequest;

class StoreBannerRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:150'],
            'media' => ['required', 'file', 'mimetypes:image/jpeg,image/png,image/webp,video/mp4,video/webm', 'max:15360'], // 15MB
        ];
    }

    public function messages(): array
    {
        return [
            'media.mimetypes' => 'El archivo debe ser una imagen (JPG, PNG, WEBP) o un video (MP4, WEBM).',
            'media.max' => 'El archivo no puede pesar más de 15MB.',
        ];
    }
}