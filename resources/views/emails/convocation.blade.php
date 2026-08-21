<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Convocation — {{ $convocation->objet ?? '—' }}</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background: #f1f5f9;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 14px;
            color: #1f2933;
        }

        .wrapper {
            max-width: 600px;
            margin: 0 auto;
            padding: 24px 16px;
        }

        .card {
            background: #ffffff;
            border-radius: 10px;
            border: 1px solid #e5e7eb;
            padding: 28px 30px;
        }

        h1 {
            font-size: 18px;
            margin: 0 0 6px;
            color: #14532d;
        }

        p {
            line-height: 1.5;
            margin: 0 0 14px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 18px 0;
        }

        td {
            padding: 6px 0;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: top;
        }

        .info-label {
            color: #52606d;
            font-weight: bold;
            width: 160px;
        }

        .message-personnalise {
            background: #f8fafc;
            border-left: 3px solid #16a34a;
            padding: 12px 16px;
            margin: 18px 0;
            white-space: pre-line;
        }

        .footer {
            margin-top: 24px;
            font-size: 12px;
            color: #94a3b8;
        }
    </style>
</head>
<body>

    <div class="wrapper">
        <div class="card">

            <h1>Convocation officielle</h1>

            <p>
                Bonjour {{ trim(($enseignant->prenom ?? '').' '.($enseignant->nom ?? '')) ?: '' }},
            </p>

            <p>
                Vous êtes convoqué(e) dans le cadre de :
                <strong>{{ $convocation->objet ?? '—' }}</strong>.
            </p>

            <table>
                @if ($convocation->typeConvocation ?? null)
                    <tr>
                        <td class="info-label">Type</td>
                        <td>{{ $convocation->typeConvocation->libelle ?? '—' }}</td>
                    </tr>
                @endif
                @if (! empty($convocation->session))
                    <tr>
                        <td class="info-label">Session</td>
                        <td>{{ $convocation->session }}</td>
                    </tr>
                @endif
                <tr>
                    <td class="info-label">Date d'émission</td>
                    <td>{{ optional($convocation->date_emission)->format('d/m/Y') ?? '—' }}</td>
                </tr>
                <tr>
                    <td class="info-label">Période</td>
                    <td>
                        @if ($convocation->date_debut && $convocation->date_fin)
                            Du {{ $convocation->date_debut->format('d/m/Y') }} au {{ $convocation->date_fin->format('d/m/Y') }}
                        @else
                            —
                        @endif
                    </td>
                </tr>
                @if (! empty($convocation->heure_debut))
                    <tr>
                        <td class="info-label">Heure</td>
                        <td>{{ $convocation->heure_debut }}</td>
                    </tr>
                @endif
                @if (! empty($convocation->lieu_examen))
                    <tr>
                        <td class="info-label">Lieu d'examen</td>
                        <td>{{ $convocation->lieu_examen }}</td>
                    </tr>
                @endif
                @if (! empty($convocation->lieu_affectation))
                    <tr>
                        <td class="info-label">Lieu d'affectation</td>
                        <td>{{ $convocation->lieu_affectation }}</td>
                    </tr>
                @endif
            </table>

            @if (! empty($messagePersonnalise))
                <div class="message-personnalise">{{ $messagePersonnalise }}</div>
            @endif

            <p>
                Merci de vous présenter muni(e) d'une pièce d'identité valide. Pour toute question,
                veuillez contacter le service en charge de l'organisation de cet examen.
            </p>

            <p class="footer">
                Ce message est envoyé automatiquement par SICORE, ne pas répondre directement à cet e-mail.
            </p>

        </div>
    </div>

</body>
</html>
