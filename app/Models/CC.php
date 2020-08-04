<?php

namespace App\Models;
use illuminate\database\eloquent\model;



class CC extends model{

    protected $table = 'cc';
    
    protected $guarded = ['id', 'created_at', 'updated_at'];
    protected $dates = ['paied_at', 'created_at', 'updated_at'];
    
 
    
}