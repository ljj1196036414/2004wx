<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\UserModel;
class UserController extends Controller
{
    public function  index(){
        $res=UserModel::get();
        dd($res);
    }
}
