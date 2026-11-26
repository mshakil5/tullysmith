<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FcmNotification;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    public function __construct(protected Messaging $messaging) {}

    public function sendToUser(int $userId, string $title, string $body, string $type, array $data = []): void
    {
        $user = User::find($userId);
        if (!$user) return;

        $this->saveToDb($userId, $title, $body, $type, $data);
        $this->sendFcm([$user], $title, $body, $type, $data);
    }

    public function sendToUsers(array $userIds, string $title, string $body, string $type, array $data = []): void
    {
        $users = User::whereIn('id', $userIds)->get();
        foreach ($users as $user) {
            $this->saveToDb($user->id, $title, $body, $type, $data);
        }
        $this->sendFcm($users->all(), $title, $body, $type, $data);
    }

    public function sendToAll(string $title, string $body, string $type, array $data = []): void
    {
        $users = User::whereNotNull('fcm_token')->get();
        foreach ($users as $user) {
            $this->saveToDb($user->id, $title, $body, $type, $data);
        }
        $this->sendFcm($users->all(), $title, $body, $type, $data);
    }

    private function saveToDb(int $userId, string $title, string $body, string $type, array $data): void
    {
        Notification::create([
            'user_id' => $userId,
            'title'   => $title,
            'body'    => $body,
            'type'    => $type,
            'data'    => $data,
        ]);
    }

    private function sendFcm(array $users, string $title, string $body, string $type, array $data): void
    {
        $tokens = collect($users)
            ->filter(fn($u) => !empty($u->fcm_token))
            ->pluck('fcm_token')
            ->values()
            ->all();

        if ($type === 'attendance_reminder') {
            foreach ($users as $user) {
                if (empty($user->fcm_token)) AppIncident::record(['source'=>'server','operation'=>'attendance_reminder','kind'=>'push_not_registered','user_id'=>$user->id]);
            }
        }
        if (empty($tokens)) return;

        $payload = array_merge($data, ['type' => $type]);

        foreach (array_chunk($tokens, 500) as $chunk) {
            try {
                $message = CloudMessage::new()
                    ->withNotification(FcmNotification::create($title, $body))
                    ->withData($payload);
                $report = $this->messaging->sendMulticast($message, $chunk);
                if ($type === 'attendance_reminder') {
                    foreach ($report->getItems() as $item) {
                        foreach ($users as $user) {
                            if ($user->fcm_token === $item->target()->value()) {
                                AppIncident::record(['source'=>'server','operation'=>'attendance_reminder','kind'=>$item->isSuccess()?'push_accepted':'push_failed','user_id'=>$user->id]);
                            }
                        }
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('FCM send failed');
                if ($type === 'attendance_reminder') foreach ($users as $user) { if (in_array($user->fcm_token, $chunk, true)) AppIncident::record(['source'=>'server','operation'=>'attendance_reminder','kind'=>'push_failed','user_id'=>$user->id]); }
            }
        }
    }
}