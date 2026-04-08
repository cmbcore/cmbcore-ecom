<?php

declare(strict_types=1);

namespace Modules\Customer\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Modules\Customer\Http\Requests\ChangePasswordRequest;
use Modules\Customer\Http\Requests\LoginCustomerRequest;
use Modules\Customer\Http\Requests\RegisterCustomerRequest;
use Modules\Customer\Http\Requests\StoreCustomerAddressRequest;
use Modules\Customer\Http\Requests\UpdateProfileRequest;
use Modules\Customer\Http\Requests\UpdateCustomerAddressRequest;
use Modules\Customer\Services\CustomerAddressService;
use Modules\Customer\Services\CustomerAuthService;
use Modules\Customer\Services\CustomerProfileService;
use Modules\Customer\Services\CustomerService;
use Modules\Order\Services\OrderService;

class AccountController extends Controller
{
    public function __construct(
        private readonly CustomerAuthService $customerAuthService,
        private readonly CustomerAddressService $customerAddressService,
        private readonly CustomerProfileService $customerProfileService,
        private readonly CustomerService $customerService,
        private readonly OrderService $orderService,
    ) {
    }

    public function showLogin(): View|RedirectResponse
    {
        if (auth()->check() && auth()->user()?->role === 'customer') {
            return redirect()->route('storefront.account.dashboard');
        }

        return theme_manager()->view('account.login', [
            'page' => [
                'title' => theme_text('account.login_title'),
                'meta_title' => theme_text('account.login_title'),
            ],
        ]);
    }

    public function login(LoginCustomerRequest $request): RedirectResponse
    {
        $payload = $request->validated();
        $this->customerAuthService->login(
            (string) $payload['login'],
            (string) $payload['password'],
            true, // Luôn remember — session không hết hạn theo lifetime ngắn
        );

        return redirect()->intended(route('storefront.account.dashboard'))
            ->with('status', __('frontend.account.messages.logged_in'));
    }

    public function showRegister(): View|RedirectResponse
    {
        if (auth()->check() && auth()->user()?->role === 'customer') {
            return redirect()->route('storefront.account.dashboard');
        }

        return theme_manager()->view('account.register', [
            'page' => [
                'title' => theme_text('account.register_title'),
                'meta_title' => theme_text('account.register_title'),
            ],
        ]);
    }

    public function register(RegisterCustomerRequest $request): RedirectResponse
    {
        $this->customerAuthService->register($request->validated());

        return redirect()->route('storefront.account.dashboard')
            ->with('status', __('frontend.account.messages.registered'));
    }

    public function logout(): RedirectResponse
    {
        $this->customerAuthService->logout();

        return redirect()->route('storefront.account.login')
            ->with('status', __('frontend.account.messages.logged_out'));
    }

    public function dashboard(): View
    {
        $user = request()->user()->load('addresses');
        $orders = $this->orderService->listForCustomer($user);

        return theme_manager()->view('account.dashboard', [
            'page' => [
                'title' => theme_text('account.dashboard_title'),
                'meta_title' => theme_text('account.dashboard_title'),
            ],
            'customer' => $user,
            'orders' => $orders,
            'addresses' => $user->addresses,
        ]);
    }

    public function showProfile(): View
    {
        $customer = $this->customerService->profile(request()->user());

        return theme_manager()->view('account.profile', [
            'page' => [
                'title' => theme_text('account.profile_title'),
                'meta_title' => theme_text('account.profile_title'),
            ],
            'customer' => $customer,
        ]);
    }

    public function updateProfile(UpdateProfileRequest $request): RedirectResponse
    {
        $this->customerProfileService->updateProfile(request()->user(), $request->validated());

        return back()->with('status', __('frontend.account.messages.profile_saved'));
    }

    public function changePassword(ChangePasswordRequest $request): RedirectResponse
    {
        $this->customerProfileService->changePassword(
            request()->user(),
            (string) $request->validated()['password'],
        );

        return back()->with('status', __('frontend.account.messages.password_changed'));
    }

    public function orders(): View
    {
        $status = trim((string) request()->query('status', ''));

        return theme_manager()->view('account.orders', [
            'page' => [
                'title' => theme_text('account.orders_title'),
                'meta_title' => theme_text('account.orders_title'),
            ],
            'orders' => $this->orderService->listForCustomer(request()->user(), [
                'status' => $status,
            ]),
            'selected_status' => $status,
        ]);
    }

    public function orderDetail(string $orderNumber): View
    {
        return theme_manager()->view('account.order-detail', [
            'page' => [
                'title' => theme_text('account.order_detail_title'),
                'meta_title' => theme_text('account.order_detail_title'),
            ],
            'order' => $this->orderService->findForCustomer(request()->user(), $orderNumber),
        ]);
    }

    public function addresses(): View
    {
        $user = request()->user()->load('addresses');

        return theme_manager()->view('account.addresses', [
            'page' => [
                'title' => theme_text('account.addresses_title'),
                'meta_title' => theme_text('account.addresses_title'),
            ],
            'customer' => $user,
            'addresses' => $user->addresses,
        ]);
    }

    public function storeAddress(StoreCustomerAddressRequest $request): RedirectResponse
    {
        $this->customerAddressService->create(request()->user(), $request->validated());

        return back()->with('status', __('frontend.account.messages.address_saved'));
    }

    public function updateAddress(UpdateCustomerAddressRequest $request, int $id): RedirectResponse
    {
        $address = $this->customerAddressService->findForUser(request()->user(), $id);
        $this->customerAddressService->update($address, $request->validated());

        return back()->with('status', __('frontend.account.messages.address_saved'));
    }

    public function destroyAddress(int $id): RedirectResponse
    {
        $address = $this->customerAddressService->findForUser(request()->user(), $id);
        $this->customerAddressService->delete($address);

        return back()->with('status', __('frontend.account.messages.address_deleted'));
    }

    public function setDefaultAddress(int $id): RedirectResponse
    {
        $address = $this->customerAddressService->findForUser(request()->user(), $id);
        $this->customerAddressService->setDefault($address);

        return back()->with('status', __('frontend.account.messages.address_saved'));
    }
}
