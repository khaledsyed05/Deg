<?php

namespace App\Http\Requests\Api\V1\Review;

use Illuminate\Foundation\Http\FormRequest;

class UploadReviewPhotosRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'photos' => 'required|array|min:1|max:5',
            'photos.*' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'photos.required' => 'يجب رفع صورة واحدة على الأقل',
            'photos.max' => 'الحد الأقصى 5 صور',
            'photos.*.image' => 'الملف يجب أن يكون صورة',
            'photos.*.mimes' => 'الصورة يجب أن تكون من نوع: jpg, jpeg, png, webp',
            'photos.*.max' => 'حجم الصورة يجب ألا يتجاوز 5 ميغابايت',
        ];
    }
}
