<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>État de paiement des frais de déplacement — {{ $convocation->objet ?? '' }}</title>
    <style>
        @page { margin: 22px 26px 30px; }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #1f2933;
        }

        .entete td { border: none; padding: 0; vertical-align: top; }
        .entete p { margin: 0; line-height: 1.35; }
        .republique { font-weight: bold; text-transform: uppercase; font-size: 11px; }
        .devise { font-style: italic; font-size: 9px; }
        .date-edition { text-align: right; font-size: 9px; color: #52606d; }

        h1 {
            margin: 16px 0 4px;
            font-size: 15px;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .sous-titre { margin: 0 0 12px; text-align: center; font-size: 11px; }

        .infos { width: 100%; margin-bottom: 12px; border-collapse: collapse; }
        .infos td { border: none; padding: 2px 10px 2px 0; font-size: 10px; }
        .infos .label { color: #52606d; font-weight: bold; width: 18%; }

        h2 {
            margin: 14px 0 6px;
            font-size: 11px;
            text-transform: uppercase;
            border-bottom: 1px solid #94a3b8;
            padding-bottom: 3px;
        }

        table.etat { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
        table.etat th, table.etat td {
            border: 1px solid #94a3b8;
            padding: 4px 5px;
            font-size: 9px;
            vertical-align: middle;
        }
        table.etat th { background: #e2e8f0; text-align: center; font-weight: bold; }
        .num { text-align: center; width: 22px; }
        .montant { text-align: right; white-space: nowrap; }
        .centre { text-align: center; }
        .rib { white-space: nowrap; font-family: DejaVu Sans Mono, monospace; }
        tr.sous-total td { background: #f1f5f9; font-weight: bold; }
        tr.total td { background: #e2e8f0; font-weight: bold; font-size: 10px; }

        .arrete { margin: 14px 0 0; font-size: 10px; }
        .arrete strong { text-transform: uppercase; }

        .signatures { width: 100%; margin-top: 28px; border-collapse: collapse; }
        .signatures td { border: none; width: 33%; text-align: center; vertical-align: top; font-size: 10px; }
        .signatures .titre { font-weight: bold; text-decoration: underline; }
        .signatures .espace { height: 60px; }

        .note { margin-top: 10px; font-size: 8px; color: #52606d; }
        .vide { text-align: center; padding: 30px 0; color: #52606d; }
    </style>
</head>
<body>

    <table class="entete" style="width: 100%;">
        <tr>
            <td style="width: 60%;">
                <p class="republique">République du Sénégal</p>
                <p class="devise">Un Peuple - Un But - Une Foi</p>
            </td>
            <td class="date-edition">Édité le {{ $dateEdition }}</td>
        </tr>
    </table>

    <h1>État de paiement des frais de déplacement</h1>
    <p class="sous-titre">{{ $convocation->objet ?? '—' }}@if (! empty($convocation->session)) — Session {{ $convocation->session }}@endif</p>

    <table class="infos">
        <tr>
            <td class="label">Période d'examen</td>
            <td>{{ $periode }}</td>
            <td class="label">Lieu d'affectation</td>
            <td>{{ $convocation->lieu_affectation ?: '—' }}</td>
        </tr>
        <tr>
            <td class="label">Centre(s)</td>
            <td>{{ $centresLibelle }}</td>
            <td class="label">Bénéficiaires</td>
            <td>{{ $nombreBeneficiaires }}</td>
        </tr>
    </table>

    @if ($nombreBeneficiaires === 0)
        <p class="vide">Aucune fiche de déplacement à payer pour cette convocation{{ $centreFiltre ? ' et ce centre' : '' }}.</p>
    @else
        @php($numero = 0)

        @foreach ($groupes as $groupe)
            @if (count($groupes) > 1)
                <h2>Centre : {{ $groupe['centre'] }}</h2>
            @endif

            <table class="etat">
                <thead>
                    <tr>
                        <th class="num">N°</th>
                        <th>Matricule</th>
                        <th>Prénom(s) et nom</th>
                        <th>Catégorie</th>
                        <th>Fonction</th>
                        <th>Partant de</th>
                        <th>Pour se rendre à</th>
                        <th>Période</th>
                        <th>Numéro de compte</th>
                        <th>Montant (FCFA)</th>
                        <th>Émargement</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($groupe['lignes'] as $ligne)
                        @php($numero++)
                        <tr>
                            <td class="num">{{ $numero }}</td>
                            <td>{{ $ligne['matricule'] ?: '—' }}</td>
                            <td>{{ $ligne['nom_complet'] }}</td>
                            <td class="centre">{{ $ligne['categorie'] }}</td>
                            <td>{{ $ligne['fonction'] }}</td>
                            <td>{{ $ligne['lieu_depart'] ?: '—' }}</td>
                            <td>{{ $ligne['lieu_destination'] ?: '—' }}</td>
                            <td class="centre">{{ $ligne['periode'] }}</td>
                            <td class="centre rib">{{ $ligne['rib']['affichage'] ?: 'Non renseigné' }}</td>
                            <td class="montant">{{ number_format($ligne['montant'], 0, ',', ' ') }}</td>
                            <td style="width: 70px;"></td>
                        </tr>
                    @endforeach

                    @if (count($groupes) > 1)
                        <tr class="sous-total">
                            <td colspan="9" class="montant">Sous-total {{ $groupe['centre'] }}</td>
                            <td class="montant">{{ number_format($groupe['total'], 0, ',', ' ') }}</td>
                            <td></td>
                        </tr>
                    @endif
                </tbody>
            </table>
        @endforeach

        <table class="etat">
            <tr class="total">
                <td class="montant">TOTAL GÉNÉRAL — {{ $nombreBeneficiaires }} bénéficiaire(s)</td>
                <td class="montant" style="width: 110px;">{{ number_format($total, 0, ',', ' ') }} FCFA</td>
            </tr>
        </table>

        <p class="arrete">
            Arrêté le présent état à la somme de
            @if ($totalEnLettres)
                <strong>{{ $totalEnLettres }} francs CFA</strong>
            @endif
            ({{ number_format($total, 0, ',', ' ') }} FCFA).
        </p>

        <table class="signatures">
            <tr>
                <td><p class="titre">Le Chef de centre</p><div class="espace"></div></td>
                <td><p class="titre">Le Président du jury</p><div class="espace"></div></td>
                <td><p class="titre">L'Ordonnateur</p><div class="espace"></div></td>
            </tr>
        </table>

        <p class="note">
            Montant retenu : montant approuvé de la fiche s'il existe, sinon montant calculé.
            Fiches en brouillon ou rejetées non incluses.
        </p>
    @endif

</body>
</html>
