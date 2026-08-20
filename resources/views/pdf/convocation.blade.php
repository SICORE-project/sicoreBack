<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Convocation #{{ $convocation->id }}</title>
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

        h3 {
            font-size: 12.5px;
            margin: 10px 0 6px;
            color: #334155;
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
            width: 150px;
        }

        .empty-message {
            color: #52606d;
            font-style: italic;
        }
    </style>
</head>
<body>

    <h1>Convocation — {{ $convocation->objet ?? '—' }}</h1>

    <p class="meta">
        Émise le {{ optional($convocation->date_emission)->format('d/m/Y') ?? '—' }}
        @if ($convocation->typeConvocation)
            &middot; {{ $convocation->typeConvocation->libelle }}
        @endif
    </p>

    <table class="info-grid">
        <tr>
            <td class="info-label">Session</td>
            <td>{{ $convocation->session ?? '—' }}</td>
            <td class="info-label">Statut</td>
            <td>{{ ucfirst($convocation->statut ?? '—') }}</td>
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
            <td class="info-label">Heure</td>
            <td>{{ $convocation->heure_debut ?? '—' }}</td>
        </tr>
        <tr>
            <td class="info-label">Lieu d'examen</td>
            <td>{{ $convocation->lieu_examen ?? '—' }}</td>
            <td class="info-label">Lieu d'affectation</td>
            <td>{{ $convocation->lieu_affectation ?? '—' }}</td>
        </tr>
    </table>

    <h2>Centres d'examen</h2>

    @if ($convocation->centres->isEmpty())

        <p class="empty-message">Aucun centre renseigné pour cette convocation.</p>

    @else

        @php
            $statutsPersonnel = [
                'fonctionnaire' => 'Fonctionnaire',
                'contractuel' => 'Contractuelle',
                'vacataire' => 'Vacataire',
            ];
        @endphp

        @foreach ($convocation->centres as $centre)

            <table class="info-grid">
                <tr>
                    <td class="info-label">Centre</td>
                    <td>{{ $centre->centre ?? '—' }}</td>
                    <td class="info-label">Jury</td>
                    <td>{{ $centre->jury ?? '—' }}</td>
                </tr>
                <tr>
                    <td class="info-label">Président du jury</td>
                    <td>{{ trim(($centre->presidentJury->prenom ?? '').' '.($centre->presidentJury->nom ?? '')) ?: '—' }}</td>
                    <td class="info-label">Téléphone</td>
                    <td>{{ $centre->president_jury_telephone ?? '—' }}</td>
                </tr>
                <tr>
                    <td class="info-label">Chef de centre</td>
                    <td>{{ trim(($centre->chefCentre->prenom ?? '').' '.($centre->chefCentre->nom ?? '')) ?: '—' }}</td>
                    <td class="info-label">Téléphone</td>
                    <td>{{ $centre->chef_centre_telephone ?? '—' }}</td>
                </tr>
            </table>

            @if ($centre->metiers->isEmpty())

                <p class="empty-message">Aucun métier renseigné pour ce centre.</p>

            @else

                @foreach ($centre->metiers as $metier)

                    <h3>{{ $metier->metier ?? '—' }}</h3>

                    @if ($metier->enseignants->isEmpty())

                        <p class="empty-message">Aucun membre ajouté pour ce métier.</p>

                    @else

                        <table>
                            <thead>
                                <tr>
                                    <th>Nom</th>
                                    <th>Prénoms</th>
                                    <th>Fonction</th>
                                    <th>Statut</th>
                                    <th>Provenance</th>
                                    <th>Téléphone</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($metier->enseignants as $enseignant)
                                    <tr>
                                        <td>{{ $enseignant->nom ?? '—' }}</td>
                                        <td>{{ $enseignant->prenom ?? '—' }}</td>
                                        <td>{{ $enseignant->pivot->fonction ?? '—' }}</td>
                                        <td>{{ $statutsPersonnel[$enseignant->categorie_personnel ?? null] ?? '—' }}</td>
                                        <td>{{ $enseignant->pivot->provenance ?? '—' }}</td>
                                        <td>{{ $enseignant->telephone ?? '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                    @endif

                @endforeach

            @endif

        @endforeach

    @endif

</body>
</html>
