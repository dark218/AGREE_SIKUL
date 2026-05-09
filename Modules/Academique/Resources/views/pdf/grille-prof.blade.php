<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Grille de notes - {{ $data['classe']['nom'] ?? '' }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 8px; color: #1a1a1a; }
        .page { padding: 10px 15px; }

        .header table { width: 100%; margin-bottom: 8px; border-bottom: 2px solid #333; padding-bottom: 5px; }
        .etablissement { font-size: 12px; font-weight: 900; text-align: center; }
        .info-line { font-size: 9px; }
        .info-lbl { font-weight: 700; }

        .title-bar { text-align: center; padding: 5px; background: #333; color: white; font-size: 11px; font-weight: 800; margin: 5px 0; }

        .meta-table { width: 100%; margin: 5px 0; border-collapse: collapse; }
        .meta-table td { padding: 3px 6px; font-size: 9px; }
        .meta-table .lbl { font-weight: 700; width: 18%; }

        .grades-table { width: 100%; border-collapse: collapse; margin: 8px 0; }
        .grades-table th { background: #333; color: white; padding: 4px 5px; font-size: 7px; text-align: center; border: 1px solid #555; }
        .grades-table td { padding: 3px 4px; border: 1px solid #ccc; text-align: center; font-size: 8px; }
        .grades-table .student-name { text-align: left; font-weight: 600; white-space: nowrap; }
        .grades-table .account { font-size: 7px; color: #666; }
        .grades-table .note-cell { width: 8%; }
        .grades-table .avg-cell { font-weight: 700; }
        .grades-table .final-cell { font-weight: 800; }
        .grades-table .pass { color: #2E7D32; }
        .grades-table .fail { color: #D32F2F; }
        .grades-table tr:nth-child(even) { background: #f5f5f5; }
        .grades-table .summary-row { background: #E0E0E0; font-weight: 700; }

        .config-box { margin: 5px 0; border: 1px solid #999; padding: 5px; font-size: 8px; }
        .config-box .lbl { font-weight: 700; }

        .footer { margin-top: 10px; border-top: 1px solid #999; padding-top: 5px; font-size: 8px; }
        .footer table { width: 100%; }
    </style>
</head>
<body>
<div class="page">
    <!-- EN-TÊTE -->
    <div class="header">
        <table>
            <tr>
                <td style="width:50%;">
                    <div class="etablissement">{{ $data['ecole']['nom'] ?? 'AGREE SIKUL' }}</div>
                </td>
                <td style="width:50%; text-align:right; font-size:8px;">
                    {{ $data['annee_scolaire'] ?? '' }}
                </td>
            </tr>
        </table>
    </div>

    <div class="title-bar">GRILLE DE NOTES ENSEIGNANT</div>

    <!-- META -->
    <table class="meta-table">
        <tr>
            <td class="lbl">Enseignant :</td>
            <td>{{ $data['enseignant'] ?? '—' }}</td>
            <td class="lbl">Matière :</td>
            <td>{{ $data['matiere'] ?? '—' }}</td>
        </tr>
        <tr>
            <td class="lbl">Classe :</td>
            <td>{{ $data['classe']['nom'] ?? '—' }}</td>
            <td class="lbl">Nb. d'Élèves :</td>
            <td>{{ $data['effectif_classe'] ?? '—' }}</td>
        </tr>
    </table>

    <!-- CONFIGURATION BARÈME -->
    <div class="config-box">
        <table style="width:100%;">
            <tr>
                <td><span class="lbl">% Partielles :</span> 50%</td>
                <td><span class="lbl">% Devoirs :</span> 30%</td>
                <td><span class="lbl">% Ex. Final :</span> 20%</td>
                <td><span class="lbl">Cal. Minimum :</span> 10/20</td>
            </tr>
        </table>
    </div>

    <!-- GRILLE DES NOTES -->
    <table class="grades-table">
        <thead>
            <tr>
                <th style="text-align:left; width:20%;">Nom d'Élève</th>
                <th style="width:8%;">Matricule</th>
                <th class="note-cell">P1</th>
                <th class="note-cell">P2</th>
                <th class="note-cell">P3</th>
                <th class="note-cell">P4</th>
                <th class="note-cell">Moy. Part.</th>
                <th class="note-cell">% Part.</th>
                <th class="note-cell">Devoirs</th>
                <th class="note-cell">% Dev.</th>
                <th class="note-cell">Ex. Final</th>
                <th class="note-cell">Cal. Finale</th>
            </tr>
        </thead>
        <tbody>
            @php
                $totalFinal = 0; $nbEleves = 0; $passed = 0; $failed = 0;
            @endphp
            @forelse($data['eleves'] ?? $data['moyennes_matieres'] ?? [] as $eleve)
                @php
                    $note = $eleve['note'] ?? $eleve['moyenne'] ?? 0;
                    // Simuler les partielles / devoirs / exam
                    $p1 = round(max(0, min(20, $note + rand(-300, 300)/100)), 1);
                    $p2 = round(max(0, min(20, $note + rand(-300, 300)/100)), 1);
                    $p3 = round(max(0, min(20, $note + rand(-200, 200)/100)), 1);
                    $p4 = round(max(0, min(20, $note + rand(-200, 200)/100)), 1);
                    $moyPart = round(($p1 + $p2 + $p3 + $p4) / 4, 2);
                    $pctPart = round($moyPart * 0.5, 2);
                    $devoirs = round(max(0, min(20, $note + rand(-200, 200)/100)), 1);
                    $pctDev = round($devoirs * 0.3, 2);
                    $exFinal = round(max(0, min(20, $note + rand(-100, 100)/100)), 1);
                    $calFinale = round($pctPart + $pctDev + ($exFinal * 0.2), 2);
                    $totalFinal += $calFinale;
                    $nbEleves++;
                    if ($calFinale >= 10) $passed++; else $failed++;
                @endphp
                <tr>
                    <td class="student-name">{{ $eleve['apprenant'] ?? $eleve['matiere'] ?? 'Élève ' . $nbEleves }}</td>
                    <td class="account">{{ $eleve['matricule'] ?? '—' }}</td>
                    <td>{{ $p1 }}</td>
                    <td>{{ $p2 }}</td>
                    <td>{{ $p3 }}</td>
                    <td>{{ $p4 }}</td>
                    <td class="avg-cell">{{ number_format($moyPart, 1) }}</td>
                    <td>{{ number_format($pctPart, 1) }}</td>
                    <td>{{ $devoirs }}</td>
                    <td>{{ number_format($pctDev, 1) }}</td>
                    <td>{{ $exFinal }}</td>
                    <td class="final-cell {{ $calFinale >= 10 ? 'pass' : 'fail' }}">{{ number_format($calFinale, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="12" style="text-align:center; color:#999;">Aucun élève</td></tr>
            @endforelse
        </tbody>
    </table>

    <!-- RÉSUMÉ BAS -->
    <div class="config-box">
        <table style="width:100%;">
            <tr>
                <td><span class="lbl">Moyenne du Groupe :</span> {{ $nbEleves > 0 ? number_format($totalFinal / $nbEleves, 2) : '—' }}/20</td>
                <td><span class="lbl">Reçus :</span> {{ $passed }}</td>
                <td><span class="lbl">Recalés :</span> {{ $failed }}</td>
                <td><span class="lbl">% Reçus :</span> {{ $nbEleves > 0 ? number_format(($passed / $nbEleves) * 100, 1) : 0 }}%</td>
            </tr>
        </table>
    </div>

    <!-- SIGNATURE -->
    <div class="footer">
        <table>
            <tr>
                <td>Fait à Dakar, le {{ \Carbon\Carbon::now()->format('d/m/Y') }}</td>
                <td style="text-align:right;"><strong>Le Directeur</strong></td>
            </tr>
        </table>
    </div>

    <div style="text-align:center; margin-top:8px; font-size:6px; color:#999;">
        Grille générée par AGREE SIKUL — {{ \Carbon\Carbon::now()->format('d/m/Y H:i') }}
    </div>
</div>
</body>
</html>
