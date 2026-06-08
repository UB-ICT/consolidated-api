<?php

namespace Modules\UBForms\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Modules\Auth\Models\User;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Google\Client as GoogleClient;
use Google\Service\Directory as GoogleDirectory;
use Exception;
use App\Services\FirestoreService;


class AnnualReportAuthController extends Controller
{
    /**
     * STEP 1:
     * Redirect the user to Google's OAuth login page.
     *
     * This route MUST be public (no auth middleware),
     * because the user does not have a token yet.
     */
    public function redirect(Request $request)
    {
        // Construct redirect URI dynamically to ensure it matches the actual callback URL
        // This ensures it works across different environments (local, staging, production)
        $redirectUri = config('services.google_annual_report.redirect_uri');
        Log::info('Redirect URI11: ' . $redirectUri);

        // If not set in config, construct absolute URL from request
        if (!$redirectUri) {
            $baseUrl = rtrim(config('app.url'), '/');
            // Fallback to request URL if APP_URL is not set
            if ($baseUrl === 'http://localhost:3031') {
                $baseUrl = $request->getSchemeAndHttpHost();
            }
            $redirectUri = $baseUrl . '/auth/google/annual-report-callback';
        }

        Log::info('Redirect URI: ' . $redirectUri);

        return Socialite::driver('google')
            ->stateless()
            ->redirectUrl($redirectUri)
            ->redirect();
    }

    
}
