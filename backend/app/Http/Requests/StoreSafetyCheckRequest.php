<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreSafetyCheckRequest extends FormRequest
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
            'title'       => '題名',
            'message'     => '本文',
            'type'        => '種別',
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
            'title'       => ['required', 'string', 'max:255'],
            'message'      => ['required', 'string'],
            'type' => ['required', 'in:training,production'],
            'scheduled_at' => [
                'nullable',
                'date_format:Y-m-d H:i:s',
                'required_if:type,training',
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
            'type.required' => '種別を指定してください。',
            'type.in' => '種別はtrainingまたはproductionを指定してください。',
            'scheduled_at.required_if' => '訓練の場合は日時を指定してください。',
            'scheduled_at.date_format' => '日時はY-m-d H:i:s形式で指定してください。'
        ];
    }
}
