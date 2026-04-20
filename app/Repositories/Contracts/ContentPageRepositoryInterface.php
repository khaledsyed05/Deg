<?php

namespace App\Repositories\Contracts;

use App\Models\ContentPage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

interface ContentPageRepositoryInterface
{
    public function find(int $id): ?ContentPage;

    public function findOrFail(int $id): ContentPage;

    public function create(array $data): ContentPage;

    public function update(ContentPage $model, array $data): ContentPage;

    public function delete(ContentPage $model): bool;

    public function query(): Builder;

    public function findBySlug(string $slug): ?ContentPage;

    public function findPublished(): Collection;
}
