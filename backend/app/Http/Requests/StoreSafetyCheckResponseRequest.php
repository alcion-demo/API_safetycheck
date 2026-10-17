<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreSafetyCheckResponseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function attributes(): array
    {
        return [
            'status'     => '状態',
            'memo'        => '備考',
        ];

    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status'       => ['required', 'string', 'in:safe,serious,traffic,unknown'],
            'memo'      => ['nullable', 'string', 'max:2000'],
        ];
    }
}
