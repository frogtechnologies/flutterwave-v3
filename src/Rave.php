<?php

declare(strict_types=1);

namespace Frog\FlutterwaveV3;

use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;
use Unirest\Request;
use Unirest\Request\Body;

class Rave
{
    //Api keys
    public $publicKey;
    public $secretKey;
    public $txref;
    public $type;
    protected $integrityHash;
    protected $payButtonText = 'Proceed with Payment';
    protected $redirectUrl;
    protected $meta = [];
    //protected $env;
    protected $transactionPrefix;
    // public $logger;
    protected $handler;
    protected $stagingUrl = 'https://api.flutterwave.com';
    protected $liveUrl = 'https://api.flutterwave.com';
    protected $baseUrl;
    protected $transactionData;
    protected $overrideTransactionReference;
    protected $requeryCount = 0;

    //Payment information
    protected $account;
    protected $accountno;
    protected $key;
    protected $pin;
    protected $json_options;
    protected $post_data;
    protected $options;
    protected $card_no;
    protected $cvv;
    protected $expiry_month;
    protected $expiry_year;
    protected $amount;
    protected $paymentOptions = null;
    protected $customDescription;
    protected $customLogo;
    protected $customTitle;
    protected $country;
    protected $currency;
    protected $customerEmail;
    protected $customerFirstname;
    protected $customerLastname;
    protected $customerPhone;

    //EndPoints
    protected $end_point;
    protected $authModelUsed;
    protected $flwRef;

    /**
     * Construct
     *
     * @param string $publicKey Your Rave publicKey. Sign up on https://rave.flutterwave.com to get one from your settings page
     * @param string $secretKey Your Rave secretKey. Sign up on https://rave.flutterwave.com to get one from your settings page
     * @param string $prefix This is added to the front of your transaction reference numbers
     * @param string $env This can either be 'staging' or 'live'
     * @param bool $overrideRefWithPrefix Set this parameter to true to use your prefix as the transaction reference
     *
     * @return object
     * */
    public function __construct(string $secretKey, string $prefix = 'RV', bool $overrideRefWithPrefix = false)
    {
        $this->secretKey = $secretKey;
        $this->publicKey = $_ENV['PUBLIC_KEY'];
        $this->env = $_ENV['ENV'];
        $this->transactionPrefix = $overrideRefWithPrefix ? $prefix : $prefix . '_';
        $this->overrideTransactionReference = $overrideRefWithPrefix;
        // create a log channel
        $log = new Logger('flutterwave/rave');
        $this->logger = $log;
        $log->pushHandler(new RotatingFileHandler('rave.log', 90, Logger::DEBUG));

        $this->createReferenceNumber();

        if ($this->env === 'staging') {
            $this->baseUrl = $this->stagingUrl;
        } elseif ($this->env === 'live') {
            $this->baseUrl = $this->liveUrl;
        } else {
            $this->baseUrl = $this->stagingUrl;
        }

        // set the baseurl
        //$this->baseUrl = $this->liveUrl;

        $this->logger->notice('Rave Class Initializes....');
        return $this;
    }

    /**
     * Generates a checksum value for the information to be sent to the payment gateway
     */
    public function createCheckSum(): object
    {
        $this->logger->notice('Generating Checksum....');
        $options = [
            'public_key' => $this->publicKey,
            'amount' => $this->amount,
            'tx_ref' => $this->txref,
            'currency' => $this->currency,
            'payment_options' => 'card,mobilemoney,ussd',
            'customer' => [
                'email' => $this->customerEmail,
                'phone_number' => $this->customerPhone,
                'name' => $this->customerFirstname . ' ' . $this->customerLastname,
            ],
            'redirect_url' => $this->redirectUrl,
            'customizations' => [
                'description' => $this->customDescription,
                'logo' => $this->customLogo,
                'title' => $this->customTitle,
            ],
        ];

        ksort($options);

        // $this->transactionData = $options;

        // $hashedPayload = '';

        // foreach($options as $key => $value){
        //     $hashedPayload .= $value;
        // }

        // echo $hashedPayload;
        // $completeHash = $hashedPayload.$this->secretKey;
        // $hash = hash('sha256', $completeHash);

        // $this->integrityHash = $hash;
        // return $this;
    }

    /**
     * Generates a transaction reference number for the transactions
     */
    public function createReferenceNumber(): object
    {
        $this->logger->notice('Generating Reference Number....');
        if ($this->overrideTransactionReference) {
            $this->txref = $this->transactionPrefix;
        } else {
            $this->txref = uniqid($this->transactionPrefix);
        }
        $this->logger->notice('Generated Reference Number....' . $this->txref);
        return $this;
    }

    /**
     * gets the current transaction reference number for the transaction
     */
    public function getReferenceNumber(): string
    {
        return $this->txref;
    }

    /**
     * Sets the transaction amount
     *
     * @param int $amount Transaction amount
     */
    public function setAmount(int $amount): object
    {
        $this->amount = $amount;
        return $this;
    }

    /**
     * Sets the transaction amount
     *
     * @param int $amount Transaction amount
     */
    public function setAccountNumber($accountno): object
    {
        $this->accountno = $accountno;
        return $this;
    }

    /**
     * Sets the transaction transaction card number
     *
     * @param int $card_no Transaction card number
     */
    public function setCardNo(int $card_no): object
    {
        $this->card_no = $card_no;
        return $this;
    }

    /**
     * Sets the transaction transaction CVV
     *
     * @param int $CVV Transaction CVV
     */
    public function setCVV($cvv): object
    {
        $this->cvv = $cvv;
        return $this;
    }

    /**
     * Sets the transaction transaction expiry_month
     *
     * @param int $expiry_month Transaction expiry_month
     */
    public function setExpiryMonth(int $expiry_month): object
    {
        $this->expiry_month = $expiry_month;
        return $this;
    }

    /**
     * Sets the transaction transaction expiry_year
     *
     * @param int $expiry_year Transaction expiry_year
     */
    public function setExpiryYear(int $expiry_year): object
    {
        $this->expiry_year = $expiry_year;
        return $this;
    }

    /**
     * Sets the transaction transaction end point
     *
     * @param string $end_point Transaction expiry_year
     */
    public function setEndPoint(string $end_point): object
    {
        $this->end_point = $end_point;
        return $this;
    }

    /**
     * Sets the transaction authmodel
     */
    public function setAuthModel(string $authmodel): object
    {
        $this->authModelUsed = $authmodel;
        return $this;
    }

    /**
     * gets the transaction amount
     */
    public function getAmount(): string
    {
        return $this->amount;
    }

    /**
     * Sets the allowed payment methods
     *
     * @param string $paymentOptions The allowed payment methods. Can be card, account or both
     */
    public function setPaymentOptions(string $paymentOptions): object
    {
        $this->paymentOptions = $paymentOptions;
        return $this;
    }

    /**
     * gets the allowed payment methods
     */
    public function getPaymentOptions(): string
    {
        return $this->paymentOptions;
    }

    /**
     * Sets the transaction description
     *
     * @param string $customDescription The description of the transaction
     */
    public function setDescription(string $customDescription): object
    {
        $this->customDescription = $customDescription;
        return $this;
    }

    /**
     * gets the transaction description
     */
    public function getDescription(): string
    {
        return $this->customDescription;
    }

    /**
     * Sets the payment page logo
     *
     * @param string $customLogo Your Logo
     */
    public function setLogo(string $customLogo): object
    {
        $this->customLogo = $customLogo;
        return $this;
    }

    /**
     * gets the payment page logo
     */
    public function getLogo(): string
    {
        return $this->customLogo;
    }

    /**
     * Sets the payment page title
     *
     * @param string $customTitle A title for the payment. It can be the product name, your business name or anything short and descriptive
     */
    public function setTitle(string $customTitle): object
    {
        $this->customTitle = $customTitle;
        return $this;
    }

    /**
     * gets the payment page title
     */
    public function getTitle(): string
    {
        return $this->customTitle;
    }

    /**
     * Sets transaction country
     *
     * @param string $country The transaction country. Can be NG, US, KE, GH and ZA
     */
    public function setCountry(string $country): object
    {
        $this->country = $country;
        return $this;
    }

    /**
     * gets the transaction country
     */
    public function getCountry(): string
    {
        return $this->country;
    }

    /**
     * Sets the transaction currency
     *
     * @param string $currency The transaction currency. Can be NGN, GHS, KES, ZAR, USD, EUR and GBP
     */
    public function setCurrency(string $currency): object
    {
        $this->currency = $currency;
        return $this;
    }

    /**
     * gets the transaction currency
     */
    public function getCurrency(): string
    {
        return $this->currency;
    }

    /**
     * Sets the customer email
     *
     * @param string $customerEmail This is the paying customer's email
     */
    public function setEmail(string $customerEmail): object
    {
        $this->customerEmail = $customerEmail;
        return $this;
    }

    /**
     * gets the customer email
     */
    public function getEmail(): string
    {
        return $this->customerEmail;
    }

    /**
     * Sets the customer firstname
     *
     * @param string $customerFirstname This is the paying customer's firstname
     */
    public function setFirstname(string $customerFirstname): object
    {
        $this->customerFirstname = $customerFirstname;
        return $this;
    }

    /**
     * gets the customer firstname
     */
    public function getFirstname(): string
    {
        return $this->customerFirstname;
    }

    /**
     * Sets the customer lastname
     *
     * @param string $customerLastname This is the paying customer's lastname
     */
    public function setLastname(string $customerLastname): object
    {
        $this->customerLastname = $customerLastname;
        return $this;
    }

    /**
     * gets the customer lastname
     */
    public function getLastname(): string
    {
        return $this->customerLastname;
    }

    /**
     * Sets the customer phonenumber
     *
     * @param string $customerPhone This is the paying customer's phonenumber
     */
    public function setPhoneNumber(string $customerPhone): object
    {
        $this->customerPhone = $customerPhone;
        return $this;
    }

    /**
     * gets the customer phonenumber
     */
    public function getPhoneNumber(): string
    {
        return $this->customerPhone;
    }

    /**
     * Sets the payment page button text
     *
     * @param string $payButtonText This is the text that should appear on the payment button on the Rave payment gateway.
     */
    public function setPayButtonText(string $payButtonText): object
    {
        $this->payButtonText = $payButtonText;
        return $this;
    }

    /**
     * gets payment page button text
     */
    public function getPayButtonText(): string
    {
        return $this->payButtonText;
    }

    /**
     * Sets the transaction redirect url
     *
     * @param string $redirectUrl This is where the Rave payment gateway will redirect to after completing a payment
     */
    public function setRedirectUrl(string $redirectUrl): object
    {
        $this->redirectUrl = $redirectUrl;
        return $this;
    }

    /**
     * gets the transaction redirect url
     */
    public function getRedirectUrl(): string
    {
        return $this->redirectUrl;
    }

    /**
     * Sets the transaction meta data. Can be called multiple time to set multiple meta data
     *
     * @param array $meta This are the other information you will like to store with the transaction. It is a key => value array. eg. PNR for airlines, product colour or attributes. Example. array('name' => 'femi')
     */
    public function setMetaData(array $meta): object
    {
        array_push($this->meta, $meta);
        return $this;
    }

    /**
     * gets the transaction meta data
     */
    public function getMetaData(): array
    {
        return $this->meta;
    }

    /**
     * Sets the event hooks for all available triggers
     *
     * @param object $handler This is a class that implements the Event Handler Interface
     */
    public function eventHandler(object $handler): object
    {
        $this->handler = $handler;
        return $this;
    }

    /**
     * Requerys a previous transaction from the Rave payment gateway
     *
     * @param string $referenceNumber This should be the reference number of the transaction you want to requery
     */
    public function requeryTransaction(string $referenceNumber): object
    {
        $this->txref = $referenceNumber;
        $this->requeryCount++;
        $this->logger->notice('Requerying Transaction....' . $this->txref);
        if (isset($this->handler)) {
            $this->handler->onRequery($this->txref);
        }

        $data = [
            'id' => (int) $referenceNumber,
            // 'only_successful' => '1'
        ];

        // make request to endpoint using unirest.
        $headers = ['Content-Type' => 'application/json', 'Authorization' => 'Bearer ' . $this->secretKey];
        $body = Body::json($data);
        $url = $this->baseUrl . '/v3/transactions/' . $data['id'] . '/verify';
        // Make `POST` request and handle response with unirest
        $response = Request::get($url, $headers);

//         print_r($response);

        //check the status is success
        if ($response->body && $response->body->status === 'success') {
            if ($response->body && $response->body->data && $response->body->data->status === 'successful') {
                $this->logger->notice('Requeryed a successful transaction....' . json_encode($response->body->data));
                // Handle successful
                if (isset($this->handler)) {
                    $this->handler->onSuccessful($response->body->data);
                }
            } elseif ($response->body && $response->body->data && $response->body->data->status === 'failed') {
                // Handle Failure
                $this->logger->warn('Requeryed a failed transaction....' . json_encode($response->body->data));
                if (isset($this->handler)) {
                    $this->handler->onFailure($response->body->data);
                }
            } else {
                // Handled an undecisive transaction. Probably timed out.
                $this->logger->warn('Requeryed an undecisive transaction....' . json_encode($response->body->data));
                // I will requery again here. Just incase we have some devs that cannot setup a queue for requery. I don't like this.
                if ($this->requeryCount > 4) {
                    // Now you have to setup a queue by force. We couldn't get a status in 5 requeries.
                    if (isset($this->handler)) {
                        $this->handler->onTimeout($this->txref, $response->body);
                    }
                } else {
                    $this->logger->notice('delaying next requery for 3 seconds');
                    sleep(3);
                    $this->logger->notice('Now retrying requery...');
                    $this->requeryTransaction($this->txref);
                }
            }
        } else {
            // $this->logger->warn('Requery call returned error for transaction reference.....'.json_encode($response->body).'Transaction Reference: '. $this->txref);
            // Handle Requery Error
            if (isset($this->handler)) {
                $this->handler->onRequeryError($response->body);
            }
        }
        return $this;
    }

    /**
     * Generates the final json to be used in configuring the payment call to the rave payment gateway
     */
    public function initialize(): string
    {
        $this->createCheckSum();

        echo '<html>';
        echo '<body>';
        echo '<center>Proccessing...<br /><img src="ajax-loader.gif" /></center>';

        echo '<script type="text/javascript" src="https://checkout.flutterwave.com/v3.js"></script>';

        echo '<script>';
        echo 'document.addEventListener("DOMContentLoaded", function(event) {';
        echo 'FlutterwaveCheckout({
            public_key: "' . $this->publicKey . '",
            tx_ref: "' . $this->txref . '",
            amount: ' . $this->amount . ',
            currency: "' . $this->currency . '",
            country: "' . $this->country . '",
            payment_options: "card,ussd,mpesa,barter,mobilemoneyghana,mobilemoneyrwanda,mobilemoneyzambia,mobilemoneyuganda,banktransfer,account",
            redirect_url:"' . $this->redirectUrl . '",
            customer: {
              email: "' . $this->customerEmail . '",
              phone_number: "' . $this->customerPhone . '",
              name: "' . $this->customerFirstname . ' ' . $this->customerLastname . '",
            },
            callback: function (data) {
              console.log(data);
            },
            onclose: function() {
                window.location = "?cancelled=cancelled";
              },
            customizations: {
              title: "' . $this->customTitle . '",
              description: "' . $this->customDescription . '",
              logo: "' . $this->customLogo . '",
            }
        });';
        echo '});';
        echo '</script>';
        echo '</body>';
        echo '</html>';
    }

    /**
     * this is the getKey function that generates an encryption Key for you by passing your Secret Key as a parameter.
     *
     * @param string
     */

    public function getKey($seckey): string
    {
        $hashedkey = md5($seckey);
        $hashedkeylast12 = substr($hashedkey, -12);

        $seckeyadjusted = str_replace('FLWSECK-', '', $seckey);
        $seckeyadjustedfirst12 = substr($seckeyadjusted, 0, 12);

        return $seckeyadjustedfirst12 . $hashedkeylast12;
    }

    /**
     * this is the encrypt3Des function that generates an encryption Key for you by passing your transaction Data and Secret Key as a parameter.
     *
     * @param string
     */

    public function encrypt3Des($data, $key): string
    {
        $encData = openssl_encrypt($data, 'DES-EDE3', $key, OPENSSL_RAW_DATA);
        return base64_encode($encData);
    }

    /**
     * this is the encryption function that combines the getkey() and encryptDes().
     *
     * @param string
     */

    public function encryption($options): string
    {
        //encrypt and return the key using the secrekKey
        $this->key = $_ENV['ENCRYPTION_KEY'];
        //set the data to transactionData
        $this->transactionData = $options;
        //encode the data and the
        return $this->encrypt3Des($this->transactionData, $this->key);
    }

    /**
     * makes a post call to the api
     *
     * @param array
     */

    public function postURL($data): object
    {
        // make request to endpoint using unirest

        $bearerTkn = 'Bearer ' . $this->secretKey;
        $headers = ['Content-Type' => 'application/json', 'Authorization' => $bearerTkn];
        $body = Body::json($data);
        $url = $this->baseUrl . '/' . $this->end_point;
        $response = Request::post($url, $headers, $body);
        return $response->raw_body;    // Unparsed body
    }

    public function putURL($data)
    {
        $bearerTkn = 'Bearer ' . $this->secretKey;
        $headers = ['Content-Type' => 'application/json', 'Authorization' => $bearerTkn];
        $body = Body::json($data);
        $url = $this->baseUrl . '/' . $this->end_point;
        $response = Request::put($url, $headers, $body);
        return $response->raw_body;
    }

    public function delURL($url)
    {
        $bearerTkn = 'Bearer ' . $this->secretKey;
        $headers = ['Content-Type' => 'application/json', 'Authorization' => $bearerTkn];
        //$body = Body::json($data);
        $path = $this->baseUrl . '/' . $this->end_point;
        $response = Request::delete($path . $url, $headers);
        return $response->raw_body;
    }

    /**
     * makes a get call to the api
     *
     * @param array
     */

    public function getURL($url): object
    {
        // make request to endpoint using unirest.
        $bearerTkn = 'Bearer ' . $this->secretKey;
        $headers = ['Content-Type' => 'application/json', 'Authorization' => $bearerTkn];
        //$body = Body::json($data);
        $path = $this->baseUrl . '/' . $this->end_point;
        $response = Request::get($path . $url, $headers);
        return $response->raw_body;    // Unparsed body
    }

    /**
     * verify the transaction before giving value to your customers
     *
     * @param string
     */
    public function verifyTransaction($id): object
    {
        $url = '/' . $id . '/verify';
        $this->logger->notice('Verifying transaction...');
        $this->setEndPoint('v3/transactions');
        $result = $this->getURL($url);
        return json_decode($result, true);
    
    }

    /**
     * Validate the transaction to be charged
     *
     * @param string
     */
    public function validateTransaction($otp, $ref, $type): object
    {
        $this->logger->notice('Validating otp...');
        $this->setEndPoint('v3/validate-charge');
        $this->post_data = [
            'type' => $type,//type can be card or account
            'flw_ref' => $ref,
            'otp' => $otp,
        ];
        return $this->postURL($this->post_data);
    }

    public function validateTransaction2($pin, $Ref)
    {
        $this->logger->notice('Validating pin...');
        $this->setEndPoint('v3/validate-charge');
        $this->post_data = [
            'PBFPubKey' => $this->publicKey,
            'transactionreference' => $Ref,
            'otp' => $otp,
        ];
        return $this->postURL($this->post_data);
    }

    /**
     * Get all Transactions
     */

    public function getAllTransactions(): object
    {
        $this->logger->notice('Getting all Transactions...');
        $url = '';
        $result = $this->getURL($url);
        return json_decode($result, true);
    }

    public function getTransactionFee()
    {
        $url = '';
        $result = $this->getURL($url);
        return json_decode($result, true);
    }

    public function transactionTimeline()
    {
        $url = '';
        $result = $this->getURL($url);
        return json_decode($result, true);
    }

    /**
     * Get all Settlements
     */

    public function getAllSettlements(): object
    {
        $this->logger->notice('Getting all Subscription...');
        $url = '';
        $result = $this->getURL($url);
        return json_decode($result, true);
    }

    /**
     * Validating your bvn
     *
     * @param string
     */

    public function bvn($bvn): object
    {
        $this->logger->notice('Validating bvn...');
        $url = '/' . $bvn;
        return json_decode($this->getURL($url), true);
    }

    /**
     * Get all Subscription
     */

    public function getAllSubscription(): object
    {
        $this->logger->notice('Getting all Subscription...');
        $url = '';
        return json_decode($this->getURL($url), true);
    }

    /**
     * Get a Subscription
     *
     * @param $id ,$email
     */

    public function cancelSubscription(): object
    {
        $this->logger->notice('Canceling Subscription...');
        $data = [];
        $result = $this->putURL($data);
        return json_decode($result, true);
    }

    /**
     * Get a Settlement
     *
     * @param $id ,$email
     */

    public function fetchASettlement(): object
    {
        $this->logger->notice('Fetching a Subscription...');
        $url = '?seckey=' . $this->secretKey;
        return $this->getURL($url);
    }

    /**
     * activating  a subscription
     */

    public function activateSubscription(): object
    {
        $this->logger->notice('Activating Subscription...');
        $data = [];
        return $this->putURL($data);
    }

    /**
     * Creating a payment plan
     *
     * @param array
     */

    public function createPlan($array): object
    {
        $this->logger->notice('Creating Payment Plan...');
        $result = $this->postURL($array);
        return json_decode($result, true);
    
    }

    public function updatePlan($array)
    {
        $this->logger->notice('Updating Payment Plan...');

        $result = $this->putURL($array);
        return json_decode($result, true);
    
    }

    public function cancelPlan($array)
    {
        $this->logger->notice('Canceling Payment Plan...');

        $result = $this->putURL($array);
        return json_decode($result, true);
    
    }

    public function getPlans()
    {
        $url = '';
        $result = $this->getURL($url);
        return json_decode($result, true);
    
    }

    public function get_a_Plan()
    {
        $url = '';
        $result = $this->getURL($url);
        return json_decode($result, true);
    
    }

    /**
     * Creating a beneficiary
     *
     * @param array
     */

    public function createBeneficiary($array): object
    {
        $this->logger->notice('Creating beneficiaries ...');
        $result = $this->postURL($array);
        return json_decode($result, true);
    
    }

    /**
     * get  beneficiaries
     *
     * @param array
     */

    public function getBeneficiaries(): object
    {
        $url = '';
        $result = $this->getURL($url);
        return json_decode($result, true);
    
    }

    /**
     * transfer payment api
     *
     * @param array
     */

    public function transferSingle($array): object
    {
        $this->logger->notice('Processing transfer...');
        $result = $this->postURL($array);
        return json_decode($result, true);
    
    }

    public function deleteBeneficiary()
    {
        $url = '';
        $result = $this->delURL($url);
        return json_decode($result, true);
    
    }

    /**
     * bulk transfer payment api
     *
     * @param array
     */

    public function transferBulk($array): object
    {
        $this->logger->notice('Processing bulk transfer...');
        $result = $this->postURL($array);
        return json_decode($result, true);
    
    }

    /**
     * Refund payment api
     *
     * @param array
     */

    public function refund($array): object
    {
        $this->logger->notice('Initiating a refund...');
        $result = $this->postURL($array);
        return json_decode($result, true);
    
    }

    /**
     * Generates the final json to be used in configuring the payment call to the rave payment gateway api
     *
     * @param array
     */

    public function chargePayment($array): object
    {

        //remove the type param from the payload

        $this->options = $array;

        if ($this->type === 'card') {
            $this->json_options = json_encode($this->options);
            $this->logger->notice('Checking payment details..');
            //encrypt the required options to pass to the server
            $this->integrityHash = $this->encryption($this->json_options);
            $this->post_data = [
                'client' => $this->integrityHash,
            ];

            $result = $this->postURL($this->post_data);
            // the result returned requires validation
            $result = json_decode($result, true);
            // echo '<pre>';
            // print_r($result);
            // echo '</pre>';

            if ($result['status'] === 'success') {
                if ($result['meta']['authorization']['mode'] === 'pin' || $result['meta']['authorization']['mode'] === 'avs_noauth'
                    || $result['meta']['authorization']['mode'] === 'redirect' || $result['meta']['authorization']['mode'] === 'otp') {
                    $this->logger->notice('Payment requires otp validation...authmodel:' . $result['meta']['authorization']['mode']);
                    $this->authModelUsed = $result['meta']['authorization']['mode'];

                    if ($this->authModelUsed === 'redirect') {
                        header('Location:' . $result['meta']['authorization']['redirect']);
                    }

                    if ($this->authModelUsed === 'pin' || $this->authModelUsed === 'avs_noauth') {
                        return $result;
                    }

                    if ($this->authModelUsed === 'otp') {
                        $this->flwRef = $result['data']['flw_ref'];
                        return ['data' => ['flw_ref' => $this->flwRef, 'id' => $result['data']['id'], 'auth_mode' => $result['meta']['authorization']['mode']]];
                    }
                }
            } else {
                return '<div class="alert alert-danger text-center">' . $result['message'] . '</div>';
            }

            //passes the result to the suggestedAuth function which re-initiates the charge
        } elseif ($this->type === 'momo') {
            $result = $this->postURL($array);
            $result = json_decode($result, true);

            // print_r($result['meta']);
            //echo "momo payment";
            if (isset($result['meta']['authorization'])) {
                header('Location:' . $result['meta']['authorization']['redirect']);
            }
        } else {
            $result = $this->postURL($array);
            // the result returned requires validation
            $result = json_decode($result, true);

            if (isset($result['meta']['redirect'])) {
                header('Location:' . $result['meta']['redirect']);
            }

            if (isset($result['data']['status'])) {
                $this->logger->notice('Payment requires otp validation...');
                $this->authModelUsed = $result['data']['auth_model'];
                $this->flwRef = $result['data']['flw_ref'];
                $this->txref = $result['data']['tx_ref'];
            }

            return $result;
        }
    }

    /**
     * sends a post request to the virtual APi set by the user
     *
     * @param array
     */

    public function vcPostRequest($array): object
    {
        $this->post_data = $array;
        //post the data to the API
        $result = $this->postURL($this->post_data);
        //decode the response
        return json_decode($result, true);
        //return result
        // return $result;
    }

    public function vcGetRequest()
    {
        $url = '';
        $result = $this->getURL($url);
        return json_decode($result, true);
    
    }

    public function vcPutRequest($array = [])
    {
        $result = $this->putURL($array);
        return json_decode($result, true);
    
    }

    /**
     * Used to create sub account on the rave dashboard
     *
     * @param array
     */
    public function createSubaccount($array): object
    {
        $this->options = $array;
        $this->logger->notice('Creating Sub account...');
        //pass $this->options to the postURL function to call the api
        $result = $this->postURL($this->options);
        return json_decode($result, true);
    
    }

    public function getSubaccounts()
    {
        $url = '';
        //pass $this->options to the postURL function to call the api
        $result = $this->getURL($url);
        return json_decode($result, true);
    
    }

    public function fetchSubaccount()
    {
        $url = '';
        //pass $this->options to the postURL function to call the api
        $result = $this->getURL($url);
        return json_decode($result, true);
    
    }

    public function updateSubaccount($array)
    {
        $this->options = $array;
        $this->logger->notice('updating Sub account...');
        //pass $this->options to the postURL function to call the api
        $result = $this->putURL($this->options);
        return json_decode($result, true);
    
    }

    public function deleteSubaccount($array = [])
    {
        $this->logger->notice('deleting  Sub account...');
        //pass $this->options to the postURL function to call the api
        $result = $this->putURL($array);
        return json_decode($result, true);
    
    }

    /**
     * Handle canceled payments with this method
     *
     * @param string $referenceNumber This should be the reference number of the transaction that was canceled
     */
    public function paymentCanceled(string $referenceNumber): object
    {
        $this->logger->notice('Payment was canceled by user..' . $this->txref);
        if (isset($this->handler)) {
            $this->handler->onCancel($referenceNumber);
        }
        return $this;
    }

    /**
     * This is used to create virtual account for a merchant.
     */
    public function createVirtualAccount(string $array): object
    {
        $this->options = $array;
        $this->logger->notice('creating virtual account..');
        return $this->postURL($this->options);
    }

    /**
     * Create bulk virtual accounts with this method
     */

    public function createBulkAccounts(string $array): object
    {
        $this->options = $array;
        $this->logger->notice('creating bulk virtual account..');
        return $this->postURL($this->options);
    }

    /**
     * Get  bulk virtual virtual cards method
     */

    public function getBulkAccounts(): object
    {
        $url = '';
        $result = $this->getURL($url);
        return json_decode($result, true);
    }

    /**
     * Create an Order with this method
     */

    public function createOrder(string $array): object
    {
        $this->logger->notice('creating Ebill order for customer with email: ' . $array['email']);

        if (empty($array['narration']) || ! array_key_exists('narration', $array)) {
            $array['narration'] = '';
        }
        if (empty($data['IP'])) {
            $array['IP'] = '10.30.205.3';
        }
        if (! isset($array['custom_business_name']) || empty($array['custom_business_name'])) {
            $array['custom_business_name'] = '';
        }

        if (empty($array['number_of_units']) || ! array_key_exists('number_of_units', $array)) {
            $array['number_of_units'] = '1';
        }

        $data = [
            'narration' => $array['narration'],
            'number_of_units' => $array['number_of_units'],
            'currency' => $array['currency'],
            'amount' => $array['amount'],
            'phone_number' => $array['phone_number'],
            'email' => $array['email'],
            'tx_ref' => $array['tx_ref'],
            'ip' => $array['ip'],
            'country' => $array['country'],
            'custom_business_name' => $array['custom_business_name'],
        ];
        $result = $this->postURL($data);
        return json_decode($result, true);
    
    }

    /**
     * Update an Order with this method
     */
    public function updateOrder(string $array): object
    {
        $this->logger->notice('updating Ebill order..');

        $data = [
            'amount' => $array['amount'],
            'currency' => 'NGN',// only NGN can be passed
        ];

        $result = $this->putURL($data);
        return json_decode($result, true);
    
    }

    /**
     * pay bill or query bill information with this method
     */

    public function bill(string $array): object
    {
        if (! isset($array['type'])) {
            return ['Type' => 'Missing the type property in the payload'];
        }

        $this->logger->notice($array['type'] . ' Billing ...');

        $data = [];
        $data['type'] = $array['type'];
        $data['country'] = $array['country'];
        $data['customer'] = $array['customer'];
        $data['amount'] = $array['amount'];
        $data['recurrence'] = $array['recurrence'];
        $data['reference'] = $array['reference'];
        $result = $this->postUrl($data);

        return json_decode($result, true);
    
    }

    public function bulkBills($array)
    {
        $data = $array;

        $result = $this->postUrl($data);

        return json_decode($result, true);
    
    }

    public function getBill($array)
    {
        if (array_key_exists('reference', $array) && ! array_key_exists('from', $array)) {
            $url = '/' . $array['reference'];
        } elseif (array_key_exists('code', $array) && ! array_key_exists('customer', $array)) {
            $url = '/' . $array['item_code'];
        } elseif (array_key_exists('id', $array) && array_key_exists('product_id', $array)) {
            $url = '/' . $array['id'] . '/products/' . $array['product_id'];
        } elseif (array_key_exists('from', $array) && array_key_exists('to', $array)) {
            if (isset($array['page']) && isset($array['reference'])) {
                $url = '?from=' . $array['from'] . '&' . $array['to'] . '&' . $array['page'] . '&' . $array['reference'];
            } else {
                $url = '?from=' . $array['from'] . '&' . $array['to'];
            }
        }

        return $this->getURL($url);
    }

    public function getBillers()
    {
        $url = '/billers';
        return $this->getURL($url);
    }

    public function getBillCategories()
    {
        $url = '/bill-categories';
        return $this->getURL($url);
    }

    public function tokenCharge($array)
    {
        $data = $array;

        if (! isset($data['token']) && ! isset($data['currency']) &&
            ! isset($data['country']) && ! isset($data['amount']) &&
            ! isset($data['tx_ref']) && ! isset($data['email'])) {
            return ['error' => 'Your payload is missing all properties'];
        }

        $result = $this->postUrl($array);

        return json_decode($result, true);
    
    }

    /**
     * List of all transfers with this method
     */

    public function listTransfers(string $data): object
    {
        $this->logger->notice('Fetching list of transfers...');

        if (isset($data['page'])) {
            $url = '?page=' . $data['page'];
            return json_decode($this->getURL($url), true);
        }
        if (isset($data['page']) && isset($data['status'])) {
            $url = '?page' . $data['page'] . '&status' . $data['status'];
            return json_decode($this->getURL($url), true);
        }
        if (isset($data['status'])) {
            $url = '?status=' . $data['status'];
            return json_decode($this->getURL($url), true);
        }

        $url = '';
        return json_decode($this->getURL($url), true);

    
    }

    /**
     * Check  a bulk transfer status with this method
     */

    public function bulkTransferStatus(string $data): object
    {
        $this->logger->notice('Checking bulk transfer status...');
        $url = '?batch_id=' . $data['batch_id'];
        return $this->getURL($url);
    }

    /**
     * Check applicable fees with this method
     */

    public function applicableFees(string $data): object
    {
        $this->logger->notice('Fetching applicable fees...');
        $url = '?currency=' . $data['currency'] . '&amount=' . $data['amount'];
        return $this->getURL($url);
    }

    /**
     * Retrieve Transfer balance with this method
     */

    public function getTransferBalance(string $array): object
    {
        $this->logger->notice('Fetching Transfer Balance...');
        if (empty($array['currency'])) {
            $array['currency'] === 'NGN';
        }
        $data = [
            'currency' => $array['currency'],
        ];
        return $this->postURL($data);
    }

    /**
     * Verify an Account to Transfer to with this method
     */

    public function verifyAccount(string $array): object
    {
        $this->logger->notice('Verifying transfer recipents account...');
        $data = [
            'account_number' => $array['account_number'],
            'account_bank' => $array['account_bank'],
        ];
        return $this->postURL($data);
    }

    /**
     * Lists banks for Transfer with this method
     */

    public function getBanksForTransfer(): object
    {
        $this->logger->notice('Fetching banks available for Transfer...');

        //get banks for transfer
        $url = '';
        $result = $this->getURL($url);
    }

    /**
     * Captures funds this method
     */

    public function captureFunds(string $array): object
    {
        $this->logger->notice('capturing funds for flw_ref: ' . $array['flw_ref'] . ' ...');
        unset($array['flw_ref']);
        $data = [
            'amount' => $array['amount'],
        ];
        return $this->postURL($data);
    }

    public function getvAccountsNum()
    {
        $url = '';
        $result = $this->getURL($url);
        return json_decode($result, true);
    
    }

    /**
     * Void a Preauthorized fund with this method
     */

    public function void(string $array): object
    {
        $this->logger->notice('voided a captured fund with the flw_ref=' . $array['flw_ref']);
        unset($array['flw_ref']);
        $data = [];
        return $this->postURL($data);
    }

    /**
     * Refund a Preauthorized fund with this method
     */

    public function preRefund(string $array): object
    {
        $this->logger->notice('refunding a captured fund with the flw_ref=' . $array['flw_ref']);
        unset($array['flw_ref']);
        $data = [
            'amount' => $array['amount'],
        ];
        return $this->postURL($data);
    }
}
