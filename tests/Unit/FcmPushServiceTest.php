<?php

namespace Tests\Unit;

use App\Services\Push\FcmPushService;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class FcmPushServiceTest extends TestCase
{
    public function test_not_registered_response_is_treated_as_invalid_token(): void
    {
        $method = new ReflectionMethod(FcmPushService::class, 'looksLikeInvalidToken');

        $body = json_encode([
            'error' => [
                'code' => 404,
                'message' => 'NotRegistered',
                'status' => 'NOT_FOUND',
            ],
        ], JSON_THROW_ON_ERROR);

        $this->assertTrue($method->invoke(new FcmPushService(), $body, 404));
    }

    public function test_unrelated_not_found_response_is_not_treated_as_invalid_token(): void
    {
        $method = new ReflectionMethod(FcmPushService::class, 'looksLikeInvalidToken');

        $body = json_encode([
            'error' => [
                'code' => 404,
                'message' => 'Some other missing resource',
                'status' => 'NOT_FOUND',
            ],
        ], JSON_THROW_ON_ERROR);

        $this->assertFalse($method->invoke(new FcmPushService(), $body, 404));
    }
}
