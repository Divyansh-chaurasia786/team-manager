<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $tl = \App\Models\User::where('role', 'tl')->first();
        if ($tl) {
            $tl->update([
                'name'                 => 'Sumit',
                'username'             => 'sumit.ecofone',
                'email'                => 'sumitecofone@gmail.com',
                'designation'          => 'Operations Team Lead',
                'mobile_number'        => '+91 98765 43210',
                'password'             => \Illuminate\Support\Facades\Hash::make('ECO-' . strtoupper(bin2hex(random_bytes(5)))),
                'must_change_password' => true,
                'temp_password_plain'  => null,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tl = \App\Models\User::where('email', 'sumitecofone@gmail.com')->first();
        if ($tl) {
            $tl->update([
                'name'                 => 'Team Lead',
                'username'             => 'tl.ecofone',
                'email'                => 'tl@ecofone.com',
            ]);
        }
    }
};
