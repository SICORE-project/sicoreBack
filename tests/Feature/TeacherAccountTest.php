<?php

namespace Tests\Feature;

use App\Mail\TeacherAccountWelcomeMail;
use App\Models\Admin\Role;
use App\Models\Admin\User;
use App\Models\Personnel\Enseignant;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TeacherAccountTest extends TestCase
{
    private User $admin;
    private Enseignant $teacher;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['2026_07_04_000000_create_users_table', '2026_07_27_235253_create_roles_table', '2026_07_07_120005_create_enseignants_table', '2026_09_24_000001_make_user_teacher_unique'] as $migration) {
            (require database_path('migrations/'.$migration.'.php'))->up();
        }
        $role = Role::create(['nom' => 'Administrateur', 'slug' => 'super_admin', 'est_actif' => true]);
        Role::create(['nom' => 'Enseignant', 'slug' => 'enseignant', 'est_actif' => true]);
        $this->admin = User::create(['nom' => 'Admin', 'prenom' => 'Test', 'email' => 'admin@example.test', 'password' => 'password123', 'role_id' => $role->id]);
        Sanctum::actingAs($this->admin);
        $this->teacher = Enseignant::create(['nom' => 'Ndiaye', 'prenom' => 'Awa', 'matricule' => '001234/F', 'email' => 'awa@example.test']);
        config(['services.sicore.frontend_url' => 'https://sicore.example.test']);
        Mail::fake();
    }

    private function createAccount(array $extra = [])
    {
        return $this->postJson('/api/admin/users/teacher-accounts', array_merge([
            'enseignant_id' => $this->teacher->id, 'email' => 'awa@example.test',
        ], $extra));
    }

    public function test_creation_links_existing_teacher_assigns_teacher_role_and_sends_welcome(): void
    {
        $this->createAccount(['role_id' => $this->admin->role_id, 'nom' => 'Injected'])->assertCreated()->assertJsonPath('email_sent', true);
        $user = User::where('enseignant_id', $this->teacher->id)->sole();
        $this->assertSame('enseignant', $user->role->slug);
        $this->assertSame('Ndiaye', $user->nom);
        $this->assertSame('actif', $user->statut);
        $this->assertSame($this->admin->id, $user->created_by);
        $this->assertDatabaseCount('enseignants', 1);
        Mail::assertSent(TeacherAccountWelcomeMail::class, function ($mail) use ($user) {
            $html = $mail->render();
            $this->assertStringContainsString('001234/F', $html);
            $this->assertStringContainsString('awa@example.test', $html);
            $this->assertStringContainsString('https://sicore.example.test/forgot-password?', $html);
            $this->assertStringNotContainsString($user->password, $html);
            return $mail->hasTo('awa@example.test');
        });
    }

    public function test_search_includes_teachers_with_existing_or_archived_accounts(): void
    {
        foreach (['001234', 'Ndiaye', 'Awa', 'Awa Ndiaye'] as $search) {
            $this->getJson('/api/admin/users/teacher-candidates?'.http_build_query(['search' => $search]))
                ->assertOk()->assertJsonPath('total', 1)->assertJsonPath('data.0.id', $this->teacher->id);
        }
        $this->createAccount()->assertCreated();
        $this->getJson('/api/admin/users/teacher-candidates')->assertJsonPath('total', 1)->assertJsonPath('data.0.has_account', true);
        User::where('enseignant_id', $this->teacher->id)->first()->delete();
        $this->getJson('/api/admin/users/teacher-candidates')->assertJsonPath('total', 1)->assertJsonPath('data.0.has_account', true);
        $this->createAccount(['email' => 'other@example.test'])->assertUnprocessable()->assertJsonValidationErrors('enseignant_id');
    }

    public function test_duplicate_account_and_duplicate_email_are_rejected(): void
    {
        $this->createAccount()->assertCreated();
        $this->createAccount(['email' => 'other@example.test'])->assertUnprocessable()->assertJsonValidationErrors('enseignant_id');
        $this->createAccount(['email' => $this->admin->email])->assertUnprocessable()->assertJsonValidationErrors('email');
        $this->assertDatabaseCount('users', 2);
        Mail::assertSentCount(1);
    }

    public function test_unique_database_constraint_protects_against_duplicate_links(): void
    {
        $this->createAccount()->assertCreated();
        $this->expectException(QueryException::class);
        User::create(['nom' => 'Duplicate', 'prenom' => 'Test', 'email' => 'duplicate@example.test', 'password' => 'password123', 'enseignant_id' => $this->teacher->id]);
    }

    public function test_missing_role_and_deleted_teacher_do_not_create_accounts(): void
    {
        Role::where('slug', 'enseignant')->update(['est_actif' => false]);
        $this->createAccount()->assertUnprocessable();
        $this->teacher->delete();
        $this->createAccount()->assertNotFound();
        $this->assertDatabaseCount('users', 1);
        Mail::assertNothingSent();
    }

    public function test_mail_failure_preserves_account_and_resend_does_not_create_another(): void
    {
        Mail::shouldReceive('to')->once()->andThrow(new \RuntimeException('Mail unavailable'));
        $response = $this->createAccount()->assertCreated()->assertJsonPath('email_sent', false);
        $id = $response->json('data.id');
        $hash = User::findOrFail($id)->password;
        Mail::swap(new \Illuminate\Mail\MailManager($this->app));
        Mail::fake();
        $this->postJson("/api/admin/users/{$id}/teacher-invitation")->assertOk();
        $this->assertDatabaseCount('users', 2);
        $this->assertSame($hash, User::findOrFail($id)->password);
        Mail::assertSent(TeacherAccountWelcomeMail::class);
    }

    public function test_users_without_permission_cannot_search_create_or_resend(): void
    {
        // No role implies no permissions without requiring the permissions schema.
        $this->admin->role_id = null;
        $this->admin->unsetRelation('role');
        $this->getJson('/api/admin/users/teacher-candidates')->assertForbidden();
        $this->createAccount()->assertForbidden();
        $this->postJson('/api/admin/users/'.$this->admin->id.'/teacher-invitation')->assertForbidden();
        Mail::assertNothingSent();
    }

    public function test_welcome_recipient_can_define_password_using_existing_otp_flow(): void
    {
        foreach (['2026_08_17_162420_create_password_reset_otps_table', '2026_07_30_135016_create_personal_access_tokens_table'] as $migration) {
            (require database_path('migrations/'.$migration.'.php'))->up();
        }
        $this->createAccount()->assertCreated();
        $user = User::where('email', 'awa@example.test')->sole();
        $this->assertNull($user->password_changed_at);
        $this->postJson('/api/admin/users/'.$user->id.'/teacher-invitation')->assertOk();
        $this->postJson('/api/send-otp', ['email' => 'awa@example.test'])->assertOk();
        $otp = Mail::sent(\App\Mail\OtpMail::class)->first()->otp;
        $verification = $this->postJson('/api/verify-otp', ['email' => 'awa@example.test', 'otp' => $otp])->assertOk();
        $this->assertNull($user->fresh()->password_changed_at);
        $this->postJson('/api/admin/users/'.$user->id.'/teacher-invitation')->assertOk();
        $this->postJson('/api/reset-password-otp', [
            'email' => 'awa@example.test', 'reset_token' => $verification->json('reset_token'),
            'password' => 'NouveauSecret123!', 'password_confirmation' => 'NouveauSecret123!',
        ])->assertOk();
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('NouveauSecret123!', User::where('email', 'awa@example.test')->sole()->password));
        $user = User::where('email', 'awa@example.test')->sole();
        $this->assertNotNull($user->password_changed_at);
        Mail::fake();
        $this->postJson('/api/admin/users/'.$user->id.'/teacher-invitation')->assertUnprocessable();
        Mail::assertNothingSent();
    }

    public function test_search_is_paginated(): void
    {
        for ($i = 0; $i < 22; $i++) {
            Enseignant::create(['nom' => 'Diallo', 'prenom' => 'Test', 'matricule' => 'M'.$i]);
        }
        $this->getJson('/api/admin/users/teacher-candidates')->assertJsonCount(20, 'data')->assertJsonPath('last_page', 2);
        $this->getJson('/api/admin/users/teacher-candidates?page=2')->assertJsonCount(3, 'data');
    }
}
