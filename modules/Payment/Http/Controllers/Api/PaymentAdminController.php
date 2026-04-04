<?php

declare(strict_types=1);

namespace Modules\Payment\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Payment\Models\PaymentTransaction;
use Modules\Payment\Services\PaymentService;

class PaymentAdminController extends Controller
{
    public function __construct(
        private readonly PaymentService $paymentService,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $transactions = $this->paymentService->paginate($request->all());

        return response()->json([
            'success' => true,
            'data' => $transactions->items(),
            'meta' => [
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'per_page' => $transactions->perPage(),
                'total' => $transactions->total(),
            ],
        ]);
    }

    public function confirm(Request $request, int $id): JsonResponse
    {
        /** @var PaymentTransaction $transaction */
        $transaction = PaymentTransaction::query()->with('order')->findOrFail($id);
        $payload = $request->validate([
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        return response()->json([
            'success' => true,
            'data' => $this->paymentService->confirmTransaction($transaction, $request->user(), $payload['note'] ?? null),
            'message' => 'Da xác nhận thanh toán.',
        ]);
    }
}
