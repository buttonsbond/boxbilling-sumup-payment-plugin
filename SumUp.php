<?php
/**
 * BoxBilling
 *
 * @copyright BoxBilling, Inc (http://www.boxbilling.com)
 * @license   Apache-2.0
 *
 * Copyright BoxBilling, Inc
 * This source file is subject to the Apache-2.0 License that is bundled
 * with this source code in the file LICENSE
 *
 * This module was written by Mark van Bellen, All Tech Plus, Rojales
 * source code is on GitHub https://github.com/buttonsbond/boxbilling-sumup-payment-plugin
 *
 */
 DEFINE (AUTH_URL,"https://api.sumup.com/authorize");
 DEFINE (TOKEN_URL,"https://api.sumup.com/token");
 DEFINE (CHECKOUT_URL,"https://api.sumup.com/v0.1/checkouts");
 
 // we will want SumUp to allow us to access a 'restricted scope' when we
 // authorize ourselves with the API - we will specifically want 'payments'

class Payment_Adapter_SumUp
{
    private $config = array();
    protected $di;

    public function __construct($config)
    {
        $this->config = $config;
        
        if (!extension_loaded('curl')) {
            throw new Payment_Exception('cURL extension is not enabled');
        }

        if(!isset($this->config['client_id'])) {
            throw new Payment_Exception('Payment gateway "SumUp" is not configured properly. Please update configuration parameter "api_key" at "Configuration -> Payments".');
        }

        if(!isset($this->config['client_secret'])) {
            throw new Payment_Exception('Payment gateway "SumUp" is not configured properly. Please update configuration parameter "pub_key" at "Configuration -> Payments".');
        }
        
        if(!isset($this->config['client_code'])) {
            throw new Payment_Exception('Payment gateway "SumUp" is not configured properly. Please update configuration parameter "client_code" at "Configuration -> Payments (should be merchant id from your sumup account)".');
        }
        
        if(!isset($this->config['pay_to_email'])) {
            throw new Payment_Exception('Payment gateway "SumUp" is not configured properly. Please update configuration parameter "pay_to_email" at "Configuration -> Payments".');
        }
        
    }

    /**
     * @param Box_Di $di
     */
    public function setDi($di)
    {
        $this->di = $di;
    }

    public static function getConfig()
    {
        return array(
            'can_load_in_iframe'   =>  true,
            'supports_one_time_payments'   =>  true,
            'supports_subscriptions'       =>  false,
            'description'     =>  'SumUp payment gateway allows you to give instructions to clients on how they can pay with their credit or debit card. See documentation for more information.',
            'form'  => array(
                'client_id' => array('text', array(
                    'label' => 'Test or Live Client ID:',
                ),
                ),
               'client_secret' => array('text', array(
                    'label' => 'Test or Live Client Secret:',
                ),
                ),
                'client_code' => array('text', array(
                    'label' => 'Test or Live Merchant ID (id from your SumUp account):',
                ),
                ),
                'pay_to_email' => array('text', array(
                    'label' => 'Pay to merchant e-mail:',
                    'validators'=>array('EmailAddress'),
                ),
                ),
                'locale_code' => array('text', array(
                    'label' => 'Locale code eg. en-GB, es-ES:',
                ),
                ),               
                
            ),
        );
    }

    /**
     * Generate payment text
     * 
     * @param Api_Admin $api_admin
     * @param int $invoice_id
     * @param bool $subscription
     * 
     * @since BoxBilling v2.9.15
     * 
     * @return string - html form with auto submit javascript
     */
    public function getHtml($api_admin, $invoice_id, $subscription)
    {
       // $invoiceModel = $this->di['db']->load('Invoice', $invoice_id);
       // $invoiceService = $this->di['mod_service']("Invoice");
       // $invoice = $invoiceService->toApiArray($invoiceModel, true);
        
        
        // mvb added
        $invoice = $api_admin->invoice_get(array('id'=>$invoice_id));
        $buyer = $invoice['buyer'];

        $p = array(
            ':id'=>sprintf('%05s', $invoice['nr']),
            ':serie'=>$invoice['serie'],
            ':title'=>$invoice['lines'][0]['title']
        );
        $title = __('Payment for invoice :serie:id [:title]', $p);
        $number = $invoice['nr'];
        
        
        
        

        $vars = array(
            '_client_id'    => $invoice['client']['id'],
            'invoice'   =>  $invoice,
            '_tpl'      =>  $subscription ? (isset($this->config['recurrent']) ? $this->config['recurrent'] : null) : (isset($this->config['single']) ? $this->config['single'] : null),
        );
        $systemService = $this->di['mod_service']('System');
        //return $systemService->renderString($vars['_tpl'], true, $vars);
        
        
        // added by mvb
        $data = array();
            $data['itemname']        = $title;
            $data['currency']        = $invoice['currency'];
            $data['merchant']        = $this->config['email'];
            $data['clientemail']    = $invoice['client']['email'];
            $data['totaltopay']         = $invoice['total'];
            $data['locale'] = "en-GB"; // this will be overridden by config if it is set
        //$db="<pre>";
       // $db=print_r($invoice);
       // $db.="</pre>";
        return $this->_generateForm($url, $data);
    }

    public function process($tx)
    {
        //do processing
        //var_dump($tx);
        
        // I don't think this function actually gets called
        
        $this->getLog()->info('Transaction data: ' . var_dump($tx));
        
        return true;
    }
    

function get_sumup_token() {
       // need to get an auth token first using the TOKEN_URL
        $clientid=$this->config['client_id'];
        $clientse=$this->config['client_secret'];
        $granttype="client_credentials";
        $authurl=TOKEN_URL;    
        $tokenpayload=Array('client_id'=>$clientid,'client_secret'=>$clientse,'grant_type'=>$granttype);
        $authresponse=$this->sumup_query($authurl, $tokenpayload, "=", "POST","");
        // do we have access_token?
        $dresponse=json_decode($authresponse);
        $accesstoken=$dresponse->access_token; // this worked to here    
    return $accesstoken;
}
    
    // form presentation
 function _generateForm($url, $data, $method = 'post')
    {
        // by default the GB locale will be used to present the SumUp form
        // unless overridden in the config of the payment gateway
        if ($this->config['locale_code'] != "") {
            $data['locale'] = $this->config['locale_code'];
        }
        
        $myuniqueid=uniqid(); // using this instead of session id as if the code fails we get duplicate checkout this should avoid that
        
        $accesstoken=$this->get_sumup_token();
        
        //$this->getLog()->info('payment gateway token: ' . $accesstoken);
        
        $payto=$this->config['pay_to_email'];
        $mcode=$this->config['client_code'];
        // redirect url is where control is sent once payment process completed
        // return url is where the status of the transaction is sent
        
        // change from redirect so both are notify 
        $redirecturl=$this->config['redirect_url'] . "&currency=" . $data['currency'] . "&amount=" . $data[totaltopay] . "&pay_to_email=" . $payto;
        $returnurl=$this->config['notify_url'] . "&currency=" . $data['currency'] . "&amount=" . $data[totaltopay] . "&pay_to_email=" . $payto;;
        
        $mycheckout=Array('checkout_reference'=>$myuniqueid,'amount'=>$data['totaltopay'],'currency'=>$data['currency'],'pay_to_email'=>$payto,'description'=>$data['itemname'], 'merchant_code'=>$mcode, 'redirect_url'=>$redirecturl,'return-url'=>$returnurl);
        
        $sumupresult=$this->sumup_query(CHECKOUT_URL,$mycheckout,":","POST",$accesstoken);
        
        // do we now have an id of the checkout resource?
        
        $idt=json_decode($sumupresult);
        $idtoken=$idt->id;
        
        //$this->getLog()->info('payment gateway checkout id: ' . $idtoken);
        
        $cfg=$this->config;
        
        if ($cfg['test_mode'] == "1") {
            $test="<h1>You are in test mode - providing you are using test credentials in the payment module settings your card will not be charged.</h1>";
        } else {
            $test="";
        }
        
        $form  = '';
        $form .= $test;
        $form .= "<p class='alert alert-danger'>" . $data['itemname'] . "</p>";
        $form .= "<p>Receipts are sent to " . $data['clientemail'] . "</p>";
      
      
        $form .= '<script src="https://gateway.sumup.com/gateway/ecom/card/v2/sdk.js"></script>';
        $form .=
                '<div id="sumup-card"></div>
                <script type="text/javascript" src="https://gateway.sumup.com/gateway/ecom/card/v2/sdk.js"></script>
                <script type="text/javascript">';
        $form .= "
        SumUpCard.mount({
        checkoutId: '$idtoken',
        onResponse: function(type, body) {
            console.log('Type', type);
            console.log('Body', body);
        },
        amount: '" . $data['totaltopay'] . "',
        currency: '" . $data['currency'] ."',
        email: '" . $data['clientemail'] . "',
        locale: '" . $data['locale'] . "'
        });
                </script><p class='alert alert-info'>Once you press the pay button, please wait, do not keep pressing!</p>";
        
        
        return $form;
    }

/**
     * Calls to the sumup API
     * 
     * @param String $url
     * @param Array $params
     * @param String $delim
     * @param String $postorget
     * @param String $at
     * 
     * 
     * @return string - json response
     */
function sumup_query($url, $params, $delim, $postorget, $at) {
          // Setup cURL options and make call to API
          // $url = API url to use
          // $params = the parameters to pass, comes in as an array
          // $delim = one of : or =
          // $postorget = can be POST or GET (though only POST is used)
          // $at = either blank or the authorisation token from first call
          //
          // I borrowed this function from elsewhere and have adapted it
          // The first call to sumup is to get an authorization token
          // this sends data as a normal post where parameters are
          // delimited with the & sign.
          // The second call uses that a-t to get a resource id - the data
          // that is passed this time is json formatted so the delimiters
          // will be a : and the key and values with quotes ' around them and
          // curly braces.
          // For clarity I may rewrite and perhaps have 2 separate functions
          // to handle - maybe!
          //
          $curl = curl_init();
          $headr = array();
          
          if ($delim == ":") {
            // in this case we've already got our token and are creating
            // a checkout
            $headr[] = 'Authorization: Bearer '.$at;
            $headr[] = 'Content-type: application/json';
          } else {
              // assuming non json call to get the token so header will be
              // if it's this, we're getting the authentication token using
              // normal post vars
              $headr[] = 'Content-Type: application/x-www-form-urlencoded';
          }
            curl_setopt($curl, CURLOPT_HTTPHEADER,$headr);
            $paramfields="";

              if (($delim == ":") && (is_array($params))) {
                  $paramfields = json_encode($params);
              } else {   
                  
                // only do this next bit if we're not sending json as I've already
                // encoded json to the variable $paramfields above if so
                $quoteit = "";
                $s="&";
                  if (is_array($params)) {
                       // Prepare the params list to be posted with curl
                       foreach($params as $key => $value) {
                           if ($key != "amount") {
                           $paramfields .= $quoteit . $key . $quoteit . $delim . $quoteit .$value. $quoteit . $s;
                           } else
                           {
                            $paramfields .= $quoteit . $key . $quoteit . $delim . $value . $s;
                           }
                       }
                       $paramfields = rtrim($paramfields, $s); // this removes the last comma

                } // end of normal posting parameter setup
                       
              }   
               
               curl_setopt($curl, CURLOPT_HEADER, 0); 
               
               $extra="";
               if ($postorget == "POST") {
                   curl_setopt($curl, CURLOPT_POST, count($params));
                   curl_setopt($curl, CURLOPT_POSTFIELDS, $paramfields);
               }
               if ($postorget == "GET") {
                   $extra="?" . $paramfields;
                   curl_setopt($curl,CURLOPT_HTTPGET, true);
               }
              curl_setopt($curl, CURLOPT_URL, $url . $extra);             
              // Configure cURL request options
              
              curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
              curl_setopt($curl, CURLOPT_TIMEOUT, 200);
              curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 1);
              curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 1);
    
              // Run cURL and check for errors
              $result = curl_exec($curl);
              $jresult=$result;
              if (curl_errno($curl)) { $result = Array('success' => 'false', 'result' => curl_errno($curl).' - '.curl_error($curl)); }
              curl_close($curl);
 
        //  echo var_dump($headr) . " those were the headers<br/><br/>";
        //  echo   var_dump($paramfields) . " those were the paramfields<br/><br/>";
        //  echo var_dump($jresult) . " and that was the result</br></br>";
        //  echo var_dump($result) . " this is the raw result";
          return $result;
     }
// copied from server manager logging
public function getLog()
    {
        if(!$this->_log instanceof Box_Log) {
            $log = new Box_Log();
            $log->addWriter(new Box_LogDb('Model_ActivitySystem'));
            return $log;
        }
        return $this->_log;
    }
 public function setLog(Box_Log $value)
    {
        $this->_log = $value;
        return $this;
    }


function processTransaction($api_admin, $id, $data, $gateway_id)
    {
        // we'll get a new access token just in case we're processing
        // this later
        $at=$this->get_sumup_token();
 
        // call the checkout_url with the checkout_id so can confirm
        // the status of the transaction
        $checkout=$data['get']['checkout_id'];
        $am=$data['get']['amount'];
        $cu=$data['get']['currency'];
        $pt=$data['get']['pay_to_email'];
        $nl="";
        $checktx=$this->sumup_query(CHECKOUT_URL . "/" . $checkout,$nl,":","GET",$at);
        
        $decode=json_decode($checktx);
        $txid=$decode->transaction_id;
        $txco=$decode->transaction_code;
        $status=$decode->status;
        $t=$decode->transactions;
        $status2=$t[0]->status; // this is whether the card payment actually worked
 
        $invoice = $this->di['db']->getExistingModelById('Invoice', $data['get']['bb_invoice_id']);
        $tx      = $this->di['db']->getExistingModelById('Transaction', $id);

        $title = $this->getInvoiceTitle($invoice);

    $invoiceService = $this->di['mod_service']('Invoice');

        $tx->invoice_id = $invoice->id;
        $tx->type = 'SumUp Checkout';

        switch ($status) {
            case "PENDING":
                $tx->txn_status = "pending"; 
                break;
            case "PAID":
                switch ($status2) {
                    case "SUCCESSFUL":
                         $tx->txn_status = "success";
                         break;
                    default:
                         $tx->txn_status = $status2;
                         break;
                    }
                break;
            case "FAILED":
                $tx->txn_status = "failed"; 
                break;
        }
        //
        $tx->txn_id = $txid;
        $tx->amount = $am;
        $tx->currency = $cu;
//        $tx->txn_id = $data['get']['transaction_id']; // from the api?
//        $tx->amount = $invoiceService->getTotalWithTax($invoice); // WOULDN'T IT BE BETTER GETTING THIS FROM THE CONFIRMED CHECKOUT
//       $tx->currency = $invoice->currency;

// we shouldn't mark invoice as paid if 1) there isn't a transaction code, id and a PAID status
if (($txid == "") || ($txco == ""))
    {
        // transaction was not successful
        $tx->status = $tx->txn_status;
    } else {
        // transaction appears to have been successful
    

// changed amount from $tx->amount to $am
// changed $data['post']['transaction_code'] to $txcode
        $bd = array(
            'amount'        =>  $am,
            'description'   =>  'SumUp transaction '.$txcode,
            'type'          =>  'transaction',
            'rel_id'        =>  $tx->id,
        );
        $client = $this->di['db']->getExistingModelById('Client', $invoice->client_id);
        // i got this from elsewhere, it seems to credit client account before
        // charging those funds - although I couldn't see anywhere in BB
        // where a clients credit is shown
        $clientService = $this->di['mod_service']('client');
        $clientService->addFunds($client, $bd['amount'], $bd['description'], $bd);
        if($tx->invoice_id) {
            $invoiceService->payInvoiceWithCredits($invoice);
        }
        $invoiceService->doBatchPayWithCredits(array('client_id'=>$client->id));

        $tx->status = 'processed';
    } // end processing for successful transaction
    
        $tx->updated_at = date('Y-m-d H:i:s');
        $this->di['db']->store($tx);
    }

    public function getInvoiceTitle(\Model_Invoice $invoice)
    {
        $invoiceItems = $this->di['db']->getAll('SELECT title from invoice_item WHERE invoice_id = :invoice_id', array(':invoice_id' => $invoice->id));

        $params = array(
            ':id'=>sprintf('%05s', $invoice->nr),
            ':serie'=>$invoice->serie,
            ':title'=>$invoiceItems[0]['title']);
        $title = __('Payment for invoice :serie:id [:title]', $params);
        if(count($invoiceItems) > 1) {
            $title = __('Payment for invoice :serie:id', $params);
        }
        return $title;
    }    
}
