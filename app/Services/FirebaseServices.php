<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging;
class FirebaseServices
{
    protected $messaging;
    public function __construct()
    {

        $factory = (new Factory)->withServiceAccount(storage_path('app/firebase_credentials.json'));
        $this->messaging = $factory->createMessaging();
    }

    public function sendNotification($user, $action)
    {
        $token = $user->fcm_token;
        if ($token) {
            $title = 'Notification';
            $body = $this->getActionMessage($action);
            $this->sendNotificationToDevice($token, $title, $body);
        }
    }



    protected function sendNotificationToDevice($token, $title, $body)
    {
        $message = Messaging\CloudMessage::withTarget('token', $token)
            ->withNotification(Messaging\Notification::create($title, $body));

        try {
            $this->messaging->send($message);
            // يمكنك تسجيل رسالة نجاح هنا إذا كنت ترغب في ذلك
            Log::info('Notification sent successfully to token: ' . $token."\n".$body);
        } catch (\Kreait\Firebase\Exception\MessagingException $e) {
            // يمكنك التعامل مع الأخطاء هنا
            Log::error('Failed to send notification: ' . $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('An error occurred: ' . $e->getMessage());
        }
    }


    public function sendNotificationToAll($action)
    {    $user = Auth::user();
        $userName=$user->full_name;
        $users = User::whereNotNull('fcm_token')->get();

        $title = 'Notification';
        $body = $this->getActionMessage($action,$userName);

        foreach ($users as $user) {
            $this->sendNotificationToDevice($user->fcm_token, $title, $body);
        }
    }


    protected function getActionMessage($action,$userName=null)
    {
        $messages = [
            'checkIn' => 'the file is reserved by '.$userName ,
            'checkOut' => 'the file is free',
            'updateFile' => 'file updated ',
            'removeUser'=>'removing user from group',
            'addUsers'=>'adding user to group',
            'createGroup'=>'creating group',
            'reject'=>'request rejecting',
            'accept'=>' request accepted',
            'uploadToGroup'=>'uploading file'


        ];
        return $messages[$action] ?? 'Default message';
    }
}
