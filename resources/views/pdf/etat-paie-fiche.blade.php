<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>État de paie — {{ $typeLibelle }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1f2933; }
        h1 { font-size: 16px; margin: 0 0 4px; }
        .meta { color: #52606d; margin: 0 0 14px; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        th, td { border: 1px solid #cbd5e1; padding: 5px 7px; text-align: left; font-size: 10px; vertical-align: top; }
        th { background: #f1f5f9; text-align: center; }
        .info td { border: none; padding: 3px 8px 3px 0; font-size: 11px; }
        .info .label { color: #52606d; font-weight: bold; width: 130px; }
        .montant { text-align: right; white-space: nowrap; }
        .total td { font-weight: bold; background: #f1f5f9; }
    </style>
</head>
<body>
    <h1>État de paiement — {{ $typeLibelle }}</h1>
    <p class="meta">
        @if (! empty($reference)) Référence {{ $reference }} · @endif
        @if (! empty($statut)) Statut : {{ ucfirst($statut) }} · @endif
        Édité le {{ now()->format('d/m/Y') }}
    </p>

    <table class="info">
        <tr><td class="label">Objet</td><td>{{ $objet ?: '—' }}</td></tr>
        <tr><td class="label">Session</td><td>{{ $session ?: '—' }}</td></tr>
        @if (! empty($periode))
            <tr><td class="label">Période</td><td>{{ $periode }}</td></tr>
        @endif
        <tr><td class="label">Centre</td><td>{{ $centre ?: 'Tous les centres' }}</td></tr>
    </table>

    <table>
        <thead>
            <tr>
                <th>N°</th>
                <th>Nom et prénom</th>
                <th>Fonction</th>
                <th>Catégorie</th>
                <th>Centre</th>
                <th>Coordonnées bancaires</th>
                <th>Montant (FCFA)</th>
            </tr>
        </thead>
        <tbody>
            @php $total = 0; @endphp
            @foreach ($details as $i => $ligne)
                @php
                    $montant = (float) ($ligne['montant'] ?? 0);
                    $total += $montant;
                    $rib = trim(implode(' ', array_filter([
                        $ligne['code_banque'] ?? null,
                        $ligne['code_guichet'] ?? null,
                        $ligne['numero_compte_bancaire'] ?? null,
                        $ligne['cle_rib'] ?? null,
                    ])));
                @endphp
                <tr>
                    <td style="text-align:center;">{{ $i + 1 }}</td>
                    <td>{{ trim(($ligne['nom'] ?? '') . ' ' . ($ligne['prenom'] ?? '')) }}</td>
                    <td>{{ $ligne['fonction'] ?? '—' }}</td>
                    <td>{{ $ligne['categorie_personnel'] ?? '—' }}</td>
                    <td>{{ $ligne['centre'] ?? '—' }}</td>
                    <td>{{ $rib !== '' ? $rib : '—' }}</td>
                    <td class="montant">{{ number_format($montant, 0, ',', ' ') }}</td>
                </tr>
            @endforeach
            <tr class="total">
                <td colspan="6" style="text-align:right;">Total</td>
                <td class="montant">{{ number_format($total, 0, ',', ' ') }}</td>
            </tr>
        </tbody>
    </table>
</body>
</html>
