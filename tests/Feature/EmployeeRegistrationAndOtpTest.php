<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\EmployeeWelcomeMail;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class EmployeeRegistrationAndOtpTest extends TestCase
{
    use RefreshDatabase;

    public function test_tl_can_register_employee_and_otp_is_generated_and_emailed(): void
    {
        Mail::fake();

        $tl = User::where('role', 'tl')->first();
        if (!$tl) {
            $tl = User::create([
                'name' => 'Team Lead',
                'email' => 'tl_test@ecofone.com',
                'password' => Hash::make('password123'),
                'role' => 'tl',
            ]);
        }

        $employeeEmail = 'employee_' . time() . '@ecofone.com';

        $response = $this->actingAs($tl)->post(route('tl.members.store'), [
            'name'          => 'Rohit Verma',
            'mobile_number' => '+91 9988776655',
            'designation'   => 'Flutter Engineer',
            'email'         => $employeeEmail,
        ]);

        $response->assertRedirect(route('tl.members'));
        $response->assertSessionHas('success');
        $response->assertSessionHas('new_member');

        $newMemberData = session('new_member');
        $this->assertEquals('Rohit Verma', $newMemberData['name']);
        $this->assertEquals('rohit.verma', $newMemberData['username']);
        $this->assertEquals($employeeEmail, $newMemberData['email']);
        $this->assertEquals('+91 9988776655', $newMemberData['mobile']);
        $this->assertEquals('Flutter Engineer', $newMemberData['designation']);
        $this->assertStringStartsWith('ECO-', $newMemberData['otp']);

        $user = User::where('email', $employeeEmail)->first();
        $this->assertNotNull($user);
        $this->assertEquals('rohit.verma', $user->username);
        $this->assertTrue($user->must_change_password);
        $this->assertTrue(Hash::check($newMemberData['otp'], $user->password));

        Mail::assertSent(EmployeeWelcomeMail::class, function ($mail) use ($employeeEmail) {
            return $mail->hasTo($employeeEmail);
        });
    }

    public function test_user_with_must_change_password_is_redirected_to_force_change_page(): void
    {
        $employee = User::create([
            'name'                 => 'New Employee',
            'username'             => 'new.employee',
            'email'                => 'new_emp_' . time() . '@ecofone.com',
            'mobile_number'        => '+91 9123456780',
            'designation'          => 'QA Engineer',
            'password'             => Hash::make('ECO-TEST99'),
            'must_change_password' => true,
            'role'                 => 'member',
        ]);

        // Attempt login with unique username and temporary password
        $response = $this->post(route('login'), [
            'login'    => 'new.employee',
            'password' => 'ECO-TEST99',
        ]);

        $response->assertRedirect(route('password.force_change'));

        // Attempting to visit dashboard directly while must_change_password is true should redirect to force change
        $dashResponse = $this->actingAs($employee)->get(route('member.dashboard'));
        $dashResponse->assertRedirect(route('password.force_change'));
    }

    public function test_user_can_set_permanent_password_and_unlock_dashboard(): void
    {
        $employee = User::create([
            'name'                 => 'Pooja Nair',
            'username'             => 'pooja.nair',
            'email'                => 'pooja_' . time() . '@ecofone.com',
            'mobile_number'        => '+91 9871122334',
            'designation'          => 'Backend Developer',
            'password'             => Hash::make('ECO-SECURE'),
            'must_change_password' => true,
            'temp_password_plain'  => null,
            'role'                 => 'member',
        ]);

        $response = $this->actingAs($employee)->post(route('password.force_change.update'), [
            'current_password'      => 'ECO-SECURE',
            'password'              => 'MyPermanentPass@2026',
            'password_confirmation' => 'MyPermanentPass@2026',
        ]);

        $response->assertRedirect(route('member.dashboard'));

        $employee->refresh();
        $this->assertFalse((bool)$employee->must_change_password);
        $this->assertNull($employee->temp_password_plain);
        $this->assertTrue(Hash::check('MyPermanentPass@2026', $employee->password));

        // Now can access dashboard normally
        $dashResponse = $this->actingAs($employee)->get(route('member.dashboard'));
        $dashResponse->assertStatus(200);
    }

    public function test_employee_can_log_in_with_unique_username_after_permanent_password(): void
    {
        $employee = User::create([
            'name'                 => 'Aman Gupta',
            'username'             => 'aman.gupta',
            'email'                => 'aman_' . time() . '@ecofone.com',
            'mobile_number'        => '+91 9811223344',
            'designation'          => 'DevOps Engineer',
            'password'             => Hash::make('SuperSecretPass@2026'),
            'must_change_password' => false,
            'role'                 => 'member',
        ]);

        $response = $this->post(route('login'), [
            'login'    => 'aman.gupta',
            'password' => 'SuperSecretPass@2026',
        ]);

        $response->assertRedirect(route('member.dashboard'));
        $this->assertAuthenticatedAs($employee);
    }

    public function test_tl_can_view_and_update_member_details(): void
    {
        $tl = User::where('role', 'tl')->first();
        if (!$tl) {
            $tl = User::create([
                'name' => 'Team Lead',
                'username' => 'tl.test',
                'email' => 'tl_test@ecofone.com',
                'password' => Hash::make('password123'),
                'role' => 'tl',
            ]);
        }

        $member = User::create([
            'name'          => 'Kavita Iyer',
            'username'      => 'kavita.iyer',
            'email'         => 'kavita_' . time() . '@ecofone.com',
            'mobile_number' => '+91 9112233445',
            'designation'   => 'Junior Analyst',
            'password'      => Hash::make('secret'),
            'role'          => 'member',
            'created_by'    => $tl->id,
        ]);

        // 1. View details page
        $viewResponse = $this->actingAs($tl)->get(route('tl.members.show', $member));
        $viewResponse->assertStatus(200);
        $viewResponse->assertSee('Kavita Iyer');
        $viewResponse->assertSee('kavita.iyer');

        // 2. Update details
        $updateResponse = $this->actingAs($tl)->put(route('tl.members.update', $member), [
            'name'          => 'Kavita R. Iyer',
            'username'      => 'kavita.r.iyer',
            'email'         => 'kavita.new@ecofone.com',
            'mobile_number' => '+91 9998887776',
            'designation'   => 'Senior Operations Analyst',
        ]);

        $updateResponse->assertSessionHas('success');

        $member->refresh();
        $this->assertEquals('Kavita R. Iyer', $member->name);
        $this->assertEquals('kavita.r.iyer', $member->username);
        $this->assertEquals('kavita.new@ecofone.com', $member->email);
        $this->assertEquals('+91 9998887776', $member->mobile_number);
        $this->assertEquals('Senior Operations Analyst', $member->designation);
    }

    public function test_tl_can_reset_member_otp(): void
    {
        Mail::fake();

        $tl = User::where('role', 'tl')->first();
        if (!$tl) {
            $tl = User::create([
                'name' => 'Team Lead',
                'username' => 'tl.test',
                'email' => 'tl_test@ecofone.com',
                'password' => Hash::make('password123'),
                'role' => 'tl',
            ]);
        }

        $member = User::create([
            'name'                 => 'Suresh Raina',
            'username'             => 'suresh.raina',
            'email'                => 'suresh_' . time() . '@ecofone.com',
            'mobile_number'        => '+91 9334455667',
            'designation'          => 'Backend Developer',
            'password'             => Hash::make('OldPermanentPass@123'),
            'must_change_password' => false,
            'role'                 => 'member',
            'created_by'           => $tl->id,
        ]);

        $response = $this->actingAs($tl)->post(route('tl.members.reset_otp', $member));
        $response->assertSessionHas('success');
        $response->assertSessionHas('new_member');

        $member->refresh();
        $this->assertTrue((bool)$member->must_change_password);
        $this->assertNull($member->temp_password_plain);
        $this->assertTrue(Hash::check(session('new_member')['otp'], $member->password));
    }
}
