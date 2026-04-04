<?php

declare(strict_types=1);

namespace Modules\Customer\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Customer\Models\CustomerAddress;

class CustomerAddressResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var CustomerAddress $address */
        $address = $this->resource;

        return [
            'id' => $address->id,
            'label' => $address->label,
            'recipient_name' => $address->recipient_name,
            'phone' => $address->phone,
            'province' => $address->province,
            'district' => $address->district,
            'ward' => $address->ward,
            'address_line' => $address->address_line,
            'address_note' => $address->address_note,
            'is_default' => (bool) $address->is_default,
            'full_address' => $address->formattedAddress(),
        ];
    }
}
