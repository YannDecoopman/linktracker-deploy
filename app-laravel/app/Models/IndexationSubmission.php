<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IndexationSubmission extends Model
{
    protected $fillable = [
        'campaign_id',
        'backlink_id',
        'source_url',
        'submission_status',
        'provider_response',
        'provider_task_id',
        'submitted_at',
        'check_7d_at',
        'indexed_at',
        'last_check_result',
        'last_checked_at',
        'error_message',
    ];

    protected $casts = [
        'provider_response' => 'array',
        'last_check_result' => 'boolean',
        'submitted_at'      => 'datetime',
        'check_7d_at'       => 'datetime',
        'indexed_at'        => 'datetime',
        'last_checked_at'   => 'datetime',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(IndexationCampaign::class);
    }

    public function backlink(): BelongsTo
    {
        return $this->belongsTo(Backlink::class);
    }

public function getStatusLabelAttribute(): string
    {
        return match ($this->submission_status) {
            'pending'      => 'En attente',
            'submitted'    => 'Soumis',
            'submit_error' => 'Erreur soumission',
            'indexed'      => 'Indexé',
            'not_indexed'  => 'Non indexé',
            'check_error'  => 'Erreur vérification',
            default        => ucfirst($this->submission_status),
        };
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->submission_status) {
            'pending'      => 'neutral',
            'submitted'    => 'brand',
            'submit_error' => 'danger',
            'indexed'      => 'success',
            'not_indexed'  => 'warning',
            'check_error'  => 'danger',
            default        => 'neutral',
        };
    }
}
