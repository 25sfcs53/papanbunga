<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ComponentRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Akses dibatasi via middleware role
        return true;
    }

    public function rules(): array
    {
    $types = ['huruf_besar','huruf_kecil','angka','simbol','hiasan','kata_sambung'];

        return [
            'name'               => ['required', 'string', 'max:255'],
            'type'               => ['required', 'string', Rule::in($types)],
            'quantity_available' => ['required', 'integer', 'min:0'],
        ];
    }
}
