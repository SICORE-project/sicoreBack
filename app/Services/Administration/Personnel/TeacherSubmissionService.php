<?php
namespace App\Services\Administration\Personnel;

use App\Models\Admin\User;
use App\Models\Personnel\TeacherSubmission;
use App\Services\Administration\IaScope;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class TeacherSubmissionService
{
    public function submit(User $user, array $data): TeacherSubmission
    {
        $ia = app(IaScope::class)->id($user);
        abort_unless((int) ($data['ia_id'] ?? 0) === $ia, 403);
        Validator::make($data, [
            'cni' => [Rule::unique('teacher_submissions', 'cni')],
            'matricule' => [Rule::unique('teacher_submissions', 'matricule')],
            'email' => ['nullable', Rule::unique('teacher_submissions', 'email')],
            'compte_bancaire.rib' => [Rule::unique('teacher_submissions', 'rib')],
        ], ['unique' => 'Cette valeur figure déjà dans un dossier soumis pour validation.'])->validate();
        return TeacherSubmission::create([
            'ia_id' => $ia, 'submitted_by' => $user->id, 'payload' => $data,
            'cni' => $data['cni'], 'matricule' => $data['matricule'],
            'email' => $data['email'] ?? null, 'rib' => $data['compte_bancaire']['rib'], 'status' => 'pending',
        ]);
    }

    public function approve(User $user, int $id): TeacherSubmission
    {
        abort_unless($user->hasRole('super_admin'), 403);
        return DB::transaction(function () use ($user, $id) {
            $submission = TeacherSubmission::query()->lockForUpdate()->findOrFail($id);
            if ($submission->status === 'approved') return $submission;
            abort_unless($submission->status === 'pending', 409);
            $data = $submission->payload;
            Validator::make($data, [
                'cni' => [Rule::unique('enseignants', 'cni')],
                'matricule' => [Rule::unique('enseignants', 'matricule')],
                'email' => ['nullable', Rule::unique('enseignants', 'email')],
                'indice' => ['nullable', Rule::unique('enseignants', 'indice')],
                'compte_bancaire.rib' => [Rule::unique('comptes_bancaires_enseignants', 'rib')],
            ], ['unique' => 'Cette valeur est déjà utilisée. Le dossier ne peut pas être validé.'])->validate();
            $teacher = app(EnseignantService::class)->create($data, $submission->submitted_by);
            $submission->update(['status' => 'approved', 'approved_by' => $user->id,
                'approved_at' => now(), 'enseignant_id' => $teacher->id]);
            return $submission->refresh();
        });
    }
}
