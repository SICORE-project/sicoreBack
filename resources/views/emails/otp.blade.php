<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Code de vérification</title>
</head>
<body style="font-family: Arial, sans-serif; background:#f4f4f5; padding:24px;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:480px;margin:0 auto;background:#ffffff;border-radius:8px;overflow:hidden;">
        <tr>
            <td style="background:#111827;padding:20px;text-align:center;">
                <h1 style="color:#ffffff;font-size:20px;margin:0;">Sicore</h1>
            </td>
        </tr>
        <tr>
            <td style="padding:24px;">
                <p style="font-size:15px;color:#111827;">
                    Bonjour {{ $user->prenom ?? '' }},
                </p>
                <p style="font-size:15px;color:#111827;">
                    Voici votre code de vérification pour réinitialiser votre mot de passe :
                </p>
                <p style="font-size:32px;font-weight:bold;letter-spacing:6px;text-align:center;color:#111827;margin:24px 0;">
                    {{ $otp }}
                </p>
                <p style="font-size:13px;color:#6b7280;">
                    Ce code expire dans 10 minutes. Si vous n'êtes pas à l'origine de cette demande, ignorez cet email.
                </p>
            </td>
        </tr>
    </table>
</body>
</html>