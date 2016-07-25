<?php

use \brunojk\LaravelRethinkdb\Eloquent\Model;

class Roler extends Model
{
    protected $table = 'rolers';
    protected static $unguarded = true;

    public function permissions() {
        return $this->belongsToMany('Permission');
    }

    public static function create(array $attributes) {
        $attributes['permission_ids'] = [];
        return parent::create($attributes);
    }
}
