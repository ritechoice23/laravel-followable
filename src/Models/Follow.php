<?php

namespace Ritechoice23\Followable\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Follow extends Model
{
    protected $fillable = [
        'follower_id',
        'follower_type',
        'followable_id',
        'followable_type',
        'metadata',
    ];

    protected $casts = [
        'follower_id' => 'integer',
        'followable_id' => 'integer',
        'metadata' => 'array',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->setTable(config('follow.table_name', 'follows'));
    }

    public function follower(): MorphTo
    {
        return $this->morphTo('follower');
    }

    public function followable(): MorphTo
    {
        return $this->morphTo('followable');
    }
}
