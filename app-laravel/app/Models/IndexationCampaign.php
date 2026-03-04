<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IndexationCampaign extends Model
{
    protected $fillable = [
        'user_id',
        'provider',
        'status',
        'name',
        'total_urls',
        'submitted_count',
        'indexed_count',
        'failed_count',
        'notes',
        'submitted_at',
        'completed_at',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(IndexationSubmission::class, 'campaign_id');
    }

    public function getSuccessRateAttribute(): ?float
    {
        if ($this->submitted_count === 0) {
            return null;
        }

        return round($this->indexed_count / $this->submitted_count * 100, 1);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending'   => 'En attente',
            'running'   => 'En cours',
            'completed' => 'Terminé',
            'failed'    => 'Échoué',
            'partial'   => 'Partiel',
            default     => ucfirst($this->status),
        };
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'pending'   => 'neutral',
            'running'   => 'brand',
            'completed' => 'success',
            'failed'    => 'danger',
            'partial'   => 'warning',
            default     => 'neutral',
        };
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}
