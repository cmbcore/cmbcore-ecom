<?php

declare(strict_types=1);

namespace Modules\Page\Http\Resources;

use App\Support\MediaUrl;
use App\Services\PageShortcodeService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Page\Models\Page as PageModel;

class PageResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var PageModel $page */
        $page = $this->resource;
        $parsedContent = app(PageShortcodeService::class)->parse($page->content);

        return [
            'id' => $page->id,
            'title' => $page->title,
            'slug' => $page->slug,
            'template' => $page->template,
            'featured_image' => $page->featured_image,
            'featured_image_url' => $this->resolveImageUrl($page->featured_image),
            'excerpt' => $page->excerpt,
            'content' => $page->content,
            'content_body' => $parsedContent['content'],
            'content_blocks' => $parsedContent['blocks'],
            'puck_data' => $page->puck_data,
            'status' => $page->status,
            'published_at' => $page->published_at?->toISOString(),
            'view_count' => (int) $page->view_count,
            'meta_title' => $page->meta_title,
            'meta_description' => $page->meta_description,
            'meta_keywords' => $page->meta_keywords,
            'created_at' => $page->created_at?->toISOString(),
            'updated_at' => $page->updated_at?->toISOString(),
            'deleted_at' => $page->deleted_at?->toISOString(),
        ];
    }

    private function resolveImageUrl(?string $path): ?string
    {
        return MediaUrl::resolve($path);
    }
}
