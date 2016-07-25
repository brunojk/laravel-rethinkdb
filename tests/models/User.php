<?php

use \brunojk\LaravelRethinkdb\Eloquent\Model;

class User extends Model
{
    protected $dates = ['birthday', 'entry.date'];
    protected static $unguarded = true;

    public function books()
    {
        return $this->hasMany('Book', 'author_id');
    }

    public function items()
    {
        return $this->hasMany('Item');
    }

    public function role()
    {
        return $this->hasOne('Role');
    }

    public function clients()
    {
        return $this->belongsToMany('Client');
    }

    public function groups()
    {
        return $this->belongsToMany('Group', null, 'users', 'groups');
    }

    public function photos()
    {
        return $this->morphMany('Photo', 'imageable');
    }

    public function addresses()
    {
        return $this->embedsMany('Address');
    }

    public function father()
    {
        return $this->embedsOne('User');
    }

    public function roles() {
        return $this->belongsToMany('Role', null, 'roles_ids');
    }
}
