<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use MongoDB\Laravel\Eloquent\Model;

abstract class BaseUuidModel extends Model
{
    use HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];
    protected $connection = 'mongodb';
    protected $primaryKey = '_id';

    public function isFillable($key)
    {
        // Laravel's default mass-assignment rule excludes leading underscores, including MongoDB's primary key.
        return $key === '_id' || parent::isFillable($key);
    }

    public function toArray(): array
    {
        $data = parent::toArray();
        $data['id'] = (string) $this->getKey();
        unset($data['_id']);
        return $data;
    }
}
