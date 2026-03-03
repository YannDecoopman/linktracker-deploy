<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SourceDomain extends Model
{
    protected $fillable = [
        'domain',
        'first_seen_at',
        'last_synced_at',
        'notes',
    ];

    protected $casts = [
        'first_seen_at'  => 'datetime',
        'last_synced_at' => 'datetime',
    ];

    /**
     * Métriques SEO du domaine (jointure via colonne `domain`, pas `id`).
     */
    public function domainMetric(): HasOne
    {
        return $this->hasOne(DomainMetric::class, 'domain', 'domain');
    }

    /**
     * Extrait le domaine depuis une URL et retourne (ou crée) le SourceDomain correspondant.
     */
    public static function fromUrl(string $url): self
    {
        $domain = DomainMetric::extractDomain($url);

        return static::firstOrCreate(
            ['domain' => $domain],
            ['first_seen_at' => now()]
        );
    }
}
