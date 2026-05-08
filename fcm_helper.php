<?php
/**
 * FCM Helper for GIITChat
 * Handles sending push notifications via Firebase Cloud Messaging
 */

class FCMHelper
{
    // Replace with your Firebase Server Key (Legacy) or OAuth2 Token (v1)
    // For now, using a placeholder. The user should provide this.
    private static $serverKey = 'YOUR_FCM_SERVER_KEY'; 

    /**
     * Send notification to a specific FCM token
     */
    public static function send($token, $title, $body, $data = [])
    {
        if (empty($token))
            return false;

        $url = 'https://fcm.googleapis.com/fcm/send';

        $notification = [
            'title' => $title,
            'body' => $body,
            'icon' => 'images/message.png',
            'sound' => 'default',
            'click_action' => 'student_message.php'
        ];

        $payload = [
            'to' => $token,
            'notification' => $notification,
            'data' => $data,
            'priority' => 'high'
        ];

        $headers = [
            'Authorization: key=' . self::$serverKey,
            'Content-Type: application/json'
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $httpCode === 200;
    }

    /**
     * Send notification to multiple tokens
     */
    public static function sendToMultiple($tokens, $title, $body, $data = [])
    {
        if (empty($tokens))
            return false;

        // FCM limit for multicast is 1000 tokens
        $chunks = array_chunk($tokens, 1000);
        $overallSuccess = true;

        foreach ($chunks as $chunk) {
            $url = 'https://fcm.googleapis.com/fcm/send';
            $payload = [
                'registration_ids' => $chunk,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                    'icon' => 'images/message.png',
                    'sound' => 'default',
                    'click_action' => 'student_message.php'
                ],
                'data' => $data,
                'priority' => 'high'
            ];

            $headers = [
                'Authorization: key=' . self::$serverKey,
                'Content-Type: application/json'
            ];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

            curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode !== 200)
                $overallSuccess = false;
        }

        return $overallSuccess;
    }
}
