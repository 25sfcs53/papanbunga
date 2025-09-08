<?php

namespace App\Http\Requests;

use App\Models\Expense;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Akses telah dibatasi via middleware role
        return true;
    }

    public function rules(): array
    {
        return [
            'date'        => ['required', 'date'],
            'category'    => ['required', 'string', Rule::in(array_keys(Expense::getCategories()))],
            'description' => ['nullable', 'string'],
            'amount'      => ['required', 'numeric', 'min:0'],
        ];
    }


    public function messages(): array
    {
        return [
            'category.in' => 'Kategori tidak valid.',
        ];
    }
}
