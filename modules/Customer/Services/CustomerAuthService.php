<?php

declare(strict_types=1);

namespace Modules\Customer\Services;

use App\Core\Plugin\HookManager;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Modules\Cart\Services\CartService;

class CustomerAuthService
{
    public function __construct(
        private readonly CartService $cartService,
        private readonly HookManager $hookManager,
    ) {
    }

    public function register(array $payload): User
    {
        $user = User::query()->create([
            'name' => trim((string) $payload['name']),
            'email' => trim((string) $payload['email']),
            'phone' => trim((string) $payload['phone']),
            'password' => (string) $payload['password'],
            'role' => User::ROLE_CUSTOMER,
            'is_active' => true,
        ]);

        Auth::guard('web')->login($user, true);
        $this->cartService->mergeGuestCartIntoUser($user);
        $this->hookManager->fire('customer.registered', $user);

        return $user->refresh();
    }

    public function login(string $login, string $password, bool $remember = true): User
    {
        $login = trim($login);
        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';
        $credentials = [
            $field => $login,
            'password' => $password,
            'is_active' => true,
        ];

        if (! Auth::guard('web')->attempt($credentials, $remember)) {
            throw ValidationException::withMessages([
                'login' => [__('frontend.account.messages.login_failed')],
            ]);
        }

        /** @var User $user */
        $user = Auth::guard('web')->user();

        if ($user->role !== User::ROLE_CUSTOMER) {
            Auth::guard('web')->logout();

            throw ValidationException::withMessages([
                'login' => [__('frontend.account.messages.login_failed')],
            ]);
        }

        $this->cartService->mergeGuestCartIntoUser($user);

        return $user;
    }

    public function logout(): void
    {
        Auth::guard('web')->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
    }
}
