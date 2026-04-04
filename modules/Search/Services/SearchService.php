<?php

declare(strict_types=1);

namespace Modules\Search\Services;

use Illuminate\Support\Facades\DB;
use Modules\Product\Models\Product;

class SearchService
{
    public function track(string $term): void
    {
        $normalized = trim(mb_strtolower($term));

        if ($normalized === '') {
            return;
        }

        $existing = DB::table('search_terms')->where('term', $normalized)->first();

        if ($existing) {
            DB::table('search_terms')
                ->where('id', $existing->id)
                ->update([
                    'hits' => (int) $existing->hits + 1,
                    'last_searched_at' => now(),
                    'updated_at' => now(),
                ]);

            return;
        }

        DB::table('search_terms')->insert([
            'term' => $normalized,
            'hits' => 1,
            'last_searched_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * @return array<int, string>
     */
    public function suggestions(string $query = ''): array
    {
        $normalized = trim(mb_strtolower($query));

        $terms = DB::table('search_terms')
            ->when($normalized !== '', fn ($builder) => $builder->where('term', 'like', '%' . $normalized . '%'))
            ->orderByDesc('hits')
            ->limit(5)
            ->pluck('term')
            ->map(static fn ($term): string => (string) $term)
            ->all();

        $products = Product::query()
            ->active()
            ->when($normalized !== '', fn ($queryBuilder) => $queryBuilder->where('name', 'like', '%' . $normalized . '%'))
            ->limit(5)
            ->pluck('name')
            ->map(static fn ($name): string => (string) $name)
            ->all();

        return array_values(array_unique(array_filter(array_merge($products, $terms))));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function analytics(): array
    {
        return DB::table('search_terms')
            ->orderByDesc('hits')
            ->limit(50)
            ->get()
            ->map(fn ($row): array => [
                'term' => (string) $row->term,
                'hits' => (int) $row->hits,
                'last_searched_at' => $row->last_searched_at,
            ])
            ->all();
    }
}
