<!doctype html>
<html lang="fr">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Bienvenue sur SICORE</title></head>
<body style="margin:0;padding:24px 12px;background:#f1f5f9;font-family:Arial,sans-serif;color:#1e293b;">
<div style="display:none;max-height:0;overflow:hidden;">Votre espace enseignant est prêt. Définissez votre mot de passe pour vous connecter.</div>
<table role="presentation" style="width:100%;max-width:600px;margin:auto;border-collapse:collapse;background:#fff;border-radius:16px;overflow:hidden;">
<tr><td style="background:#14532d;padding:32px;color:#fff;"><p style="margin:0;font-size:26px;font-weight:bold;letter-spacing:3px;">SICORE</p><p style="margin:10px 0 0;color:#dcfce7;">Votre espace enseignant</p></td></tr>
<tr><td style="padding:32px;">
<p style="color:#15803d;font-size:12px;font-weight:bold;letter-spacing:2px;">BIENVENUE</p>
<h1 style="font-size:25px;line-height:1.3;">Bonjour {{ $user->prenom }} {{ $user->nom }},</h1>
<p style="line-height:1.7;">Votre compte d’accès à SICORE a été créé et associé à votre dossier enseignant. Votre espace est prêt à vous accueillir.</p>
<table role="presentation" style="width:100%;padding:20px;background:#f8fafc;border-radius:12px;line-height:1.8;">
<tr><td style="color:#64748b;">Identifiant</td><td style="font-weight:bold;">{{ $user->email }}</td></tr>
<tr><td style="color:#64748b;">Matricule</td><td>{{ $teacher?->matricule }}</td></tr>
<tr><td style="color:#64748b;">Profil</td><td>Enseignant</td></tr>
</table>
<h2 style="font-size:18px;margin-top:28px;">Commencez en quelques instants</h2>
<p style="line-height:1.7;">Cliquez sur le bouton ci-dessous, demandez votre code de vérification par e-mail, puis choisissez votre mot de passe personnel.</p>
<p style="margin:28px 0;"><a href="{{ $setupUrl }}" style="display:inline-block;background:#15803d;color:#fff;text-decoration:none;padding:16px 24px;border-radius:8px;font-weight:bold;">Définir mon mot de passe</a></p>
<p style="font-size:14px;line-height:1.7;">Vous avez déjà défini votre mot de passe ? <a href="{{ $loginUrl }}" style="color:#15803d;">Accédez à la plateforme</a>.</p>
<p style="font-size:13px;line-height:1.7;color:#64748b;">Gardez votre mot de passe et vos codes de vérification confidentiels. Si vous ne reconnaissez pas ce compte, contactez votre gestionnaire.</p>
<p style="font-size:12px;word-break:break-all;color:#64748b;">Si le bouton ne fonctionne pas, ouvrez ce lien :<br><a href="{{ $setupUrl }}" style="color:#15803d;">{{ $setupUrl }}</a></p>
</td></tr>
<tr><td style="padding:24px 32px;border-top:1px solid #e2e8f0;color:#64748b;font-size:12px;">SICORE · Système Intégré des COrps Émergents<br>Message automatique — merci de ne pas répondre.</td></tr>
</table>
</body></html>
