<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PostStat extends Model
{
    protected $primaryKey = 'post_id';
    public $incrementing = false;
    public $timestamps = false;
    protected $keyType = 'string';
    protected $guarded = [];
}
