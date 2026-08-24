<?php

namespace App\Models;

use App\Enums\CampaignStatus;
use App\Models\Scopes\CampaignOwnerScope;
use Database\Factories\CampaignFactory;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

#[ScopedBy([CampaignOwnerScope::class])]
class Campaign extends Model
{
    /** @use HasFactory<CampaignFactory> */
    use HasFactory;

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::creating(function (Campaign $campaign): void {
            $campaign->user_id ??= Auth::id();
        });
    }

    protected $fillable = [
        'name',
        'status',
        'delay_min_seconds',
        'delay_max_seconds',
    ];

    protected function casts(): array
    {
        return [
            'status' => CampaignStatus::class,
            'delay_min_seconds' => 'integer',
            'delay_max_seconds' => 'integer',
        ];
    }

    /**
     * The candidate messages in this campaign's message pool.
     *
     * @return HasMany<CampaignMessage, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(CampaignMessage::class);
    }

    /**
     * The Lead snapshot recipients for this campaign.
     *
     * @return HasMany<CampaignRecipient, $this>
     */
    public function recipients(): HasMany
    {
        return $this->hasMany(CampaignRecipient::class);
    }
}
