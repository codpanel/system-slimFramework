<?php 

namespace App\Helpers\Stats;
use \Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\MultiSale;

defined('BASEPATH') OR exit('No direct script access allowed');

class DailyDelivers { 
    

 
    public $params;
    public $listing;
    public $deliver;
    public $query;
    public $canceled;
    public $noanswer;
    public $recall;
    public $employee;
    public $waiting;
    public $all;
    public $days;
    public $delivers;
    public $day;
    
    
    
    
    public function __construct() {
        
        $this->days        = getDays();
        
        $this->delivers   =  GetDelivers();

        return $this->load();
    }
    
    

    public function load() {
        
        $data = [];
        
        foreach( $this->days as $day ){
            
            $this->day = $day;

            foreach( $this->delivers as $employee ){
                
               $this->deliver = $employee->id;
              
               $data[$day][$employee->username] = $this->list();
               
            }
                
        }
        
        return $data;
    }
    
    
        
    public function list(){
        $this->manipulate();
        $get = $this->get();
        return array_merge($get,$this->percent($get));
    }
    
    
   
   
   
   
   
    public function init(){
        $this->listing = \App\Models\Lists::where('DeliverID',$this->deliver)->whereNull('deleted_at')->whereNotNull('accepted_at')->whereNotNull('verified_at')->whereNull('duplicated_at');
        return $this;
    }
    
    
    public function all() {
       $this->init();
       $this->all = $this->listing->count();
    }
    
    
    public function waiting() {
       $this->init();
       $this->waiting = $this->listing->whereNull('canceled_at')->whereNull('delivred_at')->whereNull('recall_at')->whereNull('delivred_at')->where('statue','!=','NoAnswer')->count();
    }
    
    
    public function canceled() {
       $this->init();
       $this->canceled = $this->listing->whereNotNull('canceled_at')->where('statue','!=','NoAnswer')->whereNull('delivred_at')->whereNull('recall_at')->whereDate('canceled_at', '=', $this->day )->count();
    }


    public function noanswer() {
       $this->init();
       $this->noanswer = $this->listing->whereNull('canceled_at')->where('statue','NoAnswer')->whereNull('delivred_at')->whereDate('no_answer_time', '=', $this->day )->count();
    }


    public function recall() {
        $this->init();
        $this->recall =  $this->listing->whereNull('canceled_at')->whereNull('delivred_at')->where('statue','!=','NoAnswer')->whereNotNull('recall_at')->whereDate('recall_at', '=', $this->day )->count();
    }
 
 
    public function delivred() {
       $this->delivred = \App\Models\Lists::where('DeliverID',$this->deliver)->whereNotNull('delivred_at')->whereDate('delivred_at', '=', $this->day )->count();
    }
    
   
   
   
   
   
   
    

    public function get(){
        return [
            'all'        =>  $this->all,  
            'waiting'    =>  $this->waiting,
            'canceled'   =>  $this->canceled,
            'noanswer'   =>  $this->noanswer,
            'recall'     =>  $this->recall,
            'delivred'   =>  $this->delivred,
        ];
    }

    public function manipulate(){
        $this->waiting();
        $this->canceled();
        $this->noanswer();
        $this->recall();
        $this->all();
        $this->delivred();
    }
    
    
    public function percent($employeeData){
        $y = $employeeData['all'];
        $data = [
            'waitingPercent'   =>  $employeeData['waiting'] == 0 ? '0%' : number_format( ($employeeData['waiting'] / $y ) * 100, 2 ) . '%',
            'canceledPercent'  =>  $employeeData['canceled'] == 0 ? '0%' : number_format( ($employeeData['canceled'] / $y ) * 100, 2 ) . '%',
            'noanswerPercent'  =>  $employeeData['noanswer'] == 0 ? '0%' : number_format( ($employeeData['noanswer'] / $y ) * 100, 2 ) . '%',
            'recallPercent'    =>  $employeeData['recall'] == 0 ? '0%' : number_format( ($employeeData['recall'] / $y ) * 100, 2 ) . '%',
            'delivredPercent'  =>  $employeeData['delivred'] == 0 ? '0%' : number_format( ($employeeData['delivred'] / $y ) * 100, 2 ) . '%',
        ];
        return $data;
    }
    

}
