<?php

namespace App\Http\Requests\Api\V1\Chat;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class SendMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'conversation_id' => ['required', 'integer', 'exists:conversations,id'],
            'channel_id' => ['sometimes', 'integer'],
            'body' => ['required_without:attachment_url', 'nullable', 'string', 'max:5000'],
            'content' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'type' => ['sometimes', 'string', 'in:text,image,audio,booking,system'],
            'attachment_url' => ['sometimes', 'nullable', 'url', 'max:500'],
            'idempotency_key' => ['required', 'string', 'min:8', 'max:100'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            // The spec uses both `body` and `content` interchangeably
            // (mobile sends one or the other depending on the screen).
            // Accept either; merge into `body` so downstream code only
            // sees one shape.
            if ($v->errors()->has('body') && $this->filled('content')) {
                $this->merge(['body' => $this->input('content')]);
                $v->errors()->forget('body');
            }
        });
    }
}
