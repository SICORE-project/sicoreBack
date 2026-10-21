<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\TeacherAccountWelcomeMail;
use App\Models\Admin\Role;
use App\Models\Admin\User;
use App\Models\Personnel\Enseignant;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class TeacherAccountController extends Controller
{
    public function teachers(Request $request)
    {
        $data = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);
        $query = Enseignant::query()->select(['id', 'matricule', 'nom', 'prenom', 'email'])
            ->selectSub(User::withTrashed()->selectRaw('1')->whereColumn('users.enseignant_id', 'enseignants.id')->limit(1), 'has_account');
        foreach (preg_split('/\s+/u', trim($data['search'] ?? ''), -1, PREG_SPLIT_NO_EMPTY) as $term) {
            $query->where(function ($query) use ($term) {
                $query->where('matricule', 'like', '%'.$term.'%')
                    ->orWhere('nom', 'like', '%'.$term.'%')
                    ->orWhere('prenom', 'like', '%'.$term.'%');
            });
        }

        return response()->json($query->orderBy('nom')->orderBy('prenom')->orderBy('id')->paginate(20)
            ->through(fn ($teacher) => $teacher->setAttribute('has_account', (bool) $teacher->has_account)));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'enseignant_id' => ['required', 'integer', 'exists:enseignants,id'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
        ]);
        try {
            $user = DB::transaction(function () use ($data, $request) {
                $teacher = Enseignant::whereKey($data['enseignant_id'])->lockForUpdate()->firstOrFail();
                if (User::withTrashed()->where('enseignant_id', $teacher->id)->exists()) {
                    throw ValidationException::withMessages(['enseignant_id' => 'Cet enseignant possède déjà un compte, éventuellement archivé.']);
                }
                $role = Role::where('slug', 'enseignant')->where('est_actif', true)->first();
                if (! $role) {
                    throw ValidationException::withMessages(['enseignant_id' => 'Le rôle Enseignant doit être configuré et actif avant de créer ce compte.']);
                }
    
                return User::create([
                    'enseignant_id' => $teacher->id,
                    'nom' => $teacher->nom,
                    'prenom' => $teacher->prenom,
                    'email' => $data['email'],
                    'password' => Str::random(64),
                    'role_id' => $role->id,
                    'statut' => 'actif',
                    'fonction' => 'Enseignant',
                    'created_by' => $request->user()->id,
                ]);
            });
        } catch (UniqueConstraintViolationException $exception) {
            // Another request may have used the same email after validation.
            if (User::withTrashed()->where('email', $data['email'])->exists()) {
                throw ValidationException::withMessages(['email' => 'Cette adresse e-mail est déjà utilisée.']);
            }
            if (User::withTrashed()->where('enseignant_id', $data['enseignant_id'])->exists()) {
                throw ValidationException::withMessages(['enseignant_id' => 'Cet enseignant possède déjà un compte.']);
            }
            throw $exception;
        }

        $sent = $this->sendWelcome($user);

        return response()->json([
            'success' => true,
            'email_sent' => $sent,
            'message' => $sent ? 'Compte enseignant créé. L’invitation a été transmise au serveur de messagerie ; sa réception dans la boîte du destinataire n’est pas encore confirmée. Pensez également à vérifier les courriers indésirables (spams).'
                : 'Le compte est créé, mais l’e-mail n’a pas pu être envoyé. Vous pouvez renvoyer l’invitation depuis la liste des utilisateurs.',
            'data' => ['id' => $user->id],
        ], 201);
    }

    public function resend(User $user)
    {
        abort_unless($user->enseignant_id && $user->statut === 'actif', 422, 'Un compte enseignant actif est nécessaire.');
        abort_if($user->password_changed_at !== null, 422, 'Cet enseignant a déjà défini son mot de passe.');
        $sent = $this->sendWelcome($user);

        return response()->json([
            'success' => $sent,
            'message' => $sent ? 'L’invitation a été transmise au serveur de messagerie ; sa réception dans la boîte du destinataire n’est pas encore confirmée. Vérifiez également les courriers indésirables.' : 'Envoi impossible. Vérifiez la configuration du service de messagerie puis réessayez.',
        ], $sent ? 200 : 503);
    }

    private function sendWelcome(User $user): bool
    {
        try {
            $message = Mail::to($user->email)->send(new TeacherAccountWelcomeMail($user));
            Log::info('teacher_account.invitation_sent', [
                'user_id' => $user->id,
                'mailer' => config('mail.default'),
                'message_id' => $message?->getMessageId(),
            ]);
            return true;
        } catch (Throwable $exception) {
            Log::warning('teacher_account.invitation_failed', [
                'user_id' => $user->id,
                'mailer' => config('mail.default'),
                'exception_class' => get_class($exception),
            ]);
            report($exception);
            return false;
        }
    }
}
