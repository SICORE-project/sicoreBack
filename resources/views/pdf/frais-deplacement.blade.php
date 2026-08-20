<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Fiche de déplacement {{ $mission->reference ?? '#'.$mission->id }}</title>
    <style>
      
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #1f2933;
        }

        h1 {
            font-size: 16px;
            margin: 0 0 4px;
        }

        h2 {
            font-size: 13px;
            margin: 16px 0 8px;
            padding-bottom: 4px;
            border-bottom: 1px solid #cbd5e1;
            text-transform: uppercase;
        }

        .meta {
            color: #52606d;
            margin: 0 0 14px;
            font-size: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }

        th, td {
            border: 1px solid #cbd5e1;
            padding: 5px 7px;
            text-align: left;
            font-size: 10px;
            vertical-align: top;
        }

        th {
            background: #f1f5f9;
            text-align: center;
        }

        .info-grid td {
            border: none;
            padding: 3px 8px 3px 0;
            font-size: 11px;
        }

        .info-label {
            color: #52606d;
            font-weight: bold;
            width: 190px;
        }

        .empty-message {
            color: #52606d;
            font-style: italic;
        }

        .montant {
            font-size: 14px;
            font-weight: bold;
        }

        .page-break {
            page-break-before: always;
        }

        {{-- Entête officielle (RECTO) --}}
        .letterhead {
            border: none;
            margin-bottom: 6px;
        }

        .letterhead td {
            border: none;
            padding: 0;
            vertical-align: top;
        }

        .letterhead .republique {
            font-weight: bold;
        }

        .letterhead .ministere {
            margin-top: 10px;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 10px;
        }

        .letterhead .direction {
            text-transform: uppercase;
            font-size: 9px;
            color: #52606d;
        }

        .letterhead .titre-feuille {
            text-align: right;
            font-weight: bold;
            font-size: 13px;
        }

        .letterhead .numero-feuille {
            text-align: right;
            font-weight: bold;
            font-size: 18px;
            margin-top: 4px;
        }

        {{-- Colonne latérale des notes (1)-(5) + NOTA, comme sur le papier --}}
        .recto-layout {
            border: none;
        }

        .recto-layout td {
            border: none;
            padding: 0;
            vertical-align: top;
        }

        .recto-sidebar {
            width: 27%;
            padding-right: 14px !important;
            font-size: 9px;
            color: #475569;
        }

        .recto-sidebar p {
            margin: 0 0 6px;
        }

        .recto-sidebar .nota-titre {
            margin-top: 12px;
            font-weight: bold;
            color: #1f2933;
        }

        .recto-fields {
            width: 73%;
            font-size: 11px;
        }

        .recto-fields p {
            margin: 0 0 8px;
        }

        .recto-fields .champ-valeur {
            font-weight: bold;
        }

        {{-- Tableau VERSO combiné (1 seul tableau, comme sur le papier) --}}
        .visa-table th,
        .visa-table td {
            font-size: 9px;
            padding: 4px 5px;
        }

        .visa-cell-avance {
            width: 26%;
        }

        .visa-cell-observations {
            width: 12%;
        }

        .visa-avance-titre {
            margin: 0 0 4px;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 9px;
        }

        .visa-avance-titre.reglement {
            margin-top: 8px;
        }

        .visa-mini-table {
            margin-bottom: 4px;
        }

        .visa-mini-table th,
        .visa-mini-table td {
            font-size: 8px;
            padding: 2px 3px;
        }

        .visa-avance-ligne {
            margin: 2px 0;
            font-size: 9px;
        }
    </style>
</head>
<body>

    <p class="meta">
        Fiche {{ $mission->reference ?? '—' }}
        &middot; {{ trim(($mission->beneficiaire->prenom ?? '').' '.($mission->beneficiaire->nom ?? '')) ?: '—' }}
        @if (! empty($mission->beneficiaire->matricule))
            (matricule {{ $mission->beneficiaire->matricule }})
        @endif
        @if (! empty($mission->convocation->objet))
            &middot; {{ $mission->convocation->objet }}
        @endif
        &middot; Statut de la fiche : {{ ucfirst($mission->statut ?? '—') }}
    </p>

    {{-- ================================================================
         RECTO — entête officielle
    ================================================================ --}}

    <table class="letterhead">
        <tr>
            <td style="width: 55%;">
                <p class="republique">République du Sénégal</p>
                <p>Un Peuple - Un But - Une Foi</p>
                <p class="ministere">Ministère des Finances et du Budget</p>
                <p class="direction">Direction du Matériel et du Transit Administratif</p>
            </td>
            <td style="width: 45%;">
                <p class="titre-feuille">FEUILLE DE DEPLACEMENT N°</p>
                <p class="numero-feuille">{{ $mission->reference ?? '—' }}</p>
            </td>
        </tr>
    </table>

    {{-- ================================================================
         RECTO — colonne des notes (1)-(5) + NOTA / champs du formulaire,
         dans l'ordre exact du papier.
    ================================================================ --}}

    <table class="recto-layout">
        <tr>
            <td class="recto-sidebar">
                <p><strong>(1)</strong> Nom et Prénoms</p>
                <p><strong>(2)</strong> Grade et emploi</p>
                <p><strong>(3)</strong> Catégorie</p>
                <p><strong>(4)</strong> Nom et garde de l'autorité qui délivre la feuille de déplacement</p>
                <p><strong>(5)</strong> Lorsque le fonctionnaire utilise un véhicule dont l'exploitation relève de la règle des Chemins de fer</p>
                <p class="nota-titre">NOTA :</p>
                <p>Le titulaire de la présente feuille doit s'assurer que toutes les indications nécessaires à la constatation de ses droits ont été apposées par chaque fonctionnaire ou agent compétent.</p>
            </td>
            <td class="recto-fields">
                <p>Délivré à M.(1) <span class="champ-valeur">{{ trim(($mission->beneficiaire->prenom ?? '').' '.($mission->beneficiaire->nom ?? '')) ?: '—' }}</span></p>
                <p>(2) {{ $mission->grade_emploi ?? '—' }}</p>
                <p>
                    Partant de {{ $mission->lieu_depart ?? 'Dakar' }} le
                    <span class="champ-valeur">{{ optional($mission->date_depart)->format('d/m/Y') ?? '—' }}</span>
                    à {{ $mission->heure_depart ?? '—' }} heure
                </p>
                <p>Pour se rendre à <span class="champ-valeur">{{ $mission->lieu_destination ?? '—' }}</span></p>
                <p>Suivant ordre de service N° {{ $mission->ordre_service_numero ?? '—' }}</p>
                <p>
                    en date du {{ optional($mission->ordre_service_date)->format('d/m/Y') ?? '—' }}
                    de {{ $mission->ordre_service_emetteur ?? '—' }}
                </p>
                <p>Accompagné de {{ $mission->accompagne_de ?? '—' }}</p>
                <p>
                    groupe (3) {{ $mission->groupe ?? '—' }}
                    &nbsp; &nbsp; indice {{ $mission->statut_agent === 'fonctionnaire' ? ($mission->indice_agent ?? '—') : 'Non applicable' }}
                </p>
                <p>Itinéraire à suivre, avance à faire, le cas échéant {{ $mission->itineraire ?? '—' }}</p>
                <p>Nature du déplacement (temporaire ou définitif) {{ $mission->motif ?? '—' }}</p>
                <p>Poids de bagages dont le transport est autorisé {{ $mission->poids_bagages_kg ?? '—' }} kgs.</p>
                <p>Délivré par nous (4) {{ $mission->delivre_par ?? '—' }}</p>
                <p>{{ $mission->lieu_depart ?? 'Dakar' }} le {{ optional($mission->date_emission_fiche)->format('d/m/Y') ?? '—' }}</p>
            </td>
        </tr>
    </table>

    {{-- ================================================================
         RECTO — DECOMPTE DES AVANCES AU DEPART (toujours affiché, comme
         le modèle papier vierge — pas conditionné à avance_total).
    ================================================================ --}}

    <h2>Décompte des avances au départ</h2>

    <table>
        <thead>
            <tr>
                <th></th>
                <th>Nombre</th>
                <th>Taux</th>
                <th>Décompte</th>
                <th>Indication des réquisitions délivrées au départ</th>
                <th>Poids des bagages et du mobilier constaté (nourriture et logement assurés ?)</th>
            </tr>
        </thead>
        <tbody>
            @foreach ([
                ['Frais de voyage et de transport (5)', $mission->avance_frais_transport_nombre, $mission->avance_frais_transport_taux],
                ['Indemnité journalière normale', $mission->avance_indemnite_normale_nombre, $mission->avance_indemnite_normale_taux],
                ['réduite', $mission->avance_indemnite_reduite_nombre, $mission->avance_indemnite_reduite_taux],
                ['partielle', $mission->avance_indemnite_partielle_nombre, $mission->avance_indemnite_partielle_taux],
            ] as $ligne)
                <tr>
                    <td>{{ $ligne[0] }}</td>
                    <td>{{ $ligne[1] ?? '—' }}</td>
                    <td>{{ $ligne[2] ?? '—' }}</td>
                    <td>{{ number_format(($ligne[1] ?? 0) * ($ligne[2] ?? 0), 0, ',', ' ') }}</td>
                    @if ($loop->first)
                        <td rowspan="4">{{ $mission->indication_requisitions ?? '—' }}</td>
                        <td rowspan="4">{{ $mission->poids_bagages_mobilier ?? '—' }}</td>
                    @endif
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3"><strong>TOTAL</strong></td>
                <td><strong>{{ $mission->avance_total !== null ? number_format($mission->avance_total, 0, ',', ' ') : '—' }}</strong></td>
                <td colspan="2"></td>
            </tr>
        </tfoot>
    </table>

    <table class="info-grid">
        <tr>
            <td class="info-label">ARRETE à la somme de</td>
            <td colspan="3">{{ $mission->arrete_somme ?? '—' }}</td>
        </tr>
        <tr>
            <td class="info-label">Payé à titre d'avance</td>
            <td>{{ $mission->avance_versee !== null ? number_format($mission->avance_versee, 0, ',', ' ') : '—' }}</td>
            <td class="info-label">Fait à {{ $mission->lieu_depart ?? 'Dakar' }} le</td>
            <td>{{ optional($mission->date_fait_avance)->format('d/m/Y') ?? '—' }}</td>
        </tr>
    </table>

    {{-- ================================================================
         VERSO — "DETAIL DES VISAS ET PAIEMENT SUCCESSIFS EN COURS DE
         ROUTE", sur une nouvelle page, comme le recto/verso du papier.
         Toujours affiché en entier (structure du tableau papier), même si
         aucune donnée n'a été saisie.
    ================================================================ --}}

    <div class="page-break"></div>

    <h2>Détail des visas et paiement successifs en cours de route</h2>

    @php
        $visasRoute = $mission->visas_route ?? [];
    @endphp

    <table class="visa-table">
        <thead>
            <tr>
                <th colspan="2">Visa</th>
                <th rowspan="2">Réquisitions délivrées</th>
                <th rowspan="2">Poids des bagages</th>
                <th rowspan="2">Logement et nourriture</th>
                <th rowspan="2" class="visa-cell-avance">Avance ou compte perçus en route</th>
                <th rowspan="2" class="visa-cell-observations">Observations</th>
            </tr>
            <tr>
                <th>À l'arrivée</th>
                <th>Au départ</th>
            </tr>
        </thead>
        <tbody>
            @for ($i = 0; $i < 4; $i++)
                @php $visa = $visasRoute[$i] ?? []; @endphp
                <tr>
                    <td>
                        {{ $visa['arrivee_lieu'] ?? '—' }}<br>
                        le {{ ! empty($visa['arrivee_date']) ? \Illuminate\Support\Carbon::parse($visa['arrivee_date'])->format('d/m/Y') : '—' }}
                        à {{ $visa['arrivee_heure'] ?? '—' }} heure
                    </td>
                    <td>
                        {{ $visa['depart_lieu'] ?? '—' }}<br>
                        le {{ ! empty($visa['depart_date']) ? \Illuminate\Support\Carbon::parse($visa['depart_date'])->format('d/m/Y') : '—' }}
                        à {{ $visa['depart_heure'] ?? '—' }} heure
                    </td>
                    <td>{{ $visa['requisitions'] ?? '—' }}</td>
                    <td>{{ $visa['poids_bagages'] ?? '—' }}</td>
                    <td>{{ $visa['logement_nourriture'] ?? '—' }}</td>
                    @if ($i === 0)
                        <td rowspan="4" class="visa-cell-avance">

                            <p class="visa-avance-titre">Avance ou compte perçus en route</p>

                            <table class="visa-mini-table">
                                <thead>
                                    <tr>
                                        <th></th>
                                        <th>Nombre</th>
                                        <th>Taux</th>
                                        <th>Décompte</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ([
                                        ['Indemnité journa. normale', $mission->visa_avance_indemnite_normale_nombre, $mission->visa_avance_indemnite_normale_taux],
                                        ['réduite', $mission->visa_avance_indemnite_reduite_nombre, $mission->visa_avance_indemnite_reduite_taux],
                                        ['partielle', $mission->visa_avance_indemnite_partielle_nombre, $mission->visa_avance_indemnite_partielle_taux],
                                    ] as $ligne)
                                        <tr>
                                            <td>{{ $ligne[0] }}</td>
                                            <td>{{ $ligne[1] ?? '—' }}</td>
                                            <td>{{ $ligne[2] ?? '—' }}</td>
                                            <td>{{ number_format(($ligne[1] ?? 0) * ($ligne[2] ?? 0), 0, ',', ' ') }}</td>
                                        </tr>
                                    @endforeach
                                    <tr>
                                        <td colspan="3"><strong>TOTAL</strong></td>
                                        <td><strong>{{ $mission->visa_avance_total !== null ? number_format($mission->visa_avance_total, 0, ',', ' ') : '—' }}</strong></td>
                                    </tr>
                                </tbody>
                            </table>

                            <p class="visa-avance-ligne">PAYER la somme de {{ $mission->visa_avance_payer_somme ?? '—' }}</p>
                            <p class="visa-avance-ligne">
                                A {{ $mission->visa_avance_lieu ?? '—' }}
                                le {{ optional($mission->visa_avance_date)->format('d/m/Y') ?? '—' }}
                            </p>

                            <p class="visa-avance-titre reglement">Règlement définitif</p>

                            <table class="visa-mini-table">
                                <thead>
                                    <tr>
                                        <th></th>
                                        <th>Nombre</th>
                                        <th>Taux</th>
                                        <th>Décompte</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ([
                                        ['Indemnité journa. normale', $mission->reglement_indemnite_normale_nombre, $mission->reglement_indemnite_normale_taux],
                                        ['réduite', $mission->reglement_indemnite_reduite_nombre, $mission->reglement_indemnite_reduite_taux],
                                        ['partielle', $mission->reglement_indemnite_partielle_nombre, $mission->reglement_indemnite_partielle_taux],
                                    ] as $ligne)
                                        <tr>
                                            <td>{{ $ligne[0] }}</td>
                                            <td>{{ $ligne[1] ?? '—' }}</td>
                                            <td>{{ $ligne[2] ?? '—' }}</td>
                                            <td>{{ number_format(($ligne[1] ?? 0) * ($ligne[2] ?? 0), 0, ',', ' ') }}</td>
                                        </tr>
                                    @endforeach
                                    <tr>
                                        <td colspan="3"><strong>TOTAL</strong></td>
                                        <td><strong>{{ $mission->reglement_total !== null ? number_format($mission->reglement_total, 0, ',', ' ') : '—' }}</strong></td>
                                    </tr>
                                </tbody>
                            </table>

                            <p class="visa-avance-ligne">Montant des avances ou acompte {{ $mission->reglement_montant_avances !== null ? number_format($mission->reglement_montant_avances, 0, ',', ' ') : '—' }}</p>
                            <p class="visa-avance-ligne">RESTE à payer {{ $mission->reglement_reste_a_payer !== null ? number_format($mission->reglement_reste_a_payer, 0, ',', ' ') : '—' }}</p>
                            <p class="visa-avance-ligne">ARRETE le décompte final à la somme de {{ $mission->reglement_arrete_somme ?? '—' }}</p>
                            <p class="visa-avance-ligne">
                                A {{ $mission->reglement_lieu ?? '—' }}
                                le {{ optional($mission->reglement_date)->format('d/m/Y') ?? '—' }}
                            </p>

                        </td>
                        <td rowspan="4" class="visa-cell-observations">{{ $mission->observations ?? '—' }}</td>
                    @endif
                </tr>
            @endfor
        </tbody>
    </table>



  

</body>
</html>
