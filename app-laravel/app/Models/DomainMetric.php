<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class DomainMetric extends Model
{
    use HasFactory;

    protected $fillable = [
        'domain',
        'da',
        'dr',
        'tf',
        'cf',
        'spam_score',
        'backlinks_count',
        'referring_domains_count',
        'organic_keywords_count',
        'provider',
        'last_updated_at',
    ];

    protected $casts = [
        'da'                      => 'integer',
        'dr'                      => 'integer',
        'tf'                      => 'integer',
        'cf'                      => 'integer',
        'spam_score'              => 'integer',
        'backlinks_count'         => 'integer',
        'referring_domains_count' => 'integer',
        'organic_keywords_count'  => 'integer',
        'last_updated_at'         => 'datetime',
    ];

    /**
     * Récupère ou crée un enregistrement pour un domaine.
     */
    public static function forDomain(string $domain): self
    {
        return static::firstOrCreate(
            ['domain' => $domain],
            ['provider' => 'custom']
        );
    }

    /**
     * Extrait le domaine (host) depuis une URL complète.
     */
    public static function extractDomain(string $url): string
    {
        $host = parse_url($url, PHP_URL_HOST) ?? $url;
        // Supprime le "www." pour normaliser
        return preg_replace('/^www\./', '', strtolower($host));
    }

    /**
     * Scope : métriques périmées (> 24h) ou jamais récupérées.
     */
    public function scopeStale(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->whereNull('last_updated_at')
              ->orWhere('last_updated_at', '<', now()->subDay());
        });
    }

    /**
     * Scope : métriques récentes (mises à jour dans les 24h).
     */
    public function scopeFresh(Builder $query): Builder
    {
        return $query->whereNotNull('last_updated_at')
                     ->where('last_updated_at', '>=', now()->subDay());
    }

    /**
     * Vérifie si les métriques sont périmées.
     */
    public function isStale(): bool
    {
        return is_null($this->last_updated_at)
            || $this->last_updated_at->lt(now()->subDay());
    }

    /**
     * Vérifie si les métriques ont été chargées (au moins une valeur non nulle).
     */
    public function hasData(): bool
    {
        return ! is_null($this->da)
            || ! is_null($this->dr)
            || ! is_null($this->tf);
    }

    /**
     * Couleur CSS pour le DA/DR selon les seuils SEO.
     */
    public function getAuthorityColorAttribute(): string
    {
        $score = $this->da ?? $this->dr ?? null;

        if (is_null($score)) {
            return 'gray';
        }

        return match(true) {
            $score >= 40 => 'green',
            $score >= 20 => 'orange',
            default      => 'red',
        };
    }

    /**
     * Couleur CSS pour le Spam Score (inversé : haut = mauvais).
     */
    public function getSpamColorAttribute(): string
    {
        if (is_null($this->spam_score)) {
            return 'gray';
        }

        return match(true) {
            $this->spam_score < 5  => 'green',
            $this->spam_score < 15 => 'orange',
            default                => 'red',
        };
    }

    /**
     * Relation inverse : backlinks dont le domaine source correspond.
     * Usage : $domainMetric->backlinks
     */
    public function backlinks()
    {
        return Backlink::whereRaw("LOWER(REPLACE(REPLACE(source_url, 'https://www.', ''), 'http://www.', '')) LIKE ?", [$this->domain . '%']);
    }
}
