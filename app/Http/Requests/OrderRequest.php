<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Akses sudah dibatasi via middleware role
        return true;
    }

    public function rules(): array
    {
        // For update, we need to allow assets that are already assigned to this order
        $order = $this->route('order');
        // Specify assets.id to avoid ambiguous column error on join
        $currentAssetIds = $order ? $order->assets()->pluck('assets.id')->toArray() : [];

        $assetRule = function ($type) use ($currentAssetIds) {

            return Rule::exists('assets', 'id')->where('type', $type)->where(function ($query) use ($currentAssetIds) {
                // Asset is valid if it's available OR it's already part of this order
                $query->where('status', 'tersedia')
                      ->orWhereIn('id', $currentAssetIds);
            });
        };

        // On create (POST) the controller will auto-allocate assets based on product
        // so the form is not required to supply papan_hias_id/rak_id. For updates
        // (PATCH/PUT) we require them.
        $isCreate = $this->isMethod('post');

        return [
            'customer_id'    => ['required', 'integer', 'exists:customers,id'],
            'product_id'     => ['required', 'integer', 'exists:products,id'],
            // Assets are allocated automatically; allow nullable on both create & update
            'papan_hias_id'  => ['nullable', 'integer', $assetRule('papan')],
            'rak_id'         => ['nullable', 'integer', $assetRule('rak')],
            'discount_type'  => ['nullable', 'string', Rule::in(['percent', 'fixed'])],
            'discount_value' => ['nullable', 'numeric', 'min:0'],
            'text_content'   => ['nullable', 'string'],
            'shipping_address' => ['nullable', 'string', 'max:2000'],
            'components'     => ['nullable', 'array'],
            'components.*.id' => ['required_with:components', 'integer', 'exists:components,id'],
            'components.*.quantity' => ['required_with:components', 'integer', 'min:1'],
            'delivery_date'  => ['required', 'date'],
            // Accept both lowercase and capitalized enum variants (DB uses capitalized values)
            'status'         => ['nullable', 'string', Rule::in(['pending', 'disewa', 'selesai', 'Pending', 'Disewa', 'Selesai'])],
            // Status & Komponen tidak lagi divalidasi di form create,
            // karena status di-set otomatis menjadi 'Disewa' dan komponen akan
            // dihandle pada fitur terpisah atau di-trigger oleh status.
        ];
    }


}
