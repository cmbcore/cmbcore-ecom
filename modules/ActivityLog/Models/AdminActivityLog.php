<?php

declare(strict_types=1);

namespace Modules\ActivityLog\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminActivityLog extends Model
{
    protected $fillable = [
        'admin_user_id',
        'action',
        'description',
        'subject_type',
        'subject_id',
        'request_method',
        'request_path',
        'route_uri',
        'ip_address',
        'user_agent',
        'payload',
        'meta',
    ];

    protected $casts = [
        'payload' => 'array',
        'meta' => 'array',
    ];

    /**
     * @return BelongsTo<User, AdminActivityLog>
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_user_id');
    }
}
