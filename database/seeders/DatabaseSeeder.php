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
        $tl = User::where('email', 'sumitecofone@gmail.com')->first();
        if (!$tl) {
            $existingTl = User::where('role', 'tl')->first();
            $randomOtp = 'ECO-' . strtoupper(bin2hex(random_bytes(5)));
            if ($existingTl) {
                $existingTl->update([
                    'name'                 => 'Sumit',
                    'username'             => 'sumit.ecofone',
                    'email'                => 'sumitecofone@gmail.com',
                    'mobile_number'        => '+91 98765 43210',
                    'designation'          => 'Operations Team Lead',
                    'password'             => Hash::make($randomOtp),
                    'must_change_password' => true,
                ]);
                $tl = $existingTl;
            } else {
                $tl = User::create([
                    'name'                 => 'Sumit',
                    'username'             => 'sumit.ecofone',
                    'email'                => 'sumitecofone@gmail.com',
                    'mobile_number'        => '+91 98765 43210',
                    'designation'          => 'Operations Team Lead',
                    'password'             => Hash::make($randomOtp),
                    'role'                 => 'tl',
                    'must_change_password' => true,
                ]);
            }
        }

        $ceo = User::firstOrCreate(
            ['email' => 'ceo@ecofone.com'],
            [
                'name'                 => 'Chief Executive Officer',
                'username'             => 'ceo.ecofone',
                'mobile_number'        => '+91 99999 00001',
                'designation'          => 'Founder & CEO',
                'password'             => Hash::make('password123'),
                'role'                 => 'ceo',
                'must_change_password' => false,
            ]
        );

        $hr = User::firstOrCreate(
            ['email' => 'hr@ecofone.com'],
            [
                'name'                 => 'HR Manager',
                'username'             => 'hr.ecofone',
                'mobile_number'        => '+91 99999 00002',
                'designation'          => 'People & Culture Lead',
                'password'             => Hash::make('password123'),
                'role'                 => 'hr',
                'must_change_password' => false,
            ]
        );

        if (!$tl->username) {
            $tl->username = 'tl.ecofone';
            $tl->save();
        }

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
            }
            if ($u->role === 'member') {
                if (empty($u->designation)) {
                    $u->designation = 'Operations Specialist';
                }
                if (empty($u->mobile_number)) {
                    $u->mobile_number = '+91 98765 43210';
                }
            }
            $u->save();
        }
    }
}
