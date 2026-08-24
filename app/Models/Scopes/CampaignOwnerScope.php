<?php

namespace App\Models\Scopes;

use App\Models\Campaign;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

/**
 * Isolates Campaigns by owner directly, and CampaignMessages/CampaignRecipients
 * by their parent Campaign's owner, since only Campaign carries a user_id column.
 */
class CampaignOwnerScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        if (! Auth::check()) {
            return;
        }

        if ($model instanceof Campaign) {
            $builder->where($model->qualifyColumn('user_id'), Auth::id());

            return;
        }

        $builder->whereHas('campaign', fn (Builder $query) => $query->where('user_id', Auth::id()));
    }
}
