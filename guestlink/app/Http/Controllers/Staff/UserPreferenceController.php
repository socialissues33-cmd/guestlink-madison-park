<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\ServiceRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserPreferenceController extends Controller
{
    /**
     * Persist per-account UI & operational preferences (theme, sidebar, Madi AI bot).
     */
    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'theme_preference'       => 'sometimes|in:dark,light',
            'sidebar_collapsed'      => 'sometimes|boolean',
            'bot_auto_reply_enabled' => 'sometimes|boolean',
        ]);

        $updates = [];
        $updatedTickets = 0;

        if (array_key_exists('theme_preference', $validated)) {
            $updates['theme_preference'] = $validated['theme_preference'];
        }

        if (array_key_exists('sidebar_collapsed', $validated)) {
            $updates['sidebar_collapsed'] = (bool) $validated['sidebar_collapsed'];
        }

        if (array_key_exists('bot_auto_reply_enabled', $validated)) {
            // Kitchen-only accounts do not manage guest request AI bot settings
            if ($user->hasRole('restaurant') && !$user->hasAnyRole(['admin', 'owner', 'front_desk'])) {
                return response()->json([
                    'success' => false,
                    'error'   => 'Kitchen accounts do not manage Madi AI chat bot settings.',
                    'message' => 'Kitchen accounts do not manage Madi AI chat bot settings.',
                ], 403);
            }

            $enabled = (bool) $validated['bot_auto_reply_enabled'];
            $updates['bot_auto_reply_enabled'] = $enabled;

            if ($user->hasAnyRole(['admin', 'owner', 'front_desk'])) {
                \Illuminate\Support\Facades\Cache::forever('guestlink_hotel_bot_auto_reply', $enabled);
            }

            // Apply immediately to active open guest tickets so turning OFF silences the bot right away
            $ticketQuery = ServiceRequest::whereNotIn('status', ['resolved', 'closed']);
            if ($user->hasAnyRole(['maintenance', 'maintenance_supervisor']) && !$user->hasAnyRole(['admin', 'owner', 'front_desk'])) {
                $ticketQuery->where(function ($q) {
                    $q->where('type', 'maintenance')
                      ->orWhereRaw("LOWER(COALESCE(department, '')) IN ('maintenance', 'engineering')");
                });
            } elseif ($user->hasRole('housekeeping') && !$user->hasAnyRole(['admin', 'owner', 'front_desk'])) {
                $ticketQuery->whereRaw("LOWER(COALESCE(department, '')) = 'housekeeping'");
            }
            $updatedTickets = $ticketQuery->update(['auto_reply_bot' => $enabled]);
        }

        if (!empty($updates)) {
            $user->forceFill($updates)->save();
        }

        return response()->json([
            'success' => true,
            'preferences' => [
                'theme_preference'       => $user->theme_preference ?? 'dark',
                'bot_auto_reply_enabled' => (bool) ($user->bot_auto_reply_enabled ?? true),
                'sidebar_collapsed'      => (bool) ($user->sidebar_collapsed ?? false),
            ],
            'updated_tickets' => $updatedTickets,
            'message' => array_key_exists('bot_auto_reply_enabled', $updates)
                ? ($updates['bot_auto_reply_enabled']
                    ? 'Madi AI Auto-Reply Bot saved ON for your account.'
                    : 'Madi AI Auto-Reply Bot saved OFF for your account.')
                : 'Account preferences saved.',
        ]);
    }
}
