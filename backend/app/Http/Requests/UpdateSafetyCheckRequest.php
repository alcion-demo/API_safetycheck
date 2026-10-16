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
                'after_or_equal:today'
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
        'title.required' => '題名を入力してください。',
        'title.string' => '題名は文字列で入力してください。',
        'title.max' => '題名は255文字以内で入力してください。',
        'message.required' => '本文を入力してください。',
        'message.string' => '本文は文字列で入力してください。',
        'scheduled_at.required' => '日時を指定してください。',
        'scheduled_at.date_format' => '日時はY-m-d H:i:s形式で指定してください。',
        'scheduled_at.after_or_equal' => ':attribute には今日以降の日付を入力してください。',
        ];
    }
}
