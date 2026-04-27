<?php

namespace App\Http\Requests\Api\V1\Venue;

use Illuminate\Foundation\Http\FormRequest;

class ReportVenueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'in:inappropriate_content,false_information,safety_concern,pricing_dispute,fake_venue,other'],
            'description' => ['required', 'string', 'min:10', 'max:2000'],
            'evidence_urls' => ['sometimes', 'array', 'max:10'],
            'evidence_urls.*' => ['url'],
        ];
    }
}
