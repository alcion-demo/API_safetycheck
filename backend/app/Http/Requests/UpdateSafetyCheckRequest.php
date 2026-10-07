<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSafetyCheckRequest extends FormRequest
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
            'title'        => '題名',
            'message'      => '本文',
            'scheduled_at' => '日時',
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
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'message'     => ['sometimes', 'required', 'string'],
            'scheduled_at' => [
                'sometimes',
                'required',
                'date_format:Y-m-d H:i:s',
            ],
        ];
    }

    /**
     * ルールに違反した場合にエラーメッセージを出力する
     *
     * @return array
     */
    public function messages()
    {
        return [
            'scheduled_at.required_if' => '訓練の場合は日時を指定してください。',
            'scheduled_at.date_format' => '日時はY-m-d H:i:s形式で指定してください。'
        ];
    }
}
