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
     * Login — stateless token auth (Sanctum PAT).
     * Không dùng session, không cần CSRF cookie.
     *
     * @throws ValidationException
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->safe()->only(['email', 'password']);

        // Auth::once → stateless, không tạo session
        if (! Auth::once($credentials)) {
            throw ValidationException::withMessages([
                'email' => [__('admin.auth.errors.invalid_credentials')],
            ]);
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Kiểm tra quyền admin trước khi cấp token
        if (! $user->canAccessAdminPanel()) {
            throw ValidationException::withMessages([
                'email' => [__('admin.auth.errors.invalid_credentials')],
            ]);
        }

        // Xoá token cũ cùng tên (tránh tích luỹ) rồi tạo mới
        $user->tokens()->where('name', 'admin-session')->delete();
        $token = $user->createToken('admin-session')->plainTextToken;

        $this->activityLogService->remember($request, ['skip' => true]);
        $this->activityLogService->log($user, [
            'action'         => 'auth.login',
            'description'    => 'Admin signed in (token).',
            'subject_type'   => 'auth',
            'request_method' => $request->method(),
            'request_path'   => $request->path(),
            'route_uri'      => $request->route()?->uri(),
            'ip_address'     => $request->ip(),
            'user_agent'     => $request->userAgent(),
            'payload'        => [
                'email'    => $request->input('email'),
                'remember' => (bool) $request->boolean('remember'),
            ],
        ]);

        return response()->json([
            'success' => true,
            'data'    => [
                'token' => $token,
                'user'  => new AuthUserResource($user),
            ],
            'message' => __('admin.auth.messages.login_success'),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => new AuthUserResource($request->user()),
            'message' => __('admin.auth.messages.me_loaded'),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user !== null) {
            $this->activityLogService->remember($request, ['skip' => true]);
            $this->activityLogService->log($user, [
                'action'         => 'auth.logout',
                'description'    => 'Admin signed out.',
                'subject_type'   => 'auth',
                'request_method' => $request->method(),
                'request_path'   => $request->path(),
                'route_uri'      => $request->route()?->uri(),
                'ip_address'     => $request->ip(),
                'user_agent'     => $request->userAgent(),
                'payload'        => [],
            ]);

            // Huỷ token hiện tại (stateless logout)
            $user->currentAccessToken()->delete();
        }

        return response()->json([
            'success' => true,
            'data'    => null,
            'message' => __('admin.auth.messages.logout_success'),
        ]);
    }
}
