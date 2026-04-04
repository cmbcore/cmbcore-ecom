<?php

declare(strict_types=1);

namespace Modules\Banner\Services;

use Carbon\Carbon;
use Modules\Banner\Models\Banner;

class BannerService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function all(): array
    {
        return Banner::query()
            ->orderBy('position')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (Banner $banner): array => [
                'id' => $banner->id,
                'title' => $banner->title,
                'desktop_image' => $banner->desktop_image,
                'mobile_image' => $banner->mobile_image,
                'link' => $banner->link,
                'position' => $banner->position,
                'sort_order' => (int) $banner->sort_order,
                'is_active' => (bool) $banner->is_active,
                'start_date' => $banner->start_date?->toISOString(),
                'end_date' => $banner->end_date?->toISOString(),
            ])
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function activeSlides(string $position = 'homepage_slider'): array
    {
        return Banner::query()
            ->where('position', $position)
            ->where('is_active', true)
            ->where(function ($query): void {
                $query->whereNull('start_date')->orWhere('start_date', '<=', now());
            })
            ->where(function ($query): void {
                $query->whereNull('end_date')->orWhere('end_date', '>=', now());
            })
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (Banner $banner): array => [
                'title' => $banner->title,
                'desktop' => $banner->desktop_image,
                'mobile' => $banner->mobile_image ?: $banner->desktop_image,
                'link_url' => $banner->link,
                'alt' => $banner->title,
            ])
            ->all();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function save(array $payload): Banner
    {
        /** @var Banner $banner */
        $banner = Banner::query()->updateOrCreate(
            ['id' => isset($payload['id']) ? (int) $payload['id'] : null],
            [
                'title' => trim((string) ($payload['title'] ?? '')),
                'desktop_image' => trim((string) ($payload['desktop_image'] ?? '')),
                'mobile_image' => $this->nullableString($payload['mobile_image'] ?? null),
                'link' => $this->nullableString($payload['link'] ?? null),
                'position' => trim((string) ($payload['position'] ?? 'homepage_slider')) ?: 'homepage_slider',
                'sort_order' => (int) ($payload['sort_order'] ?? 0),
                'is_active' => (bool) ($payload['is_active'] ?? true),
                'start_date' => $this->nullableDate($payload['start_date'] ?? null),
                'end_date' => $this->nullableDate($payload['end_date'] ?? null),
            ],
        );

        return $banner->refresh();
    }

    public function delete(int $id): void
    {
        Banner::query()->findOrFail($id)->delete();
    }

    private function nullableString(mixed $value): ?string
    {
        return is_scalar($value) && trim((string) $value) !== '' ? trim((string) $value) : null;
    }

    private function nullableDate(mixed $value): ?Carbon
    {
        return is_string($value) && trim($value) !== '' ? Carbon::parse($value) : null;
    }
}
