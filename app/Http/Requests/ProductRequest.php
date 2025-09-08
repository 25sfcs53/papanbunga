<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Akses sudah dibatasi via middleware role, izinkan di sini
        return true;
    }

    public function rules(): array
    {
        return [
            'name'                      => ['required', 'string', 'max:255'],
            'base_price'                => ['required', 'numeric', 'min:0'],
            'photo'                     => ['nullable', 'image', 'max:2048'], // 2MB
            'required_papan_color'      => ['required', 'string', 'max:50'],
            'required_papan_quantity'   => ['required', 'integer', 'min:1'],
            'default_rack_color'        => ['required', 'string', 'max:50'],
            'description'               => ['nullable', 'string'],
        ];
    }
}
