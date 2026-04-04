<?php

declare(strict_types=1);

namespace Modules\Blog\Http\Resources;

use App\Support\MediaUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Blog\Models\BlogCategory;

class BlogCategoryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var BlogCategory $category */
        $category = $this->resource;

        return [
            'id' => $category->id,
            'name' => $category->name,
            'slug' => $category->slug,
            'description' => $category->description,
            'image' => $category->image,
            'image_url' => MediaUrl::resolve($category->image),
            'status' => $category->status,
            'meta_title' => $category->meta_title,
            'meta_description' => $category->meta_description,
            'created_at' => $category->created_at?->toISOString(),
            'updated_at' => $category->updated_at?->toISOString(),
            'deleted_at' => $category->deleted_at?->toISOString(),
        ];
    }
}
