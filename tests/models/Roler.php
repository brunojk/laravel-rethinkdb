<?php

use \brunojk\LaravelRethinkdb\Eloquent\Model;

class Roler extends Model
{
    protected $table = 'rolers';
    protected static $unguarded = true;

    public function permissions() {
        return $this->belongsToMany('Permission');
    }
}
