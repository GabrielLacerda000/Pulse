<?php

namespace App\Concerns;

use Illuminate\Support\Facades\Log;

trait LogsStructuredEvents
{
    /**
     * Emit a structured log event with a stable name and context payload,
     * routed to the channel returned by logChannel(). Callers are
     * responsible for keeping the context free of ambient state (e.g.
     * Auth::id()) and PII (mask phone numbers, never log message bodies).
     *
     * @param  array<string, mixed>  $context
     */
    protected function logEvent(string $event, array $context = [], string $level = 'info'): void
    {
        Log::channel($this->logChannel())->{$level}($event, $context);
    }

    /**
     * The log channel structured events should be routed to. Consuming
     * classes may override this to target a different channel.
     */
    protected function logChannel(): string
    {
        return 'campaigns';
    }
}
