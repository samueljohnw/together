<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreItemMessageRequest extends FormRequest
{
    /**
     * The largest attachment accepted, in kilobytes.
     */
    public const int MaxAttachmentKilobytes = 20480;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'body' => ['required_without:attachments', 'nullable', 'string', 'max:5000'],
            'attachments' => ['required_without:body', 'array', 'max:10'],
            'attachments.*' => ['file', 'max:'.self::MaxAttachmentKilobytes],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'attachments.*.max' => 'Each file must be 20 MB or smaller.',
            'attachments.*.uploaded' => 'That file could not be uploaded. It may be larger than the server allows.',
        ];
    }
}
