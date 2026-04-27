<?php

namespace App\Services\Review;

use App\Exceptions\Review\ReviewException;
use App\Models\Review;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ReviewPhotoService
{
    public const MAX_PHOTOS_PER_REVIEW = 5;

    /**
     * @param  array<int, UploadedFile>  $photos
     * @return array<int, string>
     */
    public function uploadPhotos(Review $review, array $photos): array
    {
        $currentPhotos = $review->photos ?? [];
        $remainingSlots = self::MAX_PHOTOS_PER_REVIEW - count($currentPhotos);

        if ($remainingSlots <= 0) {
            throw new ReviewException(
                'تم الوصول للحد الأقصى من الصور ('.self::MAX_PHOTOS_PER_REVIEW.')'
            );
        }

        if (count($photos) > $remainingSlots) {
            throw new ReviewException("يمكنك رفع {$remainingSlots} صورة فقط");
        }

        $uploadedUrls = [];

        foreach ($photos as $photo) {
            $uploadedUrls[] = $this->saveImage($review, $photo);
        }

        $allPhotos = array_merge($currentPhotos, $uploadedUrls);
        $review->update(['photos' => $allPhotos]);

        return $allPhotos;
    }

    private function saveImage(Review $review, UploadedFile $file): string
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        $filename = sprintf('photo_%s.%s', Str::random(12), $extension);
        $directory = "reviews/{$review->id}";

        $file->storeAs($directory, $filename, 'public');

        return Storage::disk('public')->url("{$directory}/{$filename}");
    }
}
