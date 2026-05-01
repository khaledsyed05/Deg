<?php

namespace App\Console\Commands\Firebase;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class CheckFirebaseSetupCommand extends Command
{
    protected $signature = 'firebase:check {--ping : Attempt an OAuth2 token exchange against Google}';

    protected $description = 'Verify Firebase / FCM credentials are configured correctly.';

    public function handle(): int
    {
        $projectId = (string) config('services.firebase.project_id', '');
        $credentialsPath = (string) config('services.firebase.credentials_path', '');
        $serviceAccountJson = (string) config('services.firebase.service_account', '');

        if ($projectId === '') {
            $this->error('FIREBASE_PROJECT_ID is not set.');

            return self::FAILURE;
        }

        $this->info("Project ID: {$projectId}");

        $json = null;
        $source = null;

        if ($credentialsPath !== '' && is_file($credentialsPath) && is_readable($credentialsPath)) {
            $json = file_get_contents($credentialsPath) ?: null;
            $source = "file ({$credentialsPath})";
        } elseif ($serviceAccountJson !== '' && $serviceAccountJson !== '{}') {
            $json = $serviceAccountJson;
            $source = 'env (FIREBASE_SERVICE_ACCOUNT)';
        }

        if ($json === null) {
            $this->error('No Firebase credentials found. Set FIREBASE_CREDENTIALS to a service-account JSON file path, or paste the JSON into FIREBASE_SERVICE_ACCOUNT.');

            return self::FAILURE;
        }

        $this->info("Credentials source: {$source}");

        $decoded = json_decode($json, true);

        if (! is_array($decoded)) {
            $this->error('Service account JSON failed to parse.');

            return self::FAILURE;
        }

        if (empty($decoded['client_email']) || empty($decoded['private_key'])) {
            $this->error('Service account JSON is missing required fields: client_email and/or private_key.');

            return self::FAILURE;
        }

        $this->info("client_email: {$decoded['client_email']}");

        if ($this->option('ping')) {
            return $this->ping($decoded);
        }

        $this->info('Firebase credentials look valid. Use --ping to attempt a real OAuth2 token exchange.');

        return self::SUCCESS;
    }

    /**
     * @param  array<string, mixed>  $credentials
     */
    private function ping(array $credentials): int
    {
        $now = time();
        $header = base64_encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $payload = base64_encode(json_encode([
            'iss' => $credentials['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600,
        ]));

        $signingInput = "{$header}.{$payload}";
        openssl_sign($signingInput, $signature, $credentials['private_key'], OPENSSL_ALGO_SHA256);
        $jwt = "{$signingInput}.".base64_encode($signature);

        $response = Http::asForm()
            ->timeout(10)
            ->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]);

        if (! $response->successful() || ! $response->json('access_token')) {
            $this->error('OAuth2 token exchange failed: '.$response->status().' '.$response->body());

            return self::FAILURE;
        }

        $this->info('OAuth2 token exchange succeeded. Firebase setup is fully working.');

        return self::SUCCESS;
    }
}
