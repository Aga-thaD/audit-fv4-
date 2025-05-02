<?php

namespace App\Http\Controllers;

use App\Models\Audit;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DisputeHistoryController extends Controller
{
    /**
     * Add a new entry to the audit's event history
     *
     * @param Request $request
     * @param Audit $audit
     * @return \Illuminate\Http\JsonResponse
     */
    public function addEntry(Request $request, Audit $audit)
    {
        try {
            $request->validate([
                'message' => 'required|string',
                'action_type' => 'required|string|in:dispute,reply,acknowledge,status change to',
                'attachments' => 'nullable|array',
                'attachments.*' => 'file|max:10240', // 10MB max per file
            ]);

            // Get current history or initialize empty array
            $history = $audit->event_history ?? [];

            // Create new history entry
            $entry = [
                'user_id' => Auth::id(),
                'user_name' => Auth::user()->name,
                'user_role' => Auth::user()->user_role,
                'action_type' => $request->action_type,
                'message' => $request->message,
                'reply' => $request->reply,
                'reason' => $request->reason,
                'old_status' => $request->old_status ?? null,
                'new_status' => $request->new_status ?? null,
                'attachments' => [],
                'timestamp' => now()->toIso8601String(),
            ];

            // Handle file uploads if any
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $path = $file->store('audit_history_attachments', 'public');
                    $entry['attachments'][] = [
                        'name' => $file->getClientOriginalName(),
                        'path' => $path,
                        'url' => Storage::url($path),
                        'type' => $file->getMimeType(),
                        'size' => $file->getSize()
                    ];
                }
            }

            // Add entry to history
            $history[] = $entry;

            // Update the audit
            $audit->update([
                'event_history' => $history
            ]);

            Log::info("Added new entry to audit #{$audit->id} history. Type: {$request->action_type}");

            return response()->json([
                'success' => true,
                'message' => 'History entry added successfully',
                'entry' => $entry
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to add history entry to audit #{$audit->id}: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to add history entry: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get the full event history for an audit
     *
     * @param Audit $audit
     * @return \Illuminate\Http\JsonResponse
     */
    public function getHistory(Audit $audit)
    {
        try {
            $history = $audit->event_history ?? [];
            
            return response()->json([
                'success' => true,
                'history' => $history
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to retrieve history for audit #{$audit->id}: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve history: ' . $e->getMessage()
            ], 500);
        }
    }
}