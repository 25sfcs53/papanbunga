<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Sudah dibatasi oleh middleware role
        return true;
    }

    public function rules(): array
    {
    $types = ['papan', 'rak'];
        $statuses = ['tersedia', 'disewa', 'perbaikan'];

        return [
            // Top-level fields are required only when items[] is not provided
            'type'   => ['required_without:items', 'string', Rule::in($types)],
            'color'  => ['nullable', 'string', 'max:100'],
            'quantity_total' => ['required_without:items', 'integer', 'min:1'],
            // status required for top-level single-item submit; for batch use items.*.status
            'status' => ['required_without:items', 'string', Rule::in($statuses)],
            // Batch items: optional array of items to add in one submit
            'items' => ['nullable', 'array'],
            'items.*.type' => ['required_with:items', 'string', Rule::in($types)],
            'items.*.color' => ['nullable', 'string', 'max:100'],
            'items.*.quantity_total' => ['required_with:items', 'integer', 'min:1'],
            'items.*.status' => ['required_with:items', 'string', Rule::in($statuses)],
        ];
    }
}
