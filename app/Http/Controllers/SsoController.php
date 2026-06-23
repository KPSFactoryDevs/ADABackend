<?php

namespace App\Http\Controllers;

use App\Domains\Auth\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SsoController extends Controller
{
    /**
     * Handle SSO callback from KPS Suites.
     * Validates the signed token, finds or creates the user,
     * generates a Passport API token, and redirects to the React frontend.
     */
    public function callback(Request $request)
    {
        $payloadEncoded = $request->query('payload');
        $signature = $request->query('signature');

        if (!$payloadEncoded || !$signature) {
            return redirect(config('app.frontend_url') . '/login?error=' . urlencode('Token SSO mancante.'));
        }

        // Verify signature
        $expectedSignature = hash_hmac('sha256', $payloadEncoded, config('app.sso_secret'));

        if (!hash_equals($expectedSignature, $signature)) {
            return redirect(config('app.frontend_url') . '/login?error=' . urlencode('Token SSO non valido.'));
        }

        // Decode payload
        $payload = json_decode(base64_decode($payloadEncoded), true);

        if (!$payload || !isset($payload['email'], $payload['name'], $payload['expires'])) {
            return redirect(config('app.frontend_url') . '/login?error=' . urlencode('Payload SSO corrotto.'));
        }

        // Check expiry (5 min window)
        if (now()->timestamp > $payload['expires']) {
            return redirect(config('app.frontend_url') . '/login?error=' . urlencode('Token SSO scaduto. Riprova da KPS Suites.'));
        }

        // Find or create user
        $user = User::where('email', $payload['email'])->first();

        if (!$user) {
            $user = User::create([
                'type' => User::TYPE_USER,
                'name' => $payload['name'],
                'email' => $payload['email'],
                'email_verified_at' => now(),
                'password' => Hash::make(Str::random(32)),
                'active' => true,
            ]);
        } else {
            $user->update([
                'name' => $payload['name'],
            ]);
        }

        // Generate Passport API token
        $token = $user->createToken('SSO Token')->accessToken;

        // Build user data for frontend
        $userData = base64_encode(json_encode([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ]));

        // Build apps data for frontend launchpad
        $appsData = base64_encode(json_encode($payload['apps'] ?? []));

        // Additional SSO metadata
        $meta = base64_encode(json_encode([
            'kps_suites_url' => config('app.kps_suites_login_url'),
            'license_expires_at' => $payload['license_expires_at'] ?? null,
            'is_owner' => $payload['is_owner'] ?? false,
            'organization_id' => $payload['organization_id'] ?? null,
        ]));

        // Redirect to React frontend with token and data
        $frontendUrl = rtrim(config('app.frontend_url'), '/');
        $redirectUrl = $frontendUrl . '/sso-login?' . http_build_query([
            'token' => $token,
            'user' => $userData,
            'apps' => $appsData,
            'meta' => $meta,
        ]);

        return redirect()->away($redirectUrl);
    }
}
