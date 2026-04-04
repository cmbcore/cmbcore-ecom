<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Auth\LoginRequest;
use App\Http\Resources\Admin\AuthUserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Modules\ActivityLog\Services\ActivityLogService;

class AuthController extends Controller
{
    public function __construct(
        private readonly ActivityLogService $activityLogService,
    ) {
    }

    /**
     * @throws ValidationException
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->safe()->only(['email', 'password']);
        $remember = (bool) $request->boolean('remember');

        if (! Auth::guard('web')->attempt($credentials, $remember)) {
            throw ValidationException::withMessages([
                'email' => [__('admin.auth.errors.invalid_credentials')],
            ]);
        }

        $request->session()->regenerate();

        if ($request->user() !== null) {
            $this->activityLogService->remember($request, ['skip' => true]);
            $this->activityLogService->log($request->user(), [
                'action' => 'auth.login',
                'description' => 'Admin signed in.',
                'subject_type' => 'auth',
                'request_method' => $request->method(),
                'request_path' => $request->path(),
                'route_uri' => $request->route()?->uri(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'payload' => [
                    'email' => $request->input('email'),
                    'remember' => $remember,
                ],
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => new AuthUserResource($request->user()),
            'message' => __('admin.auth.messages.login_success'),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => new AuthUserResource($request->user()),
            'message' => __('admin.auth.messages.me_loaded'),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        if ($request->user() !== null) {
            $this->activityLogService->remember($request, ['skip' => true]);
            $this->activityLogService->log($request->user(), [
                'action' => 'auth.logout',
                'description' => 'Admin signed out.',
                'subject_type' => 'auth',
                'request_method' => $request->method(),
                'request_path' => $request->path(),
                'route_uri' => $request->route()?->uri(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'payload' => [],
            ]);
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'success' => true,
            'data' => null,
            'message' => __('admin.auth.messages.logout_success'),
        ]);
    }
}
