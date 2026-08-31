<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\User;
use App\Models\Appraisal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppraisalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed database
        $this->seed();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/');
        $response->assertRedirect('/login');
    }

    public function test_user_can_view_login_page(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
        $response->assertSee('Sign in to your account');
    }

    public function test_user_can_login_with_correct_credentials(): void
    {
        $user = User::where('email', 'rahul.sharma@cmrsl.example')->first();
        $this->assertNotNull($user);

        $response = $this->post('/login', [
            'email' => 'rahul.sharma@cmrsl.example',
            'password' => 'Cybermedia@123',
        ]);

        $response->assertRedirect('/');
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_fails_with_incorrect_credentials(): void
    {
        $response = $this->post('/login', [
            'email' => 'rahul.sharma@cmrsl.example',
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_authenticated_user_can_access_dashboard(): void
    {
        $user = User::where('email', 'rahul.sharma@cmrsl.example')->first();
        
        $response = $this->actingAs($user)->get('/');
        
        $response->assertStatus(200);
        $response->assertSee('Rahul Sharma');
    }

    public function test_hr_can_access_dashboard(): void
    {
        $user = User::where('email', 'sanjay.mishra@cmrsl.example')->first();
        
        $response = $this->actingAs($user)->get('/');
        
        $response->assertStatus(200);
        $response->assertSee('Sanjay Mishra');
        $response->assertSee('HR Panel');
    }

    public function test_bu_head_can_access_dashboard(): void
    {
        $user = User::where('email', 'meera.kapoor@cmrsl.example')->first();
        
        $response = $this->actingAs($user)->get('/');
        
        $response->assertStatus(200);
        $response->assertSee('Meera Kapoor');
        $response->assertDontSee('HR Panel');
    }

    public function test_user_can_view_appraisal_details(): void
    {
        $user = User::where('email', 'rahul.sharma@cmrsl.example')->first();
        $appraisal = Appraisal::where('employeeId', $user->employeeId)->first();
        $this->assertNotNull($appraisal);

        $response = $this->actingAs($user)->get("/appraisals/{$appraisal->id}");
        
        $response->assertStatus(200);
        $response->assertSee('Appraisal details');
    }

    public function test_employee_cannot_access_admin_routes(): void
    {
        $user = User::where('email', 'rahul.sharma@cmrsl.example')->first();
        
        $response = $this->actingAs($user)->post('/admin/cycle/assign', [
            'employeeId' => 'some-id',
            'cycleId' => 'some-id',
        ]);

        $response->assertStatus(403);
    }

    public function test_hr_can_access_admin_routes(): void
    {
        $user = User::where('email', 'sanjay.mishra@cmrsl.example')->first();
        $this->assertNotNull($user);
        
        // Assert redirect (success) instead of 403
        $response = $this->actingAs($user)->post('/admin/cycle/assign', [
            'employeeId' => $user->employeeId,
            'cycleId' => 'invalid-cycle-id', // will fail validation/lookup and redirect back
        ]);

        $this->assertNotEquals(403, $response->getStatusCode());
    }

    public function test_manager_can_submit_appraiser_review_with_current_form_fields(): void
    {
        $manager = User::where('email', 'anita.rao@cmrsl.example')->first();
        $this->assertNotNull($manager);

        $appraisal = Appraisal::where('managerId', $manager->employeeId)
            ->where('status', 'SUBMITTED')
            ->first();
        $this->assertNotNull($appraisal);

        $response = $this->actingAs($manager)->post("/appraisals/{$appraisal->id}/submit", [
            'appraiserSection' => [
                'overallRating' => '8.4',
                'recommendation' => 'Strong performance with clear ownership across the cycle.',
                'newKraNotes' => 'Expand delivery planning responsibilities next cycle.',
            ],
        ]);

        $response->assertRedirect("/appraisals/{$appraisal->id}");
        $response->assertSessionHas('success');
        $response->assertSessionDoesntHaveErrors();

        $appraisal->refresh();

        $this->assertSame('MANAGER_REVIEW', $appraisal->status);
        $this->assertSame(8.4, $appraisal->appraiserOverallRating);
        $this->assertSame('Strong performance with clear ownership across the cycle.', $appraisal->appraiserRecommendation);
        $this->assertSame('Expand delivery planning responsibilities next cycle.', $appraisal->appraiserNewKraNotes);
        $this->assertNotNull($appraisal->managerSubmittedAt);
        $this->assertNotNull($appraisal->appraiserSignedAt);
    }
}
