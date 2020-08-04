<?php 

namespace App\Helpers;
use App\Models\MultiSale;
use PHPtricks\Orm\Database;
use Illuminate\Database\Capsule\Manager as Capsule;
use \App\Models\{ HistoryEntree , StockSortieList , Lists };

defined('BASEPATH') OR exit('No direct script access allowed');



        // get all the products
        
            // foreach product ad product 
                
                
                // recue 
                    // get the total ot stock sortie for that product in that city
                    
                // livre
                    // get all the lists where this product exist and list is delivred in that city
                
                // stock en cours
                
                    // get all stock where  this product exist and list is waiting in that city 
                
                
                // stock physique
                
                    // recue - livre
                    
                // Stock theorique	
                
                    // (stock physique) - ( stock en cours)
                
  
class Reception { 
        
        
        
      protected $query;
      protected $products;
      protected $product;
      
      protected $livre;
      protected $recue;
      protected $physique;
      protected $encours;

    
    
    
    
      public function __construct(){
          $this->products = \App\Models\Product::all()->toArray();
      }
      
      public function load(){
          return $this->get();         
      }
      
      
      public function get(){
            
            $data = [];
              
              foreach( $this->products as $product ){
                  
                  $this->product = $product['id'];
                  
                  $item = [
                        'product_id'  =>  $product['id'],
                        'product_name'=>  $product['name'],
                        'recue'       =>  $this->getStockRecue() ,
                        'livre'       =>  $this->getStockDelivred() ,
                        'physique'    =>  $this->getStockPhysique() ,
                        'theorique'   =>  $this->getStockTheorique(),
                        'encours'     =>  $this->getStockEncours() ,
                        
                  ];
                  
                  array_push($data,$item);
              }
        
              return $data;
      }
    

      public function getStockRecue(){
          
            $validSortie =  StockSortieList::where('productID',$this->product)
                ->selectRaw('*, sum(quantity) as sum_quantity ,  sum(valid) as sum_valid ')
                ->get()->toArray();
            $this->recue = $validSortie[0]['sum_valid'] ?? 0;
            
            return  $this->recue;
     
      }
    
    
      public function getStockDelivred() {
            $list = \App\Models\Lists::with('deliver','employee','products','products.product','realcity')->whereNotNull('delivred_at')->whereHas('products.product', function ($query) {
                    return $query->where('id', '=', $this->product);
                })->get()->toArray();
                
            $this->livre =  $this->getQuantityFromList($list);
    
            return $this->livre;
            
                
      }
    
    
      public function getStockPhysique(){
            $this->physique =   $this->recue - $this->recue ;
            return $this->physique ;
      }
      
      
      public function getStockTheorique() {
           
      }
    
    
      public function getStockEncours(){

            $list = \App\Models\Lists::with('deliver','employee','products','products.product','realcity')
            ->whereNull('deleted_at')->whereNotNull('accepted_at')->whereNotNull('verified_at')->whereNull('duplicated_at')
            ->whereNull('canceled_at')->whereNull('delivred_at')->whereNull('recall_at')->whereNull('delivred_at')->where('statue','!=','NoAnswer')
            ->get()->toArray();

            $this->encours =  $this->getQuantityFromList($list) ?? 0 ;
            return $this->encours;

      }
  
      public function getQuantityFromList($list){
          $ok = [];
          foreach($list as $item){
              
                foreach($item['products'] as $product){    
                    $ok[] = $product['quanity'];
                }
          }
          
          return array_sum($ok);
      }
  

  
}













