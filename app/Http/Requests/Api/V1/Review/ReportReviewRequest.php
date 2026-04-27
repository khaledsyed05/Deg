<?php

namespace App\Http\Requests\Api\V1\Review;

use Illuminate\Foundation\Http\FormRequest;

class ReportReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'in:spam,offensive_language,false_review,personal_attack,inappropriate_content,fake_review,other'],
            'description' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ];
    }
}
