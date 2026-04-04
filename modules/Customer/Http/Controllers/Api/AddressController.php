<?php

declare(strict_types=1);

namespace Modules\Customer\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Customer\Http\Requests\StoreCustomerAddressRequest;
use Modules\Customer\Http\Requests\UpdateCustomerAddressRequest;
use Modules\Customer\Http\Resources\CustomerAddressResource;
use Modules\Customer\Services\CustomerAddressService;

class AddressController extends Controller
{
    public function __construct(
        private readonly CustomerAddressService $customerAddressService,
    ) {
    }

    public function index(): JsonResponse
    {
        $addresses = request()->user()->addresses()->get();

        return response()->json([
            'success' => true,
            'data' => CustomerAddressResource::collection($addresses)->resolve(),
        ]);
    }

    public function store(StoreCustomerAddressRequest $request): JsonResponse
    {
        $address = $this->customerAddressService->create(request()->user(), $request->validated());

        return response()->json([
            'success' => true,
            'data' => (new CustomerAddressResource($address))->resolve(),
            'message' => __('frontend.account.messages.address_saved'),
        ], 201);
    }

    public function update(UpdateCustomerAddressRequest $request, int $id): JsonResponse
    {
        $address = $this->customerAddressService->findForUser(request()->user(), $id);
        $address = $this->customerAddressService->update($address, $request->validated());

        return response()->json([
            'success' => true,
            'data' => (new CustomerAddressResource($address))->resolve(),
            'message' => __('frontend.account.messages.address_saved'),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $address = $this->customerAddressService->findForUser(request()->user(), $id);
        $this->customerAddressService->delete($address);

        return response()->json([
            'success' => true,
            'message' => __('frontend.account.messages.address_deleted'),
        ]);
    }

    public function setDefault(int $id): JsonResponse
    {
        $address = $this->customerAddressService->findForUser(request()->user(), $id);
        $address = $this->customerAddressService->setDefault($address);

        return response()->json([
            'success' => true,
            'data' => (new CustomerAddressResource($address))->resolve(),
            'message' => __('frontend.account.messages.address_saved'),
        ]);
    }
}
