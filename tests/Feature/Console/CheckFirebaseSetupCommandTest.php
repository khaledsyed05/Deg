<?php

namespace Tests\Feature\Console;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class CheckFirebaseSetupCommandTest extends TestCase
{
    private static ?string $privateKey = null;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        $resource = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);
        openssl_pkey_export($resource, $privateKey);
        self::$privateKey = $privateKey;
    }

    public function test_fails_when_project_id_missing(): void
    {
        config([
            'services.firebase.project_id' => '',
            'services.firebase.credentials_path' => '',
            'services.firebase.service_account' => '',
        ]);

        $this->artisan('firebase:check')
            ->expectsOutputToContain('FIREBASE_PROJECT_ID is not set.')
            ->assertExitCode(1);
    }

    public function test_fails_when_no_credentials_supplied(): void
    {
        config([
            'services.firebase.project_id' => 'test-project',
            'services.firebase.credentials_path' => '/no/such/file.json',
            'services.firebase.service_account' => '',
        ]);

        $this->artisan('firebase:check')
            ->expectsOutputToContain('No Firebase credentials found')
            ->assertExitCode(1);
    }

    public function test_fails_when_json_missing_required_keys(): void
    {
        config([
            'services.firebase.project_id' => 'test-project',
            'services.firebase.credentials_path' => '',
            'services.firebase.service_account' => json_encode(['client_email' => 'x@y.com']),
        ]);

        $this->artisan('firebase:check')
            ->expectsOutputToContain('missing required fields')
            ->assertExitCode(1);
    }

    public function test_succeeds_with_valid_inline_json(): void
    {
        config([
            'services.firebase.project_id' => 'test-project',
            'services.firebase.credentials_path' => '',
            'services.firebase.service_account' => json_encode([
                'client_email' => 'fake@test-project.iam.gserviceaccount.com',
                'private_key' => self::$privateKey,
            ]),
        ]);

        $this->artisan('firebase:check')
            ->expectsOutputToContain('Firebase credentials look valid')
            ->assertExitCode(0);
    }

    public function test_succeeds_with_valid_credentials_file(): void
    {
        $path = storage_path('app/firebase-test-creds.json');
        File::ensureDirectoryExists(dirname($path));
        File::put($path, json_encode([
            'client_email' => 'fake@test-project.iam.gserviceaccount.com',
            'private_key' => self::$privateKey,
        ]));

        config([
            'services.firebase.project_id' => 'test-project',
            'services.firebase.credentials_path' => $path,
            'services.firebase.service_account' => '',
        ]);

        try {
            $this->artisan('firebase:check')
                ->expectsOutputToContain('Credentials source: file')
                ->assertExitCode(0);
        } finally {
            File::delete($path);
        }
    }
}
