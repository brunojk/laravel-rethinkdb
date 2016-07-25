<?php

use \brunojk\LaravelRethinkdb\Eloquent\Model;

class Permission extends Model
{
    protected $table = 'permissions';
    protected static $unguarded = true;

//    protected $attributes = array('roler_ids' => []);

    public function rolers() {
        return $this->belongsToMany('Roler');
    }
}
