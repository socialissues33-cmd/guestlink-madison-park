<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RequestCategory extends Model
{
    protected $fillable = ['name', 'type', 'icon', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function serviceRequests(): HasMany
    {
        return $this->hasMany(ServiceRequest::class, 'category_id');
    }

    /**
     * Resolve existing category or dynamically create one with matching luxury emoji icon.
     */
    public static function arrangeCategory(string $categoryName, string $type = 'maintenance'): self
    {
        $cleanName = trim($categoryName);
        if (empty($cleanName)) {
            $cleanName = ($type === 'maintenance') ? 'General Maintenance' : 'General Inquiry';
        }

        // 1. Exact or case-insensitive match
        $existing = self::where('type', $type)
            ->whereRaw('LOWER(name) = ?', [strtolower($cleanName)])
            ->first();

        if ($existing) {
            return $existing;
        }

        // 2. Check existing categories
        $allCats = self::where('type', $type)->get();
        foreach ($allCats as $cat) {
            if (strcasecmp($cat->name, $cleanName) === 0) {
                return $cat;
            }
        }

        // 3. Dynamically create and arrange new category
        $icon = self::determineCategoryIcon($cleanName);

        return self::firstOrCreate(
            ['name' => $cleanName, 'type' => $type],
            ['icon' => $icon, 'is_active' => true]
        );
    }

    /**
     * Determine suitable luxury emoji icon for arranged categories.
     */
    public static function determineCategoryIcon(string $name): string
    {
        $n = strtolower($name);
        if (preg_match('/door|lock|key|handle|escutcheon|latch|entry/i', $n)) return '🚪';
        if (preg_match('/shower|bath|faucet|tap|sink|plumb|pipe|drain|water|leak|toilet/i', $n)) return '🚿';
        if (preg_match('/air|ac|climate|hvac|cool|thermostat|vent|heat/i', $n)) return '❄️';
        if (preg_match('/light|lamp|bulb|electr|power|socket|switch|outlet|voltage|wire|wiring|cord/i', $n)) return '💡';
        if (preg_match('/hardware|fixture|screw|mount|wall|ceiling|repair/i', $n)) return '🔩';
        if (preg_match('/tv|television|remote|internet|wifi|cable|network/i', $n)) return '📺';
        if (preg_match('/bed|chair|table|desk|drawer|sofa|furniture|wood/i', $n)) return '🪑';
        if (preg_match('/window|glass|curtain|blind/i', $n)) return '🪟';
        if (preg_match('/safe|security|alarm|smoke|fire|emergency/i', $n)) return '🚨';
        if (preg_match('/clean|towel|linen|trash|housekeeping/i', $n)) return '🧹';
        if (preg_match('/food|dining|drink|beverage|meal|snack/i', $n)) return '🍽️';
        return '🛠️';
    }
}
