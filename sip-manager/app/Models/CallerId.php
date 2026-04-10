<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CallerId extends Model
{
    protected $fillable = ['number', 'label', 'trunk_id', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function trunk(): BelongsTo
    {
        return $this->belongsTo(Trunk::class);
    }

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(CallerIdGroup::class, 'caller_id_group_items');
    }
}
