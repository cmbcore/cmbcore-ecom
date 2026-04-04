<?php

declare(strict_types=1);

namespace Plugins\ContactForm\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContactForm extends Model
{
    protected $table = 'contact_forms';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'fields',
        'success_message',
        'settings',
        'is_active',
    ];

    protected $casts = [
        'fields'    => 'array',
        'settings'  => 'array',
        'is_active' => 'boolean',
    ];

    public function submissions(): HasMany
    {
        return $this->hasMany(ContactSubmission::class, 'form_id');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function resolvedFields(): array
    {
        return is_array($this->fields) ? $this->fields : [];
    }
}
