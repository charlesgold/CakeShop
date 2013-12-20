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
      public $uses = array();

  
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
            
            //Shopify Config
            $config     =     $this->shopifyConfig();
            
            //Authorize
            $authParams  =     array(
                                                      'client_id'       =>    $config['APIKey']
                                                      ,'scope'            =>        $config['scope']
            );
            $authUrl            =     $config['Protocol'].$storeName.$config['AuthUrl'];
            //$authResults    =     $this->apiTalk($authUrl,$authParams);
            $this->redirect($authUrl.'?'.http_build_query($authParams));
            
      }

      private function apiTalk($url,$param=''){ 
            
            //Configure Socket
            $socketConfig   =   array(
                                                            //'ssl_verify_host'     =>  false
                                                           // 'ssl_verify_peer'           =>  true
                                                            //,'ssl_allow_self_signed' => true
            );
            $HttpSocket     =   new HttpSocket($socketConfig);
            
            $result     =           $HttpSocket->post($url,$param);
            
            return $result;            
      }
      
      
        private function curlHttpApiRequest($method, $url, $query='', $payload='', $request_headers=array())
        {
                $url = $this->curlAppendQuery($url, $query);
                $ch = curl_init($url);
                $this->curlSetopts($ch, $method, $payload, $request_headers);
                $response = curl_exec($ch);
                $errno = curl_errno($ch);
                $error = curl_error($ch);
                curl_close($ch);

                if ($errno) throw new ShopifyCurlException($error, $errno);
                list($message_headers, $message_body) = preg_split("/\r\n\r\n|\n\n|\r\r/", $response, 2);
                $this->last_response_headers = $this->curlParseHeaders($message_headers);

                return $message_body;
        }      
      
      private function curlAppendQuery($url, $query)
        {
                if (empty($query)) return $url;
                if (is_array($query)) return "$url?".http_build_query($query);
                else return "$url?$query";
        }  
      
      private function curlSetopts($ch,$method,$payload,$request_headers){
          
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($ch, CURLOPT_USERAGENT, 'HAC');
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            
            curl_setopt ($ch, CURLOPT_CUSTOMREQUEST, $method);
            if (!empty($request_headers)) curl_setopt($ch, CURLOPT_HTTPHEADER, $request_headers);
            
            if ($method != 'GET' && !empty($payload))
            {
                    if (is_array($payload)) $payload = http_build_query($payload);
                    curl_setopt ($ch, CURLOPT_POSTFIELDS, $payload);
            }          
          
      }

      public function index() {
      
            if($this->request->is('post')){
                  
                  //Collection Installation information
                  $formData   =     $this->request->data;
                  $shopName   =     $formData['shopName'];
                  
                  //Call OAuth function
                  $this->iniAuth($shopName);
                  
            }

            //Not post, just ask for shop name for authentication.
            $installBlurb     =     '<h3>Login</h3> To install application, please type your store name below.';
            $this->set('installBlurb',$installBlurb);
      
      }

    public function capture(){
        
        //Get temp token receieved to exchange for Permanent Token        
        $capture    =   array(
                                                'code'       =>      $this->request->query('code') //CODE
                                                ,'shop'       =>       $this->request->query('shop')//ShOP
        );
        //Now perform exchange for Perm
        $permToken  =   $this->permExchange($capture);
        
        die(print_r($permToken));
    }
    
    
    public function permExchange($capture){

        $config =   $this->shopifyConfig();
        
        $permParam  =   array(
                                                    'client_id'             =>      $config['APIKey']
                                                    ,'client_secret'    =>      $config['SharedSecret']
                                                    ,'code'                   =>       $capture['code']
        );
        $permUrl        =   $config['Protocol'].$capture['shop'].$config['AuthUrl'];
        
        //get perm token
        $perm   =   $this->apiTalk($permUrl,http_build_query($permParam));
        
        return $perm;
    }
    
    public function welcome(){
        
        die(print_r($_GET));
        
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
