<?php

declare(strict_types=1);

namespace Modules\Review\Services;

use App\Models\User;
use App\Support\SearchEscape;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Order\Models\Order;
use Modules\Product\Models\Product;
use Modules\Review\Models\ProductReview;

class ReviewService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function approvedForProduct(Product $product): array
    {
        return ProductReview::query()
            ->with('user')
            ->where('product_id', $product->id)
            ->where('status', ProductReview::STATUS_APPROVED)
            ->latest('id')
            ->get()
            ->map(fn (ProductReview $review): array => [
                'id' => $review->id,
                'rating' => (int) $review->rating,
                'title' => $review->title,
                'content' => $review->content,
                'status' => $review->status,
                'is_verified_purchase' => (bool) $review->is_verified_purchase,
                'admin_reply' => $review->admin_reply,
                'author_name' => $review->user?->name,
                'created_at' => $review->created_at?->toISOString(),
            ])
            ->all();
    }

    public function canReview(?User $user, Product $product): bool
    {
        if (! $user instanceof User) {
            return false;
        }

        return Order::query()
            ->where('user_id', $user->id)
            ->where(function (Builder $query): void {
                $query->where('order_status', Order::ORDER_STATUS_CONFIRMED)
                    ->orWhere('fulfillment_status', Order::FULFILLMENT_STATUS_DELIVERED);
            })
            ->whereHas('items', fn (Builder $itemQuery) => $itemQuery->where('product_id', $product->id))
            ->exists();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function submit(User $user, Product $product, array $payload): ProductReview
    {
        abort_unless($this->canReview($user, $product), 422, 'Chi khach da mua hang moi duoc danh gia san pham.');

        return DB::transaction(function () use ($user, $product, $payload): ProductReview {
            /** @var ProductReview $review */
            $review = ProductReview::query()->updateOrCreate(
                [
                    'product_id' => $product->id,
                    'user_id' => $user->id,
                ],
                [
                    'rating' => (int) $payload['rating'],
                    'title' => trim((string) $payload['title']),
                    'content' => trim((string) $payload['content']),
                    'status' => ProductReview::STATUS_PENDING,
                    'is_verified_purchase' => true,
                    'admin_reply' => null,
                ],
            );

            $this->syncProductAggregates($product);

            return $review->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function createFromAdmin(array $payload): ProductReview
    {
        /** @var Product $product */
        $product = Product::query()->findOrFail((int) $payload['product_id']);

        return DB::transaction(function () use ($payload, $product): ProductReview {
            $reviewer = $this->resolveAdminReviewer($payload);

            /** @var ProductReview $review */
            $review = ProductReview::query()->updateOrCreate(
                [
                    'product_id' => $product->id,
                    'user_id' => $reviewer->id,
                ],
                [
                    'rating' => (int) $payload['rating'],
                    'title' => trim((string) $payload['title']),
                    'content' => trim((string) $payload['content']),
                    'status' => (string) ($payload['status'] ?? ProductReview::STATUS_APPROVED),
                    'is_verified_purchase' => (bool) ($payload['is_verified_purchase'] ?? false),
                    'admin_reply' => $payload['admin_reply'] ?? null,
                ],
            );

            $this->syncProductAggregates($product);

            return $review->refresh()->load(['product', 'user']);
        });
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, ProductReview>
     */
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        $search = trim((string) ($filters['search'] ?? ''));

        return ProductReview::query()
            ->with(['product', 'user'])
            ->when($search !== '', function (Builder $query) use ($search): void {
                $like = SearchEscape::like($search);
                $query->where('title', 'like', $like)
                    ->orWhere('content', 'like', $like)
                    ->orWhereHas('product', fn (Builder $productQuery) => $productQuery->where('name', 'like', $like))
                    ->orWhereHas('user', fn (Builder $userQuery) => $userQuery->where('name', 'like', $like));
            })
            ->when(! empty($filters['status']), fn (Builder $query) => $query->where('status', $filters['status']))
            ->latest('id')
            ->paginate((int) ($filters['per_page'] ?? config('review.per_page', 20)));
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, ProductReview>
     */
    public function paginateForUser(User $user, array $filters = []): LengthAwarePaginator
    {
        $status = trim((string) ($filters['status'] ?? ''));

        return ProductReview::query()
            ->with(['product', 'user'])
            ->where('user_id', $user->id)
            ->when($status !== '', fn (Builder $query) => $query->where('status', $status))
            ->latest('id')
            ->paginate((int) ($filters['per_page'] ?? config('review.per_page', 20)));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function moderate(ProductReview $review, array $payload): ProductReview
    {
        $review->forceFill([
            'status' => $payload['status'],
            'admin_reply' => $payload['admin_reply'] ?? $review->admin_reply,
        ])->save();

        $this->syncProductAggregates($review->product);

        return $review->refresh()->load(['product', 'user']);
    }

    public function syncProductAggregates(Product $product): void
    {
        $approved = ProductReview::query()
            ->where('product_id', $product->id)
            ->where('status', ProductReview::STATUS_APPROVED);

        $product->forceFill([
            'review_count' => $approved->count(),
            'rating_value' => round((float) ($approved->avg('rating') ?? 0), 2),
        ])->save();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function resolveAdminReviewer(array $payload): User
    {
        $name = trim((string) ($payload['reviewer_name'] ?? ''));
        $name = $name !== '' ? $name : 'Khach hang';

        $email = trim((string) ($payload['reviewer_email'] ?? ''));

        if ($email === '') {
            $email = sprintf(
                '%s+%s@fake-review.local',
                Str::slug($name, '-') ?: 'khach-hang',
                Str::lower(Str::random(10)),
            );
        }

        /** @var User $user */
        $user = User::query()->firstOrNew(['email' => $email]);

        if (! $user->exists) {
            // Fake reviewer: is_active=false so they don't appear in customer lists
            $user->forceFill([
                'name' => $name,
                'email' => $email,
                'role' => User::ROLE_CUSTOMER,
                'is_active' => false,
                'password' => Str::random(64),
            ])->save();

            return $user;
        }

        // Only update name for existing users, never change their is_active or role
        $user->forceFill([
            'name' => $name,
        ])->save();

        return $user;
    }
}
