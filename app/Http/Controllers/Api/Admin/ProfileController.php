<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\AuthUserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    /**
     * Cập nhật thông tin cơ bản (tên, email, phone).
     */
    public function update(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $validated = $request->validate([
            'name'  => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:191', 'unique:users,email,' . $user->id],
            'phone' => ['nullable', 'string', 'max:20'],
        ]);

        $user->fill($validated)->save();

        return response()->json([
            'success' => true,
            'data'    => new AuthUserResource($user->fresh()),
            'message' => 'Đã cập nhật thông tin thành công.',
        ]);
    }

    /**
     * Đổi mật khẩu.
     */
    public function changePassword(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $validated = $request->validate([
            'current_password'      => ['required', 'string'],
            'password'              => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if (! Hash::check($validated['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Mật khẩu hiện tại không đúng.'],
            ]);
        }

        $user->update(['password' => $validated['password']]);

        return response()->json([
            'success' => true,
            'data'    => null,
            'message' => 'Đổi mật khẩu thành công.',
        ]);
    }

    /**
     * Upload / cập nhật ảnh đại diện.
     */
    public function updateAvatar(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $request->validate([
            'avatar' => ['required', 'image', 'max:2048'],
        ]);

        // Xoá ảnh cũ nếu có
        if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
            Storage::disk('public')->delete($user->avatar);
        }

        $path = $request->file('avatar')->store('avatars', 'public');
        $user->update(['avatar' => $path]);

        return response()->json([
            'success' => true,
            'data'    => [
                'avatar'     => $path,
                'avatar_url' => asset('storage/' . $path),
            ],
            'message' => 'Đã cập nhật ảnh đại diện.',
        ]);
    }
}
