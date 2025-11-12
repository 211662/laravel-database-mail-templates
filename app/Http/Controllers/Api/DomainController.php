<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Domain;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DomainController extends Controller
{
    /**
     * Get list of available domains
     */
    public function index(Request $request): JsonResponse
    {
        $query = Domain::active();

        // Only show public domains for non-authenticated users
        if (!$request->user()) {
            $query->public();
        }

        $domains = $query->orderBy('priority', 'desc')
            ->orderBy('domain')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $domains->map(function ($domain) {
                return [
                    'id' => $domain->id,
                    'domain' => $domain->domain,
                    'full_domain' => $domain->full_domain,
                    'is_custom' => $domain->is_custom,
                ];
            }),
        ]);
    }

    /**
     * Create a new domain (admin only)
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'domain' => 'required|string|unique:domains,domain',
            'mx_record' => 'nullable|string',
            'is_custom' => 'boolean',
            'priority' => 'integer|min:0|max:100',
        ]);

        $domain = Domain::create([
            'domain' => $request->domain,
            'mx_record' => $request->mx_record,
            'is_custom' => $request->is_custom ?? false,
            'priority' => $request->priority ?? 0,
            'user_id' => $request->user() ? $request->user()->id : null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Domain created successfully',
            'data' => $domain,
        ], 201);
    }

    /**
     * Update domain (admin only)
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $domain = Domain::findOrFail($id);

        $request->validate([
            'is_active' => 'boolean',
            'priority' => 'integer|min:0|max:100',
            'mx_record' => 'nullable|string',
        ]);

        $domain->update($request->only(['is_active', 'priority', 'mx_record']));

        return response()->json([
            'success' => true,
            'message' => 'Domain updated successfully',
            'data' => $domain,
        ]);
    }

    /**
     * Delete domain (admin only)
     */
    public function destroy(int $id): JsonResponse
    {
        $domain = Domain::findOrFail($id);
        
        $emailCount = $domain->tempEmails()->count();
        
        if ($emailCount > 0) {
            return response()->json([
                'success' => false,
                'message' => "Cannot delete domain. It has {$emailCount} associated temp emails.",
            ], 422);
        }

        $domain->delete();

        return response()->json([
            'success' => true,
            'message' => 'Domain deleted successfully',
        ]);
    }
}
