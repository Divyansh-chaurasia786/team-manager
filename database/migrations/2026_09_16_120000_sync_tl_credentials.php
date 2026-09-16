<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Models\User;

return new class extends Migration
{
    public function up(): void
    {
        \Illuminate\Support\Facades\DB::table('users')
            ->where('email', 'sumitecofone@gmail.com')
            ->orWhere('role', 'tl')
            ->update([
                'name'                 => 'Sumit',
                'username'             => 'sumit.ecofone',
                'email'                => 'sumitecofone@gmail.com',
                'designation'          => 'Operations Team Lead',
                'mobile_number'        => '+91 98765 43210',
                'password'             => app()->environment('testing') ? \Illuminate\Support\Facades\Hash::make('testpass123') : '$2y$12$xADQ3gY933ih5CqsbDIFcOwMGQvXzV8rRTot1pbQiS5WRL1LDFDIe',
                'must_change_password' => 1,
                'temp_password_plain'  => null,
            ]);
    }

    public function down(): void
    {
    }
};