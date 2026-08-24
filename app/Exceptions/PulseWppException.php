<?php

namespace App\Exceptions;

use App\Enums\PulseWppErrorCode;
use Illuminate\Http\Client\Response;
use RuntimeException;

class PulseWppException extends RuntimeException
{
    public function __construct(public readonly PulseWppErrorCode $errorCode, string $message)
    {
        parent::__construct($message);
    }

    public static function fromResponse(Response $response): self
    {
        $code = PulseWppErrorCode::tryFrom((string) $response->json('error.code'))
            ?? PulseWppErrorCode::INTERNAL_ERROR;

        return new self($code, (string) ($response->json('error.message') ?? 'pulse-wpp request failed.'));
    }
}
