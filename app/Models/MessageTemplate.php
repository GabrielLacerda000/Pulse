<?php

namespace App\Models;

use App\Models\Scopes\MessageTemplateOwnerScope;
use Database\Factories\MessageTemplateFactory;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

#[ScopedBy([MessageTemplateOwnerScope::class])]
class MessageTemplate extends Model
{
    /** @use HasFactory<MessageTemplateFactory> */
    use HasFactory;

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::creating(function (MessageTemplate $template): void {
            $template->user_id ??= Auth::id();
        });
    }

    protected $fillable = [
        'name',
        'content',
        'channel',
    ];
}
