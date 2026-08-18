<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Fiche de déplacement {{ $mission->reference ?? '#'.$mission->id }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #1f2933;
        }

        h1 {
            font-size: 18px;
            margin: 0 0 4px;
        }

        h2 {
            font-size: 14px;
            margin: 18px 0 8px;
            padding-bottom: 4px;
            border-bottom: 1px solid #cbd5e1;
        }

        .meta {
            color: #52606d;
            margin: 0 0 16px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }

        th, td {
            border: 1px solid #cbd5e1;
            padding: 6px 8px;
            text-align: left;
            font-size: 11px;
        }

        th {
            background: #f1f5f9;
        }

        .info-grid td {
            border: none;
            padding: 4px 8px 4px 0;
        }

        .info-label {
            color: #52606d;
            font-weight: bold;
            width: 160px;
        }

        .empty-message {
            color: #52606d;
            font-style: italic;
        }

        .montant {
            font-size: 14px;
            font-weight: bold;
        }
    </style>
</head>
<body>

    <h1>Feuille de déplacement — {{ $mission->reference ?? '—' }}</h1>

    <p class="meta">
        {{ trim(($mission->beneficiaire->prenom ?? '').' '.($mission->beneficiaire->nom ?? '')) ?: '—' }}
        @if (! empty($mission->convocation->objet))
            &middot; {{ $mission->convocation->objet }}
        @endif
        &middot; Statut : {{ ucfirst($mission->statut ?? '—') }}
    </p>

    <h2>Bénéficiaire</h2>

    <table class="info-grid">
        <tr>
            <td class="info-label">Nom et prénoms</td>
            <td>{{ trim(($mission->beneficiaire->prenom ?? '').' '.($mission->beneficiaire->nom ?? '')) ?: '—' }}</td>
            <td class="info-label">Matricule</td>
            <td>{{ $mission->beneficiaire->matricule ?? '—' }}</td>
        </tr>
        <tr>
            <td class="info-label">Type de bénéficiaire</td>
            <td>{{ ucfirst($mission->statut_agent ?? '—') }}</td>
            <td class="info-label">Indice</td>
            <td>{{ $mission->statut_agent === 'fonctionnaire' ? ($mission->indice_agent ?? '—') : 'Non applicable' }}</td>
        </tr>
        <tr>
            <td class="info-label">Grade et emploi</td>
            <td colspan="3">{{ $mission->grade_emploi ?? '—' }}</td>
        </tr>
    </table>

    <h2>Trajet et ordre de mission</h2>

    <table class="info-grid">
        <tr>
            <td class="info-label">Lieu de départ</td>
            <td>{{ $mission->lieu_depart ?? '—' }}</td>
            <td class="info-label">Date de départ</td>
            <td>{{ optional($mission->date_depart)->format('d/m/Y') ?? '—' }}</td>
        </tr>
        <tr>
            <td class="info-label">Lieu de destination</td>
            <td>{{ $mission->lieu_destination ?? '—' }}</td>
            <td class="info-label">Date de retour</td>
            <td>{{ optional($mission->date_retour)->format('d/m/Y') ?? '—' }}</td>
        </tr>
        <tr>
            <td class="info-label">Nature du déplacement</td>
            <td>{{ $mission->motif ?? '—' }}</td>
            <td class="info-label">Moyen de transport</td>
            <td>{{ $mission->moyen_transport ?? '—' }}</td>
        </tr>
        <tr>
            <td class="info-label">Distance</td>
            <td>{{ $mission->distance_km ?? '—' }} km</td>
            <td class="info-label">Ordre de service N°</td>
            <td>
                {{ $mission->ordre_service_numero ?? '—' }}
                @if ($mission->ordre_service_date)
                    du {{ $mission->ordre_service_date->format('d/m/Y') }}
                @endif
            </td>
        </tr>
    </table>

    @if ($mission->avance_total)

        <h2>Décompte des avances au départ</h2>

        <table>
            <thead>
                <tr>
                    <th></th>
                    <th>Nombre</th>
                    <th>Taux</th>
                    <th>Décompte</th>
                    <th>Indication des réquisitions délivrées au départ</th>
                    <th>Poids des bagages et du mobilier constaté</th>
                </tr>
            </thead>
            <tbody>
                @foreach ([
                    ['Frais de voyage et de transport', $mission->avance_frais_transport_nombre, $mission->avance_frais_transport_taux],
                    ['Indemnité journalière normale', $mission->avance_indemnite_normale_nombre, $mission->avance_indemnite_normale_taux],
                    ['Indemnité journalière réduite', $mission->avance_indemnite_reduite_nombre, $mission->avance_indemnite_reduite_taux],
                    ['Indemnité journalière partielle', $mission->avance_indemnite_partielle_nombre, $mission->avance_indemnite_partielle_taux],
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
                    <td><strong>{{ number_format($mission->avance_total, 0, ',', ' ') }}</strong></td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
        </table>

    @endif

    {{--
        VERSO — "DETAIL DES VISAS ET PAIEMENT SUCCESSIFS EN COURS DE ROUTE"
        (demande utilisatrice : "tu peux faire le verso ?"), affiché
        seulement si au moins une donnée du verso a été saisie — même
        principe que le tableau RECTO ci-dessus (@if ($mission->avance_total)).
    --}}

    @php
        $visasRoute = $mission->visas_route ?? [];
        $visaRempli = collect($visasRoute)->contains(function ($visa) {
            return collect($visa ?? [])->filter(fn ($v) => $v !== null && $v !== '')->isNotEmpty();
        });
        $afficherVerso = $visaRempli
            || $mission->visa_avance_total
            || $mission->reglement_total
            || $mission->observations;
    @endphp

    @if ($afficherVerso)

        <h2>Détail des visas et paiement successifs en cours de route (verso)</h2>

        <table>
            <thead>
                <tr>
                    <th rowspan="2"></th>
                    <th colspan="3">À l'arrivée</th>
                    <th colspan="3">Au départ</th>
                    <th rowspan="2">Réquisitions délivrées en cours de route</th>
                    <th rowspan="2">Poids des bagages et du mobilier constaté</th>
                    <th rowspan="2">Logement et nourriture assurés</th>
                </tr>
                <tr>
                    <th>Lieu</th>
                    <th>Date</th>
                    <th>Heure</th>
                    <th>Lieu</th>
                    <th>Date</th>
                    <th>Heure</th>
                </tr>
            </thead>
            <tbody>
                @for ($i = 0; $i < 4; $i++)
                    @php
                        $visa = $visasRoute[$i] ?? [];
                    @endphp
                    <tr>
                        <td>Visa {{ $i + 1 }}</td>
                        <td>{{ $visa['arrivee_lieu'] ?? '—' }}</td>
                        <td>{{ ! empty($visa['arrivee_date']) ? \Illuminate\Support\Carbon::parse($visa['arrivee_date'])->format('d/m/Y') : '—' }}</td>
                        <td>{{ $visa['arrivee_heure'] ?? '—' }}</td>
                        <td>{{ $visa['depart_lieu'] ?? '—' }}</td>
                        <td>{{ ! empty($visa['depart_date']) ? \Illuminate\Support\Carbon::parse($visa['depart_date'])->format('d/m/Y') : '—' }}</td>
                        <td>{{ $visa['depart_heure'] ?? '—' }}</td>
                        <td>{{ $visa['requisitions'] ?? '—' }}</td>
                        <td>{{ $visa['poids_bagages'] ?? '—' }}</td>
                        <td>{{ $visa['logement_nourriture'] ?? '—' }}</td>
                    </tr>
                @endfor
            </tbody>
        </table>

        @if ($mission->visa_avance_total)

            <h2>Avance ou compte perçus en route</h2>

            <table>
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
                        ['Indemnité journalière normale', $mission->visa_avance_indemnite_normale_nombre, $mission->visa_avance_indemnite_normale_taux],
                        ['Indemnité journalière réduite', $mission->visa_avance_indemnite_reduite_nombre, $mission->visa_avance_indemnite_reduite_taux],
                        ['Indemnité journalière partielle', $mission->visa_avance_indemnite_partielle_nombre, $mission->visa_avance_indemnite_partielle_taux],
                    ] as $ligne)
                        <tr>
                            <td>{{ $ligne[0] }}</td>
                            <td>{{ $ligne[1] ?? '—' }}</td>
                            <td>{{ $ligne[2] ?? '—' }}</td>
                            <td>{{ number_format(($ligne[1] ?? 0) * ($ligne[2] ?? 0), 0, ',', ' ') }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3"><strong>TOTAL</strong></td>
                        <td><strong>{{ number_format($mission->visa_avance_total, 0, ',', ' ') }}</strong></td>
                    </tr>
                </tfoot>
            </table>

            <table class="info-grid">
                <tr>
                    <td class="info-label">Arrêté à payer la somme de</td>
                    <td colspan="3">{{ $mission->visa_avance_payer_somme ?? '—' }}</td>
                </tr>
                <tr>
                    <td class="info-label">Fait à</td>
                    <td>{{ $mission->visa_avance_lieu ?? '—' }}</td>
                    <td class="info-label">Le</td>
                    <td>{{ optional($mission->visa_avance_date)->format('d/m/Y') ?? '—' }}</td>
                </tr>
            </table>

        @endif

        @if ($mission->reglement_total)

            <h2>Règlement définitif</h2>

            <table>
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
                        ['Indemnité journalière normale', $mission->reglement_indemnite_normale_nombre, $mission->reglement_indemnite_normale_taux],
                        ['Indemnité journalière réduite', $mission->reglement_indemnite_reduite_nombre, $mission->reglement_indemnite_reduite_taux],
                        ['Indemnité journalière partielle', $mission->reglement_indemnite_partielle_nombre, $mission->reglement_indemnite_partielle_taux],
                    ] as $ligne)
                        <tr>
                            <td>{{ $ligne[0] }}</td>
                            <td>{{ $ligne[1] ?? '—' }}</td>
                            <td>{{ $ligne[2] ?? '—' }}</td>
                            <td>{{ number_format(($ligne[1] ?? 0) * ($ligne[2] ?? 0), 0, ',', ' ') }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3"><strong>TOTAL</strong></td>
                        <td><strong>{{ number_format($mission->reglement_total, 0, ',', ' ') }}</strong></td>
                    </tr>
                </tfoot>
            </table>

            <table class="info-grid">
                <tr>
                    <td class="info-label">Montant des avances déjà perçues</td>
                    <td>{{ $mission->reglement_montant_avances !== null ? number_format($mission->reglement_montant_avances, 0, ',', ' ') : '—' }}</td>
                    <td class="info-label">Reste à payer</td>
                    <td>{{ $mission->reglement_reste_a_payer !== null ? number_format($mission->reglement_reste_a_payer, 0, ',', ' ') : '—' }}</td>
                </tr>
                <tr>
                    <td class="info-label">Arrêté à la somme de</td>
                    <td colspan="3">{{ $mission->reglement_arrete_somme ?? '—' }}</td>
                </tr>
                <tr>
                    <td class="info-label">Fait à</td>
                    <td>{{ $mission->reglement_lieu ?? '—' }}</td>
                    <td class="info-label">Le</td>
                    <td>{{ optional($mission->reglement_date)->format('d/m/Y') ?? '—' }}</td>
                </tr>
            </table>

        @endif

        @if ($mission->observations)

            <h2>Observations</h2>

            <p>{{ $mission->observations }}</p>

        @endif

    @endif

    {{--
        PIECES JOINTES (RECTO/VERSO) — remis dans le PDF (demande
        utilisatrice "corrige aussi le pdf", après l'ajout de la gestion
        recto/verso sur le formulaire de modification) ; simple liste des
        noms de fichiers déposés, pas d'aperçu image (voir
        FraisDeplacementPdfController::RELATIONS_POUR_PDF).
    --}}

    @php
        $justificatifsParCommentaire = $mission->justificatifs->groupBy(fn ($j) => $j->commentaire ?? '');
        $rectoPdf = $justificatifsParCommentaire->get('Recto', collect())->first();
        $versoPdf = $justificatifsParCommentaire->get('Verso', collect())->first();
    @endphp

    <h2>Pièces jointes</h2>

    <table class="info-grid">
        <tr>
            <td class="info-label">Recto</td>
            <td>{{ $rectoPdf->nom_original ?? 'Non déposé' }}</td>
            <td class="info-label">Verso</td>
            <td>{{ $versoPdf->nom_original ?? 'Non déposé' }}</td>
        </tr>
    </table>

</body>
</html>
