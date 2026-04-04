<?php

declare(strict_types=1);

namespace Modules\Customer\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerAddress extends Model
{
    protected $fillable = [
        'user_id',
        'label',
        'recipient_name',
        'phone',
        'province',
        'district',
        'ward',
        'address_line',
        'address_note',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    /**
     * @return BelongsTo<User, CustomerAddress>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function formattedAddress(): string
    {
        return collect([
            $this->address_line,
            $this->ward,
            $this->district,
            $this->province,
        ])->filter()->implode(', ');
    }
}
