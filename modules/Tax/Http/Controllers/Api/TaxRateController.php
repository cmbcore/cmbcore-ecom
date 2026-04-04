<?php

declare(strict_types=1);

namespace Modules\Tax\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Tax\Services\TaxService;

class TaxRateController extends Controller
{
    public function __construct(
        private readonly TaxService $taxService,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $rates = $this->taxService->paginate($request->all());

        return response()->json([
            'success' => true,
            'data' => $rates->items(),
            'meta' => [
                'current_page' => $rates->currentPage(),
                'last_page' => $rates->lastPage(),
                'per_page' => $rates->perPage(),
                'total' => $rates->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'id' => ['nullable', 'integer', 'exists:tax_rates,id'],
            'name' => ['required', 'string', 'max:255'],
            'province' => ['nullable', 'string', 'max:255'],
            'rate' => ['required', 'numeric', 'min:0'],
            'threshold' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        return response()->json([
            'success' => true,
            'data' => $this->taxService->save($payload),
            'message' => 'Da luu thuế suất.',
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->taxService->delete($id);

        return response()->json([
            'success' => true,
            'message' => 'Da xoa thuế suất.',
        ]);
    }
}
