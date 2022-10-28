<?php

namespace Andruby\Pay\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CashOutOrder extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $hidden = ['updated_at'];

    const TYPE_ALI = 1;
    const TYPE_WX = 2;
}
