<?php

namespace App\Services;

use App\Models\FoodOrder;
use App\Models\FoodOrderStatusLog;
use App\Models\RequestStatusLog;
use App\Models\ServiceRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StatusProgressionService
{
    public const STANDARD_STAGE_MINUTES = 30;

    public const FOOD_ORDER_RANKS = [
        'pending'   => 0,
        'confirmed' => 1,
        'preparing' => 2,
        'ready'     => 3,
        'delivered' => 4,
        'cancelled' => 4,
    ];

    public const REQUEST_RANKS = [
        'pending'     => 0,
        'assigned'    => 1,
        'in_progress' => 2,
        'resolved'    => 3,
        'closed'      => 4,
    ];

    /**
     * Validate forward-only FoodOrder status transitions.
     * Terminal states ('delivered', 'cancelled') are permanently locked and cannot be reopened.
     */
    public static function canTransitionFoodOrder(string $from, string $to): array
    {
        if ($from === $to) {
            return ['allowed' => true, 'error' => null];
        }

        if (in_array($from, ['delivered', 'cancelled'], true)) {
            $label = $from === 'delivered' ? 'Completed (Delivered)' : 'Cancelled';
            return [
                'allowed' => false,
                'error'   => "This food order is already {$label} and permanently locked. It cannot be reopened or changed.",
            ];
        }

        if ($to === 'cancelled') {
            return ['allowed' => true, 'error' => null];
        }

        $fromRank = self::FOOD_ORDER_RANKS[$from] ?? 0;
        $toRank   = self::FOOD_ORDER_RANKS[$to] ?? 0;

        if ($toRank < $fromRank) {
            return [
                'allowed' => false,
                'error'   => 'Forward-Only Policy: Cannot move food order backwards from ' . ucfirst($from) . ' to ' . ucfirst($to) . '.',
            ];
        }

        return ['allowed' => true, 'error' => null];
    }

    /**
     * Validate forward-only ServiceRequest status transitions.
     * Completed/Closed tickets are locked (only Admin may re-open a closed ticket if needed for audit).
     */
    public static function canTransitionRequest(string $from, string $to, bool $isAdmin = false): array
    {
        if ($from === $to) {
            return ['allowed' => true, 'error' => null];
        }

        if ($from === 'closed') {
            if ($isAdmin) {
                return ['allowed' => true, 'error' => null];
            }
            return [
                'allowed' => false,
                'error'   => 'Security Policy: This ticket is Closed and Archived. Only an Administrator can re-open a closed ticket.',
            ];
        }

        $fromRank = self::REQUEST_RANKS[$from] ?? 0;
        $toRank   = self::REQUEST_RANKS[$to] ?? 0;

        if ($toRank < $fromRank) {
            return [
                'allowed' => false,
                'error'   => 'Forward-Only Policy: Cannot move ticket backwards from ' . ucwords(str_replace('_', ' ', $from)) . ' to ' . ucwords(str_replace('_', ' ', $to)) . '.',
            ];
        }

        return ['allowed' => true, 'error' => null];
    }

    /**
     * Automatically advance active Food Orders and Service Requests by 1 stage every 30 minutes
     * (standard hotel stage duration), ALWAYS stopping before final completion:
     *  - Food Orders: pending -> confirmed -> preparing -> ready (STOPS at 'ready'; manual touch required for 'delivered')
     *  - Requests:    pending -> assigned -> in_progress (STOPS at 'in_progress'; manual touch required for 'resolved'/'closed')
     */
    public static function runAutoProgression(bool $force = false, ?int $durationMinutes = null): array
    {
        $minutes = $durationMinutes ?? (int) env('HOTEL_STAGE_DURATION_MINUTES', self::STANDARD_STAGE_MINUTES);
        if ($minutes < 1) {
            $minutes = self::STANDARD_STAGE_MINUTES;
        }

        if (!$force) {
            try {
                if (!Cache::add('guestlink_auto_stage_progression_lock', true, 60)) {
                    return ['orders_advanced' => 0, 'requests_advanced' => 0];
                }
            } catch (\Throwable $e) {
                // Proceed if cache store unavailable
            }
        }

        $cutoff = now()->subMinutes($minutes);
        $ordersAdvanced = 0;
        $requestsAdvanced = 0;

        try {
            // 1. Auto-advance Food Orders (pending -> confirmed -> preparing -> ready; NEVER auto-delivered)
            $orderNextMap = [
                'pending'   => 'confirmed',
                'confirmed' => 'preparing',
                'preparing' => 'ready',
            ];

            $staleOrders = FoodOrder::whereIn('status', array_keys($orderNextMap))
                ->where('updated_at', '<=', $cutoff)
                ->get();

            foreach ($staleOrders as $order) {
                $from = $order->status;
                $to   = $orderNextMap[$from] ?? null;
                if (!$to) {
                    continue;
                }

                DB::transaction(function () use ($order, $from, $to, $minutes) {
                    $order->update(['status' => $to]);
                    FoodOrderStatusLog::create([
                        'food_order_id' => $order->id,
                        'from_status'   => $from,
                        'to_status'     => $to,
                        'notes'         => "Auto-progressed after {$minutes}-minute standard duration. Manual confirmation required to complete delivery.",
                        'changed_by'    => null,
                    ]);
                });
                $ordersAdvanced++;
            }

            // 2. Auto-advance Service/Maintenance Requests (pending -> assigned -> in_progress; NEVER auto-resolved/closed)
            $reqNextMap = [
                'pending'  => 'assigned',
                'assigned' => 'in_progress',
            ];

            $staleRequests = ServiceRequest::with('category')
                ->whereIn('status', array_keys($reqNextMap))
                ->where('updated_at', '<=', $cutoff)
                ->get();

            foreach ($staleRequests as $req) {
                // Event / Function Hall inquiries require in-person Front Office consultation & never auto-advance
                if ($req->isEventInquiry()) {
                    continue;
                }
                $from = $req->status;
                $to   = $reqNextMap[$from] ?? null;
                if (!$to) {
                    continue;
                }

                DB::transaction(function () use ($req, $from, $to, $minutes) {
                    $req->update(['status' => $to]);
                    RequestStatusLog::create([
                        'service_request_id' => $req->id,
                        'from_status'        => $from,
                        'to_status'          => $to,
                        'notes'              => "Auto-progressed after {$minutes}-minute standard duration. Manual staff confirmation required to complete.",
                        'changed_by'         => null,
                    ]);
                });
                $requestsAdvanced++;
            }
        } catch (\Throwable $e) {
            Log::warning('StatusProgressionService::runAutoProgression failed: ' . $e->getMessage());
        }

        return [
            'orders_advanced'   => $ordersAdvanced,
            'requests_advanced' => $requestsAdvanced,
        ];
    }
}
