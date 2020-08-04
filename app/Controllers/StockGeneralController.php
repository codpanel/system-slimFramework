<?php

namespace App\Controllers;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

use \App\Models\Options;
use \App\Models\Lists;
use \App\Models\Product;
use \App\Models\DailyStock;
use \App\Models\SentLists;
use \App\Models\Cities;
use \App\Models\User;
use \App\Models\Stock;
use \App\Models\StockEntree;
use \App\Models\StockSortie;
use \App\Models\StockSortieList;
use \App\Models\StockGeneral;
use \App\Models\HistoryEntree;
use \App\Classes\files;
use \App\Classes\Noanswser as ff;
use \App\Classes\SystemLog;
use Carbon\Carbon;
use Dompdf\Dompdf;


use \App\Controllers\MyController;


defined('BASEPATH') OR exit('No direct script access allowed');

class StockGeneralController extends Controller {
     
    public $folder = 'stockGeneral/';
    
    

        public function waitingAction($request, $response){
            $file = $this->folder.'stock.twig';
            return $this->view->render($response, 'admin/'.$file);    
        }
          public function waitingGet($request, $response){
            $file = $this->folder.'acceptSortie.twig';
            return $this->view->render($response, 'admin/'.$file);    
        }
        
    
    
        public function index($request, $response){
            $nots = $this->getStockGeneralNotification();
            
            $table = [];

            $HistoryEntree = HistoryEntree::groupBy('productID')      
            ->selectRaw('*, sum(quantity) as sum_quantity ,  sum(valid) as sum_valid ')
            ->get();
            

            foreach($HistoryEntree as $item ){

                $validSortie = StockSortieList::where('productID',$item->product->id)
                ->selectRaw('*, sum(quantity) as sum_quantity ,  sum(valid) as sum_valid ')
                ->get()->toArray();

                $table[] = [
                    'product_id' => $item->product->id,
                    'product_name' => $item->product->name,
                    'total_sortie' => $validSortie[0]['sum_valid'],
                    'total_entree' =>  $item->sum_valid ,
                ];
            }

            $file = $this->folder.'stock.twig';
            return $this->view->render($response, 'admin/'.$file, [ 'nots'  => $nots , 'stockList'=> $table ] );    
        }
    
    
        public function create_entree($request,$response){
           
            $products = Product::all();
            $stocks = StockEntree::all();

            $HistoryEntree = HistoryEntree::groupBy('productID')
            
                        ->selectRaw('*, 
            sum(quantity) as sum_quantity , 
            sum(valid) as sum_valid
                       ')->get();
            
            $nots = $this->getStockGeneralNotification();
            
            $file = $this->folder.'entree.twig';
            return $this->view->render($response, 'admin/'.$file,compact('products','stocks','HistoryEntree','nots'));    
        }


        public function getStockGeneralNotification(){
            return [
               'entree'  => StockEntree::count(),
              'sortie'  => StockSortie::where('statue','=','')->count() 
            ];
        }
    
    
    
        public function store_entree($request,$response){
            $post = $request->getParams();
            sv($post);
            StockEntree::create($post);
            $this->flashsuccess('تم الإضافة بنجاح');
            return $response->withRedirect($this->router->pathFor('stockGeneral.create.entree'));
        }
    
    
        public function activateStock($request,$response){
          $list = StockSortieList::where('sortie_list_id',$_POST['id']);
        }


        
       

    
  

        public function loadSortieList($request,$response){
           
          $sortie_list = StockSortieList::where('sortie_list_id',$_POST['id'])->get();
          $cities = Cities::all();    

          $html = '';
          foreach($sortie_list as $sortie):
            
          $html .= '<div class="row city_quantity  firstRow">
                    <div class="col-lg-11">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <div class="col-lg-10">
                                <input type="text" value="'.$sortie->quantity.'" class="form-control" name="quantities[]" placeholder="quantity">
                              </div>
                                </div>
                            </div>
                            
                            
                            <div class="col-md-4">
                                <div class="form-group">
                                    <div class="col-lg-10">
                                <input type="text" class="form-control" name="valid[]" placeholder="valid">
                              </div>
                                </div>
                            </div>
                            
                           
                            
                            
                            <div class="col-md-4">
                            <select id="citiesList" data-val="'.$sortie->cityID.'" class="form-control" name="cities[]" placeholder="produit">';
                                foreach($cities as $citie):
                                $html .= '<option value="'.$citie->id.'">'.$citie->city_name.'</option>';
                                endforeach;
              
            $html .= '</select>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-1"><a id="addRowSortie" href="javascript:;" class="btn btn-primary">+</a></div>
                </div>';

          endforeach;


            
        echo $html;
        
        }
    
    
     public function validateSortieList ($request,$response){

         $post = $request->getParams();
          
        $sortieRow = StockSortie::find($post['SortieListID']);
        // delete Old Sortie Stock list
        StockSortieList::where('sortie_list_id',$post['SortieListID'])->delete();
        
        // Create new Sortie List 
        for($x=0; $x < count($post['cities']);$x++){
                        
            $data  = [
            
                'productID' => $sortieRow->ProductID ,
                'sortie_list_id' => $post['SortieListID'],
                'quantity'  => $post['quantities'][$x],
                'valid'  => $post['valid'][$x],
                'cityID' => $post['cities'][$x],
                'statue' => 1,
                     ];
            StockSortieList::create($data);
         
        }
         
         
       // set the sortie list to valid
       $edit = StockSortie::find($post['SortieListID']);
       $edit->statue = 1;
       $edit->save();
             
         
        // add the stock sortie to stockgeneral
         
         
         
        // add the stock to delivers stock
        for($x=0;$x< count($post['cities']);$x++){
            validateSortieItem($post['ProductID'],$post['cities'][$x],$post['valid'][$x]);
        } 
         
         $this->flashsuccess('تم التعديل بنجاح');
            return $response->withRedirect($this->router->pathFor('stockGeneral.create.sortie'));  
    
     }
    
    
    
    public function addStockToStockPrincipale($CityID,$productID,$quantity){
             $Stock = Stock::where('CityID',$CityID)->where('ProduitID',$productID)->first();
             $Stock->Recue =  $Stock->Recue + $quantity;
             $Stock->StockPhisique =  $Stock->StockPhisique + $quantity;
             $Stock->save();
    }
    
     // change the Livred Products Stock
    public function addLivredStock($cityID,$productID,$quantity){
        $stockFound = Stock::where('CityID',$cityID)->where('ProduitID',$productID)->first();
        if($stockFound){
            $stockFound->Livre  =  is_numeric($stockFound->Livre) ? $stockFound->Livre + $quantity : $quantity ; 
            $stockFound->StockPhisique =  is_numeric($stockFound->StockPhisique) ? $stockFound->StockPhisique - $quantity : $quantity ;
            $stockFound->stockEnCours  =  is_numeric($stockFound->stockEnCours) ? $stockFound->stockEnCours - $quantity  : $quantity ;
            $stockFound->save();
        }
    }
    
    
    public function loadEntreeHistory($request,$response){
         
         $data = HistoryEntree::where('productID',$_POST['productID'])->get();
         
         $html =  '<table class="table table-striped datatable entreetable" >
                        <thead>
                            <tr>
                                <th><b> التاريخ </b></th>
                                <th><b> المنتوج </b></th>
                                <th><b> ملاحظة </b></th>
                                <th><b> الكمية </b></th>
                                <th><b> تأكيد  </b></th>
                            </tr>
                        </thead>
                        <tbody> ';
        
         foreach($data as $item):
         
             $html .= "<tr>
                            <td >{$item->created_at}</td>
                             <td>".$item->product->name."</td>
                             <td>$item->note</td>
                             <td> $item->quantity </td>
                             <td>$item->valid </td>
                         </tr>";
              
              
         endforeach;
                 
                             
            $html .= '</tbody></table>';
              
         echo $html;
         
         
     }
    
    
    
    
    
    
        public function validateEntree($request,$response){
            
            // get the stock entree
            $entree = StockEntree::find($_POST['id']);
            
            // add the stock entree to history
            $new = new HistoryEntree();
            $new->productID = $entree->productID;
            $new->quantity = $entree->quantity;
            $new->note = $entree->note;
            $new->valid = $_POST['valid'];
            $new->save();
         
           
            // search and check if the stock general has already this product
            $stock = StockGeneral::where('ProductID',$entree->productID)->first();
            
            
            if($stock) {
                
             // if the stock general has already this product this add the new quantity to the existing quantity
             if(!empty($stock->Entree) and is_numeric($stock->Entree)){
                        $stock->Entree = $stock->Entree + $_POST['valid'];
              }else {
                        $stock->Entree = $_POST['valid'];
              }
              
                $stock->save();
                
             }else {
                    // if it dosnt exist add it
                   StockGeneral::create(['Entree'=>$_POST['valid'] ,'ProductID'=>$entree->productID]); 
              }
             
           $entree->delete();
        }

    
       
        
        // Ajout de Stock Sortie
        public function store_sortie($request,$response){
           
           $post = $request->getParams();
         
           $sortie = StockSortie::create(['ProductID'=>$post['productID']]);

            for($x=0; $x < count($post['quantities']);$x++){
                $data  = [
                    'sortie_list_id' => $sortie->id,
                    'quantity'  => $post['quantities'][$x],
                    'cityID' => $post['cities'][$x],
                         ];
                st($post['quantities'][$x]);
                StockSortieList::create($data);
                $Stock = Stock::where('CityID',$post['cities'][$x])->where('ProduitID',$post['productID'])->first();
                if($Stock){
                   $Stock->stockVirtuel = $post['quantities'][$x];
                   $Stock->save(); 
                }else {
                    Stock::create(['CityID' => $post['cities'][$x] , 
                                  'ProduitID' => $post['productID'] ,
                                  'stockVirtuel' => $post['quantities'][$x] ,
                                  ]);
                }
                
                
            }
            
            
            $this->flashsuccess('تم الإضافة بنجاح');
            return $response->withRedirect($this->router->pathFor('stockGeneral.create.sortie'));
           
        }

        public function create_sortie($request,$response){
            $sortie = StockSortie::where('statue','=','')->get();
           
            
              $HistorySortie = StockSortieList::groupBy('productID')
            
                        ->selectRaw('*, 
            sum(quantity) as sum_quantity , 
            sum(valid) as sum_valid
                       ')->get();
            
            $nots = $this->getStockGeneralNotification();

            $file = $this->folder.'sortie.twig';
            return $this->view->render($response, 'admin/'.$file,compact('sortie','products','HistorySortie','nots'));    
        
        }    

        
    
    
    
}

