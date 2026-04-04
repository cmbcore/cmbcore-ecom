<?php

declare(strict_types=1);

namespace Plugins\ContactForm\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactSubmission extends Model
{
    protected $table = 'contact_submissions';

    protected $fillable = [
        'form_id',
        'data',
        'page_url',
        'ip_address',
        'is_read',
    ];

    protected $casts = [
        'data'    => 'array',
        'is_read' => 'boolean',
    ];

    public function form(): BelongsTo
    {
        return $this->belongsTo(ContactForm::class, 'form_id');
    }
}
