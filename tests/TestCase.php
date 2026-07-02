<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Schema;
use Laravel\Passport\ClientRepository;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpPassport();
    }

    /**
     * RefreshDatabase wipes the oauth_* tables each run, so recreate a personal-access
     * client for endpoints that issue Passport tokens (login / register / password reset).
     */
    protected function setUpPassport(): void
    {
        if (Schema::hasTable('oauth_personal_access_clients')) {
            (new ClientRepository())->createPersonalAccessClient(
                null,
                'Testing Personal Access Client',
                'http://localhost'
            );
        }
    }
}
