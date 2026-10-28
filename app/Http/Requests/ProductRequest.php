<?php

namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $data = [];

        if (is_string($this->input('name'))) {
            $data['name'] = trim($this->input('name'));
        }

        if (is_string($this->input('code'))) {
            $data['code'] = strtoupper(trim($this->input('code')));
        }

        if ($data !== []) {
            $this->merge($data);
        }
    }

    public function rules(): array
    {
        $product = $this->route('product');

        $codeRule = Rule::unique('products', 'code');

        if ($product instanceof Product) {
            $codeRule->ignore($product);
        }

        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:255', $codeRule],
            'price_per_unit' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'tax_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'stock_on_hand' => ['required', 'integer', 'min:0', 'max:1000000'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.unique' => 'That product code is already in use.',
        ];
    }

    protected function getRedirectUrl(): string
    {
        $product = $this->route('product');

        if ($product instanceof Product) {
            return route('products.index', ['edit' => $product->id]);
        }

        return route('products.index');
    }
}
