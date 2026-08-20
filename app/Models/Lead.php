<?php

namespace App\Models;

use App\Enums\LeadStatus;
use Database\Factories\LeadFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lead extends Model
{
    /** @use HasFactory<LeadFactory> */
    use HasFactory;

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
}
