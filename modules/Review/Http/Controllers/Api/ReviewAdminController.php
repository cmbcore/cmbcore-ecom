<?php

declare(strict_types=1);

namespace Modules\Review\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Review\Models\ProductReview;
use Modules\Review\Services\ReviewService;

class ReviewAdminController extends Controller
{
    public function __construct(
        private readonly ReviewService $reviewService,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $reviews = $this->reviewService->paginate($request->all());

        return response()->json([
            'success' => true,
            'data' => $reviews->items(),
            'meta' => [
                'current_page' => $reviews->currentPage(),
                'last_page' => $reviews->lastPage(),
                'per_page' => $reviews->perPage(),
                'total' => $reviews->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'reviewer_name' => ['required', 'string', 'max:255'],
            'reviewer_email' => ['nullable', 'email', 'max:255'],
            'rating' => ['required', 'integer', 'between:1,5'],
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string', 'max:2000'],
            'status' => ['nullable', 'in:pending,approved,rejected'],
            'is_verified_purchase' => ['nullable', 'boolean'],
            'admin_reply' => ['nullable', 'string'],
        ]);

        return response()->json([
            'success' => true,
            'data' => $this->reviewService->createFromAdmin($payload),
            'message' => 'Da tao review thu cong.',
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        /** @var ProductReview $review */
        $review = ProductReview::query()->with(['product', 'user'])->findOrFail($id);
        $payload = $request->validate([
            'status' => ['required', 'in:pending,approved,rejected'],
            'admin_reply' => ['nullable', 'string'],
        ]);

        return response()->json([
            'success' => true,
            'data' => $this->reviewService->moderate($review, $payload),
            'message' => 'Da cap nhat trang thai danh gia.',
        ]);
    }
}
