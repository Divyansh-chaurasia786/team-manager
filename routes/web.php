<?php
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TLDashboardController;
use App\Http\Controllers\MemberDashboardController;
use App\Http\Controllers\CEODashboardController;
use App\Http\Controllers\HRDashboardController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\UploadController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\PerformanceController;
use App\Http\Controllers\WeeklyPlanController;
use App\Http\Controllers\PersonalTaskController;
use Illuminate\Support\Facades\Route;

// Redirect root to login
Route::get('/', fn() => redirect()->route('login'));

// Database Connection & Health Verification Diagnostic Endpoint
Route::get('/db-health', function () {
    try {
        $defaultConnection = config('database.default');
        $dbConfig = config("database.connections.{$defaultConnection}");

        $pdo = \Illuminate\Support\Facades\DB::connection()->getPdo();
        $dbName = \Illuminate\Support\Facades\DB::connection()->getDatabaseName();
        $hasUsersTable = \Illuminate\Support\Facades\Schema::hasTable('users');
        $userCount = $hasUsersTable ? \Illuminate\Support\Facades\DB::table('users')->count() : 0;
        $tableList = \Illuminate\Support\Facades\DB::select(
            $defaultConnection === 'pgsql'
                ? "SELECT tablename FROM pg_catalog.pg_tables WHERE schemaname = 'public';"
                : "SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%';"
        );

        $usersSummary = $hasUsersTable ? \App\Models\User::all()->map(function ($u) {
            return [
                'id' => $u->id,
                'email' => $u->email,
                'username' => $u->username,
                'role' => $u->role,
                'must_change_password' => $u->must_change_password,
                'is_locked' => $u->isLocked(),
                'failed_attempts' => $u->failed_login_attempts,
            ];
        }) : [];

        return response()->json([
            'status' => 'healthy',
            'connected' => true,
            'default_connection' => $defaultConnection,
            'database_name' => $dbName,
            'tables_count' => count($tableList),
            'has_users_table' => $hasUsersTable,
            'users_count' => $userCount,
            'users' => $usersSummary,
            'has_database_url_env' => !empty(env('DATABASE_URL')),
            'driver' => $dbConfig['driver'] ?? null,
            'host' => $dbConfig['host'] ?? null,
            'timestamp' => now()->toIso8601String(),
        ]);
    } catch (\Throwable $e) {
        return response()->json([
            'status' => 'error',
            'connected' => false,
            'default_connection' => config('database.default'),
            'has_database_url_env' => !empty(env('DATABASE_URL')),
            'error_message' => $e->getMessage(),
            'error_class' => get_class($e),
            'timestamp' => now()->toIso8601String(),
        ], 500);
    }
});


// Auth routes (guest only)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);

    // Forgot & Reset Password Routes
    Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendForgotPasswordOtp'])->name('password.email');
    Route::get('/reset-password', [AuthController::class, 'showResetPassword'])->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'updateResetPassword'])->name('password.update');
});
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// TL-only routes
Route::middleware(['auth', 'role:tl'])->prefix('tl')->name('tl.')->group(function () {
    Route::get('/dashboard', [TLDashboardController::class, 'index'])->name('dashboard');
    Route::get('/members', [MemberController::class, 'index'])->name('members');
    Route::post('/members', [MemberController::class, 'store'])->name('members.store');
    Route::get('/members/{user}', [MemberController::class, 'show'])->name('members.show');
    Route::put('/members/{user}', [MemberController::class, 'update'])->name('members.update');
    Route::post('/members/{user}/reset-otp', [MemberController::class, 'resetOtp'])->name('members.reset_otp');
    Route::delete('/members/{user}', [MemberController::class, 'destroy'])->name('members.destroy');
    Route::post('/attendance/bulk-present', [AttendanceController::class, 'bulkMarkPresent'])->name('attendance.bulk');
});

// CEO-only routes
Route::middleware(['auth', 'role:ceo'])->prefix('ceo')->name('ceo.')->group(function () {
    Route::get('/dashboard', [CEODashboardController::class, 'index'])->name('dashboard');
    // CEO can approve/reject leaves too
    Route::post('/leaves/{leave}/approve', [LeaveController::class, 'approve'])->name('leaves.approve');
    Route::post('/leaves/{leave}/reject', [LeaveController::class, 'reject'])->name('leaves.reject');
});

// HR-only routes
Route::middleware(['auth', 'role:hr'])->prefix('hr')->name('hr.')->group(function () {
    Route::get('/dashboard', [HRDashboardController::class, 'index'])->name('dashboard');
    Route::get('/members', [MemberController::class, 'index'])->name('members');
    Route::post('/members', [MemberController::class, 'store'])->name('members.store');
    Route::get('/members/{user}', [MemberController::class, 'show'])->name('members.show');
    Route::put('/members/{user}', [MemberController::class, 'update'])->name('members.update');
    Route::post('/members/{user}/reset-otp', [MemberController::class, 'resetOtp'])->name('members.reset_otp');
    Route::delete('/members/{user}', [MemberController::class, 'destroy'])->name('members.destroy');
    Route::post('/leaves/{leave}/approve', [LeaveController::class, 'approve'])->name('leaves.approve');
    Route::post('/leaves/{leave}/reject', [LeaveController::class, 'reject'])->name('leaves.reject');
});

// Shared auth routes (TL, HR, CEO, and members)
Route::middleware('auth')->group(function () {
    // Tasks
    Route::get('/tasks', [TaskController::class, 'index'])->name('tasks.index');
    Route::post('/tasks', [TaskController::class, 'store'])->name('tasks.store');
    Route::put('/tasks/{task}/update', [TaskController::class, 'addUpdate'])->name('tasks.update.add');
    Route::put('/tasks/{task}/submit', [TaskController::class, 'submit'])->name('tasks.submit');
    Route::put('/tasks/{task}/reassign', [TaskController::class, 'reassign'])->name('tasks.reassign');
    Route::put('/tasks/{task}/complete', [TaskController::class, 'complete'])->name('tasks.complete');
    Route::delete('/tasks/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');
    Route::post('/tasks/{task}/send-overdue-reminder', [TaskController::class, 'sendOverdueReminder'])->name('tasks.send_overdue_reminder');
    Route::post('/tasks/bulk-delete', [TaskController::class, 'bulkDestroy'])->name('tasks.bulk_destroy');

    // Member dashboard
    Route::get('/member/dashboard', [MemberDashboardController::class, 'index'])->name('member.dashboard');

    // Attendance (TL marks team; Members view personal)
    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::post('/attendance/mark', [AttendanceController::class, 'mark'])->name('attendance.mark');

    // Leaves Management
    Route::get('/leaves', [LeaveController::class, 'index'])->name('leaves.index');
    Route::post('/leaves', [LeaveController::class, 'store'])->name('leaves.store');
    Route::post('/leaves/toggle', [LeaveController::class, 'toggle'])->name('leaves.toggle');
    Route::post('/leaves/quotas', [LeaveController::class, 'updateQuota'])->name('leaves.quotas.update');
    Route::post('/leaves/{leave}/approve', [LeaveController::class, 'approve'])->name('leaves.approve');
    Route::post('/leaves/{leave}/reject', [LeaveController::class, 'reject'])->name('leaves.reject');

    // Drive upload, download & deletion
    Route::get('/upload', [UploadController::class, 'index'])->name('upload.index');
    Route::post('/upload', [UploadController::class, 'store'])->name('upload.store');
    Route::get('/drive/download/{file}', [UploadController::class, 'download'])->name('drive.download');
    Route::delete('/drive/files/{file}', [UploadController::class, 'destroy'])->name('drive.destroy');

    // Google Drive 1-Click OAuth Integration
    Route::get('/google/connect', [\App\Http\Controllers\GoogleAuthController::class, 'connect'])->name('google.connect');
    Route::get('/google/callback', [\App\Http\Controllers\GoogleAuthController::class, 'callback'])->name('google.callback');
    Route::post('/google/disconnect', [\App\Http\Controllers\GoogleAuthController::class, 'disconnect'])->name('google.disconnect');
    Route::post('/google/configure', [\App\Http\Controllers\GoogleAuthController::class, 'configureCredentials'])->name('google.configure');

    // Force Password Change (First login security)
    Route::get('/password/force-change', [AuthController::class, 'showForceChangePassword'])->name('password.force_change');
    Route::post('/password/force-change', [AuthController::class, 'updateForceChangePassword'])->name('password.force_change.update');

    // Activity Audit History
    Route::get('/history', [ActivityLogController::class, 'index'])->name('history.index');

    // Weekly Planning
    Route::get('/plans', [WeeklyPlanController::class, 'index'])->name('plans.index');
    Route::post('/plans', [WeeklyPlanController::class, 'store'])->name('plans.store');
    Route::get('/plans/{plan}', [WeeklyPlanController::class, 'show'])->name('plans.show');
    Route::put('/plans/{plan}/status', [WeeklyPlanController::class, 'updateStatus'])->name('plans.status.update');
    Route::delete('/plans/{plan}', [WeeklyPlanController::class, 'destroy'])->name('plans.destroy');

    // Team Discussion & Thought Sharing Hub (WhatsApp & Instagram Live Chat)
    Route::get('/thoughts', [\App\Http\Controllers\TeamThoughtController::class, 'index'])->name('thoughts.index');
    Route::get('/thoughts/unread-count', [\App\Http\Controllers\TeamThoughtController::class, 'unreadCount'])->name('thoughts.unread_count');
    Route::get('/thoughts/messages', [\App\Http\Controllers\TeamThoughtController::class, 'getMessages'])->name('thoughts.messages');
    Route::post('/thoughts', [\App\Http\Controllers\TeamThoughtController::class, 'store'])->name('thoughts.store');
    Route::post('/thoughts/{thought}/unsend', [\App\Http\Controllers\TeamThoughtController::class, 'unsend'])->name('thoughts.unsend');
    Route::post('/thoughts/{thought}/react', [\App\Http\Controllers\TeamThoughtController::class, 'react'])->name('thoughts.react');
    Route::get('/thoughts/{thought}/info', [\App\Http\Controllers\TeamThoughtController::class, 'info'])->name('thoughts.info');
    Route::post('/thoughts/members', [\App\Http\Controllers\TeamThoughtController::class, 'addMember'])->name('thoughts.members.add');
    Route::delete('/thoughts/members/{user}', [\App\Http\Controllers\TeamThoughtController::class, 'removeMember'])->name('thoughts.members.remove');
    Route::post('/thoughts/{thought}/drive-upload', [\App\Http\Controllers\TeamThoughtController::class, 'uploadToDrive'])->name('thoughts.drive.upload');
    Route::delete('/thoughts/{thought}', [\App\Http\Controllers\TeamThoughtController::class, 'destroy'])->name('thoughts.destroy');

    // Content & Social Media Shoot Production Planner
    Route::get('/shoots', [\App\Http\Controllers\ContentShootController::class, 'index'])->name('shoots.index');
    Route::post('/shoots', [\App\Http\Controllers\ContentShootController::class, 'store'])->name('shoots.store');
    Route::get('/shoots/{shoot}', [\App\Http\Controllers\ContentShootController::class, 'show'])->name('shoots.show');
    Route::get('/shoots/{shoot}/print', [\App\Http\Controllers\ContentShootController::class, 'printSheet'])->name('shoots.print');
    Route::put('/shoots/{shoot}', [\App\Http\Controllers\ContentShootController::class, 'update'])->name('shoots.update');
    Route::patch('/shoots/{shoot}/status', [\App\Http\Controllers\ContentShootController::class, 'updateStatus'])->name('shoots.status.update');
    Route::patch('/shoots/{shoot}/assign-crew', [\App\Http\Controllers\ContentShootController::class, 'assignCrew'])->name('shoots.assign_crew');
    Route::delete('/shoots/{shoot}', [\App\Http\Controllers\ContentShootController::class, 'destroy'])->name('shoots.destroy');

    // Account & Security Settings
    Route::get('/settings', [\App\Http\Controllers\SettingsController::class, 'index'])->name('settings.index');
    Route::post('/settings/avatar', [\App\Http\Controllers\SettingsController::class, 'updateAvatar'])->name('settings.avatar');
    Route::post('/settings/request-update', [\App\Http\Controllers\SettingsController::class, 'requestUpdate'])->name('settings.request_update');
    Route::get('/settings/verify-otp', [\App\Http\Controllers\SettingsController::class, 'showOtpVerification'])->name('settings.otp_modal');
    Route::post('/settings/verify-otp', [\App\Http\Controllers\SettingsController::class, 'verifyOtp'])->name('settings.verify_otp');
    Route::post('/settings/resend-otp', [\App\Http\Controllers\SettingsController::class, 'resendOtp'])->name('settings.resend_otp');
    Route::post('/settings/cancel-pending', [\App\Http\Controllers\SettingsController::class, 'cancelPendingUpdate'])->name('settings.cancel_pending');

    // Personal Tasks (My Tasks) — private per-user tasks with optional sharing
    Route::get('/my-tasks', [PersonalTaskController::class, 'index'])->name('my-tasks.index');
    Route::post('/my-tasks', [PersonalTaskController::class, 'store'])->name('my-tasks.store');
    Route::patch('/my-tasks/{personalTask}', [PersonalTaskController::class, 'update'])->name('my-tasks.update');
    Route::delete('/my-tasks/{personalTask}', [PersonalTaskController::class, 'destroy'])->name('my-tasks.destroy');
    Route::patch('/my-tasks/{personalTask}/status', [PersonalTaskController::class, 'updateStatus'])->name('my-tasks.status');
    Route::post('/my-tasks/{personalTask}/share', [PersonalTaskController::class, 'share'])->name('my-tasks.share');
    Route::post('/my-tasks/{personalTask}/unshare', [PersonalTaskController::class, 'unshare'])->name('my-tasks.unshare');
});