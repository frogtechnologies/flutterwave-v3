<?php

declare(strict_types=1);

namespace Frog\FlutterwaveV3;

class Mpesa
{
    public function __construct()
    {
        $this->payment = new Rave($_SERVER['SECRET_KEY']);
        $this->type = 'mpesa';
    }

    public function mpesa($array)
    {
        //add tx_ref to the paylaod
        if (! isset($array['tx_ref']) || empty($array['tx_ref'])) {
            $array['tx_ref'] = $this->payment->txref;
        }

        $this->payment->type = 'mpesa';

        //set the payment handler
        $this->payment->eventHandler(new MpesaEventHandler())
            //set the endpoint for the api call
            ->setEndPoint('v3/charges?type=' . $this->payment->type);
        //returns the value from the results

        MpesaEventHandler::startRecording();
        $response = $this->payment->chargePayment($array);
        MpesaEventHandler::sendAnalytics('Initiate-Mpesa');

        return $response;
    }

    /*you will need to verify the charge
     * After validation then verify the charge with the txRef
     * You can write out your function to execute when the verification is successful in the onSuccessful function
     */
    public function verifyTransaction()
    {
        //verify the charge
        return $this->payment->verifyTransaction($this->payment->txref);//Uncomment this line if you need it
    }
}
