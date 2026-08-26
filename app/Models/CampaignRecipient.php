<?php

namespace App\Models;

use App\Enums\CampaignRecipientStatus;
use App\Enums\PulseWppErrorCode;
use App\Models\Scopes\CampaignOwnerScope;
use Database\Factories\CampaignRecipientFactory;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ScopedBy([CampaignOwnerScope::class])]
class CampaignRecipient extends Model
{
    /** @use HasFactory<CampaignRecipientFactory> */
    use HasFactory;

    protected $fillable = [
        'campaign_id',
        'lead_id',
        'company_name',
        'phone',
        'campaign_message_id',
        'rendered_content',
        'status',
        'error_code',
    ];

    protected function casts(): array
    {
        return [
            'status' => CampaignRecipientStatus::class,
            'error_code' => PulseWppErrorCode::class,
        ];
    }

    /**
     * @return BelongsTo<Campaign, $this>
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    /**
     * The Lead this recipient was snapshotted from. Nullable: the snapshot
     * survives even if the underlying Lead is later deleted.
     *
     * @return BelongsTo<Lead, $this>
     */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    /**
     * The CampaignMessage drawn for this recipient, once a send is attempted.
     *
     * @return BelongsTo<CampaignMessage, $this>
     */
    public function campaignMessage(): BelongsTo
    {
        return $this->belongsTo(CampaignMessage::class);
    }

    /**
     * Filter recipients by free text matching the company name or phone.
     */
    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        if (! $search) {
            return $query;
        }

        return $query->where(function (Builder $query) use ($search): void {
            $query->where('company_name', 'like', '%'.$search.'%')
                ->orWhere('phone', 'like', '%'.$search.'%');
        });
    }

    /**
     * Filter recipients by status. A "pending" filter also matches
     * "processing" recipients, since a recipient mid-dispatch is still
     * pending from the user's point of view.
     */
    public function scopeFilterStatus(Builder $query, ?string $status): Builder
    {
        if (! $status) {
            return $query;
        }

        if ($status === CampaignRecipientStatus::PENDING->value) {
            return $query->whereIn('status', [
                CampaignRecipientStatus::PENDING,
                CampaignRecipientStatus::PROCESSING,
            ]);
        }

        return $query->where('status', $status);
    }
}
