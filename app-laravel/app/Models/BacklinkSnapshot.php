<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BacklinkSnapshot extends Model
{
    protected $fillable = [
        'snapshot_date',
        'project_id',
        'count_active',
        'count_lost',
        'count_changed',
        'count_total',
        'count_perfect',
        'count_not_indexed',
        'count_nofollow',
    ];

    protected $casts = [
        'snapshot_date' => 'date:Y-m-d',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
