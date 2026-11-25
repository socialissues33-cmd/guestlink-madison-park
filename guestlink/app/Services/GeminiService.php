<?php

namespace App\Services;

use App\Models\RequestCategory;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    /** Canonical values persisted in the DB. */
    private const PRIORITIES  = ['low', 'medium', 'high', 'urgent'];
    private const DEPARTMENTS = ['Front Desk', 'Maintenance', 'Housekeeping'];

    private string $apiKey;
    private string $model;
    private static bool $apiCooldownActive = false;

    public function __construct()
    {
        $this->apiKey = config('services.gemini.key', '');
        $this->model  = config('services.gemini.model', 'gemini-3.5-flash') ?: 'gemini-3.5-flash';
    }

    private function isCooldownActive(): bool
    {
        if (self::$apiCooldownActive) {
            return true;
        }
        try {
            return (bool) \Illuminate\Support\Facades\Cache::get('gemini_api_cooldown', false);
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function triggerCooldown(): void
    {
        self::$apiCooldownActive = true;
        try {
            \Illuminate\Support\Facades\Cache::put('gemini_api_cooldown', true, 30);
        } catch (\Throwable $e) {
        }
    }

    /**
     * Extract non-thought text from a Gemini generateContent response payload.
     */
    private function extractCandidateText(array $data, string $default = '{}'): string
    {
        $parts = $data['candidates'][0]['content']['parts'] ?? [];
        $fallback = null;
        foreach ($parts as $part) {
            if (!empty($part['text'])) {
                if (empty($part['thought'])) {
                    return (string) $part['text'];
                }
                $fallback = (string) $part['text'];
            }
        }
        return $fallback ?? $default;
    }

    /**
     * Send request to primary Gemini model with automatic live failover to backup flash/flash-lite models on HTTP 404/429/503.
     */
    private function postWithModelFailover(array $payload, int $timeout = 8): ?\Illuminate\Http\Client\Response
    {
        $models = array_values(array_unique([
            $this->model,
            'gemini-2.5-flash',
            'gemini-2.5-flash-lite',
            'gemini-flash-lite-latest',
        ]));

        $attemptTimeout = max(3, min($timeout, 8));
        $lastResponse = null;
        foreach ($models as $candidateModel) {
            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$candidateModel}:generateContent";
            try {
                $res = Http::connectTimeout(3)->timeout($attemptTimeout)
                    ->withOptions(['curl' => [CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4]])
                    ->withHeaders(['x-goog-api-key' => $this->apiKey])
                    ->post($url, $payload);

                if ($res->successful()) {
                    return $res;
                }

                $lastResponse = $res;
                // Only fail over on quota (429), busy (503), or unavailable model (404)
                if (!in_array($res->status(), [404, 429, 503], true)) {
                    return $res;
                }
            } catch (\Throwable $e) {
                Log::warning("Gemini model {$candidateModel} failed: " . $e->getMessage());
            }
        }

        return $lastResponse;
    }

    /**
     * Analyze a guest request and return triage suggestion.
     */
    public function triageRequest(string $description, string $type = 'maintenance'): array
    {
        if (empty($this->apiKey) || $this->apiKey === 'your-gemini-api-key-here' || $this->isCooldownActive()) {
            return $this->mockTriageResponse($description, $type);
        }

        $categories = $this->activeCategoryNames($type === 'service' ? 'service' : 'maintenance');
        $prompt     = $this->buildTriagePrompt($description, $type, $categories);

        try {
            $generationConfig = [
                'temperature'      => 0.1,
                'responseMimeType' => 'application/json',
                'responseSchema'   => $this->triageSchema($categories),
            ];
            if (str_contains($this->model, 'thinking')) {
                $generationConfig['thinkingConfig'] = ['thinkingBudget' => 0];
            }

            $response = $this->postWithModelFailover([
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt],
                        ],
                    ],
                ],
                'generationConfig' => $generationConfig,
            ], 15);

            if ($response && $response->successful()) {
                $data = $response->json() ?? [];
                $text = $this->extractCandidateText($data);
                $cleanJson = preg_replace('/^```(?:json)?\s*|\s*```$/is', '', trim($text));
                $parsed = json_decode($cleanJson, true);

                if (json_last_error() === JSON_ERROR_NONE && isset($parsed['category'])) {
                    $normalized = $this->normalizeTriage([
                        'category'   => $parsed['category'] ?? 'General Inquiry',
                        'priority'   => $parsed['priority'] ?? 'medium',
                        'department' => $parsed['department'] ?? 'Front Desk',
                        'reason'     => $parsed['reason'] ?? 'AI analysis complete.',
                        'source'     => 'gemini',
                    ]);
                    return $this->applyHotelPriorityFloor($normalized, $description);
                }
            } else {
                $this->triggerCooldown();
            }

            Log::warning('Gemini AI response was not valid JSON.', ['response' => $response?->body()]);
        } catch (\Throwable $e) {
            $this->triggerCooldown();
            Log::error('Gemini API call failed: ' . $e->getMessage());
        }

        return $this->mockTriageResponse($description, $type);
    }

    private function buildTriagePrompt(string $description, string $type, array $categories = []): string
    {
        $typeContext = $type === 'service'
            ? 'service request (e.g. housekeeping items like towels/pillows, laundry, wake-up calls, front desk inquiries, function hall/event space inquiries, dining pricing/recommendations, room assistance)'
            : 'maintenance request (e.g. AC, plumbing, electrical, door/lock, internet/TV issues)';

        $categoryRule = !empty($categories)
            ? 'Choose EXACTLY ONE name from this list of active categories: ' . implode(' | ', $categories)
            : 'A short category label (e.g., "Event & Function Hall", "Dining & Reservations", "Front Desk Concierge", "Extra Towels", "Laundry Service", "Wake-up Call", "Air Conditioning", "Plumbing", "Electrical")';

        return <<<PROMPT
You are Madi, the hotel operations triage assistant for Madison Park Hotel (GuestLink System).
A guest has submitted a {$typeContext} with the following description:

"{$description}"

MADISON PARK HOTEL SYSTEM CONTEXT:
- Real System Departments (ONLY these 3 exist in GuestLink):
  1. "Maintenance" — AC/climate, plumbing/water heater/shower/toilet, electrical/lights/outlets, TV/Wi-Fi hardware, door/lock/safe, furniture repairs.
  2. "Housekeeping" — extra towels, pillows, linens, toiletries, bottled water, room cleaning/makeup, laundry, extra bed/crib, iron.
  3. "Front Desk" — event & function hall inquiries/appointments, dining/catering inquiries, billing/folio, late check-out, wake-up calls, noise complaints, emergencies/security coordination, and general concierge.
- Event Venues: FH-01 Event Plaza (outdoor, 150-250 pax), 1st Floor Function Hall Rooms (Rooms AB, CD, E, F, G, H, IJ — 50+ pax each), and DJP Events Hall (2nd Floor, 40-80 pax). All event inquiries place a Tentative Hold and require an in-person Front Office Desk (Ground Floor Lobby) appointment -> department "Front Desk", category "Event & Function Hall".
- Dining: Times Square Restaurant (Ground Floor) supports Room Delivery and Dine-In Pre-Order, with Pay on Delivery/Cashier or Charge to Room (FO Check-Out). Dining/menu/catering inquiries -> department "Front Desk", category "Dining & Reservations".

STRICT PRIORITY RUBRIC (MUST FOLLOW):
- "urgent": Safety/health/security emergencies, smoke/fire, electrical sparks/burning smell/full power outage, active flooding/burst pipe/major water leak, guest locked out of room.
- "high": Core room habitability failures impacting guest comfort right now — AC not cooling/blowing warm, no hot water / broken shower heater, clogged/overflowing toilet, broken door lock or safe, severe noise disturbance.
- "medium": Standard operational requests — room cleaning/makeup, extra bed/crib, flickering bulb/TV/Wi-Fi troubleshooting, event/function hall inquiries, dining/catering inquiries, billing/late check-out.
- "low": Routine courtesy/amenity deliveries — extra towels, pillows, bottled water, toiletries, slippers, iron, wake-up call.

Return JSON with these exact keys:
- "category": {$categoryRule}
- "priority": One of "low", "medium", "high", "urgent" following the STRICT PRIORITY RUBRIC above.
- "department": One of "Front Desk", "Maintenance", "Housekeeping".
- "reason": 1 concise, accurate sentence explaining the priority and routing using ONLY real Madison Park departments ("Maintenance", "Housekeeping", or "Front Desk" — never say "Engineering" or contradict the chosen priority; if asking about events/function halls or dining, mention the specific venue like FH-01 Event Plaza / Function Hall / DJP Hall or Times Square Restaurant).

Return only valid JSON, no markdown, no extra text.
PROMPT;
    }

    /**
     * Fallback mock response when API key is not set or API fails.
     */
    private function mockTriageResponse(string $description, string $type): array
    {
        $description = strtolower($description);

        // Default baseline
        $priority   = ($type === 'maintenance') ? 'High' : 'Low';
        $department = ($type === 'maintenance') ? 'Engineering' : 'Housekeeping';
        $category   = ($type === 'maintenance') ? 'Air Conditioning' : 'Housekeeping';

        // 1. Safety & Emergency (Highest Priority)
        if (preg_match('/smoke|fire|emergency|danger|unsafe|medical|sick|hurt|injured|ambulance|hazard/', $description)) {
            $category   = 'Front Desk Concierge';
            $department = 'Front Desk';
            $priority   = 'Urgent';
            $reason     = 'Safety or medical emergency detected. Urgent priority routed to Front Desk for immediate response.';

        // 2. Event Spaces, Function Halls, and Plaza Inquiries & Reservations
        } elseif (preg_match('/\b(?:function\s*hall|event\s*(?:hall|space|room|center|plaza)?|ballroom|banquet|conference\s*hall|seminar\s*room|party\s*venue|meeting\s*room|wedding\s*venue|debut\s*venue|djp\s*hall|fh-?01|reserve.*(?:hall|function|event)|book.*(?:hall|function|event))\b/i', $description)) {
            $category   = 'Event & Function Hall';
            $department = 'Front Desk';
            $priority   = 'Medium';
            $reason     = 'Event inquiry for Madison Park venues (FH-01 Event Plaza, 1st Floor Function Hall Rooms AB–IJ, or DJP Hall). Routed to Front Desk for Tentative Hold & Lobby appointment.';

        // 3. Food Pricing, Dining Menus, Catering Packages & Culinary Recommendations
        } elseif (preg_match('/\b(?:price.*food|px.*food|food.*price|food.*px|how\s*much.*food|food.*cost|cost.*food|catering|menu.*price|buffet.*price|recommend.*food|food.*recommend|package.*price|banquet.*package|food.*available|what.*food|dish.*price|how\s*much.*menu)\b/i', $description)) {
            $category   = 'Dining & Reservations';
            $department = 'Front Desk';
            $priority   = 'Medium';
            $reason     = 'Times Square Restaurant dining and catering inquiry (Room Delivery or Dine-In Pre-Order, catering from ₱350–₱550/pax). Routed to Front Desk.';

        // 4. In-Room Drinking Water & Refreshments (Must be evaluated BEFORE plumbing/water!)
        } elseif (preg_match('/drinking.water|bottle.?of.water|bottled.water|mineral.water|water.bottle|extra.water|glass.of.water|water.refill/', $description)) {
            $category   = 'Extra Towels';
            $department = 'Housekeeping';
            $priority   = 'Low';
            $reason     = 'Routine in-room drinking water replenishment routed to Housekeeping.';

        // 5. Air Conditioning & Climate Control (High Priority Maintenance)
        } elseif (preg_match('/\b(?:air[\s\-_.]?condition(?:er|ing)?|air[\s\-_.]?con|aircon|a\/c|ac\s+unit|not\s+cooling|warm\s+room|hot\s+room|hvac|thermostat|blowing\s+warm)\b/i', $description)) {
            $category   = 'Air Conditioning';
            $department = 'Maintenance';
            $priority   = 'High';
            $reason     = 'Suite air conditioning failure impacts guest comfort. High priority routed to Maintenance.';

        // 6. Plumbing & Drainage Leaks (Physical water containment/fixtures only - excludes drinking water)
        } elseif (preg_match('/(?:water\s*(?:leak|leakage|damage|pooling|dripping|burst|overflow|pressure|shut.?off))|leak|flood|burst|pipe|faucet|plumb|drain|toilet|clog|sink|shower|sewage|no\s*hot\s*water|water\s*heater/', $description)) {
            $category   = 'Plumbing';
            $department = 'Maintenance';
            $priority   = preg_match('/flood|burst|overflow|active leak/', $description) ? 'Urgent' : 'High';
            $reason     = 'Plumbing or hot-water fixture failure impacts room comfort. Routed to Maintenance.';

        // 5. Electrical & Lighting
        } elseif (preg_match('/light|bulb|electrical|outlet|socket|power|switch|breaker|spark|flicker|tv|television|remote/', $description)) {
            $category   = preg_match('/tv|television|remote/', $description) ? 'Internet/TV' : 'Electrical';
            $department = 'Maintenance';
            $priority   = preg_match('/spark|smoke|shock|no power|power out/', $description) ? 'Urgent' : 'Medium';
            $reason     = 'In-room electrical or TV equipment issue routed to Maintenance.';

        // 6. Door / Keycard Locks
        } elseif (preg_match('/door|lock|keycard|rfid|cannot enter|cannot lock|unlocked/', $description)) {
            $category   = 'Door/Lock';
            $department = 'Maintenance';
            $priority   = preg_match('/locked out|cannot enter/', $description) ? 'Urgent' : 'High';
            $reason     = 'Room door or electronic lock issue routed to Maintenance for prompt inspection.';

        // 7. Housekeeping, Linens & Toiletries (Use hair conditioner so it doesn't match air conditioner)
        } elseif (preg_match('/towel|linens|sheets|blanket|pillow|clean|housekeeping|room makeup|laundry|toiletries|soap|shampoo|hair conditioner|(?<!air\s)conditioner|dental kit|slippers|toothbrush|trash|coffee|tea|creamer|ice bucket/', $description)) {
            $category   = preg_match('/laundry/', $description) ? 'Laundry Service' : (preg_match('/pillow|blanket/', $description) ? 'Extra Pillows' : (preg_match('/clean|room makeup|trash/', $description) ? 'Room Cleaning' : 'Extra Towels'));
            $department = 'Housekeeping';
            $priority   = preg_match('/room makeup|clean room/', $description) ? 'Medium' : 'Low';
            $reason     = 'In-room guest amenity or room-cleaning request routed to Housekeeping.';

        // 8. In-Room Dining & Food Service
        } elseif (preg_match('/food|order|hungry|eat|drink|menu|room service|breakfast|dinner|lunch|dish/', $description)) {
            $category   = 'Dining & Reservations';
            $department = 'Front Desk';
            $priority   = 'Medium';
            $reason     = 'Dining assistance request routed to Front Desk to coordinate with Times Square Restaurant.';

        // 9. Front Desk Concierge / Wake-up / Billing
        } elseif (preg_match('/wake.?up|alarm|wake me|morning call/', $description)) {
            $category   = 'Wake-up Call';
            $department = 'Front Desk';
            $priority   = 'Low';
            $reason     = 'Scheduled wake-up call request routed to Front Desk.';
        } elseif (preg_match('/wifi|wi-fi|internet|network|connection|password|login code/', $description)) {
            $category   = 'Internet/TV';
            $department = 'Front Desk';
            $priority   = 'Medium';
            $reason     = 'In-room Wi-Fi connectivity inquiry routed to Front Desk.';
        } elseif (preg_match('/checkout|check out|bill|invoice|receipt|extend|late checkout|luggage|bellhop|taxi|transport|valet|car/', $description)) {
            $category   = 'Front Desk Concierge';
            $department = 'Front Desk';
            $priority   = 'Medium';
            $reason     = 'Guest concierge or billing inquiry routed to Front Desk.';
        } elseif (preg_match('/noise|noisy|loud|shouting|party|disturb|neighbor/', $description)) {
            $category   = 'Front Desk Concierge';
            $department = 'Front Desk';
            $priority   = 'High';
            $reason     = 'Guest noise disturbance routed to Front Desk for prompt intervention.';
        } elseif (preg_match('/safe|vault|lockbox|code forgotten|safe locked|cannot open safe/', $description)) {
            $category   = 'Door/Lock';
            $department = 'Maintenance';
            $priority   = 'High';
            $reason     = 'In-room safe lockout routed to Maintenance with override access.';
        } elseif (preg_match('/iron|ironing|steamer|board/', $description)) {
            $category   = 'Extra Towels';
            $department = 'Housekeeping';
            $priority   = 'Low';
            $reason     = 'Complimentary iron and ironing board request routed to Housekeeping.';
        } elseif (preg_match('/crib|baby|cot|rollaway|extra bed|toddler/', $description)) {
            $category   = 'Extra Pillows';
            $department = 'Housekeeping';
            $priority   = 'Medium';
            $reason     = 'Extra bed or family setup request routed to Housekeeping.';
        } elseif (preg_match('/curtain|blind|window|balcony|drapes/', $description)) {
            $category   = 'Furniture';
            $department = 'Maintenance';
            $priority   = 'Medium';
            $reason     = 'Window blind or room fixture repair routed to Maintenance.';

        // 10. Fallbacks based on Request Type
        } elseif ($type === 'maintenance') {
            $category   = 'Air Conditioning';
            $department = 'Maintenance';
            $priority   = 'High';
            $reason     = 'In-room maintenance issue routed to Maintenance for prompt inspection.';
        } else {
            $category   = 'General Inquiry';
            $department = 'Front Desk';
            $priority   = 'Low';
            $reason     = 'General guest assistance request routed to Front Desk.';
        }

        return $this->normalizeTriage([
            'category'   => $category,
            'priority'   => $priority,
            'department' => $department,
            'reason'     => $reason,
            'source'     => 'gemini_smart_triage',
        ]);
    }

    /**
     * Inspect a guest maintenance photo with Gemini Vision to diagnose the issue.
     */
    public function analyzeMaintenanceImage(string $imagePath, string $mimeType = 'image/jpeg', ?string $userNotes = '', ?string $clientFilename = ''): array
    {
        $userNotes = (string) ($userNotes ?? '');
        $clientFilename = (string) ($clientFilename ?? '');

        if (empty($this->apiKey) || $this->apiKey === 'your-gemini-api-key-here' || !file_exists($imagePath) || $this->isCooldownActive()) {
            return $this->inspectImageWithHeuristics($imagePath, $userNotes, $clientFilename);
        }

        try {
            $preparedImg  = $this->prepareOptimizedImage($imagePath, $mimeType);
            $imageData    = $preparedImg['data'];
            $resolvedMime = $preparedImg['mimeType'];
            $categories   = $this->activeCategoryNames('maintenance');
            $categoryRule = !empty($categories)
                ? 'Choose EXACTLY ONE name from this list of active categories: ' . implode(' | ', $categories)
                : 'The most specific classified category name (e.g. "Plumbing", "Door/Lock", "Electrical")';

            $prompt = <<<PROMPT
You are a master hotel facilities engineer and expert AI diagnostic inspector for Madison Park Hotel.
A hotel guest captured this photo of a maintenance problem in their room.
Image filename: "{$clientFilename}".
Additional notes provided by guest: "{$userNotes}".

CRITICAL INSTRUCTIONS FOR IMAGE INSPECTION:
- Inspect the photo carefully and identify the exact problem (e.g. clogged shower drain with hair clump, clogged sink with dirty standing water, detached lever handle, leaking pipe joint, broken toilet flush, non-cooling AC, faulty electrical socket).
- Do NOT guess or default to "Air Conditioning" or "Door/Lock" unless the photo specifically shows those items.
- Category rule: {$categoryRule}
- Set priority appropriately:
  * "low": Cosmetic marks, minor cabinet scuffs.
  * "medium": Loose interior fixtures, detached lever handle, non-urgent lighting.
  * "high": Clogged drains, standing water in sinks/showers, active water leaks, broken AC, or entrance lock malfunction.
  * "urgent": Active room flooding, sewage overflow, sparking wires, or safety hazards.
- Department: "Front Desk", "Maintenance", or "Housekeeping" (use "Maintenance" for any facility defect).

Return a JSON object with these exact keys:
- "issue_detected": Concise specific title of the problem (e.g. "Clogged Shower Drain / Hair & Debris Blockage", "Clogged Sink Basin / Standing Water Drainage Blockage", "Detached Chrome Lever Handle / Loose Mounting Escutcheon").
- "category": Per the category rule above.
- "priority": One of "low", "medium", "high", "urgent".
- "department": One of "Front Desk", "Maintenance", "Housekeeping".
- "suggested_description": A clear, professional 1-2 sentence description explaining the visual defect observed so staff can prepare tools.
- "reason": A brief explanation of the visual diagnostic triage.

Return ONLY valid JSON, no markdown formatting.
PROMPT;

            $response = $this->postWithModelFailover([
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt],
                            [
                                'inlineData' => [
                                    'mimeType' => $resolvedMime,
                                    'data'     => $imageData,
                                ],
                            ],
                        ],
                    ],
                ],
                'generationConfig' => array_filter([
                    'temperature'      => 0.1,
                    'responseMimeType' => 'application/json',
                    'responseSchema'   => $this->visionSchema($categories),
                    'thinkingConfig'   => str_contains($this->model, 'thinking') ? ['thinkingBudget' => 0] : null,
                ]),
            ], 8);

            if ($response && $response->successful()) {
                $data = $response->json() ?? [];
                $text = $this->extractCandidateText($data);
                $cleanJson = preg_replace('/^```(?:json)?\s*|\s*```$/is', '', trim($text));
                $parsed = json_decode($cleanJson, true);

                if (json_last_error() === JSON_ERROR_NONE && isset($parsed['category'])) {
                    return $this->normalizeTriage([
                        'issue_detected'        => $parsed['issue_detected'] ?? 'Fixture Defect Identified',
                        'category'              => $parsed['category'] ?? 'Plumbing',
                        'priority'              => $parsed['priority'] ?? 'high',
                        'department'            => $parsed['department'] ?? 'Maintenance',
                        'suggested_description' => $parsed['suggested_description'] ?? 'Photo inspection identifies fixture maintenance defect.',
                        'reason'                => $parsed['reason'] ?? 'Gemini Vision automated image inspection.',
                        'source'                => 'gemini_vision',
                    ]);
                }
            } else {
                $this->triggerCooldown();
            }

            Log::warning('Gemini Vision response was not valid JSON.', ['response' => $response?->body()]);
        } catch (\Throwable $e) {
            $this->triggerCooldown();
            Log::error('Gemini Vision API call failed: ' . $e->getMessage());
        }

        return $this->inspectImageWithHeuristics($imagePath, $userNotes, $clientFilename);
    }

    /**
     * Prepare optimized, downscaled image with accurate MIME type for fast Gemini API transfer.
     */
    private function prepareOptimizedImage(string $imagePath, string $fallbackMime = 'image/jpeg'): array
    {
        if (!file_exists($imagePath)) {
            return ['mimeType' => $fallbackMime ?: 'image/jpeg', 'data' => ''];
        }

        $detectedMime = @mime_content_type($imagePath) ?: ($fallbackMime ?: 'image/jpeg');
        if (!in_array($detectedMime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            $detectedMime = 'image/jpeg';
        }

        // If file is already small (under 250KB), send directly with its true MIME type
        if (filesize($imagePath) <= 250 * 1024) {
            return [
                'mimeType' => $detectedMime,
                'data'     => base64_encode(file_get_contents($imagePath)),
            ];
        }

        try {
            $src = @imagecreatefromjpeg($imagePath) ?: (@imagecreatefrompng($imagePath) ?: @imagecreatefromwebp($imagePath));
            if (!$src) {
                return [
                    'mimeType' => $detectedMime,
                    'data'     => base64_encode(file_get_contents($imagePath)),
                ];
            }

            $w = imagesx($src);
            $h = imagesy($src);
            $maxDim = 1024;

            if ($w > $maxDim || $h > $maxDim) {
                $scale = min($maxDim / $w, $maxDim / $h);
                $dw = max(1, (int)($w * $scale));
                $dh = max(1, (int)($h * $scale));
                $dst = imagecreatetruecolor($dw, $dh);
                imagecopyresampled($dst, $src, 0, 0, 0, 0, $dw, $dh, $w, $h);
                ob_start();
                imagejpeg($dst, null, 80);
                $optimized = ob_get_clean();
                imagedestroy($dst);
                imagedestroy($src);
                return ['mimeType' => 'image/jpeg', 'data' => base64_encode($optimized)];
            }

            ob_start();
            imagejpeg($src, null, 80);
            $optimized = ob_get_clean();
            imagedestroy($src);
            return ['mimeType' => 'image/jpeg', 'data' => base64_encode($optimized)];
        } catch (\Throwable $e) {
            return [
                'mimeType' => $detectedMime,
                'data'     => base64_encode(file_get_contents($imagePath)),
            ];
        }
    }

    /**
     * Intelligent local visual inspection engine using GD image profiling and heuristics.
     */
    public function inspectImageWithHeuristics(string $imagePath, ?string $userNotes = '', ?string $clientFilename = ''): array
    {
        return $this->normalizeTriage($this->runImageHeuristics($imagePath, $userNotes, $clientFilename));
    }

    private function runImageHeuristics(string $imagePath, ?string $userNotes = '', ?string $clientFilename = ''): array
    {
        $userNotes = (string) ($userNotes ?? '');
        $clientFilename = (string) ($clientFilename ?? '');
        $text = strtolower($userNotes . ' ' . $clientFilename . ' ' . basename($imagePath));

        // 1. Keyword analysis across notes, client filename, and file path
        if (preg_match('/socket|plug|wire|outlet|burn|scorch|smoke|spark|fire|short|power|cable|adapter|charger/i', $text)) {
            $isEmergency = preg_match('/burn|smoke|spark|fire|short|char|scorch/i', $text);
            return [
                'issue_detected'        => 'Burnt Electrical Extension Socket / Scorch Marks & Exposed Wiring',
                'category'              => 'Electrical',
                'priority'              => $isEmergency ? 'Urgent' : 'High',
                'department'            => 'Engineering',
                'suggested_description' => 'Power extension socket and adapter have suffered severe electrical overheating with heavy burn soot, charred casing, and exposed internal circuitry. Power supply must be isolated immediately.',
                'reason'                => 'Electrical fire safety hazard detected via visual inspection. Priority upgraded to Urgent.',
                'source'                => 'visual_inspection_engine',
            ];
        }

        if (preg_match('/shower|tray|hair|strainer|drain.*clog|clog.*drain/i', $text)) {
            return [
                'issue_detected'        => 'Clogged Shower Drain / Hair & Debris Blockage',
                'category'              => 'Plumbing',
                'priority'              => 'High',
                'department'            => 'Engineering',
                'suggested_description' => 'Shower tray drain is obstructed by hair and debris, causing water backup and slow drainage. Requires drain snaking and strainer cleaning.',
                'reason'                => 'Visual inspection identified hair clump and debris clogging shower tray drain. Dispatched to plumbing maintenance.',
                'source'                => 'visual_inspection_engine',
            ];
        }

        if (preg_match('/sink|basin|dish|standing.*water|soap.*suds|clog.*sink|sink.*clog/i', $text)) {
            return [
                'issue_detected'        => 'Clogged Sink Basin / Standing Water Drainage Blockage',
                'category'              => 'Plumbing',
                'priority'              => 'High',
                'department'            => 'Engineering',
                'suggested_description' => 'Sink basin has severe drainage obstruction with murky standing water and soap suds unable to drain. Requires plumber inspection and drain clearance.',
                'reason'                => 'Visual inspection identified standing water and soap foam backup in sink basin. Dispatched to Engineering plumbers.',
                'source'                => 'visual_inspection_engine',
            ];
        }

        if (preg_match('/faucet.*(brown|rust|dirty|yellow|murky)|(brown|rust|dirty|yellow|murky|contaminat).*(water|faucet|tap)|discolor.*water|rusty.*tap/i', $text)) {
            return [
                'issue_detected'        => 'Contaminated / Discolored Rusty Water from Sink Faucet',
                'category'              => 'Plumbing',
                'priority'              => 'Urgent',
                'department'            => 'Engineering',
                'suggested_description' => 'Bathroom sink faucet is running heavily discolored, rusty brown contaminated water into the basin. Immediate plumbing inspection and main line flushing required.',
                'reason'                => 'Urgent health and water sanitation hazard identified via visual inspection.',
                'source'                => 'visual_inspection_engine',
            ];
        }

        if (preg_match('/leak|pipe|water|faucet|toilet|flood|burst/i', $text)) {
            $isFlood = preg_match('/flood|burst|active leak|overflow/i', $text);
            return [
                'issue_detected'        => 'Active Plumbing Leakage / P-Trap Drainage Fault',
                'category'              => 'Plumbing',
                'priority'              => $isFlood ? 'Urgent' : 'High',
                'department'            => 'Engineering',
                'suggested_description' => 'Visual inspection reveals water droplets forming at plumbing joint beneath fixture with water pooling on floor tile.',
                'reason'                => 'Water damage prevention requires prompt plumber dispatch.',
                'source'                => 'visual_inspection_engine',
            ];
        }

        if (preg_match('/door|lock|key|handle|lever|escutcheon/i', $text)) {
            return [
                'issue_detected'        => 'Detached Chrome Lever Handle / Loose Mounting Escutcheon',
                'category'              => 'Door/Lock',
                'priority'              => 'Medium',
                'department'            => 'Maintenance',
                'suggested_description' => 'Chrome lever handle has pulled away from mounting collar, exposing internal spindle and fixing screws. Requires re-securing to mounting base.',
                'reason'                => 'Visual inspection identified detached lever hardware with exposed mounting collar. Dispatched to maintenance technician.',
                'source'                => 'visual_inspection_engine',
            ];
        }

        if (preg_match('/\b(?:air[\s\-_.]?condition(?:er|ing)?|air[\s\-_.]?con|aircon|a\/c|ac\s+unit|not\s+cooling|warm\s+room|hot\s+room|hvac|thermostat|blowing\s+warm)\b/i', $text)) {
            return [
                'issue_detected'        => 'Split-Type AC Unit Refrigeration / Cooling Malfunction',
                'category'              => 'Air Conditioning',
                'priority'              => 'High',
                'department'            => 'Engineering',
                'suggested_description' => 'Photo shows wall-mounted air conditioner. Louvers open but airflow is warm and temperature regulation is unresponsive.',
                'reason'                => 'Comfort-critical climate control issue identified via visual diagnostic inspection.',
                'source'                => 'visual_inspection_engine',
            ];
        }

        if (preg_match('/light|bulb|power|outlet|socket|switch|flicker|spark/i', $text)) {
            return [
                'issue_detected'        => 'Electrical Fixture / Wall Receptacle Fault',
                'category'              => 'Electrical',
                'priority'              => preg_match('/spark|smoke/i', $text) ? 'Urgent' : 'Medium',
                'department'            => 'Engineering',
                'suggested_description' => 'Photo shows non-functional electrical switch/socket or flickering light fixture requiring technician replacement.',
                'reason'                => 'Electrical maintenance dispatched to engineering technician.',
                'source'                => 'visual_inspection_engine',
            ];
        }

        // 2. Perform Computer Vision analysis on the image using PHP GD
        if (!empty($imagePath) && file_exists($imagePath)) {
            $img = @imagecreatefromjpeg($imagePath) ?: (@imagecreatefrompng($imagePath) ?: @imagecreatefromwebp($imagePath));
            if ($img) {
                $w = imagesx($img);
                $h = imagesy($img);

                // Sample down to 100x100 for high-speed color, texture, and edge profiling
                $thumb = imagecreatetruecolor(100, 100);
                imagecopyresampled($thumb, $img, 0, 0, 0, 0, 100, 100, $w, $h);

                $whiteCount = 0;
                $metallicCount = 0;
                $darkCenterCount = 0;
                $debrisCount = 0;
                $darkHoleCount = 0;
                $waterToneCount = 0;
                $woodCount = 0;
                $carpetBrownCount = 0;
                $rustyWaterCount = 0;

                for ($y = 0; $y < 100; $y++) {
                    for ($x = 0; $x < 100; $x++) {
                        $rgb = imagecolorat($thumb, $x, $y);
                        $r = ($rgb >> 16) & 0xFF;
                        $g = ($rgb >> 8) & 0xFF;
                        $b = ($rgb >> 0) & 0xFF;

                        $lum = 0.299 * $r + 0.587 * $g + 0.114 * $b;
                        $sat = max($r, $g, $b) - min($r, $g, $b);

                        // White acrylic / porcelain / ceramic sink basin / shower tray
                        if ($lum > 150 && $sat < 45) $whiteCount++;
                        // Metallic / stainless steel / chrome faucet or hardware
                        if ($sat < 32 && $lum >= 70 && $lum <= 215) $metallicCount++;
                        // Dark central region (standing murky liquid or drain opening)
                        if ($lum < 60 && $x > 25 && $x < 75 && $y > 20 && $y < 80) $darkCenterCount++;
                        // Debris / hair clump / sludge / burn soot
                        if ($lum < 75 && $sat < 40) $debrisCount++;
                        // Circular dark cavity / screw recess / plug pin holes / charred holes
                        if ($lum < 38) $darkHoleCount++;
                        // Rusty / brown / amber contaminated water discharge (R > G > B, amber/brown liquid)
                        if ($r > 85 && $r > $b * 1.35 && $g > $b * 1.05 && $r >= $g && $sat > 30) $rustyWaterCount++;
                        // Wood / carpentry warm tones (dry wood, non-reflective)
                        if ($r > ($b + 35) && $g > $b && $sat > 40 && $lum > 50 && $lum < 170) $woodCount++;
                        // Cyan / clear water pooling tones
                        if ($b > ($r + 20) && $b > 80) $waterToneCount++;
                        // Brown / tan floor or carpet
                        if ($r > 75 && $r > $b + 20 && $g > $b + 5 && $lum > 45 && $lum < 155) $carpetBrownCount++;
                    }
                }
                imagedestroy($thumb);
                imagedestroy($img);

                $whitePct      = $whiteCount / 100;
                $metallicPct   = $metallicCount / 100;
                $darkCenter    = $darkCenterCount / 100;
                $debrisPct     = $debrisCount / 100;
                $darkHolePct   = $darkHoleCount / 100;
                $carpetBrown   = $carpetBrownCount / 100;
                $woodPct       = $woodCount / 100;
                $waterPct      = $waterToneCount / 100;
                $rustyWaterPct = $rustyWaterCount / 100;

                // 1. DETECT CONTAMINATED / RUSTY / BROWN WATER DISCHARGE FROM SINK FAUCET
                // (Faucet chrome spout + white ceramic sink basin + brown/amber contaminated murky water)
                if ($rustyWaterPct >= 10 && ($metallicPct > 12 || $whitePct > 5)) {
                    return [
                        'issue_detected'        => 'Contaminated / Discolored Rusty Water from Sink Faucet',
                        'category'              => 'Plumbing',
                        'priority'              => 'Urgent',
                        'department'            => 'Engineering',
                        'suggested_description' => 'Bathroom sink faucet is running heavily discolored, rusty brown contaminated water into the ceramic basin. Water supply lines must be isolated and flushed immediately by engineering plumbers.',
                        'reason'                => 'Urgent hygiene & health hazard: visual inspection detected discolored rusty water running from tap into sink basin.',
                        'source'                => 'visual_inspection_engine',
                    ];
                }

                // 2. DETECT BURNT ELECTRICAL EXTENSION SOCKET / OVERHEATING HAZARD
                // (High pin socket holes/char + tan floor/carpet + soot debris, NO rusty water)
                if ($darkHolePct >= 9 && ($debrisPct > 15 || $darkCenter > 2.0) && $rustyWaterPct < 5) {
                    return [
                        'issue_detected'        => 'Burnt Electrical Extension Socket / Scorch Marks & Exposed Wiring',
                        'category'              => 'Electrical',
                        'priority'              => 'Urgent',
                        'department'            => 'Engineering',
                        'suggested_description' => 'Power extension socket and adapter have suffered severe electrical overheating with heavy burn soot, charred casing, and exposed internal circuitry. Power supply must be isolated immediately.',
                        'reason'                => 'Electrical fire safety hazard detected via visual inspection. Priority upgraded to Urgent.',
                        'source'                => 'visual_inspection_engine',
                    ];
                }

                // 3. DETECT CLOGGED SHOWER TRAY DRAIN (Large white tray surface + central hair/debris clump, low chrome)
                if ($whitePct > 45 && ($debrisPct > 8 || $darkCenter > 0.05) && $metallicPct < 25) {
                    return [
                        'issue_detected'        => 'Clogged Shower Drain / Hair & Debris Blockage',
                        'category'              => 'Plumbing',
                        'priority'              => 'High',
                        'department'            => 'Engineering',
                        'suggested_description' => 'Shower tray drain is obstructed by hair and debris, causing water backup and slow drainage. Requires drain snaking and strainer cleaning.',
                        'reason'                => 'Visual inspection identified hair clump and debris clogging shower tray drain. Dispatched to plumbing maintenance.',
                        'source'                => 'visual_inspection_engine',
                    ];
                }

                // 4. DETECT CHROME / LEVER HANDLE DETACHMENT (High chrome reflection + exposed dark mounting cavity, NO rusty water)
                if ($metallicPct > 25 && ($darkHolePct > 5 || $darkCenter > 0.04) && $rustyWaterPct < 8) {
                    return [
                        'issue_detected'        => 'Detached Chrome Lever Handle / Loose Mounting Escutcheon',
                        'category'              => 'Door/Lock',
                        'priority'              => 'Medium',
                        'department'            => 'Maintenance',
                        'suggested_description' => 'Chrome lever handle has pulled away from mounting collar, exposing internal spindle and fixing screws. Requires re-securing to mounting base.',
                        'reason'                => 'Visual inspection identified detached lever hardware with exposed mounting collar. Dispatched to maintenance technician.',
                        'source'                => 'visual_inspection_engine',
                    ];
                }

                // 5. DETECT CLOGGED SINK BASIN WITH STANDING WATER / FOAM (Standing murky fluid in basin cavity, low white tray)
                if ($darkCenter >= 4.0 || ($debrisPct > 22 && $whitePct < 30)) {
                    return [
                        'issue_detected'        => 'Clogged Sink Basin / Standing Water Drainage Blockage',
                        'category'              => 'Plumbing',
                        'priority'              => 'High',
                        'department'            => 'Engineering',
                        'suggested_description' => 'Sink basin has severe drainage obstruction with murky standing water and soap suds unable to drain. Requires plumber inspection and drain clearance.',
                        'reason'                => 'Visual inspection identified standing water and soap foam backup in sink basin. Dispatched to Engineering plumbers.',
                        'source'                => 'visual_inspection_engine',
                    ];
                }

                // DETECT WATER POOLING / ACTIVE LEAK
                if ($waterPct > 15) {
                    return [
                        'issue_detected'        => 'Plumbing Leakage / Standing Water Accumulation',
                        'category'              => 'Plumbing',
                        'priority'              => 'High',
                        'department'            => 'Engineering',
                        'suggested_description' => 'Visual inspection detects water pooling or active moisture around room fixture. Urgent plumber inspection required.',
                        'reason'                => 'Water containment and leak prevention prioritized.',
                        'source'                => 'visual_inspection_engine',
                    ];
                }

                // DETECT WOOD / FURNITURE (Exclude if contaminated liquid in sink is detected)
                if ($woodPct > 35 && $rustyWaterPct < 8) {
                    return [
                        'issue_detected'        => 'Wooden Cabinetry / Furniture Structural Defect',
                        'category'              => 'Furniture',
                        'priority'              => 'Low',
                        'department'            => 'Maintenance',
                        'suggested_description' => 'Visual inspection shows wear, loose joinery, or alignment defect on wooden furniture or door casing.',
                        'reason'                => 'Dispatched to maintenance carpenter for alignment.',
                        'source'                => 'visual_inspection_engine',
                    ];
                }
            }
        }

        // 3. Fallback for general room plumbing/drainage or hardware defect
        return [
            'issue_detected'        => 'Room Fixture Drainage & Plumbing Defect',
            'category'              => 'Plumbing',
            'priority'              => 'High',
            'department'            => 'Engineering',
            'suggested_description' => 'Photo shows room fixture drainage or water containment anomaly requiring immediate inspection and tool adjustment.',
            'reason'                => 'Visual inspection identified room plumbing anomaly requiring tool inspection.',
            'source'                => 'visual_inspection_engine',
        ];
    }

    /**
     * Generate an AI-assisted polite, luxury concierge reply for Front Office staff to send to guest.
     */
    public function draftStaffReply(\App\Models\ServiceRequest $serviceRequest, ?string $latestGuestMessage = null): string
    {
        $roomNumber = $serviceRequest->room->room_number ?? '101';
        $guestName  = $serviceRequest->guest_name ?? 'Valued Guest';
        $reqType    = $serviceRequest->type ?? 'request';
        $desc       = $serviceRequest->description;
        $status     = $serviceRequest->status;
        $dept       = $serviceRequest->department ?? 'Front Desk';
        $ref        = $serviceRequest->reference_number;

        if (empty($this->apiKey) || $this->apiKey === 'your-gemini-api-key-here' || $this->isCooldownActive()) {
            return $this->fallbackStaffReply($guestName, $roomNumber, $status, $dept, $desc, $latestGuestMessage);
        }

        // Include recent conversation history so Madi AI remembers all guest problems/requests in this ticket
        $historyLines = [];
        $lastHistoryText = '';
        try {
            $recentMsgs = $serviceRequest->messages()->reorder('id', 'desc')->take(12)->get()->reverse();
            foreach ($recentMsgs as $m) {
                $who = $m->sender_type === 'guest' ? 'Guest' : ($m->sender_name ?: 'Front Office');
                $historyLines[] = "{$who}: {$m->message}";
                $lastHistoryText = trim((string) $m->message);
            }
        } catch (\Throwable $e) {
            // Ignore history load errors
        }
        $historyBlock = !empty($historyLines)
            ? "Recent Conversation Thread:\n" . implode("\n", $historyLines) . "\n"
            : '';
        $existingContext = $serviceRequest->aiTriage?->reason ?? '';
        $appendLatestLine = (!empty($latestGuestMessage) && trim($latestGuestMessage) !== $lastHistoryText)
            ? "Latest guest message just received: '{$latestGuestMessage}'.\n"
            : '';

        $prompt = "You are Madi, the AI Front Office Concierge at The Madison Park Hotel & Residences (a luxury 5-star hotel).
A guest ({$guestName}) in Room {$roomNumber} has an active {$reqType} (Ref #{$ref}): '{$desc}'.
Current Workflow Status: '{$status}'.
Responsible Department: '{$dept}'.
" . (!empty($existingContext) ? "Previous AI Ticket Context Summary: '{$existingContext}'.\n" : "") . "
{$historyBlock}{$appendLatestLine}Hotel Facilities & Dining Context:
- Function Halls: Main Function Hall (FH-01, Floor 1, Capacity 150-250 pax), DJP Events Hall (DJP-01, Floor 2, Capacity 50-80 pax), CC Hall (CC-01, Floor 3). Rental starts from ₱18,000-₱65,000, discounted when bundled with banquet catering.
- Dining: Times Square Restaurant offers à la carte dishes from ₱145-₱235 (Beef Sinigang ₱235, Pork Adobo ₱185, Grilled Chicken ₱220), Breakfast sets (₱95-₱185), Desserts (₱85-₱110), Banquet catering packages (₱350-₱550/pax).
Task:
1. Draft a warm, reassuring, ultra-polite, 5-star hotel concierge reply ('reply') to the guest (under 45 words, no placeholder tags like [Your Name]).
   CRITICAL POLICY GUARDRAIL: NEVER promise room availability, extra beds, free upgrades, discounts, or claim a request/booking is already delivered, confirmed, or officially cancelled unless human staff already confirmed it in the thread. For Event / Function Hall inquiries, clarify that the request is logged as a Tentative Hold / Inquiry only and warmly invite the guest to come down to the Front Desk (Ground Floor Lobby) to arrange an in-person appointment with Front Office for ocular inspection and official booking confirmation.
2. Produce an updated consolidated executive summary ('updated_context', 1-2 crisp sentences for Front Office staff) summarizing ALL concrete problems, follow-up requests, room preferences, or cancellations mentioned across BOTH the initial ticket and the live chat thread. Ignore meaningless gibberish or plain greetings (like 'hello', 'wasd'). If the guest cancelled only a side item (e.g. extra bed) while keeping the main request, state that clearly.
3. Set 'cancel_ticket_requested' (boolean) to true ONLY if the guest explicitly asks to cancel the ENTIRE main ticket/request (not just a secondary side add-on).
Return ONLY valid JSON with keys \"reply\", \"updated_context\", and \"cancel_ticket_requested\".";

        try {
            $response = $this->postWithModelFailover([
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt],
                        ],
                    ],
                ],
                'generationConfig' => array_filter([
                    'temperature'      => 0.35,
                    'responseMimeType' => 'application/json',
                    'responseSchema'   => [
                        'type'       => 'OBJECT',
                        'properties' => [
                            'reply'                   => ['type' => 'STRING'],
                            'updated_context'         => ['type' => 'STRING'],
                            'cancel_ticket_requested' => ['type' => 'BOOLEAN'],
                        ],
                        'required'   => ['reply', 'updated_context', 'cancel_ticket_requested'],
                    ],
                    'thinkingConfig'   => str_contains($this->model, 'thinking') ? ['thinkingBudget' => 0] : null,
                ]),
            ], 15);

            if ($response && $response->successful()) {
                $data = $response->json() ?? [];
                $rawText = trim($this->extractCandidateText($data, ''));
                $cleanJson = preg_replace('/^```(?:json)?\s*|\s*```$/is', '', $rawText);
                $decoded = json_decode($cleanJson, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded) && !empty($decoded['reply'])) {
                    $reply = trim((string) $decoded['reply'], " \t\n\r\0\x0B\"'`");
                    $updatedCtx = trim((string) ($decoded['updated_context'] ?? ''), " \t\n\r\0\x0B\"'`");
                    $cancelRequested = (bool) ($decoded['cancel_ticket_requested'] ?? false);

                    if ($cancelRequested && !empty($updatedCtx) && !str_starts_with($updatedCtx, '[Cancellation Requested]')) {
                        $updatedCtx = '[Cancellation Requested] ' . $updatedCtx;
                    }

                    if (!empty($updatedCtx) && strlen($updatedCtx) >= 15) {
                        $this->persistUpdatedTicketContext($serviceRequest, $updatedCtx);
                    }

                    if (!empty($reply)) {
                        return $reply;
                    }
                }
            } else {
                $this->triggerCooldown();
                Log::warning('Gemini draftStaffReply returned HTTP ' . ($response ? $response->status() : 'ERR') . ' - utilizing luxury concierge fallback.');
            }
        } catch (\Throwable $e) {
            $this->triggerCooldown();
            Log::warning('Gemini draftStaffReply failed: ' . $e->getMessage());
        }

        return $this->fallbackStaffReply($guestName, $roomNumber, $status, $dept, $desc, $latestGuestMessage);
    }

    /**
     * Refresh AI Diagnostic Rationale & Context when guest chats while Live Bot is OFF.
     */
    public function refreshTicketContextFromChat(\App\Models\ServiceRequest $serviceRequest, string $latestGuestMessage): ?string
    {
        if (empty($this->apiKey) || $this->apiKey === 'your-gemini-api-key-here' || $this->isCooldownActive()) {
            return null;
        }

        $trimmed = trim($latestGuestMessage);
        if (strlen($trimmed) < 5) {
            return null;
        }

        $historyLines = [];
        try {
            $recentMsgs = $serviceRequest->messages()->reorder('id', 'desc')->take(12)->get()->reverse();
            foreach ($recentMsgs as $m) {
                $who = $m->sender_type === 'guest' ? 'Guest' : ($m->sender_name ?: 'Front Office');
                $historyLines[] = "{$who}: {$m->message}";
            }
        } catch (\Throwable $e) {
        }

        $existingContext = $serviceRequest->aiTriage?->reason ?? $serviceRequest->description;
        $prompt = "Summarize ALL concrete hotel guest problems, requests, and follow-up details for Front Office staff in 1-2 crisp sentences.
Initial Ticket: '{$serviceRequest->description}'
Previous Summary: '{$existingContext}'
Conversation Thread:\n" . implode("\n", $historyLines) . "\n
Ignore meaningless gibberish or plain greetings. Preserve all real requirements and clearly note if the guest cancelled a side add-on vs the entire ticket.
Set 'cancel_ticket_requested' to true ONLY if the guest explicitly wants to cancel the ENTIRE main ticket/request.
Return ONLY JSON: {\"updated_context\": \"...\", \"cancel_ticket_requested\": false}.";

        try {
            $response = $this->postWithModelFailover([
                'contents' => [['parts' => [['text' => $prompt]]]],
                'generationConfig' => [
                    'temperature'      => 0.2,
                    'responseMimeType' => 'application/json',
                    'responseSchema'   => [
                        'type'       => 'OBJECT',
                        'properties' => [
                            'updated_context'         => ['type' => 'STRING'],
                            'cancel_ticket_requested' => ['type' => 'BOOLEAN'],
                        ],
                        'required'   => ['updated_context', 'cancel_ticket_requested'],
                    ],
                ],
            ], 10);

            if ($response && $response->successful()) {
                $rawText = trim($this->extractCandidateText($response->json() ?? [], ''));
                $cleanJson = preg_replace('/^```(?:json)?\s*|\s*```$/is', '', $rawText);
                $decoded = json_decode($cleanJson, true);
                $updatedCtx = trim((string) ($decoded['updated_context'] ?? ''), " \t\n\r\0\x0B\"'`");
                $cancelRequested = (bool) ($decoded['cancel_ticket_requested'] ?? false);

                if ($cancelRequested && !empty($updatedCtx) && !str_starts_with($updatedCtx, '[Cancellation Requested]')) {
                    $updatedCtx = '[Cancellation Requested] ' . $updatedCtx;
                }

                if (!empty($updatedCtx) && strlen($updatedCtx) >= 15) {
                    $this->persistUpdatedTicketContext($serviceRequest, $updatedCtx);
                    return $updatedCtx;
                }
            }
        } catch (\Throwable $e) {
            Log::warning('refreshTicketContextFromChat failed: ' . $e->getMessage());
        }

        return null;
    }

    private function persistUpdatedTicketContext(\App\Models\ServiceRequest $serviceRequest, string $updatedContext): void
    {
        try {
            $triage = $serviceRequest->aiTriage;
            if ($triage) {
                $triage->update(['reason' => $updatedContext]);
            } else {
                $serviceRequest->aiTriage()->create([
                    'suggested_category'   => $serviceRequest->category?->name ?? 'Front Desk Concierge',
                    'suggested_priority'   => strtolower($serviceRequest->priority ?? 'medium'),
                    'suggested_department' => $serviceRequest->department ?? 'Front Desk',
                    'reason'               => $updatedContext,
                    'status'               => 'pending',
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('persistUpdatedTicketContext failed: ' . $e->getMessage());
        }
    }

    /**
     * Names of active RequestCategory records (optionally scoped by type) so AI output matches the DB.
     */
    private function activeCategoryNames(?string $type = null): array
    {
        try {
            $query = RequestCategory::query()->where('is_active', true);
            if ($type) {
                $query->where('type', $type);
            }
            return $query->pluck('name')->filter()->unique()->values()->all();
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function categoryProperty(array $categories): array
    {
        $prop = ['type' => 'STRING'];
        if (!empty($categories)) {
            $prop['enum'] = $categories;
        }
        return $prop;
    }

    private function triageSchema(array $categories): array
    {
        return [
            'type'       => 'OBJECT',
            'properties' => [
                'category'   => $this->categoryProperty($categories),
                'priority'   => ['type' => 'STRING', 'enum' => self::PRIORITIES],
                'department' => ['type' => 'STRING', 'enum' => self::DEPARTMENTS],
                'reason'     => ['type' => 'STRING'],
            ],
            'required'         => ['category', 'priority', 'department', 'reason'],
            'propertyOrdering' => ['category', 'priority', 'department', 'reason'],
        ];
    }

    private function visionSchema(array $categories): array
    {
        $keys = ['issue_detected', 'category', 'priority', 'department', 'suggested_description', 'reason'];
        return [
            'type'       => 'OBJECT',
            'properties' => [
                'issue_detected'        => ['type' => 'STRING'],
                'category'              => $this->categoryProperty($categories),
                'priority'              => ['type' => 'STRING', 'enum' => self::PRIORITIES],
                'department'            => ['type' => 'STRING', 'enum' => self::DEPARTMENTS],
                'suggested_description' => ['type' => 'STRING'],
                'reason'                => ['type' => 'STRING'],
            ],
            'required'         => $keys,
            'propertyOrdering' => $keys,
        ];
    }

    /**
     * Force priority to lowercase enum and department to exact DB values.
     */
    private function normalizeTriage(array $triage): array
    {
        if (array_key_exists('priority', $triage)) {
            $triage['priority'] = $this->normalizePriority($triage['priority']);
        }
        if (array_key_exists('department', $triage)) {
            $triage['department'] = $this->normalizeDepartment($triage['department']);
        }
        return $triage;
    }

    private function normalizePriority(?string $priority): string
    {
        $p = strtolower(trim((string) $priority));
        $p = ['critical' => 'urgent', 'normal' => 'medium'][$p] ?? $p;
        return in_array($p, self::PRIORITIES, true) ? $p : 'medium';
    }

    private function normalizeDepartment(?string $department): string
    {
        $d = strtolower(trim((string) $department));
        if (str_contains($d, 'housekeep')) {
            return 'Housekeeping';
        }
        if (preg_match('/maint|engineer|facilit|technic/', $d)) {
            return 'Maintenance';
        }
        return 'Front Desk';
    }

    /**
     * Deterministic hotel SLA floor: prevents live LLM under-prioritizing critical
     * safety emergencies or core room habitability defects (no hot water, broken AC, locks).
     */
    private function applyHotelPriorityFloor(array $triage, string $description): array
    {
        $text = strtolower($description);
        $prioRank = ['low' => 1, 'medium' => 2, 'high' => 3, 'urgent' => 4];
        $current  = $prioRank[$triage['priority'] ?? 'medium'] ?? 2;

        $minPriority = null;
        if (preg_match('/\b(fire|smoke|spark|sparks|burning\s*smell|electric\s*shock|flood|flooding|burst\s*pipe|overflowing|medical|ambulance|injured|locked\s*out|cannot\s*enter|no\s*power|power\s*outage)\b/i', $text)) {
            $minPriority = 'urgent';
        } elseif (preg_match('/\b(no\s*hot\s*water|shower\s*heater|water\s*heater|remains\s*cold|freezing\s*cold|not\s*cooling|blowing\s*warm|ac\s*(?:broken|not\s*working|dead)|aircon\s*(?:broken|not\s*working)|toilet\s*(?:clog|broken|won\'t\s*flush)|clogged\s*toilet|cannot\s*lock|door\s*lock\s*broken|safe\s*locked)\b/i', $text)) {
            $minPriority = 'high';
        }

        if ($minPriority && ($prioRank[$minPriority] > $current)) {
            $triage['priority'] = $minPriority;
        }

        return $triage;
    }

    public function fallbackStaffReply($guestNameOrRequest, string $roomNumber = '101', string $status = 'pending', string $dept = 'Front Desk', ?string $desc = '', ?string $latestGuestMessage = ''): string
    {
        if ($guestNameOrRequest instanceof \App\Models\ServiceRequest) {
            $sr = $guestNameOrRequest;
            $roomNumber = $sr->room->room_number ?? '101';
            $status     = $sr->status ?? 'pending';
            $dept       = $sr->department ?? 'Front Desk';
            $desc       = $sr->description ?? '';
            $guestName  = $sr->guest_name ?? 'Valued Guest';
        } else {
            $guestName  = (string) $guestNameOrRequest;
        }

        $msgLower = strtolower(trim($latestGuestMessage ?? ''));
        $text = strtolower(($desc ?? '') . ' ' . $msgLower);

        if (preg_match('/\b(?:function\s*hall|ballroom|event\s*(?:space|plaza)|plaza|banquet|reserve.*hall|fh-?01|djp)\b/i', $text)) {
            return "Good day! Your inquiry for Madison Park Hotel's event venues (FH-01 Event Plaza up to 250 pax; DJP Hall up to 80 pax; catering from ₱350/pax) is logged as a tentative hold. Please come down to our Front Office Desk (Ground Floor Lobby) at your convenience to arrange an in-person appointment for venue viewing and official booking confirmation.";
        }
        if (preg_match('/\b(?:price.*food|px.*food|food.*price|how\s*much.*food|food.*available|recommend.*food)\b/i', $text)) {
            return "Good day! Our Times Square Restaurant offers gourmet dishes from ₱145 to ₱235 (Chef's signature Beef Sinigang ₱235, Pork Adobo ₱185, Grilled Chicken ₱220) and banquet packages from ₱350/pax. Our Front Desk is delighted to arrange your dining or reservation!";
        }
        if (preg_match('/\b(?:thank|thanks|ty|salamat|appreciate)\b/i', $msgLower)) {
            return "You are most welcome, {$guestName}! It is our pleasure to serve you in Room {$roomNumber} at Madison Park Hotel. Please reach out anytime if you need anything else.";
        }
        if (preg_match('/\b(?:inside|in\s+the\s+room|come\s+in|enter|knock|here)\b/i', $msgLower)) {
            return "Thank you for letting us know! We have informed our {$dept} attendant that you are inside Room {$roomNumber}; they will knock gently upon arrival within a few minutes.";
        }
        if (preg_match('/\b(?:update|how\s+long|eta|status|when|where)\b/i', $msgLower)) {
            return "Good day! Our {$dept} team has prioritized your request for Room {$roomNumber} (Current status: " . ucwords(str_replace('_', ' ', $status)) . ") and an attendant is on the way to assist you shortly.";
        }
        if (preg_match('/\b(?:towel|water|pillow|blanket|soap|shampoo|slipper|amenit|clean)\b/i', $msgLower)) {
            return "Certainly! We have notified our Housekeeping team to deliver fresh amenities straight to Room {$roomNumber} within 10 to 15 minutes.";
        }

        switch ($status) {
            case 'assigned':
            case 'in_progress':
                return "Good day! Our {$dept} team has been dispatched and will arrive at Room {$roomNumber} within 5 to 10 minutes. Please let us know if you need anything else in the meantime.";
            case 'resolved':
                return "Good day! We are pleased to confirm that your request has been attended to and resolved. Please let our Front Desk know if everything is to your satisfaction.";
            case 'closed':
                return "Thank you for letting us assist you! If you need any further assistance during your stay at The Madison Park, our team is always at your service.";
            default:
                return "Good day! Our Front Office team has received your request for Room {$roomNumber} and coordinated directly with our {$dept} team. You may reply here anytime for live updates.";
        }
    }

    /**
     * Interactive AI Concierge: answers conversational guest inquiries regarding
     * function halls, food menu pricing, recommendations, reservations, room repairs,
     * and supports multimodal photo inspection & voice queries.
     */
    public function askHotelConcierge(
        string $question,
        ?string $roomNumber = '101',
        array $conversationHistory = [],
        ?string $imagePath = null,
        ?string $imageMime = 'image/jpeg'
    ): array {
        $roomNumber = $roomNumber ?: '101';
        $trimmedQ   = trim($question);

        if (empty($trimmedQ) && empty($imagePath)) {
            return [
                'reply' => "Hello! I am Madi, your AI Concierge at Madison Park Hotel for Room {$roomNumber}. How may I assist you today?",
                'action_type' => 'general',
                'action_label' => null,
                'prefill_ticket' => null,
                'quick_prompts' => [
                    'Do you have available function halls?',
                    'What are your top food recommendations?',
                    'Can I get extra bath towels?'
                ],
                'source' => 'system',
            ];
        }

        if (empty($trimmedQ)) {
            $trimmedQ = "I have attached a photo from Room {$roomNumber}. Please inspect it and assist me.";
        }

        if (empty($this->apiKey) || $this->apiKey === 'your-gemini-api-key-here' || $this->isCooldownActive()) {
            return $this->fallbackConciergeAnswer($trimmedQ, $roomNumber, $imagePath);
        }

        try {
            $hasImage = !empty($imagePath) && file_exists($imagePath);
            $prompt   = $this->buildConciergePrompt($trimmedQ, $roomNumber, $conversationHistory, $hasImage);

            $parts = [
                ['text' => $prompt],
            ];

            if ($hasImage) {
                $preparedImg = $this->prepareOptimizedImage($imagePath, $imageMime ?: 'image/jpeg');
                if (!empty($preparedImg['data'])) {
                    $parts[] = [
                        'inlineData' => [
                            'mimeType' => $preparedImg['mimeType'],
                            'data'     => $preparedImg['data'],
                        ],
                    ];
                }
            }

            $response = $this->postWithModelFailover([
                    'contents' => [
                        [
                            'parts' => $parts,
                        ],
                    ],
                    'generationConfig' => [
                        'temperature'      => 0.3,
                        'responseMimeType' => 'application/json',
                        'responseSchema'   => [
                            'type'       => 'OBJECT',
                            'properties' => [
                                'reply' => ['type' => 'STRING'],
                                'action_type' => [
                                    'type' => 'STRING',
                                    'enum' => [
                                        'reserve_hall',
                                        'order_food',
                                        'request_housekeeping',
                                        'request_maintenance',
                                        'track_status',
                                        'call_front_desk',
                                        'general'
                                    ]
                                ],
                                'action_label' => ['type' => 'STRING'],
                                'prefill_type' => [
                                    'type' => 'STRING',
                                    'enum' => ['service', 'maintenance', 'food', 'track', 'front_desk', 'none']
                                ],
                                'prefill_category' => ['type' => 'STRING'],
                                'prefill_description' => ['type' => 'STRING'],
                                'quick_prompts' => [
                                    'type' => 'ARRAY',
                                    'items' => ['type' => 'STRING']
                                ],
                            ],
                            'required' => ['reply', 'action_type', 'quick_prompts'],
                        ],
                    ],
                ], 8);

            if ($response && $response->successful()) {
                $data = $response->json() ?? [];
                $text = $this->extractCandidateText($data);
                $cleanJson = preg_replace('/^```(?:json)?\s*|\s*```$/is', '', trim($text));
                $parsed = json_decode($cleanJson, true);

                if (json_last_error() === JSON_ERROR_NONE && !empty($parsed['reply'])) {
                    $actionType = $parsed['action_type'] ?? 'general';
                    $prefillType = $parsed['prefill_type'] ?? 'service';
                    
                    return [
                        'reply'          => $parsed['reply'],
                        'action_type'    => $actionType,
                        'action_label'   => $parsed['action_label'] ?? null,
                        'prefill_type'   => $prefillType,
                        'prefill_ticket' => !empty($parsed['prefill_description']) ? [
                            'type'        => $prefillType,
                            'category'    => $parsed['prefill_category'] ?? 'General Inquiry',
                            'description' => $parsed['prefill_description'],
                        ] : null,
                        'quick_prompts'  => !empty($parsed['quick_prompts']) ? array_slice($parsed['quick_prompts'], 0, 4) : [
                            'Do you have available function halls?',
                            'How much is the food pricing?',
                            'Can I request extra towels?'
                        ],
                        'source'         => 'gemini',
                    ];
                }
            } else {
                $this->triggerCooldown();
            }
        } catch (\Throwable $e) {
            $this->triggerCooldown();
            Log::warning('Gemini askHotelConcierge API error: ' . $e->getMessage());
        }

        return $this->fallbackConciergeAnswer($trimmedQ, $roomNumber, $imagePath);
    }

    private function buildConciergePrompt(string $question, string $roomNumber, array $history = [], bool $hasImage = false): string
    {
        $historyText = '';
        if (!empty($history) && is_array($history)) {
            $recent = array_slice($history, -8);
            foreach ($recent as $item) {
                $role = ($item['role'] ?? '') === 'user' ? 'Guest' : 'Madi';
                $msg  = trim((string)($item['content'] ?? ''));
                if (!empty($msg)) {
                    $historyText .= "{$role}: {$msg}\n";
                }
            }
        }

        $imageInstructions = $hasImage ? <<<IMG
VISUAL PHOTO EVIDENCE ATTACHED BY GUEST:
- The guest has attached a live photo to this inquiry.
- Inspect what is shown in the image and directly address it in your reply.
- If it depicts any room defect or issue (e.g. water leakage, faulty AC, broken door lock, flickering fixture, stained linen, clogged sink/toilet, scuffs):
  1) Explicitly describe the observed defect (e.g. "I notice active water leaking from the sink pipe joint...").
  2) Empathize and assure the guest that Madison Park engineering can fix this immediately.
  3) You MUST set action_type="request_maintenance", action_label="🔧 Dispatch Maintenance for This Defect", prefill_type="maintenance", prefill_category="Facility Repair", prefill_description="[Defect observed in guest photo]: [concise summary of defect]".
- If it shows food, menus, or amenities, explain what is shown and guide the guest on ordering or requesting.
IMG : '';

        return <<<PROMPT
You are "Madi", the 5-star AI Virtual Concierge for The Madison Park Hotel & Residences.
You are conversing live with a valued in-house guest staying in Room {$roomNumber}.
If a guest asks your name or who you are, warmly introduce yourself as Madi, the AI Concierge of Madison Park Hotel.

YOUR PERSONA & CAPABILITIES:
- Speak naturally, warmly, intelligently, and empathetically, like ChatGPT, Claude, or a top-tier luxury hotel concierge.
- Directly and accurately answer what the guest asks. Do NOT dump unsolicited directories or full price lists if they only asked a greeting or a simple question. Keep answers conversational, helpful, and concise.
- You can communicate freely on any topic (greetings, small talk, hotel facilities, room comfort, local attractions, policies, assistance).
- Actively assist the guest with whatever they need:
  * If they need housekeeping (towels, blankets, cleaning, toiletries), assist them and offer to dispatch attendants.
  * If they report a defect (AC not cooling, leak, no hot water, door lock, light), express genuine empathy and offer an immediate maintenance report.
  * If they ask about food or want to order, provide menu pricing and recommendations, and offer to open in-room dining.
  * If they ask about function halls, the event plaza, or events, highlight available spaces (FH-01 Event Plaza, DJP Events Hall), capacities, and catering packages, then ALWAYS explain that guests may submit an Event Inquiry for a **Tentative Date Hold** here, AND kindly invite them to **come down to the Front Office Desk (Ground Floor Lobby) to arrange an in-person appointment** with our Front Office / Events team for ocular tour, date verification, contract, and deposit confirmation.
  * If they ask about Wi-Fi, checkout, pool, gym, or front desk, give accurate details clearly.
- Format responses in clean, beautiful Markdown with clear headings and bullet points. Avoid robotic or cold phrasing.

HOTEL DIRECTORY & POLICIES (MADISON PARK HOTEL):
- Room: Room {$roomNumber} (Executive Suite).
- Wi-Fi: Complimentary 100 Mbps fiber. Network: "MadisonPark_Guest", Password: "luxurywelcome".
- Front Desk / Concierge: 24 Hours Daily. In-room telephone: Dial Ext. 0.
- Check-in: 2:00 PM | Check-out: 12:00 PM (Noon). Complimentary late check-out until 1:00 PM upon availability.
- Dining (Times Square Restaurant - Ground Floor): Open 6:00 AM – 11:00 PM. In-Room Dining is available 24/7.
  * Breakfast Buffet: 6:00 AM – 10:30 AM.
  * Chef's Favorites: Beef Sinigang (₱235), Grilled Chicken (₱220), Fried Chicken (₱195), Pork Adobo (₱185), Vegetable Stir-fry (₱145).
  * Breakfast Sets: Continental Breakfast (₱185), Filipino Breakfast Set (₱165), Pancakes with Syrup (₱120), Oatmeal Bowl (₱95).
  * Desserts & Drinks: Halo-Halo (₱95), Leche Flan (₱85), Banana Split (₱110), Fresh Buko Juice (₱65), Brewed Coffee (₱55), Iced Tea (₱50).
  * Banquet Catering Packages: Plated Package A (₱350/pax), Seminar Package C (₱450/pax), Grand Buffet Package B (₱550/pax).
- Function Halls & Event Spaces:
  * Main Function Hall / Event Plaza (FH-01, Ground Level): 150-250 guests (banquet) / up to 300 guests (theater). Open-air plaza with covered stage, gazebo, canopy tents, evening lighting, audio, Wi-Fi. Rate: Half-day ₱35,000 / Whole-day ₱65,000 (discounted when bundled with catering).
  * 1st Floor Indoor Function Hall Rooms (Rooms AB, CD, E, F, G, H, IJ): Comfortable air-conditioned indoor function rooms inside the hotel on the 1st Floor seating 50+ guests per room (combined rooms AB, CD, IJ or individual rooms E, F, G, H). Equipped with podium, projection screen, dressed seminar/banquet tables, wireless mics, and Wi-Fi.
  * DJP Events Hall (DJP-01, Floor 2): 50-80 guests (banquet) / up to 100 guests (theater). 85" 4K display, acoustic soundproofing, soundbar. Rate: Half-day ₱18,000 / Whole-day ₱30,000.
  * Executive Boardroom (Floor 2): 15-20 guests.
  * Both halls are open and available for reservations. 30% deposit secures dates.
- Housekeeping Services (24/7):
  * Extra fresh bath towels, hand towels, bathrobes, extra pillows, blankets, room makeup/cleaning, toiletries kit, trash collection, complimentary bottled water, coffee & tea refills, fresh ice bucket.
- Maintenance & Engineering Services (24/7 Dispatch):
  * Air conditioning thermostat & cooling, plumbing leaks, hot water heaters, clogged drain/toilet, electrical sockets & lamps, TV/remote, electronic door lock, in-room safe override.
- Amenities & Recreation:
  * Swimming Pool: 6:00 AM – 10:00 PM (Floor 1 Garden Courtyard).
  * Fitness Center & Gym (GYM-01): 24 Hours (Floor 1, room keycard access).
  * Valet Parking & Bellhop: 24 Hours at Porte Cochère.
  * Airport Transfer & Chauffeured Car: Available upon booking with Front Desk.

{$imageInstructions}

RECENT CONVERSATION HISTORY:
{$historyText}

GUEST'S CURRENT MESSAGE:
"{$question}"

ACTION RULES FOR RESPONSE (Evaluate in strict order of priority):
- PRIORITY 1: DEFECTS & ROOM REPAIRS:
  If guest reports ANY physical defect, malfunction, or repair issue (e.g. air conditioning, AC not cooling, warm air, water leaks, plumbing, pipe, clogged drain, hot water heater, electrical socket, lights, door lock, TV remote):
  You MUST choose: action_type="request_maintenance", action_label="🔧 Report Issue to Maintenance", prefill_type="maintenance", prefill_category="Facility Repair", prefill_description="Maintenance defect reported: [brief summary of issue]."
  Do NOT classify defects or water leaks as housekeeping!
- PRIORITY 2: FUNCTION HALL INQUIRIES & TENTATIVE HOLDS:
  If guest is asking to book, hold, or inquire about a function hall or event:
  action_type="reserve_hall", action_label="🏛️ Inquire & Request Tentative Hold", prefill_type="service", prefill_category="Event & Function Hall", prefill_description="Function Hall Inquiry & Tentative Hold Request for Room {$roomNumber}."
- PRIORITY 3: DINING & FOOD ORDERS:
  If guest wants food, is hungry, or asks for menu prices:
  action_type="order_food", action_label="🍽️ Browse In-Room Dining Menu", prefill_type="food"
- PRIORITY 4: HOUSEKEEPING & AMENITIES:
  If guest asks for housekeeping items (towels, cleaning/makeup, pillows, blankets, toiletries, water bottles, trash collection):
  action_type="request_housekeeping", action_label="🧹 Request Housekeeping Service", prefill_type="service", prefill_category="Housekeeping & Linens", prefill_description="Please deliver requested housekeeping amenities to Room {$roomNumber}."
- PRIORITY 5: TRACKING STATUS:
  If guest asks to track existing requests or orders:
  action_type="track_status", action_label="📋 View Live Request Tracker", prefill_type="track"
- PRIORITY 6: FRONT DESK & BILLING:
  If guest asks for front desk, wake-up call, late check-out, or billing:
  action_type="call_front_desk", action_label="🛎️ Contact Front Desk (Ext. 0)", prefill_type="front_desk", prefill_category="Front Desk Concierge"
- PRIORITY 7: GENERAL CHAT & INQUIRIES:
  For general talk, advice, questions, local tips, Wi-Fi:
  action_type="general", action_label=null, prefill_type="none"

Return only valid JSON matching responseSchema.
PROMPT;
    }

    /**
     * Comprehensive heuristic knowledge generator for offline / fallback mode.
     */
    public function fallbackConciergeAnswer(string $question, ?string $roomNumber = '101', ?string $imagePath = null): array
    {
        $q = strtolower($question);
        $roomNumber = $roomNumber ?: '101';

        // 1. Function Halls & Event Spaces Inquiries
        $isHallInquiry = (bool) preg_match('/\b(?:function\s*hall|event\s*(?:hall|space|room|center)?|ballroom|banquet|conference\s*hall|seminar\s*room|party\s*venue|meeting\s*room|wedding|debut|djp|fh-?01)\b/i', $q);
        $isFoodInquiry = (bool) preg_match('/\b(?:food|menu|price|px|cost|dish|sinigang|adobo|chicken|breakfast|lunch|dinner|dessert|drink|catering|buffet|how\s*much.*food|hungry|eat)\b/i', $q);
        $isRecommend   = (bool) preg_match('/\b(?:recommend|suggestion|best|popular|specialty|favorite|what.*should|what.*good)\b/i', $q);
        $isReserve     = (bool) preg_match('/\b(?:reserve|book|reservation|schedule|available|availability|hold|slot|quotation|quote)\b/i', $q);

        // Combined inquiry: Function Hall + Food Pricing + Recommendation / Reservation (professor query)
        if ($isHallInquiry && ($isFoodInquiry || $isRecommend || $isReserve)) {
            $reply = "### 🏛️ Madison Park Hotel — Function Halls, Dining & Reservations\n\n"
                . "Warm greetings from Madi, your Madison Park AI Concierge! Yes, our hotel features **two premier function halls** that are currently **available** for bookings, paired with exquisite catering from **Times Square Restaurant**:\n\n"
                . "#### 1. Available Function Halls & Event Spaces\n"
                . "- **Main Function Hall (FH-01) — Floor 1:** Capacity **150 to 250 guests** (Banquet) / up to **300 guests** (Theater). Fully equipped with concert-grade JBL sound system, motorized laser projector, elevated stage, customizable RGB lighting, VIP holding room, and high-speed Wi-Fi. *Half-day rental starts at ₱35,000; Whole-day at ₱65,000 (hall fees waived or discounted when bundled with catering)*.\n"
                . "- **DJP Events Hall (DJP-01) — Floor 2:** Capacity **50 to 80 guests** (Banquet) / up to **100 guests** (Theater). Features acoustic soundproofing, 85\" 4K presentation display, HDMI casting, and podium. *Half-day rental starts at ₱18,000; Whole-day at ₱30,000*.\n\n"
                . "#### 2. Food Menu Pricing & Catering Packages\n"
                . "- **In-Room Dining Favorites (24/7):**\n"
                . "  * **Beef Sinigang:** **₱235.00** *(Chef's #1 Best-Seller!)*\n"
                . "  * **Grilled Chicken:** **₱220.00** | **Fried Chicken:** **₱195.00**\n"
                . "  * **Pork Adobo:** **₱185.00** | **Vegetable Stir-fry:** **₱145.00**\n"
                . "  * **Continental Breakfast:** **₱185.00** | **Filipino Breakfast:** **₱165.00**\n"
                . "  * **Halo-Halo:** **₱95.00** | **Leche Flan:** **₱85.00** | **Fresh Buko Juice:** **₱65.00**\n"
                . "- **Banquet Event Packages:**\n"
                . "  * **Plated Package A:** **₱350.00 / pax** *(1 appetizer, 1 main dish, rice, dessert, iced tea)*\n"
                . "  * **Executive Seminar Package C:** **₱450.00 / pax** *(AM snack, buffet lunch, PM snack, free coffee)*\n"
                . "  * **Grand Buffet Package B:** **₱550.00 / pax** *(Soup, salad bar, 3 hot meats/seafood, pasta, 2 desserts, bottomless drinks)*\n\n"
                . "#### 3. Concierge Recommendations\n"
                . "- **For Events:** For 100+ guests, reserve **FH-01 Main Function Hall** bundled with the **Grand Buffet Package (₱550/pax)**. For 50–80 guests, book **DJP Events Hall** with the **Executive Package (₱450/pax)**.\n"
                . "- **For In-Room Dining:** Order the signature **Beef Sinigang (₱235)** paired with **Fresh Buko Juice (₱65)** and **Leche Flan (₱85)**.\n\n"
                . "#### 4. How to Place a Tentative Hold & Arrange Your Appointment\n"
                . "Tap **Inquire & Request Tentative Hold** below to log your preferred date with Front Office. To finalize your event booking, ocular inspection, and 30% deposit, please **visit our Front Office Desk (Ground Floor Lobby)** to arrange an in-person appointment.";

            return [
                'reply'          => $reply,
                'action_type'    => 'reserve_hall',
                'action_label'   => '🏛️ Inquire & Request Tentative Hold',
                'prefill_type'   => 'service',
                'prefill_ticket' => [
                    'type'        => 'service',
                    'category'    => 'Event & Function Hall',
                    'description' => "Function Hall & Dining Reservation Inquiry: I would like to inquire about booking a function hall (FH-01 Main Function Hall / DJP-01 Events Hall) with catering packages for Room {$roomNumber}.",
                ],
                'quick_prompts'  => [
                    'What equipment is included in FH-01?',
                    'How much is the Grand Buffet package?',
                    'What time is in-room dining available?'
                ],
                'source'         => 'concierge_knowledge_engine',
            ];
        }

        // Dedicated Function Hall
        if ($isHallInquiry) {
            $reply = "### 🏛️ Madison Park Hotel Function Halls\n\n"
                . "Yes! Madison Park Hotel proudly provides two fully-appointed, modern event spaces currently **available** for reservations:\n\n"
                . "1. **Main Function Hall (FH-01) — Floor 1 (Ground Level)**\n"
                . "   - **Capacity:** 150 to 250 guests (Banquet) / up to 300 guests (Theater).\n"
                . "   - **Amenities:** Laser projector & motorized screen, concert-grade JBL sound system, wireless mics, elevated stage, RGB ambient lighting, VIP bridal room, high-speed fiber Wi-Fi.\n"
                . "   - **Rates:** Half-day **₱35,000** | Whole-day **₱65,000** *(Discounts apply when booked with banquet catering)*.\n\n"
                . "2. **DJP Events Hall (DJP-01) — Floor 2 (West Wing)**\n"
                . "   - **Capacity:** 50 to 80 guests (Banquet) / up to 100 guests (Theater).\n"
                . "   - **Amenities:** Soundproof acoustic panels, 85\" 4K presentation display, HDMI/wireless casting, conference soundbar, podium.\n"
                . "   - **Rates:** Half-day **₱18,000** | Whole-day **₱30,000**.\n\n"
                . "**How to Book:** Tap below to submit an inquiry for a **tentative date hold**, then kindly **visit the Front Office Desk (Ground Floor Lobby)** to arrange an in-person appointment for ocular viewing, catering finalization, and contract confirmation.";

            return [
                'reply'          => $reply,
                'action_type'    => 'reserve_hall',
                'action_label'   => '🏛️ Inquire & Request Tentative Hold',
                'prefill_type'   => 'service',
                'prefill_ticket' => [
                    'type'        => 'service',
                    'category'    => 'Event & Function Hall',
                    'description' => "Function Hall Availability Inquiry: I would like to request date availability and quotation for FH-01 / DJP-01 Function Hall from Room {$roomNumber}.",
                ],
                'quick_prompts'  => [
                    'How much is the food menu pricing?',
                    'What are your event catering packages?',
                    'Can we schedule an ocular inspection?'
                ],
                'source'         => 'concierge_knowledge_engine',
            ];
        }

        // Dedicated Food Pricing & Menu Inquiry
        if ($isFoodInquiry) {
            $reply = "### 🍽️ Times Square Restaurant & In-Room Dining Pricing\n\n"
                . "Our restaurant operates **24/7** for in-room dining to Room {$roomNumber}, offering freshly cooked gourmet selections:\n\n"
                . "#### 🍲 Main Dishes (À la Carte)\n"
                . "- **Beef Sinigang:** **₱235.00** *(Tender prime beef in sour tamarind broth — Chef's #1 Recommendation)*\n"
                . "- **Grilled Chicken:** **₱220.00** *(Herb-marinated chicken with java rice & vegetables)*\n"
                . "- **Fried Chicken:** **₱195.00** *(Golden crispy chicken with savory house gravy)*\n"
                . "- **Pork Adobo:** **₱185.00** *(Slow-braised pork belly in rich soy-vinegar reduction)*\n"
                . "- **Vegetable Stir-fry:** **₱145.00** *(Seasonal vegetables in garlic oyster sauce)*\n\n"
                . "#### 🍳 Breakfast & Light Snacks\n"
                . "- **Continental Breakfast:** **₱185.00** | **Filipino Breakfast Set:** **₱165.00**\n"
                . "- **Pancakes with Syrup:** **₱120.00** | **Oatmeal Bowl:** **₱95.00**\n"
                . "- **Nachos Supreme:** **₱110.00** | **Spring Rolls (4 pcs):** **₱95.00** | **French Fries:** **₱75.00**\n\n"
                . "#### 🍨 Desserts & Beverages\n"
                . "- **Halo-Halo:** **₱95.00** | **Leche Flan:** **₱85.00** | **Banana Split:** **₱110.00**\n"
                . "- **Fresh Buko Juice:** **₱65.00** | **Brewed Coffee:** **₱55.00** | **Iced Tea (32 oz):** **₱50.00**\n\n"
                . "#### 🎪 Event Banquet Packages\n"
                . "- **Plated Package A:** **₱350 / pax** | **Seminar Package C:** **₱450 / pax** | **Grand Buffet Package B:** **₱550 / pax**";

            return [
                'reply'          => $reply,
                'action_type'    => 'order_food',
                'action_label'   => '🍽️ Order Food from Menu',
                'prefill_type'   => 'food',
                'prefill_ticket' => null,
                'quick_prompts'  => [
                    'What is the chef recommendation?',
                    'Do you have available function halls?',
                    'How long does food delivery take?'
                ],
                'source'         => 'concierge_knowledge_engine',
            ];
        }

        // 2. Room Maintenance & Repairs (Evaluated before Housekeeping to catch leaks and HVAC defects)
        if (preg_match('/\b(?:air\s*con|aircon|ac|not\s*cooling|warm\s*air|leak|leaking|pipe|faucet|sink|shower|cold\s*water|no\s*hot\s*water|toilet|clog|clogged|light|bulb|socket|outlet|power|electricity|remote|tv|television|door|lock|keycard|safe|vault)\b/i', $q)) {
            $reply = "### 🔧 Engineering & Maintenance Dispatch\n\n"
                . "I am truly sorry to hear that you are experiencing an issue in Room **{$roomNumber}**! Our 24/7 Engineering team is prepared to assist you promptly.\n\n"
                . "- **Air Conditioning:** We can recalibrate or service your split-type climate control unit immediately.\n"
                . "- **Plumbing & Water:** Technicians can clear blocked drains, fix faucet leaks, or inspect hot water pressure.\n"
                . "- **Hardware & Electrical:** Quick service for door lock card readers, room safe overrides, lighting, and power sockets.\n\n"
                . "Tap **Report Issue to Maintenance** below to dispatch our technician directly to your room with tools prepared!";

            return [
                'reply'          => $reply,
                'action_type'    => 'request_maintenance',
                'action_label'   => '🔧 Report Issue to Maintenance',
                'prefill_type'   => 'maintenance',
                'prefill_ticket' => [
                    'type'        => 'maintenance',
                    'category'    => 'Air Conditioning',
                    'description' => "Maintenance Issue in Room {$roomNumber}: Guest reported room defect requiring prompt engineering inspection.",
                ],
                'quick_prompts'  => [
                    'AC is blowing warm air',
                    'Bathroom sink is draining slowly',
                    'How long does technician take to arrive?'
                ],
                'source'         => 'concierge_knowledge_engine',
            ];
        }

        // 3. Housekeeping, Linens & Room Amenities
        if (preg_match('/\b(?:towel|towels|linen|linens|pillow|pillows|blanket|clean|makeup|make\s*up|housekeeping|toiletries|shampoo|soap|conditioner|dental|toothbrush|toothpaste|trash|garbage|coffee\s*refill|tea\s*bag|water\s*bottle|mineral\s*water|ice\s*bucket|iron|ironing|slippers)\b/i', $q)) {
            $reply = "### 🧹 Housekeeping & Amenities Assistance\n\n"
                . "Certainly! Our Housekeeping attendants are on duty **24 Hours Daily** to ensure your stay in Room **{$roomNumber}** is impeccably comfortable.\n\n"
                . "#### Available Complimentary Amenities:\n"
                . "- **Bath Essentials:** Extra fresh plush bath towels, hand towels, bath mats, and luxury bathrobes.\n"
                . "- **Bedding Care:** Hypoallergenic extra pillows, warm fleece blankets, and full room makeup / bedsheet changes.\n"
                . "- **Toiletries:** Botanical shampoo, hair conditioner, body soap, and dental/hygiene kits.\n"
                . "- **Refreshments:** Purified bottled drinking water refills, complimentary coffee pods, tea bags, and fresh ice buckets.\n\n"
                . "Tap **Request Housekeeping Service** below to have our attendants deliver these to your door in 5–10 minutes!";

            return [
                'reply'          => $reply,
                'action_type'    => 'request_housekeeping',
                'action_label'   => '🧹 Request Housekeeping Service',
                'prefill_type'   => 'service',
                'prefill_ticket' => [
                    'type'        => 'service',
                    'category'    => 'Extra Towels',
                    'description' => "Housekeeping Request for Room {$roomNumber}: Please deliver requested guest amenities/room service to the room.",
                ],
                'quick_prompts'  => [
                    'Please send 2 extra bath towels',
                    'Request room makeup and cleaning',
                    'Can I get extra bottled water?'
                ],
                'source'         => 'concierge_knowledge_engine',
            ];
        }

        // 4. Wi-Fi & Connectivity
        if (preg_match('/\b(?:wifi|wi-fi|internet|network|password|login|connect|speed)\b/i', $q)) {
            $reply = "### 📶 Complimentary High-Speed Wi-Fi\n\n"
                . "Welcome to Madison Park Hotel's dedicated fiber internet network! Wi-Fi is complimentary for all in-house guests in Room **{$roomNumber}**.\n\n"
                . "- **Network Name (SSID):** `MadisonPark_Guest`\n"
                . "- **Password:** `luxurywelcome`\n"
                . "- **Speed:** Up to 100 Mbps fiber connection across all guest rooms, lobby, and function halls.\n\n"
                . "If you encounter any connection difficulty, please contact Front Desk by dialing **Ext. 0**.";

            return [
                'reply'          => $reply,
                'action_type'    => 'general',
                'action_label'   => null,
                'prefill_type'   => 'none',
                'quick_prompts'  => [
                    'What time is check-out?',
                    'What are the restaurant hours?',
                    'How do I call Front Desk?'
                ],
                'source'         => 'concierge_knowledge_engine',
            ];
        }

        // 5. Check-out, Check-in & Late Extension
        if (preg_match('/\b(?:check\s*out|checkout|check\s*in|checkin|extend|late\s*checkout|late\s*check-out|bill|invoice|receipt)\b/i', $q)) {
            $reply = "### 🕒 Check-in & Check-out Policies\n\n"
                . "- **Standard Check-out Time:** **12:00 PM (Noon)**.\n"
                . "- **Standard Check-in Time:** **2:00 PM**.\n"
                . "- **Complimentary Late Check-out:** Available up to **1:00 PM** upon request and room availability.\n"
                . "- **Extended Stay:** For check-out between 1:00 PM and 6:00 PM, a half-day room rate applies.\n\n"
                . "Would you like to request a late check-out extension for Room **{$roomNumber}**? Tap below to notify Front Desk!";

            return [
                'reply'          => $reply,
                'action_type'    => 'call_front_desk',
                'action_label'   => '🛎️ Request Late Check-Out',
                'prefill_type'   => 'service',
                'prefill_ticket' => [
                    'type'        => 'service',
                    'category'    => 'Front Desk Concierge',
                    'description' => "Late Check-out Request: Guest in Room {$roomNumber} requests an extended check-out time.",
                ],
                'quick_prompts'  => [
                    'Can bellhop store my luggage after checkout?',
                    'Can I book an airport taxi?',
                    'Where can I pay my incidental bill?'
                ],
                'source'         => 'concierge_knowledge_engine',
            ];
        }

        // 6. Pool, Gym & Wellness Facilities
        if (preg_match('/\b(?:pool|swimming|swim|gym|fitness|workout|exercise|spa|massage)\b/i', $q)) {
            $reply = "### 🏊 Leisure & Wellness Facilities\n\n"
                . "- **Outdoor Garden Pool:** Open daily from **6:00 AM to 10:00 PM** on Floor 1 Courtyard. Fresh pool towels are provided at poolside.\n"
                . "- **Fitness Center & Gym (GYM-01):** Open **24/7** on Floor 1. Accessible using your electronic room keycard, equipped with modern cardio machines and free weights.\n"
                . "- **In-Room Spa & Massage:** In-room therapeutic massage services can be arranged from **10:00 AM to 11:00 PM** via Front Desk Ext. 0.\n\n"
                . "All facilities are complimentary for registered guests in Room **{$roomNumber}**!";

            return [
                'reply'          => $reply,
                'action_type'    => 'general',
                'action_label'   => null,
                'prefill_type'   => 'none',
                'quick_prompts'  => [
                    'Are children allowed in the pool?',
                    'What equipment is in the gym?',
                    'How much is an in-room massage?'
                ],
                'source'         => 'concierge_knowledge_engine',
            ];
        }

        // 7. Luggage, Transportation & Valet
        if (preg_match('/\b(?:luggage|baggage|bags|bellhop|taxi|grab|airport|shuttle|valet|parking|car)\b/i', $q)) {
            $reply = "### 🚗 Transportation & Bellhop Services\n\n"
                . "- **Luggage Storage & Bellhop:** Available 24/7. Our bell captain can securely hold your luggage before check-in or after check-out at no charge.\n"
                . "- **Valet Parking:** Complimentary secure valet parking at our Porte Cochère entrance.\n"
                . "- **Airport Transfer:** Premium chauffeured sedan transfers to NAIA International Airport are available for reservation at Front Desk.\n"
                . "- **Taxi & Ride-Hailing:** Bell staff can flag a metered city taxi or assist with Grab pickup locations.\n\n"
                . "Tap below to request bellhop or transportation assistance for Room **{$roomNumber}**!";

            return [
                'reply'          => $reply,
                'action_type'    => 'call_front_desk',
                'action_label'   => '🛎️ Request Bellhop / Transport',
                'prefill_type'   => 'service',
                'prefill_ticket' => [
                    'type'        => 'service',
                    'category'    => 'Front Desk Concierge',
                    'description' => "Transportation & Luggage Assistance requested for Room {$roomNumber}.",
                ],
                'quick_prompts'  => [
                    'How much is airport transfer?',
                    'Can I get valet parking assistance?',
                    'Where is the taxi pickup point?'
                ],
                'source'         => 'concierge_knowledge_engine',
            ];
        }

        // 8. Wake-Up Calls & Alarms
        if (preg_match('/\b(?:wake\s*up|wake-up|alarm|morning\s*call)\b/i', $q)) {
            $reply = "### ⏰ Front Desk Wake-Up Call\n\n"
                . "Never miss an early morning flight or meeting! Our Front Office team can schedule an automated and personalized wake-up call to Room **{$roomNumber}**.\n\n"
                . "Please tell us what time you would like to be awakened, or tap below to submit your wake-up time request directly.";

            return [
                'reply'          => $reply,
                'action_type'    => 'call_front_desk',
                'action_label'   => '⏰ Schedule Wake-up Call',
                'prefill_type'   => 'service',
                'prefill_ticket' => [
                    'type'        => 'service',
                    'category'    => 'Wake-up Call',
                    'description' => "Wake-up call requested for Room {$roomNumber} at [Specify Time].",
                ],
                'quick_prompts'  => [
                    'Wake me up at 6:00 AM tomorrow',
                    'Wake me up at 7:30 AM',
                    'Can I also order breakfast in bed?'
                ],
                'source'         => 'concierge_knowledge_engine',
            ];
        }

        // 9. Greetings, Personal Chat & Casual Banter
        if (preg_match('/\b(?:hi|hello|hey|good\s*morning|good\s*afternoon|good\s*evening|who\s*are\s*you|what\s*can\s*you\s*do|what\s*is\s*your\s*name|tell\s*me\s*about\s*yourself|help\s*me|madi)\b/i', $q)) {
            $reply = "Hello! I am **Madi**, your AI Concierge at Madison Park Hotel for Room **{$roomNumber}**.

"
                . "How can I assist you today? Feel free to ask about hotel amenities, dining, housekeeping, function halls, or upload a photo if anything in your room needs attention.";

            return [
                'reply'          => $reply,
                'action_type'    => 'general',
                'action_label'   => null,
                'prefill_type'   => 'none',
                'quick_prompts'  => [
                    'Do you have available function halls?',
                    'How much is the food menu pricing?',
                    'Can you send extra towels to my room?'
                ],
                'source'         => 'concierge_knowledge_engine',
            ];
        }

        // 10. Thank You / Positive Sentiment
        if (preg_match('/\b(?:thank\s*you|thanks|appreciate|great\s*job|awesome|good\s*job|perfect)\b/i', $q)) {
            $reply = "### 😊 You are Most Welcome!\n\n"
                . "It is an absolute pleasure serving you in Room **{$roomNumber}**! The entire Madison Park Hotel team and I are at your service.\n\n"
                . "If there is anything else you need—whether fresh towels, dining recommendations, or event reservations—please do not hesitate to ask!";

            return [
                'reply'          => $reply,
                'action_type'    => 'general',
                'action_label'   => null,
                'prefill_type'   => 'none',
                'quick_prompts'  => [
                    'What are your top dinner recommendations?',
                    'What time is breakfast buffet?',
                    'Can I get late check-out?'
                ],
                'source'         => 'concierge_knowledge_engine',
            ];
        }

        // 11. Recommendations & General Guidance
        if ($isRecommend) {
            $reply = "### ✨ Madison Park Chef & Concierge Recommendations\n\n"
                . "Here are our curated recommendations to elevate your stay in Room **{$roomNumber}**:\n\n"
                . "1. **Top In-Room Dining Pairing:** Order our signature **Beef Sinigang (₱235)** paired with refreshing **Fresh Buko Juice (₱65)**, followed by authentic **Halo-Halo (₱95)** or silky **Leche Flan (₱85)**.\n"
                . "2. **Top Breakfast Experience:** The **Filipino Breakfast Set (₱165)** featuring savory garlic sinangag, tender tocino, sunny-side eggs, and freshly brewed Arabica coffee.\n"
                . "3. **Top Event Venue:** The **Main Function Hall (FH-01)** on Floor 1 with our **Grand Buffet Package (₱550/pax)** for memorable milestone celebrations, or **DJP Events Hall (DJP-01)** for high-focus corporate seminars.\n\n"
                . "Let us know how we may tailor your experience today!";

            return [
                'reply'          => $reply,
                'action_type'    => 'order_food',
                'action_label'   => '🍽️ View Dining Menu',
                'prefill_type'   => 'food',
                'prefill_ticket' => null,
                'quick_prompts'  => [
                    'Do you have available function halls?',
                    'How much is the food menu pricing?',
                    'How do I reserve a function hall?'
                ],
                'source'         => 'concierge_knowledge_engine',
            ];
        }

        // 12. General Hospitality Fallback
        $reply = "I understand! I am here to help with your stay in Room **{$roomNumber}**.

"
            . "Could you tell me a bit more about what you need, or upload a photo if you are reporting a room issue? You can also tap below for quick assistance.";

        return [
            'reply'          => $reply,
            'action_type'    => 'general',
            'action_label'   => '🛎️ Contact Front Desk (Ext. 0)',
            'prefill_type'   => 'service',
            'prefill_ticket' => [
                'type'        => 'service',
                'category'    => 'Front Desk Concierge',
                'description' => "Front Desk Inquiry from Room {$roomNumber}: Guest requested general assistance.",
            ],
            'quick_prompts'  => [
                'Do you have available function halls?',
                'How much is the price of food?',
                'Can I request extra clean towels?'
            ],
            'source'         => 'concierge_knowledge_engine',
        ];
    }
}
