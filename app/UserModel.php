<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserModel extends Model
{
    protected $table='p_users';
    protected $primaryKey='openid';
    public $timestamps=false;
}

