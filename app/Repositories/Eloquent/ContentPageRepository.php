<?php

namespace App\Repositories\Eloquent;

use App\Models\ContentPage;
use App\Repositories\Contracts\ContentPageRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class ContentPageRepository implements ContentPageRepositoryInterface
{
    public function find(int $id): ?ContentPage
    {
        return ContentPage::find($id);
    }

    public function findOrFail(int $id): ContentPage
    {
        return ContentPage::findOrFail($id);
    }

    public function create(array $data): ContentPage
    {
        return ContentPage::create($data);
    }

    public function update(ContentPage $model, array $data): ContentPage
    {
        $model->update($data);

        return $model->fresh();
    }

    public function delete(ContentPage $model): bool
    {
        return $model->delete();
    }

    public function query(): Builder
    {
        return ContentPage::query();
    }

    public function findBySlug(string $slug): ?ContentPage
    {
        return ContentPage::where('slug', $slug)->first();
    }

    public function findPublished(): Collection
    {
        return ContentPage::where('is_published', true)->orderBy('slug')->get();
    }
}
