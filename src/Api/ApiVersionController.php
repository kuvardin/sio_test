<?php

declare(strict_types=1);

namespace App\Api;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

abstract class ApiVersionController
{
    private function __construct()
    {
    }

    abstract public static function handle(
        ApiEnvironment $environment,
        array $route_parts,
        Request $request,
    ): JsonResponse;
}
