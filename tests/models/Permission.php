<?php

use \brunojk\LaravelRethinkdb\Eloquent\Model;

class Permission extends Model
{
    protected $table = 'permissions';
    protected static $unguarded = true;

    public function rolers() {
        return $this->belongsToMany('Roler');
    }
}