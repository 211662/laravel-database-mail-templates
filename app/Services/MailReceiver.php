<?php

namespace App\Services;

use App\Models\TempEmail;
use App\Models\InboxMessage;
use App\Models\Attachment;
use App\Events\NewEmailReceived;
use Exception;

/**
 * Mail Receiver Service
 * Handles receiving and processing incoming emails
 */
class MailReceiver
{
    /**
     * Fetch new emails for a temp email address
     * This is a simplified version - in production, you'd use IMAP
     */
    public function fetchNewMails(TempEmail $tempEmail): int
    {
        try {
            // In a real implementation, you would:
            // 1. Connect to IMAP server
            // 2. Login with temp email credentials
            // 3. Fetch unread messages
            // 4. Process each message
            
            // For now, this is a placeholder
            // You can integrate with packages like:
            // - webklex/php-imap
            // - barbushin/php-imap
            
            return 0;
        } catch (Exception $e) {
            \Log::error('Failed to fetch emails for ' . $tempEmail->email, [
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }

    /**
     * Store a received message
     */
    public function storeMessage(TempEmail $tempEmail, array $messageData): InboxMessage
    {
        $inboxMessage = InboxMessage::create([
            'temp_email_id' => $tempEmail->id,
            'message_id' => $messageData['message_id'],
            'from_address' => $messageData['from_address'],
            'from_name' => $messageData['from_name'] ?? null,
            'subject' => $messageData['subject'] ?? null,
            'body_html' => $messageData['body_html'] ?? null,
            'body_text' => $messageData['body_text'] ?? null,
            'has_attachments' => !empty($messageData['attachments']),
            'received_at' => $messageData['received_at'] ?? now(),
        ]);

        // Store attachments if any
        if (!empty($messageData['attachments'])) {
            foreach ($messageData['attachments'] as $attachmentData) {
                $this->storeAttachment($inboxMessage, $attachmentData);
            }
        }

        // Fire event
        event(new NewEmailReceived($inboxMessage));

        return $inboxMessage;
    }

    /**
     * Store an attachment
     */
    protected function storeAttachment(InboxMessage $message, array $attachmentData): Attachment
    {
        return Attachment::create([
            'inbox_message_id' => $message->id,
            'filename' => $attachmentData['filename'],
            'content_type' => $attachmentData['content_type'],
            'size' => $attachmentData['size'],
            'storage_path' => $attachmentData['storage_path'],
        ]);
    }

    /**
     * Process webhook from email service (for services like Mailgun, SendGrid)
     */
    public function processWebhook(array $webhookData): ?InboxMessage
    {
        // Extract temp email from recipient
        $recipient = $webhookData['recipient'] ?? null;
        
        if (!$recipient) {
            return null;
        }

        $tempEmail = TempEmail::where('email', $recipient)->first();
        
        if (!$tempEmail || $tempEmail->isExpired()) {
            return null;
        }

        // Parse webhook data and store message
        $messageData = [
            'message_id' => $webhookData['message_id'] ?? uniqid('msg_'),
            'from_address' => $webhookData['from'] ?? 'unknown@example.com',
            'from_name' => $webhookData['from_name'] ?? null,
            'subject' => $webhookData['subject'] ?? '(No Subject)',
            'body_html' => $webhookData['body_html'] ?? null,
            'body_text' => $webhookData['body_text'] ?? null,
            'attachments' => $webhookData['attachments'] ?? [],
            'received_at' => $webhookData['timestamp'] ?? now(),
        ];

        return $this->storeMessage($tempEmail, $messageData);
    }
}
