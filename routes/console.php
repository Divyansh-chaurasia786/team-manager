<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

use Illuminate\Support\Facades\Schedule;

Artisan::command('user:list', function () {
    $users = App\Models\User::all(['id', 'name', 'username', 'email', 'designation', 'mobile_number', 'must_change_password', 'role']);
    $this->table(['ID', 'Name', 'Username', 'Email', 'Designation', 'Mobile', 'Must Change Pass', 'Role'], $users->toArray());
});

Schedule::command('chat:cleanup-expired')->daily();

