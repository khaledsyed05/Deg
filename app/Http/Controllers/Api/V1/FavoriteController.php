<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\VenueResource;
use App\Http\Traits\ApiResponse;
use App\Models\Venue;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class FavoriteController extends Controller
{
    use ApiResponse;

    public function index(): AnonymousResourceCollection
    {
        $user = auth()->user();

        $venues = $user->favoriteVenues()
            ->with(['category', 'club.city', 'media'])
            ->orderByDesc('saved_venues.created_at')
            ->paginate(20);

        return VenueResource::collection($venues);
    }

    public function store(Venue $venue): JsonResponse
    {
        $user = auth()->user();

        if ($user->favoriteVenues()->where('venues.id', $venue->id)->exists()) {
            return $this->error(__('favorites.already_exists'), null, 409);
        }

        $user->favoriteVenues()->attach($venue->id, ['created_at' => now()]);

        activity()
            ->causedBy($user)
            ->performedOn($venue)
            ->event('venue_favorited')
            ->log('User added venue to favorites');

        return $this->success(null, __('favorites.added'));
    }

    public function destroy(Venue $venue): JsonResponse
    {
        $user = auth()->user();
        $user->favoriteVenues()->detach($venue->id);

        activity()
            ->causedBy($user)
            ->performedOn($venue)
            ->event('venue_unfavorited')
            ->log('User removed venue from favorites');

        return $this->success(null, __('favorites.removed'));
    }
}
