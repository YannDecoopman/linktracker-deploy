<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Backlink extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'source_url',
        'target_url',
        'anchor_text',
        'status',
        'http_status',
        'rel_attributes',
        'is_dofollow',
        'is_indexed',
        'first_seen_at',
        'last_checked_at',
        // Extended fields
        'tier_level',
        'parent_backlink_id',
        'spot_type',
        'published_at',
        'expires_at',
        'price',
        'currency',
        'invoice_paid',
        'platform_id',
        'contact_info',
        'contact_name',
        'contact_email',
        'created_by_user_id',
    ];

    protected $attributes = [
        'tier_level'   => 'tier1',
        'spot_type'    => 'external',
        'invoice_paid' => false,
    ];

    protected $casts = [
        'is_indexed' => 'boolean',
        'invoice_paid' => 'boolean',
        'first_seen_at' => 'datetime',
        'last_checked_at' => 'datetime',
        'published_at' => 'date',
        'expires_at' => 'date',
        'price' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Accessor is_dofollow : retourne null si le backlink n'a jamais été vérifié
     * (last_checked_at === null), sinon la valeur booléenne réelle.
     * Cela évite d'afficher "DF" ou "NF" pour un lien jamais contrôlé.
     */
    public function getIsDofollowAttribute(?int $value): ?bool
    {
        if ($this->attributes['last_checked_at'] === null) {
            return null;
        }
        return (bool) $value;
    }

    /**
     * Get the project that owns the backlink.
     */
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the platform associated with this backlink.
     */
    public function platform()
    {
        return $this->belongsTo(Platform::class);
    }

    /**
     * Get the user who created this backlink.
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * Get the parent backlink (for Tier 2 links).
     */
    public function parentBacklink()
    {
        return $this->belongsTo(Backlink::class, 'parent_backlink_id');
    }

    /**
     * Get child backlinks (Tier 2 links pointing to this backlink).
     */
    public function childBacklinks()
    {
        return $this->hasMany(Backlink::class, 'parent_backlink_id');
    }

    /**
     * Get all checks for this backlink.
     */
    public function checks()
    {
        return $this->hasMany(BacklinkCheck::class)->orderBy('checked_at', 'desc');
    }

    /**
     * Get the latest check for this backlink.
     */
    public function latestCheck()
    {
        return $this->hasOne(BacklinkCheck::class)->latest('checked_at');
    }

    /**
     * Get the SEO metrics for the source domain of this backlink.
     */
    public function domainMetric()
    {
        return $this->hasOne(DomainMetric::class, 'domain', 'source_domain');
    }

    /**
     * Get the source domain (host) extracted from source_url.
     */
    public function getSourceDomainAttribute(): string
    {
        return DomainMetric::extractDomain($this->source_url);
    }

    /**
     * All alerts for this backlink.
     */
    public function alerts()
    {
        return $this->hasMany(Alert::class)->orderBy('created_at', 'desc');
    }

    /**
     * Get unread alerts for this backlink.
     */
    public function unreadAlerts()
    {
        return $this->hasMany(Alert::class)->where('is_read', false)->orderBy('created_at', 'desc');
    }

    /**
     * Scope a query to only include active backlinks.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope a query to only include lost backlinks.
     */
    public function scopeLost($query)
    {
        return $query->where('status', 'lost');
    }

    /**
     * Scope a query to only include changed backlinks.
     */
    public function scopeChanged($query)
    {
        return $query->where('status', 'changed');
    }

    /**
     * Get the badge color for the backlink status (for UI).
     */
    public function getStatusBadgeColorAttribute(): string
    {
        return match($this->status) {
            'active' => 'bg-green-100 text-green-800 border-green-200',
            'lost' => 'bg-red-100 text-red-800 border-red-200',
            'changed' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
            default => 'bg-gray-100 text-gray-800 border-gray-200',
        };
    }

    /**
     * Get the status label (for UI).
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'active' => 'Actif',
            'lost' => 'Perdu',
            'changed' => 'Modifié',
            default => ucfirst($this->status),
        };
    }

    /**
     * Determine if the backlink is currently active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Determine if the backlink is lost.
     */
    public function isLost(): bool
    {
        return $this->status === 'lost';
    }

    /**
     * Determine if the backlink has changed.
     */
    public function hasChanged(): bool
    {
        return $this->status === 'changed';
    }
}
