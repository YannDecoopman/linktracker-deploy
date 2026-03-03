<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\BacklinkController;
use App\Http\Controllers\PlatformController;
use App\Http\Controllers\AlertController;
use App\Http\Controllers\WebhookSettingsController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SourceDomainController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// NEW BLADE ROUTES - SaaS UI Redesign (EPIC-013)

// Home page - Redirect to dashboard
Route::get('/', function () {
    return redirect('/dashboard');
});

// Dashboard principale avec nouveau layout Blade
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/api/dashboard/chart', [DashboardController::class, 'chartData'])->name('dashboard.chart');

// Projects - CRUD complet avec nouveau layout Blade
Route::get('/projects/{project}/report', [ProjectController::class, 'report'])->name('projects.report');
Route::resource('projects', ProjectController::class);

// Import CSV de backlinks (STORY-031) - AVANT la resource pour éviter le conflit avec {backlink}
Route::get('/backlinks/import', [BacklinkController::class, 'importForm'])->name('backlinks.import');
Route::post('/backlinks/import', [BacklinkController::class, 'importCsv'])
    ->name('backlinks.import.process')
    ->middleware(['throttle:backlink-import']);

// Export CSV de backlinks (STORY-035)
Route::get('/backlinks/export', [BacklinkController::class, 'exportCsv'])->name('backlinks.export');

// Backlinks - CRUD complet avec nouveau layout Blade
// Rate limiting sur index pour éviter DoS via filtres/recherche
Route::resource('backlinks', BacklinkController::class)
    ->middleware(['throttle:60,1']);

// Bulk actions (suppression/édition en masse)
Route::post('/backlinks/bulk-delete', [BacklinkController::class, 'bulkDelete'])->name('backlinks.bulk-delete');
Route::post('/backlinks/bulk-edit', [BacklinkController::class, 'bulkEdit'])->name('backlinks.bulk-edit');
Route::post('/backlinks/bulk-check', [BacklinkController::class, 'bulkCheck'])
    ->name('backlinks.bulk-check')
    ->middleware(['throttle:backlink-check']);

// Vérification manuelle d'un backlink (STORY-044: 10 req/min par utilisateur)
Route::post('/backlinks/{backlink}/check', [BacklinkController::class, 'check'])
    ->name('backlinks.check')
    ->middleware(['throttle:backlink-check']);

// Refresh métriques SEO d'un backlink (STORY-025, STORY-044: 3 req/min par utilisateur)
Route::post('/backlinks/{backlink}/seo-metrics', [BacklinkController::class, 'refreshSeoMetrics'])
    ->name('backlinks.seo-metrics')
    ->middleware(['throttle:seo-refresh']);

// Platforms - Gestion des plateformes d'achat de liens
Route::resource('platforms', PlatformController::class)->except(['show']);

// Alerts - Système d'alertes pour backlinks (EPIC-004)
Route::get('/alerts', [AlertController::class, 'index'])->name('alerts.index');
Route::patch('/alerts/{alert}/mark-read', [AlertController::class, 'markAsRead'])->name('alerts.mark-read');
Route::patch('/alerts/mark-all-read', [AlertController::class, 'markAllAsRead'])->name('alerts.mark-all-read');
Route::delete('/alerts/{alert}', [AlertController::class, 'destroy'])->name('alerts.destroy');
Route::delete('/alerts/destroy-all-read', [AlertController::class, 'destroyAllRead'])->name('alerts.destroy-all-read');

// Settings - Configuration globale (STORY-027, STORY-028)
Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
Route::patch('/settings/monitoring', [SettingsController::class, 'updateMonitoring'])->name('settings.monitoring');
Route::post('/settings/monitoring/run-check', [SettingsController::class, 'runCheck'])->name('settings.monitoring.run-check');
Route::patch('/settings/seo', [SettingsController::class, 'updateSeo'])->name('settings.seo');
Route::post('/settings/seo/test', [SettingsController::class, 'testSeoConnection'])->name('settings.seo.test');
Route::patch('/settings/dataforseo', [SettingsController::class, 'updateDataforSeo'])->name('settings.dataforseo');
Route::post('/settings/dataforseo/test', [SettingsController::class, 'testDataforSeoConnection'])->name('settings.dataforseo.test');

// Settings - Webhook configurable (STORY-019)
Route::get('/settings/webhook', [WebhookSettingsController::class, 'show'])->name('settings.webhook');
Route::put('/settings/webhook', [WebhookSettingsController::class, 'update'])->name('settings.webhook.update');
Route::post('/settings/webhook/test', [WebhookSettingsController::class, 'test'])->name('settings.webhook.test');
Route::get('/settings/webhook/generate-secret', [WebhookSettingsController::class, 'generateSecret'])->name('settings.webhook.generate-secret');

// Profile utilisateur (STORY-046)
Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
Route::patch('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

// Domaines sources (EPIC-014) - AVANT catch-all
Route::get('/domains', [SourceDomainController::class, 'index'])->name('domains.index');
Route::post('/domains', [SourceDomainController::class, 'store'])->name('domains.store');
Route::get('/domains/{domain}', [SourceDomainController::class, 'show'])->name('domains.show')->where('domain', '[a-z0-9\-\.]+');
Route::post('/domains/{domain}/refresh-metrics', [SourceDomainController::class, 'refreshMetrics'])->name('domains.refresh-metrics')->middleware(['throttle:seo-refresh'])->where('domain', '[a-z0-9\-\.]+');

// Marketplace - Commandes de liens (STORY-032/033)
Route::resource('orders', OrderController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy']);
Route::patch('/orders/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.status');

// Page "En construction" pour fonctionnalités futures
Route::view('/under-construction', 'pages.under-construction')->name('pages.under-construction');

// TODO: Ajouter routes Blade pour :
// - /alerts (EPIC-004)
// - /orders (EPIC-006)
// - /settings (EPIC-008)

// SPA entry point - all routes handled by Vue Router (ancien système)
// IMPORTANT: Cette route catch-all doit rester en dernier
Route::get('/{any}', function () {
    return view('app');
})->where('any', '.*');
