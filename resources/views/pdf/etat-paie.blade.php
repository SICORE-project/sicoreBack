<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>États de paiement {{ $titre }}</title>
    <style>

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #1f2933;
        }

        .letterhead {
            border: none;
            margin-bottom: 10px;
        }

        .letterhead td {
            border: none;
            padding: 0;
            vertical-align: top;
        }

        .letterhead .numero {
            text-align: right;
            font-weight: bold;
        }

        .letterhead .date {
            text-align: right;
            margin-top: 6px;
        }

        h1 {
            text-align: center;
            font-size: 15px;
            text-transform: uppercase;
            margin: 10px 0 14px;
        }

        .centre {
            text-align: center;
            font-weight: bold;
            font-size: 11px;
            margin: 0 0 8px;
        }

        .reference {
            margin: 0 0 4px;
            font-size: 10px;
        }

        .reference .label {
            font-weight: bold;
            text-decoration: underline;
        }

        h2 {
            text-align: center;
            font-size: 13px;
            text-decoration: underline;
            margin: 14px 0 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 4px;
        }

        th, td {
            border: 1px solid #1f2933;
            padding: 5px 7px;
            text-align: left;
            font-size: 10px;
            vertical-align: middle;
        }

        th {
            background: #f1f5f9;
            text-align: center;
            text-transform: uppercase;
            font-size: 9px;
        }

        .total-table td {
            font-weight: bold;
        }

        .total-table .total-label { background: #fef3c7; text-align: center; }
        .total-table .total-devise { text-align: center; }
        .total-table .total-lignes-label { text-align: right; }
        .total-table .total-lignes-valeur { background: #fef3c7; text-align: center; }

        .metier-row td {
            background: #e2e8f0;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 10px;
        }

        .montant { text-align: right; }

        .empty-message {
            color: #52606d;
            font-style: italic;
            margin-top: 10px;
        }

        .footer-page {
            margin-top: 16px;
            text-align: center;
            font-size: 9px;
            color: #52606d;
        }

    </style>
</head>
<body>

    <table class="letterhead">
        <tr>
            <td style="width: 55%;">
                <p><strong>République du Sénégal</strong></p>
                <p>Un Peuple - Un But - Une Foi</p>
            </td>
            <td style="width: 45%;">
                <p class="numero">N° {{ $reference ?: '________________' }}/MEFPT/SG/DECPC</p>
                <p class="date">Dakar, le {{ $dateGeneration }}</p>
            </td>
        </tr>
    </table>

    <h1>États de paiement {{ $titre }}</h1>

    @if (! empty($typeLibelle))
        <p class="centre">Type d'indemnité : {{ $typeLibelle }}</p>
    @endif

    @if (! empty($centre))
        <p class="centre">Établissement : {{ $centre }}</p>
    @endif

    <p class="reference">
        <span class="label">Référence</span> /&nbsp;: Arrêté Interministériel N° 05260 du 30/04/2009 modifiant et complétant l'arrêté N° 001498
        du 24/02/2006 fixant les taux de rénumération des membres des jury des Examens et Concours Professionnels.
    </p>

    <h2>Contrôle clé RIB / Fichier virement de masse Trésor Public</h2>

    <table class="total-table">
        <tr>
            <td class="total-label" style="width: 18%;">TOTAL</td>
            <td class="montant" style="width: 20%;">{{ number_format($totalMontant, 0, ',', ' ') }}</td>
            <td class="total-devise" style="width: 10%;">FCFA</td>
            <td class="total-lignes-label" style="width: 32%;">Nombre de lignes :</td>
            <td class="total-lignes-valeur" style="width: 20%;">{{ $nombreLignes }}</td>
        </tr>
    </table>

    @forelse ($groupes as $groupe)

        <table>
            @if (! empty($groupe['metier']))
                <tr class="metier-row">
                    <td colspan="7">{{ $groupe['metier'] }}</td>
                </tr>
            @endif
            <tr>
                <th style="width: 9%;">Code banque</th>
                <th style="width: 10%;">Code guichet</th>
                <th style="width: 16%;">Compte</th>
                <th style="width: 8%;">Clé RIB</th>
                <th style="width: 12%;">Montant</th>
                <th style="width: 30%;">Nom bénéficiaire</th>
                <th style="width: 15%;">Libellé opération</th>
            </tr>
            @foreach ($groupe['lignes'] as $ligne)
                <tr>
                    <td>{{ $ligne['code_banque'] ?: '—' }}</td>
                    <td>{{ $ligne['code_guichet'] ?: '—' }}</td>
                    <td>{{ $ligne['numero_compte_bancaire'] ?: '—' }}</td>
                    <td>{{ $ligne['cle_rib'] ?: '—' }}</td>
                    <td class="montant">{{ number_format($ligne['montant'] ?? 0, 0, ',', ' ') }}</td>
                    <td>{{ trim(($ligne['prenom'] ?? '').' '.($ligne['nom'] ?? '')) ?: '—' }}</td>
                    <td>{{ $ligne['libelle'] ?? '—' }}</td>
                </tr>
            @endforeach
        </table>

    @empty

        <p class="empty-message">Aucun membre pour ce filtre.</p>

    @endforelse

    <p class="footer-page">Page 1 de 1</p>

</body>
</html>
