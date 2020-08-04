<?php

namespace App\Controllers;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use \App\Models\{Lists , Product , Cities , User , MultiSale } ;
use Carbon\Carbon;
use \App\Helpers\{Noanswer , Listing,Revenue};



defined('BASEPATH') OR exit('No direct script access allowed');

class ListsController extends Controller {




   
    public function reset ($request,$response) {
        $id = $_POST['list'];
        $list = Lists::find($id);
        $list->canceled_at = NULL;
        $list->recall_at = NULL;
        $list->cancel_reason = NULL;
        $list->no_answer = NULL;
        $list->statue = NULL;
        $list->recall_at = NULL;
        $list->delivred_at = NULL;
        $list->statue = 'sent';
        $message = 'تم اعادة تعيين الطلب الى الحالة الإفتراضية';
        $message .= '  من طرف ';
        $message .= $this->user->username;
        $this->saveHistory($list,$message);
    }        
 


    // SET LIST STATUE TO DELIVRED
    public function setDelivred ($request,$response) {
        $id = $_POST['list'];
        $list = Lists::find($id);
        $list->canceled_at = NULL;
        $list->recall_at = NULL;
        $list->cancel_reason = NULL;
        $list->no_answer = NULL;
        $list->statue = NULL;
        $list->recall_at = NULL;
        date_default_timezone_set('Africa/Casablanca');

        $list->delivred_at = \Carbon\Carbon::Now();
        $list->statue = 'sent';
        $message = 'تم  توزيع الطلب  '.  ' - '.\Carbon\Carbon::Now();
        $message .= '  من طرف ';
        $message .= $this->user->username;
        $this->saveHistory($list,$message);
    }
    
 

    public function confirmation($request,$response) {
        $params         = $request->getParams();
        $route          = $request->getAttribute('route');
        $listing        = new Listing($params,$route);
        $pagination     = $listing->pagination();
        $lists          = $listing->list();
        $view           = $listing->view();
        $type           = $listing->viewType();
        $cities         = (new \App\Helpers\Stats())->ConfirmationCities();
        $cities         = array_chunk($cities, 3);
        return $this->view->render($response,$view,compact('cities','type','lists','params','pagination'));
    }

    public function confirm($request,$response,$args){
        $ids = explode(',',$request->getParam('confirmation_ids')) ?? [];
        ConfirmOrders($ids);
        $this->flashsuccess('تم تفعيل الطلبات وارسالها الى الموزعين');
        return $response->withRedirect($this->router->pathFor('confirmation').'?type=confirmation');   
    }





    public function employeeListing( $request,$response ) {


        $route          = $request->getAttribute('route');

        $params         = $request->getParams();

        if(isset($_SESSION['auth-deliver'])){
          $params['limit']  = 2000;
        }


        $listing        = new Listing($params,$route);

        $pagination     = $listing->pagination();

        $lists          = $listing->list();
        
        $view           = $listing->view();

        $type           = $listing->viewType();

        return $this->view->render($response,$view,compact('type','lists','params','pagination'));

    }





    public function index($request,$response) {
        
        $route          = $request->getAttribute('route');
        $params         = $request->getParams();
        if(isset($_SESSION['auth-deliver'])){
          $params['limit']  = 2000;
        }


        $listing        = new Listing($params,$route);
        $pagination     = $listing->pagination();
        $lists          = $listing->list();
        
        //dd($lists);

        $view           = $listing->view();
        $type           = $listing->viewType();
        return $this->view->render($response,$view,compact('type','lists','params','pagination'));
    }

 
    public function suivi($request,$response) {
        $route          = $request->getAttribute('route');
        $params         = $request->getParams();
        $listing        = new Listing($params,$route);
        $pagination     = $listing->pagination();
        $lists          = $listing->list();
        $view           = $listing->view();
        $type           = $listing->viewType();
        return $this->view->render($response,$view,compact('type','lists','params','pagination'));
    }


    public function VerfiedCash($request,$response) {
        $data          = $request->getAttribute('route')->getArguments();
        (new \App\Helpers\Cash)->verfiey($data);
        return $response->withRedirect('/cash');
    }





    public function cash($request,$response) {

        $revenue    =  (new Revenue('loadHistory'))->HistoryDetails($_POST['date'],$_POST['deliver']);
        $view     = 'admin/elements/revenueForm.twig';
        return $this->view->render($response, $view , compact('revenue'));

         $day       = clean($_POST['date']);
         $deliver   = clean($_POST['deliver']);
         $lists =  Listing::delivredByDay($day,$deliver);
         $html = '';
         foreach ($lists as $list) {
             $html .= "<tr class='row_{$list->id}'>
             <td>#{$list->id}</td>
             <td>{$list->name}</td>
             <td><a href='tel:{$list->tel}'>{$list->tel}</a></td>
             <td>";
             foreach ($list->products as $product) {
                 $html .= $product->quanity . ' X ' . $product->product->name . '</br>';
             }

            $html .= "</td>
            <td>{$list->total} </td>            
            <td>{$list->employee->username} </td>            
            <td>{$list->deliver->username}</td>
            </tr>";   
          }
          return $html;
    }



    public function stats($request,$response) {
        $from     = $request->getParam('from') ?? NULL;
        $to       = $request->getParam('to')   ?? NULL;
        $stats    = $this->getstats();
        //$earned   = (new \App\Helpers\Cash())->init()->getToday();
        $cities   = (new \App\Helpers\Stats())->cities();
        $products = (new \App\Helpers\Stats())->products();
        $cash     = (new \App\Helpers\Cash)->list();   

        $file = 'admin/admin/stats.twig';
        return $this->view->render($response,$file,compact('cash','stats','earned','cities','products'));
    }
    


    public function getstats($from=false,$to=false){
        return [
            'delivers' => GetAllDeliversStats($from,$to),
            'employees' =>GetAllEmployeesStats($from,$to)
        ];
    }


    

    public function all($request,$response){
        $params         = $request->getParams();
        $listing        = new \App\Helpers\Listing($params);
        $lists          = $listing->listing();
        $pagination     = $listing->pagination();
        return $this->view->render($response,$view,compact('lists','pagination'));
    }

   
    // get the lists for employees
    public function export($request,$response){
         
            $post = clean($request->getParams());

           
            $stream = fopen('php://memory', 'w+');
            fwrite($stream, chr(0xEF) . chr(0xBB) . chr(0xBF));
            
            // Add header
            $columns = [
                'date',
                'nom et prenom',
                'Telephone',
                'Ville',
                'Adresse',
                'Ref',
                'store',
                'quantity',
                'prix (DH)'
            ];

            $listing        = new \App\Helpers\Listing( $post);
            $users          = $listing->list();
                              
            $filename = date('Y-m-d') . '_cmds.csv';
            $fh = @fopen( 'php://output', 'w' );

            header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
            header( 'Content-Description: File Transfer' );
            header('Content-type: text/csv; charset=UTF-8');
            header('Content-Encoding: UTF-8');
            header('Content-Transfer-Encoding: binary');
            header( "Content-Disposition: attachment; filename={$filename}" );
            header( 'Expires: 0' );
            header( 'Pragma: public' );
            header('Content-Encoding: UTF-8');
            header('Content-type: text/csv; charset=UTF-8');

            echo "\xEF\xBB\xBF";
        
            fputcsv( $fh, $columns ,';');
            $city = $user['city']['city_name'] ?? '';
            $product = $user['products'][0]['product']['name'] ?? '';
            $quantity = $user['products'][0]['quanity'] ?? '';
            $price   = $user['products'][0]['price'] ?? '';
            foreach ($users as $user) {
                  $data = [
                    $user['created_at'],
                    $user['name'],
                    $user['tel'],
                    $city,
                    $user['adress'],
                    $product,
                    $user['source'],
                    $quantity,
                    $price,
                ];
                
                fputcsv($fh, $data, ';');
            }
            
            fclose( $fh );
    }
    



   
    // get the lists for employees
    public function exportDeliverSelected($request,$response){
        
            $ids = explode(',',$request->getParam('selectedToExport')) ?? [];
            $lists =  \App\Models\Lists::with('deliver','employee','products','products.product','city')->whereIn('id', $ids)->get()->toArray();

            $stream = fopen('php://memory', 'w+');
            fwrite($stream, chr(0xEF) . chr(0xBB) . chr(0xBF));
            
            // Add header
            $columns = [
                'order N',
                'nom et prenom',
                'Telephone',
                'Ville',
                'Adresse',
                'Ref',
                'store',
                'prix (DH)',
                'statue',
            ];
            
            
            $filename = date('Y-m-d') . '_cmds.csv';
            $fh = @fopen( 'php://output', 'w' );

            header('Cache-Control: must-revalidate, post-check=0, pre-check=0' );
            header('Content-Description: File Transfer' );
            header('Content-type: text/csv; charset=UTF-8');
            header('Content-Encoding: UTF-8');
            header('Content-Transfer-Encoding: binary');
            header("Content-Disposition: attachment; filename={$filename}" );
            header('Expires: 0' );
            header('Pragma: public' );
            header('Content-Encoding: UTF-8');
            header('Content-type: text/csv; charset=UTF-8');

            echo "\xEF\xBB\xBF";
        
            fputcsv( $fh, $columns ,';');
            
            
            foreach ($lists as $list) {
                
                  $products = '';
                  foreach($list['products'] as $product){
                      if(count($list['products']) == 1 ) {
                          $products .= $product['product']['name']. $product['quanity'] . ' = ' . $product['price'] ;
                      }else{
                        $products .= $product['product']['name'] . $product['quanity'] . ' = ' . $product['price'] . '|';    
                      }
                      
                  }
                   $city = $list['city']['city_name'] ?? '';

                  $data = [
                    $list['id'],
                    $list['name'],
                    $list['tel'],
                    $city,
                    $list['adress'],
                    $products,
                    $list['source'],
                    $list['total'],
                    $list['type'],
                ];
                
                fputcsv($fh, $data, ';');
            }
            

            fclose( $fh );
    }
    
    
    
    
    
    
    
    
    
    
    
    
    

    // get the lists for employees
    public function exportConfirmation($request,$response){
         
            $post = clean($request->getParams());

            $stream = fopen('php://memory', 'w+');
            fwrite($stream, chr(0xEF) . chr(0xBB) . chr(0xBF));
            
            // Add header
            $columns = [
                'date',
                'nom et prenom',
                'Telephone',
                'Ville',
                'Adresse',
                'Ref',
                'store',
                'quantity',
                'prix (DH)'
            ];

            $listing        = new \App\Helpers\Listing( $post);
            $users          = $listing->list();
                              
            $filename = date('Y-m-d') . '_cmds.csv';
            $fh = @fopen( 'php://output', 'w' );

            header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
            header( 'Content-Description: File Transfer' );
            header('Content-type: text/csv; charset=UTF-8');
            header('Content-Encoding: UTF-8');
            header('Content-Transfer-Encoding: binary');
            header( "Content-Disposition: attachment; filename={$filename}" );
            header( 'Expires: 0' );
            header( 'Pragma: public' );
            header('Content-Encoding: UTF-8');
            header('Content-type: text/csv; charset=UTF-8');

            echo "\xEF\xBB\xBF";
        
            fputcsv( $fh, $columns ,';');
            
            foreach ($users as $user) {
            $city = $user['city']['city_name'] ?? '';
            $product = $user['products'][0]['product']['name'] ?? '';
            $quantity = $user['products'][0]['quanity'] ?? '';
            $price   = $user['products'][0]['price'] ?? '';

                  $data = [
                    $user['created_at'],
                    $user['name'],
                    $user['tel'],
                    $city,
                    $user['adress'],
                    $product,
                    $user['source'],
                    $quantity,
                    $price,
                ];
                
                fputcsv($fh, $data, ';');
            }
            
            fclose( $fh );
    }
  

    // show the create page
    public function createForm($request,$response){
        return $this->container->view->render(
            $response,'admin/admin/create-lists.twig',
            compact('products','cities','employees')
        );
    }    
    
    // store the list
    public function create($request,$response) {  
        $post =  clean($request->getParams());
        $Lists = new Lists();
        $listID = $this->saveList($Lists,$post,true);
        $this->saveMultiSale($post,$listID);
        $this->flashsuccess('تم اضافة الطلب بنجاح');
        $redirectURL  = $post['redirectURL'];
        return $response->withRedirect($redirectURL);
    } 

    // edit the List
    public function edit($request,$response,$args){

            $id   = rtrim($args['id'], '/');
            $list = Lists::find($id);
            $multisale = MultiSale::where('listID',$id)->get();
            $data = compact('products','cities','list','multisale');

            if(isset($_SESSION['auth-admin'])) {
                return $this->view->render($response, 'admin/admin/edit-lists.twig',$data);
            }else {
                return $this->view->render($response, 'admin/employee/edit.twig',$data);
            }
    }
    
        
    // Update the list
    public function update($request,$response,$args){
        $post =  clean($request->getParams());
        $link = $post['redirectURL'];
        unset($post['redirectURL']);
        $id  = rtrim($args['id'], '/');
        $list = Lists::find($id);
        $listID = $this->saveList($list,$post);
        $this->saveMultiSale($post,$listID,true);
        $this->flashsuccess('تم تعديل الطلب بنجاح');

        if(empty($link)){
            return $response->withRedirect('/lists');
        }

        return $response->withRedirect($link);
    }
    
    
     /**
     * التحقق من أن الرقم غير مكرر في الطلبات القديمة والجديدة
     * @author TakiDDine
     */
    public function checkDuplicatedNumber($number){
        
        $newstring = substr($number, -8);
        $foundAlreadyInLists     =   \App\Models\Lists::all();
        $foundAlreadyInNeworders =  \App\Models\NewOrders::all();
        
        
        $exist = false;
        foreach($foundAlreadyInLists as $order) {
            if( substr($order->tel, -8) == $newstring ) { $exist = true; }
        }
        foreach($foundAlreadyInNeworders as $order) {
            if( substr($order->tel, -8) == $newstring ) { $exist = true; }
        }
        
        return $exist;
    }
    
    
    // creating the list OR update 
    public function saveList($model,$post,$checkNumber = false){
        
        $model->name               = $post['name'];
        $model->adress             = $post['adress'];
        $model->tel                = $post['tel'];
        $model->cityID             = $post['cityID'];
        $model->DeliverID          = Cities::find($post['cityID'])->user_id;
        $model->note               = $post['note'] ;
        $model->prix_de_laivraison = $post['prix_de_laivraison'] ;
        $model->mowadafaID         = $post['employee'] ?? $model->mowadafaID  ;
        if($checkNumber  == true ){
           if( $this->checkDuplicatedNumber($post['tel'])){
               $model->duplicated_at = Carbon::NOW();
           } 
        }
        $model->save();
        return $model->id;
    }   

    // the action of saving the products of the listing 
    public function multiSaleProductsSave($post,$listID){
       for($x=0;$x< count($post['ProductID']);$x++){
            $pro = new MultiSale();
            $pro->listID    = $listID;
            $pro->productID = $post['ProductID'][$x];
            $pro->price     = $post['prix'][$x];
            $pro->quanity   = $post['quantity'][$x];
            $pro->save();
        } 
    }

    // save OR update the products of the order
    public function saveMultiSale($post,$listID,$update = false){
        if($update){
                MultiSale::where('listID', $listID)->delete(); 
                $this->multiSaleProductsSave($post,$listID);
        }
        else{
            $this->multiSaleProductsSave($post,$listID);
        }
    }

  


    // set as sent 
    public function setSentEmployee ($request,$response) {
        
        $id                  = $_POST['list'];
        $list                = Lists::find($id);   
        $houres              = $_POST['houres'];
        $days                = $_POST['days'];

        if(!isset($_SESSION['auth-admin'])){ 
            if(empty($list->cityID) || empty($list->name) || empty($list->adress) ){
                return 'NEDDED_INFO';
            }
        }

        $products = MultiSale::where('listID',$list->id);
        if(!$products->count()){
            return 'please_fill_info';
        }

        if(!empty($houres)) {
           $deliver_at = \Carbon\Carbon::Now(new \DateTimeZone('Africa/Casablanca'))->addHours($houres+1); 
        }
        
        if(!empty($days)) {
          $deliver_at = \Carbon\Carbon::parse($days);
        } 

        $list->to_deliver_at = $deliver_at;
        $list->canceled_at    = NULL;
        $list->recall_at      = NULL;
        $list->statue         = NULL;
        $list->cancel_reason  = NULL;
        $list->no_answer      = NULL;
        $list->no_answer_time = NULL;
        $list->recall_at      = NULL;
        $list->accepted_at    = Carbon::now();
        $message  = 'تم  قبول الطلب ' .  ' - '.\Carbon\Carbon::Now(); 
        $message .= '  من طرف ';
        $message .= $this->user->username;
        $this->saveHistory($list,$message);
        sv('saved as accepted');
    }


    // set as sent 
    public function setSent ($request,$response) {
        
        $id                  = $_POST['list'];
        $list                = Lists::find($id);   

        if($list->count_no_answer == 4 ){
            return false;
        }


        if(!isset($_SESSION['auth-admin'])){ 
            if(empty($list->cityID) || empty($list->name) || empty($list->adress) ){
                return 'NEDDED_INFO';
            }
        }

        if(empty($list->tel)){
            return 'please_fill_info';
        }

        if(empty($list->name)){
            return 'please_fill_info';  
        }

        if(empty($list->adress)){
            return 'please_fill_info';
        }

        if(empty($list->cityID)){
            return 'please_fill_info';
        }

        if(empty($list->DeliverID)){
            return 'please_fill_info';
        }

        $products = MultiSale::where('listID',$list->id);
        if(!$products->count()){
            return 'please_fill_info';
        }

        $list->canceled_at   = NULL;
        $list->recall_at     = NULL;
        $list->statue        = NULL;
        $list->cancel_reason = NULL;
        $list->no_answer     = NULL;
        $list->recall_at     = NULL;
        $list->accepted_at   = Carbon::now();
        $message  = 'تم  قبول الطلب ' .  ' - '.\Carbon\Carbon::Now(); 
        $message .= '  من طرف ';
        $message .= $this->user->username;
        $this->saveHistory($list,$message);
    }

    // set recall
    public function setRecall($request,$response){
      
        // get the id;
        $id   = $_POST['list_id'];
        
        $list = Lists::find($id);


        if($list->count_no_answer == 4 ){
            return false;
        }


        $daba = Carbon::now();
        
        
        $list->canceled_at   = NULL;
        $list->recall_at     = NULL;
        $list->statue        = '';
        $list->cancel_reason = '';
        $list->no_answer     = '';
        


        $h = $_POST['recall_houres'];
        $d = $_POST['recall_days'];
        

        if($h) {
          $list->recall_at =  $daba->addMinutes($h);
        }
        if($d) {
          $list->recall_at =  Carbon::parse($d);
        }
        
        $list->statue = 'recall';
        
        $message = 'تم  تعيين ك إعادة الإتصال '.  ' - '.\Carbon\Carbon::Now();        
        $message .= '  من طرف ';
        $message .= $this->user->username;
        $this->saveHistory($list,$message);
    }
    


    // set No Response
    public function setNoResponse ($request,$response) {

        $id = $_POST['get_id'];
        $list = Lists::find($id);

        $list->canceled_at = NULL;
        $list->recall_at = NULL;

        
        

        if(Employee()){
            
                if(is_null($list->count_no_answer_employee)){
                    $list->count_no_answer_employee = 1;
                }else {
        
                    if($list->count_no_answer_employee == 7 ) {
                        $list->delivred_at    = NULL;
                        $list->recall_at      = NULL;
                        $list->no_answer      = NULL;
                        $list->no_answer_time = NULL;
                        $list->recall_at      = NULL;
                        $list->statue         = NULL;
                        $list->cancel_reason = "ملغى بسبب لا يجيب لـ 7 مرات";
                        
                        $list->canceled_at   =  \Carbon\Carbon::Now();
                        $list->save();
                        $mesage = ' تم  الإلغاء بسبب الرقم لا يجيب ل7  مرات '.  ' - '  . \Carbon\Carbon::Now();
                        return $list->save();
                    }else {
                        $list->count_no_answer_employee = $list->count_no_answer_employee + 1;
                    }
                }
        
        
        }
            
            



        if(Deliver()){
            
                if(is_null($list->count_no_answer)){
                    $list->count_no_answer = 1;
                }else {
        
                    if($list->count_no_answer == 4 ) {
                        $list->delivred_at    = NULL;
                        $list->recall_at      = NULL;
                        $list->no_answer      = NULL;
                        $list->no_answer_time = NULL;
                        $list->recall_at      = NULL;
                        $list->statue         = NULL;
                
                        $list->cancel_reason = "ملغى بسبب لا يجيب";
                        $list->canceled_at   =  \Carbon\Carbon::Now();
                        $list->save();
                        $mesage = ' تم  الإلغاء بسبب الرقم لا يجيب ل4  مرات '.  ' - '  . \Carbon\Carbon::Now();
                        return $list->save();
                    }else {
                        $list->count_no_answer = $list->count_no_answer + 1;
                    }
                }
        
        
        }
            
        



        $list->save();
        return (new \App\Helpers\Noanswer())->start($id);
    }
    


    // load list;
    public function loadListData($request,$response) {
        $id = $_POST['list_id'];
        $list = Lists::find($id);
        echo  $this->boxHtml($list);
    }

    
    // SET LIST STATUE TO CANCEL
    public function setCanceled($request,$response){
      
        // get the id;
        $id                   = clean($_POST['list_id']);
        $reason               = clean($_POST['reason']);
        
        // get the list
        $list                 = Lists::find($id);

        if($list->count_no_answer == 4 ){
            return false;
        }

        $list->canceled_at    = \Carbon\Carbon::Now();
        if(isset($_SESSION['auth-suivi'])){
           $list->deleted_at  = \Carbon\Carbon::Now();
        }
        $list->delivred_at    = NULL;
        
        $list->recall_at      = NULL;
        $list->no_answer      = NULL;
        $list->statue         = NULL;
        $list->no_answer_time = NULL;
        $list->recall_at      = NULL;
        



        // set list to canceled 
        $list->cancel_reason  = $reason;
        $message = \Carbon\Carbon::Now() . ' - تم  تعيين ك ملغى '.  ' - ' . ' السبب '  . ' : ' . ' ' . $reason;
        $message .= '  من طرف ';
        $message .= $this->user->username;




        $this->saveHistory($list,$message);
        
    }




    
    public function boxHtml($list){
        
        $city = 'غير معروفة';
        $store = $list->source;
        if(!empty($list->cityID) and is_numeric($list->cityID)){
            $city = $list->realcity->city_name ?? '';
        }
        $html =  "<div class='more_detail_loaded' >
            <h2> اسم العميل :  {$list->name}</h2>
            <h2> رقم الهاتف :  {$list->tel} </h2>        
            <h2>العنوان : {$list->adress} </h2>
            <h2>المدينة :{$city} </h2>    
            <h2> المصدر   :  {$store} </h2>    
            <h2>  المنتوج  :  {$list->product}  </h2>
            <h2>  الكمية :  {$list->quantity}  - الثمن  :  {$list->price} درهم </h2>
            <h2>    </h2>
         </div>";
        
        $html .='<div class="more_detail_loaded" style="margin-top:20px;" >
                <table class="table">
              <thead>
                <th><b>المنتوج</b></th>
                <th><b>الكمية</b></th>
                <th><b>الثمن</b></th>
              </thead><tbody>
        ';
        
        $products = MultiSale::where('listID',$list->id)->get();
        if( $products->count() !==  0 ) {
            foreach($products as $product){
                $name = $product->product->name ?? '';
               $html .="
                <tr>
                <td>{$name}</td>
                <td>{$product->quanity }</td>
                <td>{$product->price}</td>
                </tr>
                ";
            }
        }
  
        $html .='</tbody></table></div>';
        
        return $html;
    }
    

    public function stock($request,$response){
        return $this->view->render($response, 'admin/embalage/entree.twig',['products'=>Product::all()]);    
    }
    
        

    public function loadEmployeesCount($request,$response){
        $data = [];
        foreach (GetEmployees() as $employee) {
            $inj = (new Listing([ 'employee'=> $employee->id , 'type'=> 'waiting' ]))->countTotal;
            $new = (new Listing([ 'employee'=> $employee->id , 'type'=> 'NoAnswer' ]))->countTotal;
            array_push($data, [$employee->id ,'inj:' . $new  . ' - ' . 'enoure:'.$inj]);
        }
        return json_encode($data);
    }
    
   
    

    public function loadListDataWithActions($request,$response) {
        
        $id = $_POST['list_id'];
        $list = Lists::find($id);
        $html = $this->boxHtml($list);
        $deliver = Deliver();


        if(isset($deliver) and is_numeric($deliver)){
             $edit = '';
        }else{
             $edit = '<a href="/lists/edit/'.$id.'" class="btn btn-success  btn-lg">  تعديل </a>';
        }


        if(empty($list->cityID) || empty($list->name) || empty($list->adress) ){
            $edit_data = 'data-edit="true"';
        }else {
            $edit_data = 'data-edit="false"';
        }
        
        $suivi = '';
        $accept_btn = ' <button type="button" '.$edit_data.'  data-listid="'.$id.'" data-role="'. $_POST['type'] .'" data-type="confirmed"  class="adminActionBtn btn btn-danger  btn-lg">قبول الطلب</button>';
        
        if(isset($_SESSION['auth-suivi'])){
            $suivi = '<button type="button"  data-listid="'.$id.'" data-role="'. $_POST['type'] .'" data-type="restoreSuivi" class="adminActionBtn btn  btn-danger   btn-lg">استرجاع للموزع</button>';
             $accept_btn ='';
        }


        $html .= '
            <center id="ctamodal" class="colored_heading">
              <button type="button" data-listid="'.$id.'" data-role="'. $_POST['type'] .'" data-type="NoResponse" class="adminActionBtn btn btn-default btn-lg">لا يجيب</button>
              <button type="button" data-listid="'.$id.'" data-role="'. $_POST['type'] .'" data-type="Recall" class="adminActionBtn btn btn-primary  btn-lg">اعادة الإتصال</button>
              <button type="button"  data-listid="'.$id.'" data-role="'. $_POST['type'] .'" data-type="Canceled" class="adminActionBtn btn  btn-danger   btn-lg">ملغى</button>
               '. $accept_btn .'
               '. $edit .'
               '. $suivi .'
            </center>';   
        echo $html;
        exit;   
    }
    


     
    
 
   public  function loadHistory(){
        $id = $_POST['id'];
        $list = \App\Models\Lists::find($id);
        return $list->history;
    }

    public function transform($request,$response){     
        if($request->getMethod() == 'POST' )  {
            $post = clean($request->getParams());
            if(($post['from'] != $post['to']) and is_numeric($post['from']) and is_numeric($post['to'])){
                    $lists        = (new Listing(['employee' => $post['from'] , 'type' => 'waiting' ]))->listing;
                    foreach($lists as $list){
                        $list->mowadafaID = $post['to'];
                        $list->save();
                    }
                    $this->flashsuccess('تم تحويل الطلبات بنجاح');
                    return $response->withRedirect($this->router->pathFor('lists.transform'));
            }
        }
        $file = 'admin/admin/transform.twig';
        return $this->view->render($response,$file);
    }

    public function loadUserDeliveryPrice($request,$response){
        return isset($_POST['city_id']) ? (Cities::find($_POST['city_id'])->deliver->deliver_price ?? '') : '' ;
    }


    public function exportDeliver($request,$response){
         $deliver = Deliver();

         if(isset($deliver) and is_numeric($deliver)){
            $stream = fopen('php://memory', 'w+');
            fwrite($stream, chr(0xEF) . chr(0xBB) . chr(0xBF));
            
            // Add header
            $columns = [
                'date',
                'nom et prenom',
                'Telephone',
                'Ville',
                'Adresse',
                'Ref',
                'store',
                'quantity',
                'prix (DH)'
            ];

            $listing        = new \App\Helpers\Listing(['deliver'=> $deliver]);
            $users          = $listing->list();
                              
            $filename = date('Y-m-d') . '_cmds.csv';
            $fh = @fopen( 'php://output', 'w' );

            header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
            header( 'Content-Description: File Transfer' );
            header('Content-type: text/csv; charset=UTF-8');
            header('Content-Encoding: UTF-8');
            header('Content-Transfer-Encoding: binary');
            header( "Content-Disposition: attachment; filename={$filename}" );
            header( 'Expires: 0' );
            header( 'Pragma: public' );
            header('Content-Encoding: UTF-8');
            header('Content-type: text/csv; charset=UTF-8');

            echo "\xEF\xBB\xBF";
        
            fputcsv( $fh, $columns ,';');
            
            foreach ($users as $user) {
            $city = $user['city']['city_name'] ?? '';
            $product = $user['products'][0]['product']['name'] ?? '';
            $quantity = $user['products'][0]['quanity'] ?? '';
            $price   = $user['products'][0]['price'] ?? '';

                  $data = [
                    $user['created_at'],
                    $user['name'],
                    $user['tel'],
                    $city,
                    $user['adress'],
                    $product,
                    $user['source'],
                    $quantity,
                    $price,
                ];
                
                fputcsv($fh, $data, ';');
            }
            
            fclose( $fh );

         }
      


    }



    
}

