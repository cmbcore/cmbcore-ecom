<?php

declare(strict_types=1);

namespace Modules\Review\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Modules\Product\Models\Product;
use Modules\Review\Services\ReviewService;

class ReviewController extends Controller
{
    public function __construct(
        private readonly ReviewService $reviewService,
    ) {
    }

    public function myReviews(): View
    {
        $status = trim((string) request()->query('status', ''));

        return theme_manager()->view('account.reviews', [
            'page' => [
                'title' => theme_text('account.reviews_title'),
                'meta_title' => theme_text('account.reviews_title'),
            ],
            'reviews' => $this->reviewService->paginateForUser(request()->user(), [
                'status' => $status,
            ]),
            'selected_status' => $status,
        ]);
    }

    public function store(string $slug): RedirectResponse
    {
        /** @var Product $product */
        $product = Product::query()->where('slug', $slug)->firstOrFail();

        $payload = request()->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string', 'max:2000'],
        ]);

        $this->reviewService->submit(request()->user(), $product, $payload);

        return back()->with('status', __('frontend.account.messages.review_submitted'));
    }
}
