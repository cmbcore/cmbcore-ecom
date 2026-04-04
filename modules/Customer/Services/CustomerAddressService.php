<?php

declare(strict_types=1);

namespace Modules\Customer\Services;

use App\Models\User;
use Modules\Customer\Models\CustomerAddress;

class CustomerAddressService
{
    public function findForUser(User $user, int $id): CustomerAddress
    {
        /** @var CustomerAddress $address */
        $address = $user->addresses()->findOrFail($id);

        return $address;
    }

    public function create(User $user, array $payload): CustomerAddress
    {
        $isDefault = (bool) ($payload['is_default'] ?? false) || $user->addresses()->count() === 0;

        if ($isDefault) {
            $user->addresses()->update(['is_default' => false]);
        }

        /** @var CustomerAddress $address */
        $address = $user->addresses()->create([
            'label' => $payload['label'] ?? null,
            'recipient_name' => $payload['recipient_name'],
            'phone' => $payload['phone'],
            'province' => $payload['province'] ?? null,
            'district' => $payload['district'] ?? null,
            'ward' => $payload['ward'] ?? null,
            'address_line' => $payload['address_line'],
            'address_note' => $payload['address_note'] ?? null,
            'is_default' => $isDefault,
        ]);

        return $address;
    }

    public function update(CustomerAddress $address, array $payload): CustomerAddress
    {
        $isDefault = (bool) ($payload['is_default'] ?? false);

        if ($isDefault) {
            $address->user->addresses()->whereKeyNot($address->id)->update(['is_default' => false]);
        }

        $address->fill([
            'label' => $payload['label'] ?? $address->label,
            'recipient_name' => $payload['recipient_name'] ?? $address->recipient_name,
            'phone' => $payload['phone'] ?? $address->phone,
            'province' => $payload['province'] ?? $address->province,
            'district' => $payload['district'] ?? $address->district,
            'ward' => $payload['ward'] ?? $address->ward,
            'address_line' => $payload['address_line'] ?? $address->address_line,
            'address_note' => $payload['address_note'] ?? $address->address_note,
            'is_default' => $isDefault ?: $address->is_default,
        ])->save();

        return $address->refresh();
    }

    public function delete(CustomerAddress $address): void
    {
        $user = $address->user;
        $wasDefault = $address->is_default;
        $address->delete();

        if ($wasDefault) {
            $nextDefault = $user->addresses()->first();

            if ($nextDefault !== null) {
                $nextDefault->forceFill(['is_default' => true])->save();
            }
        }
    }

    public function setDefault(CustomerAddress $address): CustomerAddress
    {
        $address->user->addresses()->update(['is_default' => false]);
        $address->forceFill(['is_default' => true])->save();

        return $address->refresh();
    }
}
