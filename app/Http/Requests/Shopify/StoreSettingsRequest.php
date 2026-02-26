<?php

namespace App\Http\Requests\Shopify;

use Cron\CronExpression;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSettingsRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'auto_scan_enabled' => ['boolean'],
            'auto_scan_schedule' => [
                'nullable',
                Rule::requiredIf((bool) $this->input('auto_scan_enabled')),
                function (string $attribute, mixed $value, callable $fail): void {
                    if ($value === null) {
                        return;
                    }

                    if (in_array($value, ['daily', 'weekly'])) {
                        return;
                    }

                    if (! CronExpression::isValidExpression($value)) {
                        $fail('The :attribute must be "daily", "weekly", or a valid cron expression.');
                    }
                },
            ],
            'email_summary_enabled' => ['boolean'],
            'email_summary_address' => [
                'nullable',
                Rule::requiredIf((bool) $this->input('email_summary_enabled')),
                'email',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'auto_scan_schedule.required' => 'A schedule is required when auto-scan is enabled.',
            'email_summary_address.required' => 'An email address is required when email summary is enabled.',
            'email_summary_address.email' => 'Please enter a valid email address.',
        ];
    }
}
