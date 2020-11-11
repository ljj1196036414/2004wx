<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class InformationModel extends Model
{
    protected $table='information';
    protected $primaryKey='in_id';
    public $timestamps=false;
}

