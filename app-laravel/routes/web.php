<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\MagicLinkController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\BacklinkController;
use App\Http\Controllers\PlatformController;
use App\Http\Controllers\AlertController;
use App\Http\Controllers\WebhookSettingsController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\IndexationController;
use App\Http\Controllers\SourceDomainController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Auth routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [MagicLinkController::class, 'showLogin'])->name('login');
    Route::post('/login', [MagicLinkController::class, 'sendLink'])->name('magic-link.send')->middleware('throttle:5,1');
});

Route::get('/magic-link/authenticate', [MagicLinkController::class, 'authenticate'])->middleware('signed')->name('magic-link.authenticate');

Route::post('/logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect()->route('login');
})->name('logout');

// All app routes behind auth
Route::middleware('auth')->group(function () {
    Route::get('/', function () {
        return redirect('/dashboard');
    });

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/api/dashboard/chart', [DashboardController::class, 'chartData'])->name('dashboard.chart');

    Route::get('/projects/{project}/report', [ProjectController::class, 'report'])->name('projects.report');
    Route::resource('projects', ProjectController::class);

    Route::get('/backlinks/import', [BacklinkController::class, 'importForm'])->name('backlinks.import');
    Route::post('/backlinks/import', [BacklinkController::class, 'importCsv'])
        ->name('backlinks.import.process')
        ->middleware(['throttle:backlink-import']);

    Route::get('/backlinks/export', [BacklinkController::class, 'exportCsv'])->name('backlinks.export');

    Route::resource('backlinks', BacklinkController::class)
        ->middleware(['throttle:60,1']);

    Route::post('/backlinks/bulk-delete', [BacklinkController::class, 'bulkDelete'])->name('backlinks.bulk-delete');
    Route::post('/backlinks/bulk-edit', [BacklinkController::class, 'bulkEdit'])->name('backlinks.bulk-edit');
    Route::post('/backlinks/bulk-check', [BacklinkController::class, 'bulkCheck'])
        ->name('backlinks.bulk-check')
        ->middleware(['throttle:backlink-check']);

    Route::post('/backlinks/{backlink}/check', [BacklinkController::class, 'check'])
        ->name('backlinks.check')
        ->middleware(['throttle:backlink-check']);

    Route::post('/backlinks/{backlink}/seo-metrics', [BacklinkController::class, 'refreshSeoMetrics'])
        ->name('backlinks.seo-metrics')
        ->middleware(['throttle:seo-refresh']);

    Route::resource('platforms', PlatformController::class)->except(['show']);

    Route::get('/alerts', [AlertController::class, 'index'])->name('alerts.index');
    Route::patch('/alerts/{alert}/mark-read', [AlertController::class, 'markAsRead'])->name('alerts.mark-read');
    Route::patch('/alerts/mark-all-read', [AlertController::class, 'markAllAsRead'])->name('alerts.mark-all-read');
    Route::delete('/alerts/{alert}', [AlertController::class, 'destroy'])->name('alerts.destroy');
    Route::delete('/alerts/destroy-all-read', [AlertController::class, 'destroyAllRead'])->name('alerts.destroy-all-read');

    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::patch('/settings/monitoring', [SettingsController::class, 'updateMonitoring'])->name('settings.monitoring');
    Route::post('/settings/monitoring/run-check', [SettingsController::class, 'runCheck'])->name('settings.monitoring.run-check');
    Route::patch('/settings/seo', [SettingsController::class, 'updateSeo'])->name('settings.seo');
    Route::post('/settings/seo/test', [SettingsController::class, 'testSeoConnection'])->name('settings.seo.test');
    Route::patch('/settings/dataforseo', [SettingsController::class, 'updateDataforSeo'])->name('settings.dataforseo');
    Route::post('/settings/dataforseo/test', [SettingsController::class, 'testDataforSeoConnection'])->name('settings.dataforseo.test');

    Route::get('/settings/webhook', [WebhookSettingsController::class, 'show'])->name('settings.webhook');
    Route::put('/settings/webhook', [WebhookSettingsController::class, 'update'])->name('settings.webhook.update');
    Route::post('/settings/webhook/test', [WebhookSettingsController::class, 'test'])->name('settings.webhook.test');
    Route::get('/settings/webhook/generate-secret', [WebhookSettingsController::class, 'generateSecret'])->name('settings.webhook.generate-secret');

    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::patch('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    Route::get('/domains', [SourceDomainController::class, 'index'])->name('domains.index');
    Route::post('/domains', [SourceDomainController::class, 'store'])->name('domains.store');
    Route::get('/domains/{domain}', [SourceDomainController::class, 'show'])->name('domains.show')->where('domain', '[a-z0-9\-\.]+');
    Route::post('/domains/{domain}/refresh-metrics', [SourceDomainController::class, 'refreshMetrics'])->name('domains.refresh-metrics')->middleware(['throttle:seo-refresh'])->where('domain', '[a-z0-9\-\.]+');

    Route::get('/indexation', [IndexationController::class, 'index'])->name('indexation.index');
    Route::post('/indexation/campaigns', [IndexationController::class, 'store'])
        ->name('indexation.campaigns.store')
        ->middleware(['throttle:indexation-submit']);
    Route::get('/indexation/campaigns/{campaign}', [IndexationController::class, 'showCampaign'])
        ->name('indexation.campaigns.show');
    Route::get('/indexation/campaigns/{campaign}/status', [IndexationController::class, 'campaignStatus'])
        ->name('indexation.campaigns.status');

    Route::patch('/settings/indexation', [SettingsController::class, 'updateIndexation'])->name('settings.indexation');
    Route::post('/settings/indexation/test', [SettingsController::class, 'testIndexationConnection'])->name('settings.indexation.test');

    Route::resource('orders', OrderController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy']);
    Route::patch('/orders/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.status');

    Route::view('/under-construction', 'pages.under-construction')->name('pages.under-construction');
});

// SPA entry point - catch-all must stay last
Route::get('/{any}', function () {
    return view('app');
})->where('any', '.*');
