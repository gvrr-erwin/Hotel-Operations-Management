<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\Hotel;
use App\Models\HotelRate;
use App\Models\OperationsTask;
use App\Models\RoomType;
use App\Models\Shift;
use App\Models\Tip;
use App\Models\TimeLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // ── Users ──────────────────────────────────────────────────────────
        $admin = User::create([
            'name' => 'System Admin', 'username' => 'admin', 'email' => 'admin@hotel.local',
            'password' => Hash::make('admin123'), 'role' => 'admin',
            'department' => 'management', 'position' => 'System Administrator',
            'is_active' => true,
        ]);

        $gm = User::create([
            'name' => 'Olivia Bennett', 'username' => 'gm', 'email' => 'gm@hotel.local',
            'password' => Hash::make('gm123'), 'role' => 'general_manager',
            'department' => 'management', 'position' => 'General Manager',
            'is_active' => true,
        ]);

        $assistant = User::create([
            'name' => 'Marcus Reed', 'username' => 'assistant', 'email' => 'assistant@hotel.local',
            'password' => Hash::make('assistant123'), 'role' => 'assistant_manager',
            'department' => 'management', 'position' => 'Assistant General Manager',
            'is_active' => true,
        ]);

        $housekeeping = User::create([
            'name' => 'Sofia Alvarez', 'username' => 'housekeeping', 'email' => 'housekeeping@hotel.local',
            'password' => Hash::make('house123'), 'role' => 'housekeeping_manager',
            'department' => 'housekeeping', 'position' => 'Head of Housekeeping',
            'is_active' => true,
        ]);

        $emp1 = User::create([
            'name' => 'James Carter', 'username' => 'employee1', 'email' => 'james@hotel.local',
            'password' => Hash::make('emp123'), 'role' => 'employee',
            'department' => 'front_desk', 'position' => 'Front Desk Agent',
            'is_active' => true,
        ]);

        $emp2 = User::create([
            'name' => 'Emma Wilson', 'username' => 'employee2', 'email' => 'emma@hotel.local',
            'password' => Hash::make('emp123'), 'role' => 'employee',
            'department' => 'housekeeping', 'position' => 'Room Attendant',
            'is_active' => true,
        ]);

        $emp3 = User::create([
            'name' => 'Liam Torres', 'username' => 'employee3', 'email' => 'liam@hotel.local',
            'password' => Hash::make('emp123'), 'role' => 'employee',
            'department' => 'maintenance', 'position' => 'Maintenance Technician',
            'is_active' => true,
        ]);

        $emp4 = User::create([
            'name' => 'Noah Patel', 'username' => 'employee4', 'email' => 'noah@hotel.local',
            'password' => Hash::make('emp123'), 'role' => 'employee',
            'department' => 'food_beverage', 'position' => 'Server',
            'is_active' => true,
        ]);

        $employees = [$emp1, $emp2, $emp3, $emp4];

        // ── Hotels ─────────────────────────────────────────────────────────
        $hotel1 = Hotel::create(['name' => 'Grand Pacific Hotel', 'code' => 'GPH', 'address' => '1 Pacific Ave', 'is_active' => true]);
        $hotel2 = Hotel::create(['name' => 'Bayview Resort',      'code' => 'BVR', 'address' => '22 Bayview Blvd', 'is_active' => true]);
        $hotel3 = Hotel::create(['name' => 'City Center Inn',     'code' => 'CCI', 'address' => '5 Downtown St', 'is_active' => true]);
        $hotels = [$hotel1, $hotel2, $hotel3];

        // ── Room Types ─────────────────────────────────────────────────────
        $king   = RoomType::create(['name' => 'King Bed',    'code' => 'KNG', 'description' => 'Deluxe king room',    'is_active' => true]);
        $queen  = RoomType::create(['name' => 'Queen Bed',   'code' => 'QN',  'description' => 'Standard queen room', 'is_active' => true]);
        $double = RoomType::create(['name' => 'Double Room', 'code' => 'DBL', 'description' => 'Two double beds',     'is_active' => true]);
        $suite  = RoomType::create(['name' => 'Suite',       'code' => 'STE', 'description' => 'Executive suite',     'is_active' => true]);
        $roomTypes = [$king, $queen, $double, $suite];

        // ── Hotel Rates — 60 days ──────────────────────────────────────────
        $baseRates = [
            $hotel1->id => [$king->id => 220, $queen->id => 175, $double->id => 155, $suite->id => 380],
            $hotel2->id => [$king->id => 195, $queen->id => 155, $double->id => 140, $suite->id => 320],
            $hotel3->id => [$king->id => 160, $queen->id => 130, $double->id => 115, $suite->id => 260],
        ];

        for ($d = 59; $d >= 0; $d--) {
            $date = Carbon::now()->subDays($d)->toDateString();
            foreach ($hotels as $hotel) {
                foreach ($roomTypes as $rt) {
                    HotelRate::create([
                        'hotel_id'     => $hotel->id,
                        'room_type_id' => $rt->id,
                        'date'         => $date,
                        'rate'         => $baseRates[$hotel->id][$rt->id] + rand(-15, 25),
                        'recorded_by'  => $gm->id,
                    ]);
                }
            }
        }

        // ── Tips — 60 days ─────────────────────────────────────────────────
        for ($d = 59; $d >= 0; $d--) {
            $date = Carbon::now()->subDays($d)->toDateString();
            for ($t = 0; $t < rand(1, 4); $t++) {
                $emp = $employees[array_rand($employees)];
                Tip::create([
                    'employee_id' => $emp->id,
                    'amount'      => round(rand(10, 80) + (rand(0, 99) / 100), 2),
                    'date'        => $date,
                    'note'        => rand(0, 1) ? 'Guest satisfaction tip' : null,
                    'recorded_by' => $assistant->id,
                ]);
            }
        }

        // ── Time Logs — 30 days (self-service simulation) ──────────────────
        $shiftTimes = [
            'morning'   => ['06:00', '14:00'],
            'afternoon' => ['14:00', '22:00'],
            'evening'   => ['18:00', '02:00'],
            'night'     => ['22:00', '06:00'],
        ];
        $shiftKeys = array_keys($shiftTimes);
        $allStaff  = array_merge([$assistant, $housekeeping], $employees);

        for ($d = 29; $d >= 0; $d--) {
            $date = Carbon::now()->subDays($d)->toDateString();
            foreach ($allStaff as $staff) {
                if (rand(0, 10) > 2) {
                    $shift = $shiftKeys[array_rand($shiftKeys)];
                    [$in, $out] = $shiftTimes[$shift];
                    // Add small variation to simulate real punches
                    $in  = $this->jitter($in, rand(-10, 15));
                    $out = $this->jitter($out, rand(-15, 30));
                    // Yesterday and earlier: always closed and approved. Today: maybe still in-progress.
                    $isToday = $d === 0;
                    $hasClockOut = $isToday ? (rand(0, 10) > 6) : true;
                    TimeLog::create([
                        'user_id'    => $staff->id,
                        'date'       => $date,
                        'clock_in'   => $in,
                        'clock_out'  => $hasClockOut ? $out : null,
                        'shift_type' => $shift,
                        'status'     => $isToday ? 'pending' : 'approved',
                        'logged_by'  => $staff->id,
                        'approved_by'=> $isToday ? null : $gm->id,
                        'approved_at'=> $isToday ? null : Carbon::parse($date)->endOfDay(),
                    ]);
                }
            }
        }

        // ── Shift Schedule — next 14 days ──────────────────────────────────
        $assignments = [
            $emp1->id         => ['morning',   '06:00', '14:00'],
            $emp2->id         => ['morning',   '07:00', '15:00'],
            $emp3->id         => ['afternoon', '14:00', '22:00'],
            $emp4->id         => ['evening',   '16:00', '00:00'],
            $housekeeping->id => ['morning',   '08:00', '16:00'],
            $assistant->id    => ['afternoon', '12:00', '20:00'],
        ];

        for ($d = 0; $d < 14; $d++) {
            $date = Carbon::now()->addDays($d)->toDateString();
            foreach ($assignments as $userId => [$type, $start, $end]) {
                // 1 day off per week
                if (rand(0, 6) === 0) continue;
                Shift::create([
                    'user_id'    => $userId,
                    'date'       => $date,
                    'shift_type' => $type,
                    'start_time' => $start,
                    'end_time'   => $end,
                    'status'     => 'published',
                    'created_by' => $gm->id,
                ]);
            }
        }

        // ── Operations Tasks ───────────────────────────────────────────────
        $taskSeeds = [
            ['Deep-clean Room 412 after checkout',     'housekeeping', 'high',   '412', $emp2->id,         '+2 hours'],
            ['Restock minibar on 5th floor',           'housekeeping', 'medium', null,  $emp2->id,         '+5 hours'],
            ['AC unit not cooling in Room 207',        'maintenance',  'urgent', '207', $emp3->id,         '+1 hour'],
            ['Leaking faucet in Room 318',             'maintenance',  'medium', '318', $emp3->id,         '+1 day'],
            ['Replace lobby light bulbs',              'maintenance',  'low',    null,  $emp3->id,         '+3 days'],
            ['VIP arrival — Suite 901 ready by 3pm',   'front_desk',   'high',   '901', $emp1->id,         '+4 hours'],
            ['Restaurant: dinner prep for 80 covers',  'food_beverage','high',   null,  $emp4->id,         '+6 hours'],
            ['Weekly maintenance walk-through',        'maintenance',  'low',    null,  $emp3->id,         '+5 days'],
            ['Turndown service — floors 7-9',          'housekeeping', 'medium', null,  $emp2->id,         '+8 hours'],
            ['Investigate noise complaint Room 614',   'front_desk',   'medium', '614', $emp1->id,         '+30 minutes'],
        ];

        foreach ($taskSeeds as [$title, $cat, $pri, $room, $assignee, $due]) {
            $status = match (rand(0, 3)) {
                0       => 'completed',
                1       => 'in_progress',
                default => 'open',
            };
            OperationsTask::create([
                'title'        => $title,
                'description'  => null,
                'category'     => $cat,
                'priority'     => $pri,
                'status'       => $status,
                'room_number'  => $room,
                'assigned_to'  => $assignee,
                'created_by'   => $housekeeping->id,
                'due_at'       => Carbon::now()->modify($due),
                'completed_at' => $status === 'completed' ? Carbon::now()->subHours(rand(1, 12)) : null,
            ]);
        }

        // ── Audit Logs ─────────────────────────────────────────────────────
        $actions = ['login', 'logout', 'rate_saved', 'tip_created', 'time_clock_in', 'time_clock_out', 'task_created'];
        $users   = [$admin, $gm, $assistant, $emp1, $emp2];
        for ($i = 0; $i < 20; $i++) {
            $u      = $users[array_rand($users)];
            $action = $actions[array_rand($actions)];
            AuditLog::create([
                'user_id'    => $u->id,
                'action'     => $action,
                'description'=> "{$u->name} performed {$action}",
                'ip_address' => '127.0.0.1',
                'created_at' => Carbon::now()->subMinutes(rand(1, 10000)),
            ]);
        }
    }

    private function jitter(string $time, int $minutes): string
    {
        [$h, $m] = explode(':', $time);
        $total = ((int) $h * 60 + (int) $m + $minutes + 1440) % 1440;
        return sprintf('%02d:%02d', intdiv($total, 60), $total % 60);
    }
}
