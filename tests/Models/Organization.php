<?php

namespace Ritechoice23\Followable\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Ritechoice23\Followable\Traits\CanFollow;
use Ritechoice23\Followable\Traits\HasFollowers;

class Organization extends Model
{
    use CanFollow;
    use HasFollowers;

    protected $guarded = [];
}
