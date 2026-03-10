<?php

namespace App\Http\Controllers;

use App\Jobs\SendWebhookJob;
use App\Models\Alert;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class WebhookSettingsController extends Controller
{
    public function show()
    {
        $user = auth()->user();

        if (! $user) {
            return redirect('/dashboard')->with('error', 'Aucun utilisateur configuré.');
        }

        return view('pages.settings.webhook', [
            'user' => $user,
            'availableEvents' => $this->getAvailableEvents(),
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'webhook_url' => 'nullable|url|max:500',
            'webhook_secret' => 'nullable|string|max:255',
            'webhook_events' => 'nullable|array',
            'webhook_events.*' => 'string|in:backlink_lost,backlink_changed,backlink_recovered',
        ]);

        $user = auth()->user();
        $user->update([
            'webhook_url' => $validated['webhook_url'] ?? null,
            'webhook_secret' => $validated['webhook_secret'] ?? null,
            'webhook_events' => $validated['webhook_events'] ?? [],
        ]);

        return redirect()->route('settings.webhook')
            ->with('success', 'Configuration webhook mise à jour.');
    }

    public function generateSecret()
    {
        $secret = Str::random(40);

        return response()->json(['secret' => $secret]);
    }

    public function test(Request $request)
    {
        $user = auth()->user();

        if (!$user->webhook_url) {
            return back()->with('error', 'Aucune URL webhook configurée.');
        }

        // Crée une alerte fictive pour le test
        $testAlert = new Alert([
            'id' => 0,
            'type' => 'backlink_lost',
            'severity' => 'medium',
            'title' => 'Test Webhook — LinkTracker',
            'message' => 'Ceci est un message de test envoyé depuis LinkTracker pour vérifier votre configuration webhook.',
            'created_at' => now(),
        ]);

        try {
            // Dispatch synchrone pour le test
            $job = new SendWebhookJob($testAlert, $user);
            $job->handle();

            return back()->with('success', 'Webhook de test envoyé avec succès.');
        } catch (\Exception $e) {
            return back()->with('error', "Échec du webhook de test : {$e->getMessage()}");
        }
    }

    private function getAvailableEvents(): array
    {
        return [
            Alert::TYPE_BACKLINK_LOST => 'Backlink perdu',
            Alert::TYPE_BACKLINK_CHANGED => 'Backlink modifié',
            Alert::TYPE_BACKLINK_RECOVERED => 'Backlink récupéré',
        ];
    }
}
