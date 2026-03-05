<?php

namespace App\Policies;

use App\Models\IndexationCampaign;
use App\Models\User;

class IndexationCampaignPolicy
{
    public function view(User $user, IndexationCampaign $campaign): bool
    {
        return $user->id === $campaign->user_id;
    }
}
