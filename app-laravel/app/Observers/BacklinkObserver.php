<?php

namespace App\Observers;

use App\Models\Backlink;
use App\Models\SourceDomain;
use Illuminate\Support\Facades\Cache;

/**
 * Observer STORY-041 : Invalide le cache des stats dashboard quand un backlink change.
 * Observer STORY-063 : Synchronise source_domains depuis source_url des backlinks.
 */
class BacklinkObserver
{
    public function created(Backlink $backlink): void
    {
        Cache::forget('dashboard_stats');
        $this->syncSourceDomain($backlink);
    }

    public function updated(Backlink $backlink): void
    {
        Cache::forget('dashboard_stats');
        if ($backlink->wasChanged('source_url')) {
            $this->syncSourceDomain($backlink);
        }
    }

    public function deleted(Backlink $backlink): void
    {
        Cache::forget('dashboard_stats');
    }

    private function syncSourceDomain(Backlink $backlink): void
    {
        if (empty($backlink->source_url)) {
            return;
        }

        $sourceDomain = SourceDomain::fromUrl($backlink->source_url);
        $sourceDomain->update(['last_synced_at' => now()]);
    }
}
