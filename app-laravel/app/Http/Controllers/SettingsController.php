<?php

namespace App\Http\Controllers;

use App\Jobs\CheckBacklinkJob;
use App\Models\Backlink;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class SettingsController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $queueStats = [
            'pending'  => DB::table('jobs')->count(),
            'failed'   => DB::table('failed_jobs')->count(),
            'total'    => Backlink::count(),
            'checked_today' => Backlink::whereDate('last_checked_at', today())->count(),
            'never_checked' => Backlink::whereNull('last_checked_at')->count(),
            'overdue'  => Backlink::where(function ($q) {
                $q->whereNull('last_checked_at')
                  ->orWhere('last_checked_at', '<', now()->subDay());
            })->count(),
        ];

        return view('pages.settings.index', compact('user', 'queueStats'));
    }

    public function runCheck(Request $request)
    {
        $validated = $request->validate([
            'frequency' => ['required', 'in:daily,weekly,all'],
            'status'    => ['nullable', 'in:active,lost,changed,all'],
        ]);

        $frequency = $validated['frequency'];
        $status    = $validated['status'] ?? 'all';

        $query = Backlink::query();

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        if ($frequency === 'daily') {
            $query->where(function ($q) {
                $q->whereNull('last_checked_at')
                  ->orWhere('last_checked_at', '<', now()->subDay());
            });
        } elseif ($frequency === 'weekly') {
            $query->where(function ($q) {
                $q->whereNull('last_checked_at')
                  ->orWhere('last_checked_at', '<', now()->subWeek());
            });
        }

        $backlinks = $query->get();
        $count = $backlinks->count();

        foreach ($backlinks as $backlink) {
            CheckBacklinkJob::dispatch($backlink);
        }

        return back()->with('success', "{$count} backlink(s) ajouté(s) à la queue de vérification.");
    }

    public function updateMonitoring(Request $request)
    {
        $validated = $request->validate([
            'check_frequency'      => ['required', 'in:hourly,daily,weekly'],
            'http_timeout'         => ['required', 'integer', 'min:5', 'max:120'],
            'email_alerts_enabled' => ['sometimes', 'boolean'],
        ]);

        $validated['email_alerts_enabled'] = $request->boolean('email_alerts_enabled');

        auth()->user()->update($validated);

        return back()->with('success', 'Paramètres de monitoring sauvegardés.');
    }

    public function updateSeo(Request $request)
    {
        $validated = $request->validate([
            'seo_provider' => ['required', 'in:moz,custom'],
            'seo_api_key'  => ['nullable', 'string', 'max:500'],
        ]);

        $updateData = ['seo_provider' => $validated['seo_provider']];

        if (! empty($validated['seo_api_key'])) {
            $updateData['seo_api_key_encrypted'] = Crypt::encryptString($validated['seo_api_key']);
        }

        auth()->user()->update($updateData);

        return back()->with('success', 'Configuration API SEO sauvegardée.');
    }

    public function testSeoConnection(Request $request)
    {
        $user = auth()->user();

        if ($user->seo_provider === 'custom' || empty($user->seo_api_key_encrypted)) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun provider configuré. Sélectionnez Moz et entrez votre clé API.',
            ]);
        }

        try {
            $apiKey = Crypt::decryptString($user->seo_api_key_encrypted);
            [$accessId, $secretKey] = array_pad(explode(':', $apiKey, 2), 2, '');

            $response = Http::withBasicAuth($accessId, $secretKey)
                ->timeout(10)
                ->post('https://lsapi.seomoz.com/v2/url_metrics', [
                    'targets' => ['google.com'],
                    'metrics' => ['domain_authority'],
                ]);

            if ($response->successful()) {
                $da = $response->json('results.0.domain_authority');
                return response()->json([
                    'success' => true,
                    'message' => "Connexion réussie ! DA de google.com = " . round($da),
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => "Erreur API HTTP {$response->status()}. Vérifiez vos identifiants.",
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur : ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * STORY-068 : Sauvegarder les credentials DataforSEO (chiffrés).
     */
    public function updateDataforSeo(Request $request)
    {
        $validated = $request->validate([
            'dataforseo_login'    => ['nullable', 'string', 'max:255'],
            'dataforseo_password' => ['nullable', 'string', 'max:500'],
            'seo_provider'        => ['required', 'in:dataforseo,moz,custom'],
        ]);

        $updateData = ['seo_provider' => $validated['seo_provider']];

        if (! empty($validated['dataforseo_login'])) {
            $updateData['dataforseo_login_encrypted'] = Crypt::encryptString($validated['dataforseo_login']);
        }

        if (! empty($validated['dataforseo_password'])) {
            $updateData['dataforseo_password_encrypted'] = Crypt::encryptString($validated['dataforseo_password']);
        }

        auth()->user()->update($updateData);

        return back()->with('success', 'Configuration DataforSEO sauvegardée.');
    }

    /**
     * STORY-068 : Tester la connexion DataforSEO.
     */
    public function testDataforSeoConnection(Request $request)
    {
        $user = auth()->user();

        if (empty($user->dataforseo_login_encrypted) || empty($user->dataforseo_password_encrypted)) {
            return response()->json([
                'success' => false,
                'message' => 'Identifiants DataforSEO non configurés.',
            ]);
        }

        try {
            $login    = Crypt::decryptString($user->dataforseo_login_encrypted);
            $password = Crypt::decryptString($user->dataforseo_password_encrypted);

            $response = Http::withBasicAuth($login, $password)
                ->timeout(10)
                ->get('https://api.dataforseo.com/v3/appendix/user_data');

            if ($response->successful()) {
                $credits = $response->json('tasks.0.result.0.money.balance') ?? null;
                $msg = 'Connexion réussie !';
                if ($credits !== null) {
                    $msg .= " Crédits restants : \${$credits}";
                }
                return response()->json(['success' => true, 'message' => $msg]);
            }

            return response()->json([
                'success' => false,
                'message' => "Erreur API HTTP {$response->status()}. Vérifiez vos identifiants.",
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur : ' . $e->getMessage(),
            ]);
        }
    }
}
