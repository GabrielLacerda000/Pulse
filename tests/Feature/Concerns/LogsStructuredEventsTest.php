<?php

use App\Concerns\LogsStructuredEvents;
use Illuminate\Support\Facades\Log;

test('logEvent routes to the default campaigns channel at the given level with context', function () {
    Log::spy();
    $channel = Mockery::mock();
    $channel->shouldReceive('warning')->once()->with('campaign.sent', ['campaign_id' => 1]);
    Log::shouldReceive('channel')->with('campaigns')->andReturn($channel);

    $subject = new class
    {
        use LogsStructuredEvents;

        public function fire(): void
        {
            $this->logEvent('campaign.sent', ['campaign_id' => 1], 'warning');
        }
    };

    $subject->fire();

    Log::shouldHaveReceived('channel')->with('campaigns')->once();
});

test('logEvent defaults to the info level', function () {
    $channel = Mockery::mock();
    $channel->shouldReceive('info')->once()->with('campaign.started', ['campaign_id' => 5]);
    Log::shouldReceive('channel')->with('campaigns')->andReturn($channel);

    $subject = new class
    {
        use LogsStructuredEvents;

        public function fire(): void
        {
            $this->logEvent('campaign.started', ['campaign_id' => 5]);
        }
    };

    $subject->fire();
});

test('logChannel can be overridden by the consuming class', function () {
    $channel = Mockery::mock();
    $channel->shouldReceive('info')->once()->with('lead.imported', []);
    Log::shouldReceive('channel')->with('imports')->andReturn($channel);

    $subject = new class
    {
        use LogsStructuredEvents;

        public function fire(): void
        {
            $this->logEvent('lead.imported');
        }

        protected function logChannel(): string
        {
            return 'imports';
        }
    };

    $subject->fire();
});
