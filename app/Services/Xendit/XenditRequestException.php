<?php

namespace App\Services\Xendit;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use RuntimeException;

class XenditRequestException extends RuntimeException
{
    public static function fromResponse(Response $response): self
    {
        return new self("Xendit API request failed with status {$response->status()}: {$response->body()}");
    }

    public static function fromConnectionFailure(ConnectionException $exception): self
    {
        return new self("Xendit API request could not be completed: {$exception->getMessage()}", previous: $exception);
    }
}
