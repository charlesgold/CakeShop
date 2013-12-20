<?php
/**
 * Static content controller.
 *
 * This file will render views from views/pages/
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       app.Controller
 * @since         CakePHP(tm) v 0.2.9
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
App::import('Lib', 'Shopify');
App::uses('AppController', 'Controller');
App::uses('HttpSocket', 'Network/Http');


/**
 * Static content controller
 *
 * Override this controller by placing a copy in controllers directory of an application
 *
 * @package       app.Controller
 * @link http://book.cakephp.org/2.0/en/controllers/pages-controller.html
 */
class MyAppController extends AppController {
            
      public $name      =     "MyApp";
      public $uses = array(
                                            'Customer'
                                   );

  
      public function beforeRender() {
            //https://SHOP_NAME.myshopify.com/admin/oauth/authorize?client_id=API_KEY&scope=write_products,read_orders

      
      }

      private function shopifyConfig(){
            $authConfig       =           array(
                                                                  'AuthUrl'               =>    '.myshopify.com/admin/oauth/authorize'
                                                                  ,'Protocol'             =>    'https://'
                                                                  ,'APIKey'               =>    '438d6f90dc20af54eb2799ec73caf844'
                                                                  ,'SharedSecret'    =>    '1a7ea5b6123e86421ab93cc2eb2e23cb'
                                                                  ,'scope'                  =>   'write_shipping,read_shipping,read_products,write_products,read_fulfillments,write_fulfillments,read_orders,write_orders,read_content,write_content'
                                                                  ,'redirect'              =>    ''
                                                                  
            ); 
            
            return $authConfig;           
      }
      
      private function iniAuth($storeName){
            
            
      }


      
      

      public function index() {
          
                 
            if($this->request->is('post')){
                  
                  //Collection Installation information
                  $formData   =     $this->request->data;
                  $shopName   =     $formData['shopName'].'.myshopify.com';
                  
                  //Shopify Config
                  $config           =   $this->shopifyConfig();
                                    
                  //Create shopify client
                  $shopifyClient    =   new ShopifyClient($shopName, '' , $config['APIKey'], $config['SharedSecret']);
                  
                  //Direct user to install (Shopify Screen) 
                  //auth url
                  $authUrl            =     'http://charlietopjian.com/myapp/myapp/capture';
                $this->redirect($shopifyClient->getAuthorizeUrl($config['scope'],$authUrl));         
                  
            }

            //Not post, just ask for shop name for authentication.
            $installBlurb     =     '<h3>Login</h3> To install application, please type your store name below.';
            $this->set('installBlurb',$installBlurb);
      
      }

    public function capture(){
            //capture the Perm token
            $capture    =   $this->request->query;
            
            //config
            $config     =   $this->shopifyConfig();
            
            if(isset($capture['code'])){
                 //Create instance       
                $shopifyClient  =   new ShopifyClient($capture['shop'], "", $config['APIKey'], $config['SharedSecret']);
                //Create Perm Token
                $permToken  =   $shopifyClient->getAccessToken($capture['code']);
            }
            
            //** need to redirect user to "INSTALL" if not found in database and/or ['code'] param does not exists.
            //* WHICH MEANS no authentication took place, the user just found their way to this URL.
            //*?? COULD BLOCK USING  http://docs.shopify.com/api/tutorials/oauth #6
            
            //DOES THE Customer already exist?
            $customerCheck =    $this->checkCustomer($capture['shop']);
            if(!empty($customerCheck)){//customer found, get token                
                
                $this->Session->write('sa.token',$customerCheck['Customer']['perm_token']);
                $this->Session->write('sa.shop',$customerCheck['Customer']['shop_name']);
                $this->Session->write('sa.shopUrl',$customerCheck['Customer']['shop_url']);
                                
            } else {//we need to save the customer information for future use.
                
                   $shopName         =   explode('.',$capture['shop']);
                     //Set the session
                    $this->Session->write('sa.token',$permToken);
                    $this->Session->write('sa.shop',$shopName[0]);
                    $this->Session->write('sa.shopUrl',$capture['shop']);                    
                   
                  $customerInfo     =       array(
                                                                        'shop_url'          =>  $capture['shop']
                                                                        ,'shop_name'     =>  $shopName[0]
                                                                        ,'perm_token'   =>  $permToken
                  );
                  
                  //save to dbase
                  $this->savePermToken($customerInfo);          
                                
            }
            
            //Redirect to APP Index
            $this->appRoot('http://charlietopjian.com/myapp/myapp/welcome');
            
    }
 
    
    private function appRoot($url){
        $this->redirect($url);
    }
    
    
    private function savePermToken($customerInfo){
        
        //Save the data.
        $this->Customer->create();
        $this->Customer->save($customerInfo);
        
    }

private function checkCustomer($shop_test){
    
    //Does the current shop already exist?
    $customerInfo   =   $this->Customer->find('first',array(
                                                                                                        'conditions'        =>      array(
                                                                                                                                                            'shop_url'      =>      $shop_test
                                                                                                        )
                                                        )
    );
    
    return $customerInfo;
}    

    
    public function welcome(){
        
        $config     =   $this->shopifyConfig();
        
        //test products
        $sc     =   new ShopifyClient($this->Session->read('sa.shopUrl'), $this->Session->read('sa.token'),$config['APIKey'],$config['SharedSecret']);
        
        $products = $sc->call('GET', '/admin/products.json');
        
        $this->set('test', json_decode($products));
    }




/**
 *  Name
 *
 * @param mixed What page to display
 * @return void
 * @throws NotFoundException When the view file could not be found
 *	or MissingViewException in debug mode.
 */


 
}
