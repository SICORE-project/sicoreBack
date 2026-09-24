<!doctype html><html lang="fr"><head><meta charset="utf-8"><title>Bulletin de salaire</title>
<style>body{font-family:DejaVu Sans,sans-serif;font-size:12px;color:#243746}h1{color:#176637}table{width:100%;border-collapse:collapse;margin:24px 0}td,th{padding:10px;border-bottom:1px solid #ddd;text-align:left}.amount{text-align:right}</style></head><body>
<h1>SICORE — Bulletin de salaire</h1>
<p>{{ $teacher->prenom }} {{ $teacher->nom }} · Matricule : {{ $teacher->matricule }}</p>
<p>Période : {{ $payslip->period->label }} · Année : {{ $payslip->period->start_date->year }}</p>
<p>Référence : {{ $payslip->reference }} · Généré le {{ $payslip->created_at?->format('d/m/Y') }}</p>
<table><thead><tr><th>Rubrique</th><th>Type</th><th class="amount">Montant (FCFA)</th></tr></thead><tbody>
@foreach($payslip->lines as $line)<tr><td>{{ $line->label }}</td><td>{{ ['earning' => 'Gain', 'deduction' => 'Retenue', 'employer_contribution' => 'Cotisation employeur'][$line->category] ?? $line->category }}</td><td class="amount">{{ number_format((float) $line->amount, 0, ',', ' ') }}</td></tr>@endforeach
</tbody></table>
<p>Brut : {{ number_format((float) $payslip->gross_amount, 0, ',', ' ') }} FCFA</p>
<p>Retenues : {{ number_format((float) $payslip->deduction_amount, 0, ',', ' ') }} FCFA</p>
<h2>Net à payer : {{ number_format((float) $payslip->net_amount, 0, ',', ' ') }} FCFA</h2>
</body></html>
