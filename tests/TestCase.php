<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

abstract class TestCase extends BaseTestCase
{
    /**
     * Authenticate subsequent requests with a real JWT for the given user.
     */
    protected function actingAsJwt(?User $user = null): User
    {
        $user ??= User::factory()->create();
        $this->withHeader('Authorization', 'Bearer '.JWTAuth::fromUser($user));

        return $user;
    }
}
