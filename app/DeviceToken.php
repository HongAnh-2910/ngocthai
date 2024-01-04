<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Google_Client;
use Google_Exception;
use Google_Service_FirebaseCloudMessaging;
use Illuminate\Support\Facades\Log;
use App\Utils\Util;

class DeviceToken extends Model
{
    protected $guarded = ['id'];

    const JSON_PATH = '/firebase-cloud-messaging.json';
    const FIREBASE_URL = 'https://fcm.googleapis.com/v1/projects/ngoc-thai-a941b/messages:send';

    public function validateSaveData($input){
        $existToken = DeviceToken::where('user_id', $input['user_id'])
            ->where('token', $input['device_token'])
            ->first();

        if(!empty($existToken)){
            return [
                'success' => false,
                'message' => 'Token đã được gán cho user.'
            ];
        }

        return false;
    }

    public function sendNotifyWeb($user_id, $payment){
        $oauthToken = $this->getTokenFcm();
        if (empty($oauthToken)) {
            return false;
        }

        try {
            $util = new Util;
            $confirm_payment_types = $util->confirmPaymentTypes();
            if(isset($confirm_payment_types[$payment->type])){
                $confirm_payment_type = $confirm_payment_types[$payment->type];
            }else{
                $confirm_payment_type = $confirm_payment_types['normal'];
            }

            $amount = $payment->amount;
            if ($payment->type == 'expense') {
                $amount *= -1;
            }

            if($payment->approval_status == 'approved'){
                $title = __('sale.approved_notify_title');
                $message = __(
                    'sale.payment_confirmed_message_push',
                    [
                        'payment_type' => $confirm_payment_type,
                        'invoice_no' => !empty($payment->transaction->invoice_no) ? $payment->transaction->invoice_no : '',
                        'amount' => number_format($amount)
                    ]
                );
            }elseif($payment->approval_status == 'reject'){
                $title = __('sale.reject_notify_title');
                $message = __(
                    'sale.payment_reject_message_push',
                    [
                        'payment_type' => $confirm_payment_type,
                        'invoice_no' => !empty($payment->transaction->invoice_no) ? $payment->transaction->invoice_no : '',
                        'amount' => number_format($amount)
                    ]
                );
            }else{
                $title = __('sale.unconfirmed_notify_title');
                $message = __(
                    'sale.payment_unconfirmed_message_push',
                    [
                        'payment_type' => $confirm_payment_type,
                        'invoice_no' => !empty($payment->transaction->invoice_no) ? $payment->transaction->invoice_no : '',
                        'amount' => number_format($amount)
                    ]
                );
            }

//            $link = action('SellPosController@listTransactionPayments');

            $messageInfo = [
                'title'  => $title,
                'message' => $message,
            ];

            $tokens = DeviceToken::where('user_id', $user_id)
                ->pluck('token')
                ->toArray();

            foreach ($tokens as $token){
                $response = $this->getBodyRequest($token, $oauthToken, $messageInfo);

                if (!empty($response['error'])) {
                    Log::error('Send FCM message failed. Device token: '.$token.'. Response: ' . json_encode($response));
                }else{
                    Log::info('Send FCM message success. Response: ' . json_encode($response));
                }
            }
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());
        }

        return false;
    }

    private function generateToken(Google_Client $client)
    {
        $client->fetchAccessTokenWithAssertion();
        return $client->getAccessToken();
    }

    public function getTokenFcm(){
        $client = new Google_Client();
        $oauthToken= '';

        try {
            $client->setAuthConfig(base_path() . self::JSON_PATH);
            $client->addScope(Google_Service_FirebaseCloudMessaging::CLOUD_PLATFORM);

            $accessToken = $this->generateToken($client);
            $client->setAccessToken($accessToken);

            $oauthToken = $accessToken["access_token"];

        } catch (Google_Exception $e) {
            Log::error("Can't generate access_token. Reason: " . $e->getMessage());
        }

        return $oauthToken;
    }

    public function getBodyRequest($token, $oauthToken, $messageInfo){
        $body['message'] = [
            'token'        => $token,
            'notification' => [
                'title'    => $messageInfo['title'],
                'body'     => $messageInfo['message']
            ]
        ];

        $headers = [
            'Authorization: Bearer ' . $oauthToken,
            "Content-Type: text/plain"
        ];

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL             => self::FIREBASE_URL,
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_ENCODING        => "",
            CURLOPT_MAXREDIRS       => 10,
            CURLOPT_TIMEOUT         => 0,
            CURLOPT_FOLLOWLOCATION  => true,
            CURLOPT_HTTP_VERSION    => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST   => "POST",
            CURLOPT_POSTFIELDS      => json_encode($body),
            CURLOPT_HTTPHEADER      => $headers,
        ));

        $response = curl_exec($curl);
        curl_close($curl);

        $response = json_decode($response, 1);

        return $response;
    }
}
