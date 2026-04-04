<?php

declare(strict_types=1);

namespace Modules\Blog\Http\Resources;

use App\Support\MediaUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Blog\Models\BlogPost;

class BlogPostResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var BlogPost $post */
        $post = $this->resource;

        return [
            'id' => $post->id,
            'title' => $post->title,
            'slug' => $post->slug,
            'blog_category_id' => $post->blog_category_id,
            'author_name' => $post->author_name,
            'featured_image' => $post->featured_image,
            'featured_image_url' => $this->resolveImageUrl($post->featured_image),
            'excerpt' => $post->excerpt,
            'content' => $post->content,
            'status' => $post->status,
            'published_at' => $post->published_at?->toISOString(),
            'is_featured' => (bool) $post->is_featured,
            'view_count' => (int) $post->view_count,
            'category' => $post->relationLoaded('category') && $post->category
                ? [
                    'id' => $post->category->id,
                    'name' => $post->category->name,
                    'slug' => $post->category->slug,
                ]
                : null,
            'meta_title' => $post->meta_title,
            'meta_description' => $post->meta_description,
            'meta_keywords' => $post->meta_keywords,
            'created_at' => $post->created_at?->toISOString(),
            'updated_at' => $post->updated_at?->toISOString(),
            'deleted_at' => $post->deleted_at?->toISOString(),
        ];
    }

    private function resolveImageUrl(?string $path): ?string
    {
        return MediaUrl::resolve($path);
    }
}
