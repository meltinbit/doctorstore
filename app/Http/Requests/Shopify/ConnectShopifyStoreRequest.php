<?php

namespace App\Http\Requests\Shopify;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class ConnectShopifyStoreRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function prepareForValidation(): void
    {
        $shop = trim((string) $this->input('shop'));

        if ($shop !== '' && ! Str::endsWith($shop, '.myshopify.com')) {
            $this->merge(['shop' => $shop.'.myshopify.com']);
        }
    }

    public function rules(): array
    {
        return [
            'shop' => ['required', 'regex:/^[a-zA-Z0-9\-]+\.myshopify\.com$/'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'shop.regex' => 'Only letters, numbers and hyphens allowed.',
        ];
    }
}
