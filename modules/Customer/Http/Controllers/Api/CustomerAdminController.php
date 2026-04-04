<?php

declare(strict_types=1);

namespace Modules\Customer\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Customer\Http\Resources\CustomerUserResource;
use Modules\Customer\Services\CustomerService;

class CustomerAdminController extends Controller
{
    public function __construct(
        private readonly CustomerService $customerService,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $customers = $this->customerService->paginate($request->all());

        return response()->json([
            'success' => true,
            'data' => CustomerUserResource::collection($customers->getCollection())->resolve(),
            'meta' => [
                'current_page' => $customers->currentPage(),
                'last_page' => $customers->lastPage(),
                'per_page' => $customers->perPage(),
                'total' => $customers->total(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $customer = $this->customerService->find($id);

        return response()->json([
            'success' => true,
            'data' => (new CustomerUserResource($customer))->resolve(),
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $customer = $this->customerService->find($id);
        $payload = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($customer->id)],
            'phone' => ['nullable', 'string', 'max:50'],
            'password' => ['nullable', 'string', 'min:8'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $customer = $this->customerService->update($customer, $payload);

        return response()->json([
            'success' => true,
            'data' => (new CustomerUserResource($customer))->resolve(),
            'message' => 'Da cập nhật khách hàng.',
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $customer = $this->customerService->find($id);
        $this->customerService->delete($customer);

        return response()->json([
            'success' => true,
            'message' => 'Da xoa khách hàng.',
        ]);
    }
}
