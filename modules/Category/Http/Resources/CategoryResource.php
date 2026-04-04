<?php

declare(strict_types=1);

namespace Modules\Category\Http\Resources;

use App\Support\MediaUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Category\Models\Category;

class CategoryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Category $category */
        $category = $this->resource;

        return [
            'id' => $category->id,
            'parent_id' => $category->parent_id,
            'name' => $category->name,
            'slug' => $category->slug,
            'description' => $category->description,
            'image' => $category->image,
            'image_url' => MediaUrl::resolve($category->image),
            'icon' => $category->icon,
            'position' => $category->position,
            'level' => $category->level,
            'status' => $category->status,
            'meta_title' => $category->meta_title,
            'meta_description' => $category->meta_description,
            'product_count' => $category->product_count,
            'created_at' => $category->created_at?->toISOString(),
            'updated_at' => $category->updated_at?->toISOString(),
            'parent' => $category->relationLoaded('parent') && $category->parent
                ? [
                    'id' => $category->parent->id,
                    'name' => $category->parent->name,
                    'slug' => $category->parent->slug,
                    'level' => $category->parent->level,
                ]
                : null,
            'children' => $category->relationLoaded('children')
                ? self::collection($category->children)->resolve()
                : [],
        ];
    }
}
