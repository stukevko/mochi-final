<?php

namespace App\Services;

final class HealthCheckResult
{
    public function __construct(
        public bool $ok,
        public string $message,
    ) {}
}
