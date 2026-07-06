<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\User;
use App\Services\LgpdService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class GovernanceSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_lgpd_accept_export_and_anonymization_request(): void
    {
        $student = User::factory()->create();
        Sanctum::actingAs($student);

        $this->postJson('/api/lgpd/accept')
            ->assertOk()
            ->assertJsonPath('profile.lgpd_consent_version', LgpdService::CURRENT_TERMS_VERSION);

        $this->getJson('/api/lgpd/export')
            ->assertOk()
            ->assertJsonPath('privacy.terms_version', LgpdService::CURRENT_TERMS_VERSION);

        $this->postJson('/api/lgpd/anonymization-request')
            ->assertNoContent();

        $this->assertNotNull($student->profile()->first()->anonymization_requested_at);
    }

    public function test_certificate_public_validation_reports_revoked_status(): void
    {
        $admin = User::factory()->create();
        Role::findOrCreate('administrador');
        $admin->assignRole('administrador');
        $certificate = $this->makeCertificate();

        $this->getJson("/api/certificate/validate/{$certificate->code}")
            ->assertOk()
            ->assertJsonPath('valid', true)
            ->assertJsonPath('status', 'valid');

        Sanctum::actingAs($admin);
        $this->postJson("/api/certificates/{$certificate->id}/revoke", [
            'reason' => 'Emitido por engano',
        ])->assertOk()->assertJsonPath('status', 'revoked');

        $this->getJson("/api/certificate/validate/{$certificate->code}")
            ->assertOk()
            ->assertJsonPath('valid', false)
            ->assertJsonPath('status', 'revoked');
    }

    public function test_certificate_public_validation_can_render_html_page(): void
    {
        $certificate = $this->makeCertificate();

        $this->get("/certificado/{$certificate->code}")
            ->assertOk()
            ->assertSee('Validação de certificado')
            ->assertSee($certificate->student_name)
            ->assertSee($certificate->course_name)
            ->assertSee('Válido');
    }

    public function test_student_cannot_list_all_users(): void
    {
        $student = User::factory()->create();
        Role::findOrCreate('aluno');
        $student->assignRole('aluno');

        Sanctum::actingAs($student);

        $this->getJson('/api/users')->assertForbidden();
    }

    private function makeCertificate(): Certificate
    {
        $teacher = User::factory()->create();
        $student = User::factory()->create();
        $category = Category::create(['name' => 'Legislativo', 'slug' => 'legislativo']);
        $course = Course::create([
            'category_id' => $category->id,
            'teacher_id' => $teacher->id,
            'name' => 'Processo Legislativo',
            'slug' => 'processo-legislativo',
            'workload_hours' => 20,
            'status' => 'published',
        ]);

        return Certificate::create([
            'course_id' => $course->id,
            'user_id' => $student->id,
            'code' => 'ABC123CERT',
            'verification_hash' => hash('sha256', 'ABC123CERT'),
            'student_name' => $student->name,
            'course_name' => $course->name,
            'workload_hours' => 20,
            'completed_at' => now()->toDateString(),
            'issued_at' => now(),
            'status' => 'valid',
        ]);
    }
}
