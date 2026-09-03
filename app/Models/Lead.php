<?php

namespace App\Models;

use App\Actions\NormalizePhoneAction;
use App\Enums\LeadStatus;
use App\Models\Scopes\LeadOwnerScope;
use Database\Factories\LeadFactory;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

/**
 * @property int $user_id
 * @property-read string|null $whatsapp_phone
 */
#[ScopedBy([LeadOwnerScope::class])]
class Lead extends Model
{
    /** @use HasFactory<LeadFactory> */
    use HasFactory;

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::creating(function (Lead $lead): void {
            $lead->user_id ??= (int) Auth::id();
        });
    }

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
        'facebook',
        'has_website',
        'has_instagram',
        'likely_whatsapp',
        'city',
        'state',
        'status',
        'type',
        'responsible',
        'score',
        'rating',
        'reviews_count',
        'source',
        'google_maps_url',
        'source_created_at',
        'notes',
        'summary',
    ];

    protected function casts(): array
    {
        return [
            'status' => LeadStatus::class,
            'has_website' => 'boolean',
            'has_instagram' => 'boolean',
            'likely_whatsapp' => 'boolean',
            'score' => 'integer',
            'rating' => 'decimal:2',
            'reviews_count' => 'integer',
            'source_created_at' => 'datetime',
        ];
    }

    /**
     * The lead's phone normalized for the wa.me WhatsApp link, or null
     * when the lead has no phone or it fails normalization.
     *
     * @return Attribute<string|null, never>
     */
    protected function whatsappPhone(): Attribute
    {
        return Attribute::make(
            get: fn (): ?string => (new NormalizePhoneAction)->execute($this->phone),
        );
    }

    /**
     * Filter leads by free text matching the company name.
     *
     * @param  Builder<Lead>  $query
     * @return Builder<Lead>
     */
    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        if (! $search) {
            return $query;
        }

        return $query->where('company_name', 'like', '%'.$search.'%');
    }

    /**
     * Filter leads by status.
     *
     * @param  Builder<Lead>  $query
     * @return Builder<Lead>
     */
    public function scopeFilterStatus(Builder $query, ?string $status): Builder
    {
        if (! $status) {
            return $query;
        }

        return $query->where('status', $status);
    }

    /**
     * Filter leads by category.
     *
     * @param  Builder<Lead>  $query
     * @return Builder<Lead>
     */
    public function scopeFilterCategory(Builder $query, ?string $category): Builder
    {
        if (! $category) {
            return $query;
        }

        return $query->where('category', $category);
    }

    /**
     * Filter leads by city.
     *
     * @param  Builder<Lead>  $query
     * @return Builder<Lead>
     */
    public function scopeFilterCity(Builder $query, ?string $city): Builder
    {
        if (! $city) {
            return $query;
        }

        return $query->where('city', $city);
    }

    /**
     * Filter leads by phone, matching on digits only so the search doesn't
     * depend on the exact mask/formatting stored on the lead or typed by
     * the user (e.g. "(81) 99999-9999" matches a search for "819999999").
     *
     * @param  Builder<Lead>  $query
     * @return Builder<Lead>
     */
    public function scopeFilterPhone(Builder $query, ?string $phone): Builder
    {
        $digits = preg_replace('/\D+/', '', $phone ?? '') ?? '';

        if ($digits === '') {
            return $query;
        }

        $normalizedPhoneColumn = "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(phone, ' ', ''), '-', ''), '(', ''), ')', ''), '+', ''), '.', '')";

        return $query->whereRaw("{$normalizedPhoneColumn} LIKE ?", ['%'.$digits.'%']);
    }

    /**
     * Sort leads by an allowed column, defaulting to most recent first.
     *
     * @param  Builder<Lead>  $query
     * @return Builder<Lead>
     */
    public function scopeSortBy(Builder $query, ?string $sort, ?string $direction): Builder
    {
        $sortable = ['company_name', 'score', 'status'];

        if (! in_array($sort, $sortable, true)) {
            return $query->latest();
        }

        return $query->orderBy($sort, $direction === 'asc' ? 'asc' : 'desc');
    }

    /**
     * The leads eligible to be selected into a Campaign's Lead snapshot:
     * those with a phone NormalizePhoneAction can turn into a WhatsApp number.
     *
     * @return Collection<int, Lead>
     */
    public static function selectableForCampaigns(): Collection
    {
        return static::all()
            ->filter(fn (Lead $lead): bool => $lead->whatsapp_phone !== null)
            ->values();
    }

    /**
     * Which of the given raw ids correspond to Leads within the given
     * selectable set, used to validate a Campaign create page's
     * pre-filled Lead selection (e.g. from a retry-failed shortcut).
     *
     * @param  Collection<int, Lead>  $leads
     * @param  array<int, mixed>  $rawIds
     * @return Collection<int, int>
     */
    public static function preselectedIdsFrom(Collection $leads, array $rawIds): Collection
    {
        return collect($rawIds)
            ->map(fn ($id) => (int) $id)
            ->intersect($leads->pluck('id'))
            ->values();
    }

    /**
     * The distinct filter option values available across all leads.
     *
     * @return array{statuses: array<LeadStatus>, categories: Collection<int, string>, cities: Collection<int, string>}
     */
    public static function filterOptions(): array
    {
        return [
            'statuses' => LeadStatus::cases(),
            'categories' => static::query()->whereNotNull('category')->distinct()->orderBy('category')->pluck('category'),
            'cities' => static::query()->whereNotNull('city')->distinct()->orderBy('city')->pluck('city'),
        ];
    }

    /**
     * The distinct filter option values present within a given set of leads.
     *
     * @param  Collection<int, Lead>  $leads
     * @return array{statuses: array<LeadStatus>, categories: Collection<int, string>, cities: Collection<int, string>}
     */
    public static function filterOptionsFor(Collection $leads): array
    {
        return [
            'statuses' => LeadStatus::cases(),
            'categories' => $leads->pluck('category')->filter()->unique()->sort()->values(),
            'cities' => $leads->pluck('city')->filter()->unique()->sort()->values(),
        ];
    }
}
