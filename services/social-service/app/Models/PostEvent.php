<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PostEvent extends Model
{
    protected $primaryKey = 'post_id';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];
}
