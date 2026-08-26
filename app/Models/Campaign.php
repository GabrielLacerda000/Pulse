<?php

namespace App\Models;

use App\Enums\CampaignRecipientStatus;
use App\Enums\CampaignStatus;
use App\Models\Scopes\CampaignOwnerScope;
use Database\Factories\CampaignFactory;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Builder;
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
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => CampaignStatus::class,
            'delay_min_seconds' => 'integer',
            'delay_max_seconds' => 'integer',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
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

    /**
     * Filter campaigns by free text matching the name.
     */
    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        if (! $search) {
            return $query;
        }

        return $query->where('name', 'like', '%'.$search.'%');
    }

    /**
     * Filter campaigns by status.
     */
    public function scopeFilterStatus(Builder $query, ?string $status): Builder
    {
        if (! $status) {
            return $query;
        }

        return $query->where('status', $status);
    }

    /**
     * A campaign's Leads, message pool, and interval can only be changed
     * while it's still a draft — once sending starts, the snapshot is fixed.
     */
    public function isEditable(): bool
    {
        return $this->status === CampaignStatus::DRAFT;
    }

    /**
     * A campaign can be deleted at any point except while it's running,
     * since a send is actively in progress at that point.
     */
    public function isDeletable(): bool
    {
        return $this->status !== CampaignStatus::RUNNING;
    }

    /**
     * A campaign can only be started from draft — once it's running,
     * completed, or failed, starting it again would re-send to recipients
     * that already have an outcome.
     */
    public function isStartable(): bool
    {
        return $this->status === CampaignStatus::DRAFT;
    }

    /**
     * The recipient send counters plus the campaign's own status, as
     * consumed by the campaigns/{campaign}/progress endpoint.
     *
     * @return array{sent: int, failed: int, pending: int, total: int, status: CampaignStatus}
     */
    public function progressCounts(): array
    {
        $counts = $this->recipients()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $sent = (int) ($counts[CampaignRecipientStatus::SENT->value] ?? 0);
        $failed = (int) ($counts[CampaignRecipientStatus::FAILED->value] ?? 0);
        $pending = (int) ($counts[CampaignRecipientStatus::PENDING->value] ?? 0)
            + (int) ($counts[CampaignRecipientStatus::PROCESSING->value] ?? 0);

        return [
            'sent' => $sent,
            'failed' => $failed,
            'pending' => $pending,
            'total' => $sent + $failed + $pending,
            'status' => $this->status,
        ];
    }

    /**
     * A completed campaign with at least one failed recipient can be
     * retried: the user is offered a shortcut into a brand new campaign
     * pre-filled with just those failed recipients' Leads.
     */
    public function canRetryFailed(): bool
    {
        return $this->status === CampaignStatus::COMPLETED
            && $this->recipients()->where('status', CampaignRecipientStatus::FAILED)->exists();
    }

    /**
     * The shape consumed by the campaigns/Show page's own campaign prop.
     *
     * @return array{id: int, name: string, status: CampaignStatus, can_retry_failed: bool, started_at: ?string, completed_at: ?string}
     */
    public function toShowPayload(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'status' => $this->status,
            'can_retry_failed' => $this->canRetryFailed(),
            'started_at' => $this->started_at?->toJSON(),
            'completed_at' => $this->completed_at?->toJSON(),
        ];
    }

    /**
     * The shape consumed by the campaigns/Edit page: the campaign's own
     * fields plus the lead IDs and message contents from its current
     * snapshot, so the edit form can be prefilled.
     *
     * @return array<string, mixed>
     */
    public function toEditPayload(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'delay_min_seconds' => $this->delay_min_seconds,
            'delay_max_seconds' => $this->delay_max_seconds,
            'lead_ids' => $this->recipients()->pluck('lead_id')->filter()->values(),
            'messages' => $this->messages()->pluck('content'),
        ];
    }
}
