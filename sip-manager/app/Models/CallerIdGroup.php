<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CallerIdGroup extends Model
{
    protected $fillable = ['name', 'description'];

    public function callerIds(): BelongsToMany
    {
        return $this->belongsToMany(CallerId::class, 'caller_id_group_items');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'caller_id_group_users');
    }
}
