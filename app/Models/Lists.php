<?php

namespace App\Models;
use illuminate\database\eloquent\model;




class Lists extends model{

    protected $table = 'lists';
    
    protected $guarded = ['id', 'created_at', 'updated_at'];
    
    protected $dates = ['canceled_at', 'created_at', 'updated_at', 'recall_at','to_deliver_at','delivred_at','no_answer_time'];

    protected $appends = ['dayDelivred','cityName','type','handler','tentative','total','lastNoResponse','total','delivred','toDeliver'];

    protected $total;


    public function getDayDelivredAttribute()
    {
        return !empty($this->delivred_at) ? $this->delivred_at->format('Y-m-d') : '';
    }

    public function getToDeliverAttribute()
    {
        return !empty($this->to_deliver_at) ? $this->to_deliver_at->diffForHumans() : '' ;
    }



    public function getCityNameAttribute()
    {

        return isset($this->realcity['city_name']) ? $this->realcity['city_name'] : '';
    }

    public function getLastNoResponseAttribute()
    {

        return !empty($this->no_answer_time) ? $this->no_answer_time->diffForHumans() : '';
    }

    public function getDelivredAttribute()
    {
        return !empty($this->delivred_at) ? $this->delivred_at->diffForHumans() : '';
    }

    public function getTotalAttribute()
    {
        return $this->products->sum('price');
    }

    public function getHandlerAttribute()
    {
        if($this->accepted_at){
            return 'deliver';
        }else {
            return 'employee';
        }
    }

    public function getTentativeAttribute()
    {
        if(!empty($this->no_answer)){


                $type = $this->no_answer;
                if($type == 'no_answer_1'){
                    return 'المرحلة الأولى' ;
                }
                // No answer 2
                if($type == 'no_answer_2'){
                    return 'المرحلة الثانية' ;
                }
                // No answer 3
                if($type == 'no_answer_3'){
                    return 'المرحلة الثالثة' ;
                }
                // No answer 4
                if($type == 'no_answer_4'){
                    return 'المرحلة الرابعة' ;
                }
                
                // No answer 5
                if($type == 'no_answer_5'){
                    return 'المرحلة الخامسة';
                }

                // No answer 6
                if($type == 'no_answer_6'){
                    return 'المرحلة السادسة';
                }       

                // No answer 7
                if($type == 'no_answer_7'){
                    return 'المرحلة السابعة';
                }

                // No answer 8
                if($type == 'no_answer_8'){
                    return 'المرحلة الثامنة';
                }

        }
    }

    public function getTypeAttribute()
    {

        $by = '';
        if(isset($_SESSION['auth-admin']) or isset($_SESSION['auth-data'])){
            if($this->accepted_at){
            $by = '- عند الموزع    ' ;
            }else {
                 $by = '- عند الموظفة  ';
            }
        }
       
        
        if(!empty($this->delivred_at)){
            return 'تم توزيعها'  . $by ; 
        }
        if(!empty($this->canceled_at)){
            return 'ملغية'  . $by ;
        }
        if(!empty($this->recall_at)){
            return 'اعادة الإتصال'  . $by ;
        }
        if(!empty($this->no_answer)){
            return 'لا يجيب '    . ' - ' .  $this->tentative . $by ;
        } 
        
        return 'قيد المعالجة' . $by;
    }



    public function products(){
        return $this->hasMany('\App\Models\MultiSale','listID');
    }
    

    public function items(){
        $items = [];
        $total = 0;
        foreach ($this->products as $product) {
                $item = [
                    'name' =>   $product->product->name ?? '',
                    'price' =>   $product->price ?? 0,
                    'quantity' =>  $product->quanity ?? 0,
                ];
                $total = $total +  $product->price; 
                array_push($items, $item);
        }
        $this->items = $items;
        return $items;
    }


    public function deliver(){
       return $this->belongsTo('\App\Models\User','DeliverID'); 
    }

    public function employee(){
       return $this->belongsTo('\App\Models\User','mowadafaID');
    }
  
    public function city(){
        return $this->belongsTo('\App\Models\Cities','cityID');
    }
    
    
    public function realcity(){
        return $this->belongsTo('\App\Models\Cities','cityID');
    }
    
}