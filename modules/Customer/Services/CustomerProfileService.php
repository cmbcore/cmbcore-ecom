<?php

declare(strict_types=1);

namespace Modules\Customer\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class CustomerProfileService
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function updateProfile(User $user, array $payload): User
    {
        $avatar = $payload['avatar'] ?? null;

        $user->forceFill([
            'name' => trim((string) $payload['name']),
            'email' => trim((string) $payload['email']),
            'phone' => trim((string) $payload['phone']),
            'avatar' => $avatar instanceof UploadedFile
                ? $this->storeAvatar($user, $avatar)
                : $user->avatar,
        ])->save();

        return $user->refresh();
    }

    public function changePassword(User $user, string $password): User
    {
        $user->forceFill([
            'password' => $password,
        ])->save();

        return $user->refresh();
    }

    private function storeAvatar(User $user, UploadedFile $avatar): string
    {
        $previousAvatar = is_string($user->avatar) ? trim($user->avatar) : '';

        if (
            $previousAvatar !== ''
            && ! str_starts_with($previousAvatar, 'http://')
            && ! str_starts_with($previousAvatar, 'https://')
            && ! str_starts_with($previousAvatar, '/')
        ) {
            Storage::disk('public')->delete($previousAvatar);
        }

        return $avatar->store('customers/avatars', 'public');
    }
}
