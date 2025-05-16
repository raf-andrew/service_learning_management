<?php

namespace App\Services;

use App\Models\User;
use App\Models\Notification;
use App\Models\NotificationTemplate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FirebaseNotification;

class NotificationService
{
    protected $notification;
    protected $template;
    protected $firebase;

    public function __construct(Notification $notification, NotificationTemplate $template)
    {
        $this->notification = $notification;
        $this->template = $template;
        $this->firebase = app('firebase.messaging');
    }

    public function sendEmailNotification(User $user, string $type, array $data = [])
    {
        try {
            // Get email template
            $template = $this->template->where('type', $type)
                ->where('channel', 'email')
                ->first();

            if (!$template) {
                throw new \Exception("Email template not found for type: {$type}");
            }

            // Replace placeholders in template
            $subject = $this->replacePlaceholders($template->subject, $data);
            $content = $this->replacePlaceholders($template->content, $data);

            // Send email
            Mail::send('emails.notification', [
                'content' => $content,
                'user' => $user
            ], function ($message) use ($user, $subject) {
                $message->to($user->email)
                    ->subject($subject);
            });

            // Create notification record
            $this->notification->create([
                'user_id' => $user->id,
                'type' => $type,
                'channel' => 'email',
                'subject' => $subject,
                'content' => $content,
                'data' => $data,
                'status' => 'sent'
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Email notification failed: ' . $e->getMessage());
            throw new \Exception('Email notification failed: ' . $e->getMessage());
        }
    }

    public function sendInAppNotification(User $user, string $type, array $data = [])
    {
        try {
            // Get in-app template
            $template = $this->template->where('type', $type)
                ->where('channel', 'in_app')
                ->first();

            if (!$template) {
                throw new \Exception("In-app template not found for type: {$type}");
            }

            // Replace placeholders in template
            $title = $this->replacePlaceholders($template->subject, $data);
            $content = $this->replacePlaceholders($template->content, $data);

            // Create notification record
            $notification = $this->notification->create([
                'user_id' => $user->id,
                'type' => $type,
                'channel' => 'in_app',
                'subject' => $title,
                'content' => $content,
                'data' => $data,
                'status' => 'unread'
            ]);

            // Broadcast to user's channel if using WebSockets
            if (config('broadcasting.default') === 'pusher') {
                broadcast(new \App\Events\NewNotification($notification))->toOthers();
            }

            return $notification;
        } catch (\Exception $e) {
            Log::error('In-app notification failed: ' . $e->getMessage());
            throw new \Exception('In-app notification failed: ' . $e->getMessage());
        }
    }

    public function sendPushNotification(User $user, string $type, array $data = [])
    {
        try {
            // Get push template
            $template = $this->template->where('type', $type)
                ->where('channel', 'push')
                ->first();

            if (!$template) {
                throw new \Exception("Push template not found for type: {$type}");
            }

            // Replace placeholders in template
            $title = $this->replacePlaceholders($template->subject, $data);
            $content = $this->replacePlaceholders($template->content, $data);

            // Create notification record
            $notification = $this->notification->create([
                'user_id' => $user->id,
                'type' => $type,
                'channel' => 'push',
                'subject' => $title,
                'content' => $content,
                'data' => $data,
                'status' => 'sent'
            ]);

            // Send push notification if user has FCM token
            if ($user->fcm_token) {
                $message = CloudMessage::withTarget('token', $user->fcm_token)
                    ->withNotification(FirebaseNotification::create($title, $content))
                    ->withData($data);

                $this->firebase->send($message);
            }

            return $notification;
        } catch (\Exception $e) {
            Log::error('Push notification failed: ' . $e->getMessage());
            throw new \Exception('Push notification failed: ' . $e->getMessage());
        }
    }

    public function markAsRead(Notification $notification)
    {
        try {
            $notification->update(['status' => 'read']);
            return $notification;
        } catch (\Exception $e) {
            Log::error('Failed to mark notification as read: ' . $e->getMessage());
            throw new \Exception('Failed to mark notification as read: ' . $e->getMessage());
        }
    }

    public function markAllAsRead(User $user)
    {
        try {
            $this->notification->where('user_id', $user->id)
                ->where('status', 'unread')
                ->update(['status' => 'read']);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to mark all notifications as read: ' . $e->getMessage());
            throw new \Exception('Failed to mark all notifications as read: ' . $e->getMessage());
        }
    }

    public function getUserNotifications(User $user, array $filters = [])
    {
        try {
            $query = $this->notification->where('user_id', $user->id);

            if (isset($filters['type'])) {
                $query->where('type', $filters['type']);
            }

            if (isset($filters['channel'])) {
                $query->where('channel', $filters['channel']);
            }

            if (isset($filters['status'])) {
                $query->where('status', $filters['status']);
            }

            return $query->orderBy('created_at', 'desc')->get();
        } catch (\Exception $e) {
            Log::error('Failed to get user notifications: ' . $e->getMessage());
            throw new \Exception('Failed to get user notifications: ' . $e->getMessage());
        }
    }

    protected function replacePlaceholders(string $text, array $data)
    {
        foreach ($data as $key => $value) {
            $text = str_replace("{{$key}}", $value, $text);
        }
        return $text;
    }
} 