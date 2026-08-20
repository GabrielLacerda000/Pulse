<?php

namespace App\Models;

use App\Actions\NormalizePhoneAction;
use App\Enums\LeadStatus;
use Database\Factories\LeadFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lead extends Model
{
    /** @use HasFactory<LeadFactory> */
    use HasFactory;

    protected $appends = [
        'whatsapp_phone',
    ];

    protected $fillable = [
        'company_name',
        'category',
        'phone',
        'email',
        'website',
        'instagram',
        'has_website',
        'has_instagram',
        'city',
        'state',
        'status',
        'type',
        'score',
        'rating',
        'reviews_count',
        'source',
        'google_maps_url',
        'source_created_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => LeadStatus::class,
            'has_website' => 'boolean',
            'has_instagram' => 'boolean',
            'score' => 'integer',
            'rating' => 'decimal:2',
            'reviews_count' => 'integer',
            'source_created_at' => 'datetime',
        ];
    }

    /**
     * The lead's phone normalized for the wa.me WhatsApp link, or null
     * when the lead has no phone or it fails normalization.
     */
    protected function whatsappPhone(): Attribute
    {
        return Attribute::make(
            get: fn (): ?string => (new NormalizePhoneAction)->execute($this->phone),
        );
    }
}
