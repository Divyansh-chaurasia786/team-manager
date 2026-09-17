<?php
namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Setup Team Lead (TL)
        $tl = User::where('email', 'sumitecofone@gmail.com')->first();
        if (!$tl) {
            $existingTl = User::where('role', 'tl')->first();
            $defaultPassword = Hash::make('password123');
            if ($existingTl) {
                $existingTl->update([
                    'name'                 => 'Sumit',
                    'username'             => 'sumit.ecofone',
                    'email'                => 'sumitecofone@gmail.com',
                    'mobile_number'        => '+91 98765 43210',
                    'designation'          => 'Operations Team Lead',
                    'role'                 => 'tl',
                ]);
                $tl = $existingTl;
            } else {
                $tl = User::create([
                    'name'                 => 'Sumit',
                    'username'             => 'sumit.ecofone',
                    'email'                => 'sumitecofone@gmail.com',
                    'mobile_number'        => '+91 98765 43210',
                    'designation'          => 'Operations Team Lead',
                    'password'             => $defaultPassword,
                    'role'                 => 'tl',
                    'must_change_password' => true,
                    'otp_expires_at'       => now()->addDays(10),
                ]);
            }
        }

        // 2. Setup CEO (ecofoneofficial@gmail.com)
        $ceo = User::where('email', 'ecofoneofficial@gmail.com')->first();
        if (!$ceo) {
            $oldCeo = User::where('email', 'ceo@ecofone.com')->orWhere('role', 'ceo')->first();
            if ($oldCeo) {
                $oldCeo->update([
                    'email'       => 'ecofoneofficial@gmail.com',
                    'username'    => 'ceo.ecofone',
                    'name'        => 'Chief Executive Officer',
                    'designation' => 'Founder & CEO',
                    'role'        => 'ceo',
                ]);
                $ceo = $oldCeo;
            } else {
                $ceo = User::create([
                    'name'                 => 'Chief Executive Officer',
                    'username'             => 'ceo.ecofone',
                    'email'                => 'ecofoneofficial@gmail.com',
                    'mobile_number'        => '+91 99999 00001',
                    'designation'          => 'Founder & CEO',
                    'password'             => Hash::make('password123'),
                    'role'                 => 'ceo',
                    'must_change_password' => true,
                    'otp_expires_at'       => now()->addDays(10),
                ]);
            }
        }

        // 3. Setup HR (ecofonehr@gmail.com)
        $hr = User::where('email', 'ecofonehr@gmail.com')->first();
        if (!$hr) {
            $oldHr = User::where('email', 'hr@ecofone.com')->orWhere('role', 'hr')->first();
            if ($oldHr) {
                $oldHr->update([
                    'email'       => 'ecofonehr@gmail.com',
                    'username'    => 'hr.ecofone',
                    'name'        => 'HR Manager',
                    'designation' => 'People & Culture Lead',
                    'role'        => 'hr',
                ]);
                $hr = $oldHr;
            } else {
                $hr = User::create([
                    'name'                 => 'HR Manager',
                    'username'             => 'hr.ecofone',
                    'email'                => 'ecofonehr@gmail.com',
                    'mobile_number'        => '+91 99999 00002',
                    'designation'          => 'People & Culture Lead',
                    'password'             => Hash::make('password123'),
                    'role'                 => 'hr',
                    'must_change_password' => true,
                    'otp_expires_at'       => now()->addDays(10),
                ]);
            }
        }

        // 4. Ensure no unwanted demo member seeded; members are only created by TL, HR, or CEO
        User::where('email', 'chaurasiadivyansh86@gmail.com')->delete();

        // 5. Ensure usernames are populated
        foreach (User::all() as $u) {
            if (!$u->username) {
                $clean = Str::slug($u->name, '.');
                if (empty($clean)) $clean = 'user';
                $base = $clean;
                $uname = $base;
                $c = 1;
                while (User::where('username', $uname)->where('id', '!=', $u->id)->exists()) {
                    $c++;
                    $uname = $base . $c;
                }
                $u->username = $uname;
                $u->save();
            }
        }
    }
}
