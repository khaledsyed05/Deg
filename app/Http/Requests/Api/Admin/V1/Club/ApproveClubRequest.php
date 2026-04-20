<?php

namespace App\Http\Requests\Api\Admin\V1\Club;

use Illuminate\Foundation\Http\FormRequest;

class ApproveClubRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->hasRole('admin');
    }

    public function rules(): array
    {
        return [
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
