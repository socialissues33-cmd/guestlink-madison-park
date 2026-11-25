<?php

namespace Database\Seeders;

use App\Models\FoodCategory;
use App\Models\FoodItem;
use App\Models\FoodOrder;
use App\Models\FoodOrderItem;
use App\Models\FoodOrderStatusLog;
use App\Models\RequestCategory;
use App\Models\Room;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ── 1. Create Roles ──────────────────────────────────────────────────
        $roles = [
            'admin',
            'front_desk',
            'maintenance',
            'maintenance_supervisor',
            'housekeeping',
            'restaurant',
            'owner',
        ];
        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }

        // ── 2. Create Users ──────────────────────────────────────────────────
        $admin = User::updateOrCreate(['email' => 'admin@madisonpark.com'], [
            'name'       => 'System Administrator',
            'username'   => 'admin',
            'password'   => Hash::make('admin123'),
            'department' => 'Administration',
            'is_active'  => true,
        ]);
        $admin->syncRoles(['admin']);

        $frontDesk1 = User::updateOrCreate(['email' => 'frontdesk@madisonpark.com'], [
            'name'       => 'Maria Santos',
            'username'   => 'front',
            'password'   => Hash::make('front123'),
            'department' => 'Front Desk',
            'is_active'  => true,
        ]);
        $frontDesk1->syncRoles(['front_desk']);

        $maintenance1 = User::updateOrCreate(['email' => 'maintenance@madisonpark.com'], [
            'name'       => 'Jose Reyes',
            'username'   => 'main',
            'password'   => Hash::make('main123'),
            'department' => 'Maintenance',
            'is_active'  => true,
        ]);
        $maintenance1->syncRoles(['maintenance']);

        $maintSupervisor = User::updateOrCreate(['email' => 'maintsup@madisonpark.com'], [
            'name'       => 'Ricardo Cruz',
            'username'   => 'maintsup',
            'password'   => Hash::make('main123'),
            'department' => 'Maintenance',
            'is_active'  => true,
        ]);
        $maintSupervisor->syncRoles(['maintenance_supervisor']);

        $housekeeping = User::updateOrCreate(['email' => 'hksup@madisonpark.com'], [
            'name'       => 'Elena Gomez',
            'username'   => 'hksup',
            'password'   => Hash::make('house123'),
            'department' => 'Housekeeping',
            'is_active'  => true,
        ]);
        $housekeeping->syncRoles(['housekeeping']);

        $restaurant1 = User::updateOrCreate(['email' => 'restaurant@madisonpark.com'], [
            'name'       => 'Ana Villanueva',
            'username'   => 'kitch',
            'password'   => Hash::make('kitch123'),
            'department' => 'Restaurant',
            'is_active'  => true,
        ]);
        $restaurant1->syncRoles(['restaurant']);

        $owner = User::updateOrCreate(['email' => 'owner@madisonpark.com'], [
            'name'       => 'Hotel Manager',
            'username'   => 'owner',
            'password'   => Hash::make('owner123'),
            'department' => 'Management',
            'is_active'  => true,
        ]);
        $owner->syncRoles(['owner']);

        // ── 3. Create Request Categories ──────────────────────────────────────
        $serviceCategories = [
            ['name' => 'Extra Towels',     'type' => 'service',     'icon' => '🛁'],
            ['name' => 'Room Cleaning',    'type' => 'service',     'icon' => '🧹'],
            ['name' => 'Extra Pillows',    'type' => 'service',     'icon' => '🛏️'],
            ['name' => 'Laundry Service',  'type' => 'service',     'icon' => '👕'],
            ['name' => 'Wake-up Call',     'type' => 'service',     'icon' => '⏰'],
            ['name' => 'General Inquiry',         'type' => 'service',     'icon' => '❓'],
            ['name' => 'Event & Function Hall',   'type' => 'service',     'icon' => '🏛️'],
            ['name' => 'Dining & Reservations',   'type' => 'service',     'icon' => '🍽️'],
            ['name' => 'Front Desk Concierge',    'type' => 'service',     'icon' => '🛎️'],
            ['name' => 'Air Conditioning',        'type' => 'maintenance', 'icon' => '❄️'],
            ['name' => 'Plumbing',                'type' => 'maintenance', 'icon' => '🚿'],
            ['name' => 'Electrical',              'type' => 'maintenance', 'icon' => '💡'],
            ['name' => 'Furniture',               'type' => 'maintenance', 'icon' => '🪑'],
            ['name' => 'Internet/TV',             'type' => 'maintenance', 'icon' => '📡'],
            ['name' => 'Door/Lock',               'type' => 'maintenance', 'icon' => '🔑'],
        ];
        foreach ($serviceCategories as $cat) {
            RequestCategory::firstOrCreate(['name' => $cat['name'], 'type' => $cat['type']], $cat);
        }

        // ── 4. Create Rooms ──────────────────────────────────────────────────
        // Madison Park Hotel is a 4-story building:
        // Floor 1: Ground Lobby / Function Hall / Restaurant
        // Floor 2: Rooms 201–210
        // Floor 3: Rooms 301–310
        // Floor 4 (Top Floor): Rooms 501–510
        $roomTypes = ['Standard', 'Deluxe', 'Suite', 'Family Room'];
        $rooms = [];
        $floorPrefixes = [
            2 => 2,
            3 => 3,
            4 => 5,
        ];
        foreach ($floorPrefixes as $floor => $prefix) {
            for ($num = 1; $num <= 10; $num++) {
                $roomNumber = $prefix . sprintf('%02d', $num);
                $room = Room::firstOrCreate(['room_number' => $roomNumber], [
                    'room_type'   => $roomTypes[($num - 1) % 4],
                    'floor'       => $floor,
                    'description' => "Room {$roomNumber} on Floor {$floor}",
                    'is_active'   => true,
                ]);
                $rooms[] = $room;
            }
        }
        // Add special rooms
        $specialRooms = [
            ['room_number' => 'FH-01',  'room_type' => 'Function Hall', 'floor' => 1, 'description' => 'Main Function Hall'],
            ['room_number' => 'DJP-01', 'room_type' => 'DJP Hall',      'floor' => 2, 'description' => 'DJP Events Hall'],
            ['room_number' => 'CC-01',  'room_type' => 'CC Hall',       'floor' => 3, 'description' => 'CC Events & Conference Hall'],
            ['room_number' => 'GYM-01', 'room_type' => 'Gym/Fitness',   'floor' => 1, 'description' => 'Gym Fitness Room'],
        ];
        foreach ($specialRooms as $sr) {
            $room = Room::firstOrCreate(['room_number' => $sr['room_number']], $sr);
            $rooms[] = $room;
        }

        // ── 5. Create Food Categories & Items ────────────────────────────────
        $foodData = [
            'Breakfast' => [
                ['name' => 'Continental Breakfast', 'description' => 'Toast, eggs, coffee/tea & juice', 'price' => 185.00],
                ['name' => 'Filipino Breakfast Set', 'description' => 'Sinangag, itlog, tocino & coffee', 'price' => 165.00],
                ['name' => 'Pancakes with Syrup',   'description' => 'Stack of 3 fluffy pancakes',       'price' => 120.00],
                ['name' => 'Oatmeal Bowl',           'description' => 'Oats with fruits and honey',       'price' => 95.00],
            ],
            'Main Course' => [
                ['name' => 'Grilled Chicken',      'description' => 'Herb-grilled chicken with rice & veggies', 'price' => 220.00],
                ['name' => 'Pork Adobo',           'description' => 'Classic Filipino pork adobo with rice',     'price' => 185.00],
                ['name' => 'Beef Sinigang',        'description' => 'Sour tamarind beef soup with vegetables',   'price' => 235.00],
                ['name' => 'Fried Chicken',        'description' => 'Crispy fried chicken with java rice',       'price' => 195.00],
                ['name' => 'Vegetable Stir-fry',   'description' => 'Mixed vegetables in oyster sauce',          'price' => 145.00],
            ],
            'Snacks' => [
                ['name' => 'Spring Rolls (4 pcs)', 'description' => 'Crispy lumpiang shanghai with sweet chili', 'price' => 95.00],
                ['name' => 'French Fries',         'description' => 'Crispy salted fries with ketchup',          'price' => 75.00],
                ['name' => 'Nachos',               'description' => 'Tortilla chips with cheese & salsa',        'price' => 110.00],
            ],
            'Beverages' => [
                ['name' => 'Fresh Buko Juice',   'description' => 'Cold fresh coconut water',    'price' => 65.00],
                ['name' => 'Brewed Coffee',      'description' => 'Hot freshly brewed coffee',   'price' => 55.00],
                ['name' => 'Iced Tea (32 oz)',   'description' => 'Refreshing sweetened iced tea','price' => 50.00],
                ['name' => 'Bottled Water',      'description' => 'Cold mineral water 500ml',     'price' => 35.00],
                ['name' => 'Canned Soft Drinks', 'description' => 'Cola, Sprite, Royal (330ml)',  'price' => 45.00],
            ],
            'Desserts' => [
                ['name' => 'Leche Flan',    'description' => 'Classic creamy caramel custard',     'price' => 85.00],
                ['name' => 'Halo-Halo',     'description' => 'Mixed shaved ice dessert with ube',  'price' => 95.00],
                ['name' => 'Banana Split',  'description' => 'Vanilla ice cream with banana',       'price' => 110.00],
            ],
        ];

        foreach ($foodData as $categoryName => $items) {
            $cat = FoodCategory::firstOrCreate(['name' => $categoryName], [
                'description' => "{$categoryName} items",
                'is_active'   => true,
            ]);
            foreach ($items as $item) {
                FoodItem::firstOrCreate(
                    ['name' => $item['name'], 'food_category_id' => $cat->id],
                    array_merge($item, ['is_available' => true])
                );
            }
        }

        // ── 6. Sample Service Requests (Demo Data) ────────────────────────────
        $sampleRequests = [
            [
                'room_id'     => $rooms[0]->id,
                'type'        => 'maintenance',
                'guest_name'  => 'Juan dela Cruz',
                'description' => 'The air conditioner in the room is not cooling. The temperature setting shows 18°C but the room is still warm.',
                'status'      => 'assigned',
                'priority'    => 'high',
                'department'  => 'Maintenance',
                'assigned_to' => $maintenance1->id,
            ],
            [
                'room_id'     => $rooms[2]->id,
                'type'        => 'service',
                'guest_name'  => 'Maria Clara',
                'description' => 'Please send extra towels and toiletries to my room. We need 2 extra bath towels.',
                'status'      => 'pending',
                'priority'    => 'low',
                'department'  => 'Housekeeping',
            ],
            [
                'room_id'     => $rooms[5]->id,
                'type'        => 'maintenance',
                'guest_name'  => 'Pedro Santos',
                'description' => 'The bathroom faucet is leaking and water is dripping continuously. Please fix it as soon as possible.',
                'status'      => 'in_progress',
                'priority'    => 'urgent',
                'department'  => 'Maintenance',
                'assigned_to' => $maintenance1->id,
            ],
            [
                'room_id'     => $rooms[8]->id,
                'type'        => 'maintenance',
                'guest_name'  => 'Ana Reyes',
                'description' => 'The ceiling light in the bathroom is flickering and sometimes goes out completely.',
                'status'      => 'resolved',
                'priority'    => 'medium',
                'department'  => 'Maintenance',
                'assigned_to' => $maintenance1->id,
            ],
        ];

        foreach ($sampleRequests as $req) {
            $sr = ServiceRequest::firstOrCreate(
                ['description' => $req['description']],
                array_merge($req, ['auto_reply_bot' => true])
            );

            if ($sr->statusLogs()->count() === 0) {
                $sr->statusLogs()->create([
                    'from_status' => null,
                    'to_status'   => $sr->status,
                    'notes'       => 'Initial ticket logged and routed via AI Triage.',
                    'changed_by'  => $sr->assigned_to,
                ]);
            }

            if (!$sr->aiTriage) {
                $sr->aiTriage()->create([
                    'suggested_category'   => $sr->type === 'maintenance' ? 'Air Conditioning' : 'Extra Towels',
                    'suggested_priority'   => strtolower($sr->priority ?? 'medium'),
                    'suggested_department' => $sr->department ?? 'Front Desk',
                    'reason'               => 'Automated Gemini AI diagnostic triage based on guest description and room priority.',
                    'status'               => 'pending',
                ]);
            }

            if ($sr->messages()->count() === 0) {
                $sr->messages()->create([
                    'sender_type' => 'staff',
                    'sender_name' => 'Madi AI (Auto-Reply)',
                    'message'     => "Good day, {$sr->guest_name}! Our Front Office has received your {$sr->type} request and dispatched our {$sr->department} team. Feel free to reply here anytime for live updates.",
                    'is_read'     => true,
                ]);
                $sr->messages()->create([
                    'sender_type' => 'guest',
                    'sender_name' => $sr->guest_name,
                    'message'     => 'Thank you! I am currently inside the room, please knock when the attendant arrives.',
                    'is_read'     => false,
                ]);
                $sr->messages()->create([
                    'sender_type' => 'staff',
                    'sender_name' => $sr->department === 'Maintenance' ? 'Jose Reyes (Maintenance)' : 'Maria Santos (Front Desk)',
                    'user_id'     => $sr->assigned_to ?? $frontDesk1->id,
                    'message'     => 'Noted with thanks! Our attendant is on the way to your room now and will knock gently upon arrival.',
                    'is_read'     => true,
                ]);
            }
        }

        // ── 7. Sample Food Order ──────────────────────────────────────────────
        $sampleOrder = FoodOrder::firstOrCreate(['guest_name' => 'Sample Guest'], [
            'room_id'      => $rooms[1]->id,
            'guest_name'   => 'Sample Guest',
            'status'       => 'confirmed',
            'notes'        => 'Please deliver before 7:30 PM',
            'total_amount' => 490.00,
            'processed_by' => $restaurant1->id,
        ]);

        if ($sampleOrder->statusLogs()->count() === 0) {
            $sampleOrder->statusLogs()->create([
                'from_status' => null,
                'to_status'   => 'pending',
                'notes'       => 'Order placed by guest via QR portal.',
                'changed_by'  => null,
            ]);
            $sampleOrder->statusLogs()->create([
                'from_status' => 'pending',
                'to_status'   => 'confirmed',
                'notes'       => 'Order confirmed by Times Square Kitchen.',
                'changed_by'  => $restaurant1->id,
            ]);
        }

        $chickenItem = FoodItem::where('name', 'Grilled Chicken')->first();
        $teaItem     = FoodItem::where('name', 'Iced Tea (32 oz)')->first();

        if ($chickenItem && $sampleOrder->orderItems()->count() === 0) {
            FoodOrderItem::create([
                'food_order_id' => $sampleOrder->id,
                'food_item_id'  => $chickenItem->id,
                'quantity'      => 2,
                'unit_price'    => 220.00,
                'subtotal'      => 440.00,
            ]);
        }
        if ($teaItem && $sampleOrder->orderItems()->count() < 2) {
            FoodOrderItem::create([
                'food_order_id' => $sampleOrder->id,
                'food_item_id'  => $teaItem->id,
                'quantity'      => 1,
                'unit_price'    => 50.00,
                'subtotal'      => 50.00,
            ]);
        }

        $this->command->info('✅ GuestLink demo data seeded successfully!');
        $this->command->table(
            ['Role', 'Username / Email', 'Password'],
            [
                ['Admin',                  'admin / admin@madisonpark.com',            'admin123'],
                ['Front Desk',             'front / frontdesk@madisonpark.com',        'front123'],
                ['Maintenance',            'main / maintenance@madisonpark.com',       'main123'],
                ['Maintenance Supervisor', 'maintsup / maintsup@madisonpark.com',      'main123'],
                ['Housekeeping',           'hksup / hksup@madisonpark.com',            'house123'],
                ['Restaurant Staff',       'kitch / restaurant@madisonpark.com',       'kitch123'],
                ['Hotel Owner',            'owner / owner@madisonpark.com',            'owner123'],
            ]
        );
    }
}
