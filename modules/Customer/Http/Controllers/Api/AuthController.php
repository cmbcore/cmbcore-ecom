<?php

declare(strict_types=1);

namespace Modules\Customer\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Customer\Http\Requests\LoginCustomerRequest;
use Modules\Customer\Http\Requests\RegisterCustomerRequest;
use Modules\Customer\Http\Resources\CustomerUserResource;
use Modules\Customer\Services\CustomerAuthService;

class AuthController extends Controller
{
    public function __construct(
        private readonly CustomerAuthService $customerAuthService,
    ) {
    }

    public function register(RegisterCustomerRequest $request): JsonResponse
    {
        $user = $this->customerAuthService->register($request->validated());

        return response()->json([
            'success' => true,
            'data' => (new CustomerUserResource($user))->resolve(),
            'message' => __('frontend.account.messages.registered'),
        ], 201);
    }

    public function login(LoginCustomerRequest $request): JsonResponse
    {
        $payload = $request->validated();
        $user = $this->customerAuthService->login(
            (string) $payload['login'],
            (string) $payload['password'],
            (bool) ($payload['remember'] ?? true),
        );

        return response()->json([
            'success' => true,
            'data' => (new CustomerUserResource($user))->resolve(),
            'message' => __('frontend.account.messages.logged_in'),
        ]);
    }

    public function me(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => new CustomerUserResource(request()->user()),
        ]);
    }

    public function logout(): JsonResponse
    {
        $this->customerAuthService->logout();

        return response()->json([
            'success' => true,
            'message' => __('frontend.account.messages.logged_out'),
        ]);
    }
}
