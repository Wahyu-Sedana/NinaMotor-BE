<?php

namespace App\Services;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Illuminate\Support\Facades\Log;

class FirebaseService
{
    protected $messaging;

    public function __construct()
    {
        try {
            $factory = (new Factory)
                ->withServiceAccount(storage_path('app/ninamotor-53934-firebase-adminsdk-fbsvc-1008728fde.json'))
                ->withProjectId('ninamotor-53934');

            $this->messaging = $factory->createMessaging();
        } catch (\Exception $e) {
            Log::error('Firebase initialization error: ' . $e->getMessage());
        }
    }

    public function sendNotificationToAdmin($title, $body, $data = [])
    {
        try {
            $result = $this->sendToTopic('admin-notifications', $title, $body, $data);

            if ($result) {
                Log::info('Firebase notification sent to admin topic successfully');
            } else {
                Log::warning('Failed to send Firebase notification to admin topic');
            }

            return $result;
        } catch (\Exception $e) {
            Log::error('Firebase admin notification error: ' . $e->getMessage());
            return false;
        }
    }

    public function sendToTopic($topic, $title, $body, $data = [])
    {
        try {
            $notification = Notification::create($title, $body);

            $message = CloudMessage::withTarget('topic', $topic)
                ->withNotification($notification)
                ->withData($data);

            $this->messaging->send($message);
            Log::info('Firebase topic notification sent to: ' . $topic);
            return true;
        } catch (\Exception $e) {
            Log::error('Firebase topic notification error: ' . $e->getMessage());
            return false;
        }
    }

    public function sendToSpecificUser($userToken, $title, $body, $data = [])
    {
        try {
            if (empty($userToken)) {
                return false;
            }

            $notification = Notification::create($title, $body);

            $message = CloudMessage::withTarget('token', $userToken)
                ->withNotification($notification)
                ->withData($data);

            $this->messaging->send($message);
            Log::info('Firebase notification sent to user');
            return true;
        } catch (\Exception $e) {
            Log::error('Firebase user notification error: ' . $e->getMessage());
            return false;
        }
    }
}
