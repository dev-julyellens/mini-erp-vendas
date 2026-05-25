<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\ApiRequest;
use App\Repositories\ApiRateLimitRepository;

final class ApiRateLimitService
{
    private ApiRateLimitRepository $repository;
    private int $limit;
    private int $windowSeconds;
    private int $loginLimit;

    public function __construct(?ApiRateLimitRepository $repository = null)
    {
        $this->repository = $repository ?? new ApiRateLimitRepository();
        $config = require dirname(__DIR__, 2) . '/config/app.php';
        $api = $config['api'] ?? [];
        $this->limit = max(1, (int) ($api['rate_limit'] ?? 60));
        $this->windowSeconds = max(1, (int) ($api['rate_limit_window'] ?? 60));
        $this->loginLimit = max(1, (int) ($api['login_rate_limit'] ?? 10));
    }

    /**
     * @return array{allowed: bool, retry_after: int}
     */
    public function checkLogin(string $method, string $endpoint): array
    {
        $bucketKey = $this->buildBucketKey($method, $endpoint, 'login');

        return $this->repository->hit($bucketKey, $this->loginLimit, $this->windowSeconds);
    }

    /**
     * @return array{allowed: bool, retry_after: int}
     */
    public function check(string $method, string $endpoint): array
    {
        $bucketKey = $this->buildBucketKey($method, $endpoint, 'api');

        return $this->repository->hit($bucketKey, $this->limit, $this->windowSeconds);
    }

    private function buildBucketKey(string $method, string $endpoint, string $scope): string
    {
        return hash('sha256', ApiRequest::clientIp() . '|' . $scope . '|' . strtoupper($method) . '|' . $endpoint);
    }
}
