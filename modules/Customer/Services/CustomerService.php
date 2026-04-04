<?php

declare(strict_types=1);

namespace Modules\Customer\Services;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class CustomerService
{
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        $perPage = max(1, (int) ($filters['per_page'] ?? config('customer.admin_per_page', 15)));
        $search = trim((string) ($filters['search'] ?? ''));

        return User::query()
            ->customers()
            ->withCount(['addresses', 'orders'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($innerQuery) use ($search): void {
                    $like = '%' . $search . '%';
                    $innerQuery
                        ->where('name', 'like', $like)
                        ->orWhere('email', 'like', $like)
                        ->orWhere('phone', 'like', $like);
                });
            })
            ->latest('id')
            ->paginate($perPage);
    }

    public function find(int $id): User
    {
        /** @var User $customer */
        $customer = User::query()
            ->customers()
            ->with(['addresses', 'orders.items'])
            ->withCount(['addresses', 'orders'])
            ->findOrFail($id);

        return $customer;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function update(User $customer, array $payload): User
    {
        $customer->forceFill([
            'name' => trim((string) ($payload['name'] ?? $customer->name)),
            'email' => $payload['email'] ?? null,
            'phone' => $payload['phone'] ?? null,
            'is_active' => (bool) ($payload['is_active'] ?? $customer->is_active),
        ]);

        if (! empty($payload['password'])) {
            $customer->password = (string) $payload['password'];
        }

        $customer->save();

        return $this->find($customer->id);
    }

    public function delete(User $customer): void
    {
        DB::transaction(function () use ($customer): void {
            $customer->delete();
        });
    }

    public function profile(User $customer): User
    {
        /** @var User $user */
        $user = User::query()
            ->customers()
            ->with('addresses')
            ->findOrFail($customer->id);

        return $user;
    }
}
