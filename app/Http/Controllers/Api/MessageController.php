<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InboxMessage;
use App\Models\Attachment;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MessageController extends Controller
{
    /**
     * Get a specific message
     */
    public function show(int $id): JsonResponse
    {
        $message = InboxMessage::with(['tempEmail', 'attachments'])
            ->findOrFail($id);

        $message->markAsRead();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $message->id,
                'from' => [
                    'address' => $message->from_address,
                    'name' => $message->from_name,
                ],
                'subject' => $message->subject,
                'body_html' => $message->body_html,
                'body_text' => $message->body_text,
                'two_fa_code' => $message->two_fa_code,
                'has_attachments' => $message->has_attachments,
                'attachments' => $message->attachments,
                'received_at' => $message->received_at->toIso8601String(),
                'is_read' => true,
            ],
        ]);
    }

    /**
     * Get message HTML body only
     */
    public function showHtml(int $id): string
    {
        $message = InboxMessage::findOrFail($id);
        $message->markAsRead();

        return $message->body_html ?? '<p>No HTML content available</p>';
    }

    /**
     * Delete a message
     */
    public function delete(int $id): JsonResponse
    {
        $message = InboxMessage::findOrFail($id);
        $message->delete();

        return response()->json([
            'success' => true,
            'message' => 'Message deleted successfully',
        ]);
    }

    /**
     * Mark message as read
     */
    public function markAsRead(int $id): JsonResponse
    {
        $message = InboxMessage::findOrFail($id);
        $message->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'Message marked as read',
        ]);
    }

    /**
     * Download attachment
     */
    public function downloadAttachment(int $id): StreamedResponse
    {
        $attachment = Attachment::findOrFail($id);

        if (!Storage::exists($attachment->storage_path)) {
            abort(404, 'File not found');
        }

        return Storage::download(
            $attachment->storage_path,
            $attachment->filename,
            ['Content-Type' => $attachment->content_type]
        );
    }
}
