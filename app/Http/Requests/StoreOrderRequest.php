<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has("customer") && is_array($this->input("customer"))) {
            $this->merge([
                "customer_name" => $this->input("customer.name"),
                "customer_email" => $this->input("customer.email"),
            ]);
        }

        if ($this->filled("customer_email")) {
            $this->merge([
                "customer_email" => strtolower(trim((string) $this->input("customer_email"))),
            ]);
        } elseif ($this->filled("customer.email")) {
            $this->merge([
                "customer_email" => strtolower(trim((string) $this->input("customer.email"))),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            "customer_name" => ["required_without:customer.name", "string", "max:255"],
            "customer_email" => ["required_without:customer.email", "string", "email", "max:255"],
            "customer.name" => ["required_without:customer_name", "string", "max:255"],
            "customer.email" => ["required_without:customer_email", "string", "email", "max:255"],
            "items" => ["required", "array", "min:1"],
            "items.*.product_id" => ["required", "integer", "exists:products,id"],
            "items.*.quantity" => ["required", "integer", "min:1", "max:1000"],
        ];
    }

    public function messages(): array
    {
        return [
            "items.required" => "An order must contain at least one product line.",
            "items.min" => "An order must contain at least one product line.",
            "items.*.product_id.exists" => "One of the selected products does not exist.",
        ];
    }
}

