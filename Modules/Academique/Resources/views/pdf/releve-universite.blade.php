<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Relevé - {{ $data['apprenant']['prenoms'] }} {{ $data['apprenant']['nom'] }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #1a1a1a; }
        .page { padding: 15px 20px; }

        .header { width: 100%; margin-bottom: 10px; }
        .header table { width: 100%; }
        .header .logo-cell { width: 80px; vertical-align: top; }
        .header .logo-placeholder { width: 65px; height: 65px; border: 2px solid #1A237E; text-align: center; line-height: 65px; font-size: 9px; color: #1A237E; font-weight: bold; }
        .header .center-cell { text-align: center; vertical-align: top; }
        .header .ref { font-size: 8px; color: #666; text-align: right; }
        .etablissement { font-size: 14px; font-weight: 900; color: #1A237E; letter-spacing: 1px; }
        .sous-titre { font-size: 9px; color: #555; }

        .title-bar { text-align: center; margin: 10px 0; padding: 8px; background: #1A237E; color: white; font-size: 12px; font-weight: 800; letter-spacing: 2px; }
        .title-sub { text-align: center; font-size: 10px; color: #555; font-style: italic; margin-bottom: 10px; }

        /* Info étudiant */
        .student-info table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .student-info td { padding: 4px 8px; font-size: 9px; }
        .student-info .lbl { font-weight: 700; color: #1A237E; width: 20%; }
        .student-info .val { border-bottom: 1px dotted #999; }

        /* Tableau UE */
        .ue-table { width: 100%; border-collapse: collapse; margin: 8px 0; }
        .ue-table th { background: #1A237E; color: white; padding: 5px 6px; font-size: 8px; text-align: center; border: 1px solid #0D1565; font-weight: 700; }
        .ue-table td { padding: 4px 6px; border: 1px solid #ccc; font-size: 9px; text-align: center; }
        .ue-table .ue-header { background: #E8EAF6; font-weight: 800; color: #1A237E; text-align: left; }
        .ue-table .ec-row td { font-size: 8.5px; }
        .ue-table .ec-row .ec-name { text-align: left; padding-left: 20px; }
        .ue-table .note-val { color: #E65100; font-weight: 700; }
        .ue-table .mention { font-size: 8px; }
        .ue-table .credits { font-weight: 700; color: #1A237E; }
        .ue-table .decision-va { color: #2E7D32; font-weight: 800; }
        .ue-table .decision-nv { color: #D32F2F; font-weight: 800; }

        /* Résumé bas */
        .bottom-box { margin: 10px 0; border: 2px solid #1A237E; border-radius: 4px; padding: 10px; }
        .bottom-box table { width: 100%; }
        .bottom-box .lbl { font-weight: 700; color: #1A237E; }

        .signatures { margin-top: 20px; width: 100%; font-size: 9px; }
        .signatures td { width: 50%; vertical-align: top; }
        .sig-role { font-weight: 700; color: #1A237E; margin-top: 25px; }
        .sig-place { font-size: 8px; color: #666; font-style: italic; }
    </style>
</head>
<body>
<div class="page">
    <!-- EN-TÊTE -->
    <div class="header">
        <table>
            <tr>
                <td class="logo-cell"><div class="logo-placeholder">LOGO</div></td>
                <td class="center-cell">
                    <div class="etablissement">{{ $data['ecole']['nom'] ?? 'AGREE SIKUL' }}</div>
                    <div class="sous-titre">Tél : {{ $data['ecole']['telephone'] ?? '' }} | Email : {{ $data['ecole']['email'] ?? '' }}</div>
                </td>
                <td style="width:120px; text-align:right;">
                    <div class="ref">ID N° {{ $data['apprenant']['matricule'] ?? '—' }}</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="title-bar">RELEVÉ DE NOTES ANNUEL</div>
    <div class="title-sub">/ ANNUAL MARKS TRANSCRIPT</div>

    <!-- INFO ÉTUDIANT -->
    <div class="student-info">
        <table>
            <tr>
                <td class="lbl">Nom(s) et prénom(s) :</td>
                <td class="val" colspan="3"><strong>{{ $data['apprenant']['prenoms'] }} {{ $data['apprenant']['nom'] }}</strong></td>
            </tr>
            <tr>
                <td class="lbl">Matricule :</td>
                <td class="val">{{ $data['apprenant']['matricule'] ?? '—' }}</td>
                <td class="lbl">Parcours :</td>
                <td class="val">{{ $data['classe']['nom'] ?? '—' }}</td>
            </tr>
            <tr>
                <td class="lbl">Cycle :</td>
                <td class="val">{{ $data['cycle'] ?? '—' }}</td>
                <td class="lbl">Niveau :</td>
                <td class="val">{{ $data['niveau'] ?? '—' }}</td>
            </tr>
            <tr>
                <td class="lbl">Année académique :</td>
                <td class="val" colspan="3">{{ $data['annee_scolaire'] ?? '—' }}</td>
            </tr>
        </table>
    </div>

    <!-- TABLEAU UE / EC -->
    <table class="ue-table">
        <thead>
            <tr>
                <th style="text-align:left; width:10%;">Code UE</th>
                <th style="text-align:left; width:10%;">Code EC</th>
                <th style="text-align:left; width:30%;">Intitulé UE/Cours</th>
                <th style="width:10%;">Crédits prévus</th>
                <th style="width:10%;">Note/20</th>
                <th style="width:10%;">Crédits acquis</th>
                <th style="width:10%;">Mention</th>
                <th style="width:10%;">Décision</th>
            </tr>
        </thead>
        <tbody>
            @php $totalCredits = 0; $totalAcquis = 0; $totalNoteCoef = 0; @endphp
            @forelse($data['moyennes_matieres'] as $i => $m)
                @php
                    $note = $m['note'] ?? $m['moyenne'] ?? 0;
                    $coef = $m['coefficient'] ?? 1;
                    $credits = $coef * 2;
                    $acquis = $note >= 10 ? $credits : 0;
                    $totalCredits += $credits;
                    $totalAcquis += $acquis;
                    $totalNoteCoef += $note * $coef;
                    $mention = $note >= 16 ? 'TRES BIEN' : ($note >= 14 ? 'BIEN' : ($note >= 12 ? 'ASSEZ BIEN' : ($note >= 10 ? 'PASSABLE' : 'MEDIOCRE')));
                    $decision = $note >= 10 ? 'VA' : 'NV';
                @endphp
                <tr class="ec-row">
                    <td>UE{{ str_pad($i + 1, 3, '0', STR_PAD_LEFT) }}</td>
                    <td>EC{{ str_pad($i + 1, 3, '0', STR_PAD_LEFT) }}</td>
                    <td class="ec-name">{{ $m['matiere'] }}</td>
                    <td class="credits">{{ $credits }}</td>
                    <td class="note-val">{{ number_format($note, 2) }}</td>
                    <td class="credits">{{ $acquis }}</td>
                    <td class="mention">{{ $mention }}</td>
                    <td class="{{ $decision === 'VA' ? 'decision-va' : 'decision-nv' }}">{{ $decision }}</td>
                </tr>
            @empty
                <tr><td colspan="8" style="text-align:center; color:#999;">Aucun résultat</td></tr>
            @endforelse
        </tbody>
    </table>

    <!-- RÉSUMÉ -->
    <div class="bottom-box">
        <table>
            <tr>
                <td class="lbl">Moyenne :</td>
                <td><strong>{{ number_format($data['moyenne_generale'] ?? 0, 2) }}/20</strong></td>
                <td class="lbl">Crédits acquis :</td>
                <td><strong>{{ $totalAcquis }}/{{ $totalCredits }}</strong></td>
            </tr>
            <tr>
                <td class="lbl">Décision :</td>
                <td colspan="3"><strong>{{ ($data['moyenne_generale'] ?? 0) >= 10 ? 'Admis(e)' : 'Ajourné(e)' }}</strong></td>
            </tr>
        </table>
    </div>

    <!-- SIGNATURES -->
    <table class="signatures">
        <tr>
            <td>
                <div class="sig-role">Le Chef de Département</div>
            </td>
            <td style="text-align:right;">
                <div class="sig-place">Fait à Dakar, le {{ \Carbon\Carbon::now()->format('d/m/Y') }}</div>
                <div class="sig-role">Le Directeur</div>
            </td>
        </tr>
    </table>

    <div style="text-align:center; margin-top:15px; font-size:7px; color:#999;">
        Relevé généré par AGREE SIKUL — {{ \Carbon\Carbon::now()->format('d/m/Y H:i') }}
    </div>
</div>
</body>
</html>
