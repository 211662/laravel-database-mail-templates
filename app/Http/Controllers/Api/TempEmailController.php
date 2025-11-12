<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TempEmail;
use App\Models\Domain;
use App\Jobs\CheckMailboxJob;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TempEmailController extends Controller
{
    /**
     * Generate a new temporary email address
     */
    public function generate(Request $request): JsonResponse
    {
        $request->validate([
            'domain_id' => 'nullable|exists:domains,id',
            'lifetime_hours' => 'nullable|integer|min:1|max:24',
        ]);

        try {
            $domain = null;
            if ($request->domain_id) {
                $domain = Domain::active()->findOrFail($request->domain_id);
            }

            $lifetimeHours = $request->lifetime_hours ?? 2;
            $tempEmail = TempEmail::generate($domain, $lifetimeHours);

            return response()->json([
                'success' => true,
                'data' => [
                    'email' => $tempEmail->email,
                    'username' => $tempEmail->username,
                    'domain' => $tempEmail->domain->domain,
                    'expires_at' => $tempEmail->expires_at->toIso8601String(),
                    'time_remaining' => $tempEmail->time_remaining,
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate email: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get inbox for a temp email
     */
    public function getInbox(string $email): JsonResponse
    {
        $tempEmail = TempEmail::where('email', $email)
            ->with(['domain', 'messages.attachments'])
            ->first();

        if (!$tempEmail) {
            return response()->json([
                'success' => false,
                'message' => 'Email not found',
            ], 404);
        }

        if ($tempEmail->isExpired()) {
            return response()->json([
                'success' => false,
                'message' => 'Email has expired',
            ], 410);
        }

        $messages = $tempEmail->messages()->paginate(20);

        return response()->json([
            'success' => true,
            'data' => [
                'email' => $tempEmail->email,
                'expires_at' => $tempEmail->expires_at->toIso8601String(),
                'time_remaining' => $tempEmail->time_remaining,
                'unread_count' => $tempEmail->unread_count,
                'messages' => $messages->items(),
                'pagination' => [
                    'total' => $messages->total(),
                    'per_page' => $messages->perPage(),
                    'current_page' => $messages->currentPage(),
                    'last_page' => $messages->lastPage(),
                ],
            ],
        ]);
    }

    /**
     * Check for new messages
     */
    public function checkNew(string $email): JsonResponse
    {
        $tempEmail = TempEmail::where('email', $email)->first();

        if (!$tempEmail) {
            return response()->json([
                'success' => false,
                'message' => 'Email not found',
            ], 404);
        }

        if ($tempEmail->isExpired()) {
            return response()->json([
                'success' => false,
                'message' => 'Email has expired',
            ], 410);
        }

        // Dispatch job to check mailbox
        CheckMailboxJob::dispatch($tempEmail);

        // Get messages received since last check
        $lastCheck = $tempEmail->last_checked_at ?? now()->subMinutes(5);
        $newMessages = $tempEmail->messages()
            ->where('created_at', '>', $lastCheck)
            ->get();

        $tempEmail->markAsChecked();

        return response()->json([
            'success' => true,
            'data' => [
                'new_count' => $newMessages->count(),
                'messages' => $newMessages,
                'last_checked' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * Delete a temp email
     */
    public function delete(string $email): JsonResponse
    {
        $tempEmail = TempEmail::where('email', $email)->first();

        if (!$tempEmail) {
            return response()->json([
                'success' => false,
                'message' => 'Email not found',
            ], 404);
        }

        $tempEmail->delete();

        return response()->json([
            'success' => true,
            'message' => 'Email and all messages deleted successfully',
        ]);
    }

    /**
     * Get email details
     */
    public function show(string $email): JsonResponse
    {
        $tempEmail = TempEmail::where('email', $email)
            ->with('domain')
            ->first();

        if (!$tempEmail) {
            return response()->json([
                'success' => false,
                'message' => 'Email not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'email' => $tempEmail->email,
                'username' => $tempEmail->username,
                'domain' => $tempEmail->domain->domain,
                'is_active' => $tempEmail->is_active && !$tempEmail->isExpired(),
                'expires_at' => $tempEmail->expires_at->toIso8601String(),
                'time_remaining' => $tempEmail->time_remaining,
                'message_count' => $tempEmail->messages()->count(),
                'unread_count' => $tempEmail->unread_count,
                'created_at' => $tempEmail->created_at->toIso8601String(),
            ],
        ]);
    }
}
