<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Relevé CC + Galop + Examen</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #1a1a1a; }
        .page { padding: 12px 18px; }
        .watermark { position: fixed; top: 38%; left: 12%; font-size: 55px; color: rgba(11,86,151,0.03); transform: rotate(-30deg); font-weight: 900; z-index: -1; }

        .header-row table { width: 100%; margin-bottom: 6px; }
        .rep { font-weight: 700; font-size: 8px; }
        .min { text-align: right; font-weight: 700; font-size: 8px; }
        .info-line { font-size: 8.5px; padding: 2px 0; }
        .lbl { font-weight: 700; color: #0B5697; }

        .title-bar { text-align: center; margin: 5px 0; padding: 5px; background: linear-gradient(90deg, #0B5697, #0FBCAF); color: white; font-size: 11px; font-weight: 800; letter-spacing: 2px; }

        .tbl { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
        .tbl th { background: #0B5697; color: white; padding: 4px 3px; font-size: 7px; font-weight: 700; text-align: center; border: 1px solid #094578; }
        .tbl td { padding: 3px; border: 1px solid #ddd; font-size: 8px; text-align: center; }
        .tbl .left { text-align: left; padding-left: 6px; }
        .tbl tr:nth-child(even) td { background: #f8fbff; }
        .total-row td { background: #eef3f9 !important; font-weight: 800; border-top: 2px solid #0B5697; }
        .ue-row td { background: #f0f5fa !important; font-weight: 700; }

        .note-ok { color: #28a745; font-weight: 700; }
        .note-ko { color: #dc3545; font-weight: 700; }

        .cc-col { background: #e3f2fd !important; }
        .galop-col { background: #fff8e1 !important; }
        .exam-col { background: #e8f5e9 !important; }

        .box { border: 2px solid #0B5697; border-radius: 5px; padding: 6px; text-align: center; }
        .box .big { font-size: 20px; font-weight: 900; display: block; }
        .box .sm { font-size: 7px; color: #666; text-transform: uppercase; }

        .sigs { margin-top: 12px; width: 100%; }
        .sigs td { width: 33%; text-align: center; font-size: 8px; vertical-align: top; }
        .sig-t { font-weight: 700; color: #0B5697; margin-bottom: 25px; }
        .footer { margin-top: 8px; text-align: center; font-size: 7px; color: #aaa; border-top: 1px solid #eee; padding-top: 4px; }
    </style>
</head>
<body>
<div class="watermark">AGREE SIKUL</div>
<div class="page">
    <div class="header-row"><table><tr>
        <td class="rep">REPUBLIQUE DU {{ strtoupper($pays ?? 'CAMEROUN') }}</td>
        <td class="min">MINISTERE DE L'ENSEIGNEMENT SUPERIEUR</td>
    </tr></table></div>

    <div class="info-line"><span class="lbl">Établissement :</span> {{ $ecole ?? '-' }}</div>
    <div class="info-line"><span class="lbl">Faculté :</span> {{ $faculte ?? '-' }} | <span class="lbl">Département :</span> {{ $departement ?? '-' }}</div>

    <div class="title-bar">RELEVÉ DE NOTES</div>

    <table style="width:100%; margin-bottom:5px;">
        <tr>
            <td style="font-size:8.5px;"><span class="lbl">Nom :</span> {{ $apprenant?->nom }}</td>
            <td style="font-size:8.5px;"><span class="lbl">Prénom :</span> {{ $apprenant?->prenoms }}</td>
        </tr>
        <tr>
            <td style="font-size:8.5px;"><span class="lbl">Année :</span> {{ $annee ?? '-' }}</td>
            <td style="font-size:8.5px;"><span class="lbl">Diplôme :</span> {{ $diplome ?? 'Licence' }}</td>
        </tr>
    </table>

    <table class="tbl">
        <thead>
            <tr>
                <th rowspan="2" style="width:7%;">Nature</th>
                <th rowspan="2" style="width:18%; text-align:left; padding-left:5px;">Code et Intitulé UE</th>
                <th rowspan="2" style="width:16%; text-align:left; padding-left:5px;">Matière</th>
                <th colspan="2" class="cc-col">Contrôle Continu</th>
                <th colspan="2" class="galop-col">Galop d'Essai</th>
                <th colspan="2" class="exam-col">Examen</th>
                <th rowspan="2" style="width:8%;">Note Finale</th>
                <th rowspan="2" style="width:5%;">Coef.</th>
                <th rowspan="2" style="width:8%;">Note×Coef</th>
            </tr>
            <tr>
                <th class="cc-col" style="width:5%;">Note</th>
                <th class="cc-col" style="width:5%;">Poids</th>
                <th class="galop-col" style="width:5%;">Note</th>
                <th class="galop-col" style="width:5%;">Poids</th>
                <th class="exam-col" style="width:5%;">Note</th>
                <th class="exam-col" style="width:5%;">Poids</th>
            </tr>
        </thead>
        <tbody>
            @foreach($ues as $ue)
            <tr class="ue-row">
                <td>{{ $ue['nature'] }}</td>
                <td class="left">{{ $ue['code'] }} - {{ $ue['intitule'] }}</td>
                <td colspan="10"></td>
            </tr>
            @foreach($ue['matieres'] as $mat)
            <tr>
                <td></td>
                <td></td>
                <td class="left">{{ $mat['intitule'] }}</td>
                <td class="cc-col">{{ $mat['cc_note'] !== null ? number_format($mat['cc_note'], 1) : '-' }}</td>
                <td class="cc-col">{{ $mat['cc_poids'] ?? '-' }}%</td>
                <td class="galop-col">{{ $mat['galop_note'] !== null ? number_format($mat['galop_note'], 1) : '-' }}</td>
                <td class="galop-col">{{ $mat['galop_poids'] ?? '-' }}%</td>
                <td class="exam-col">{{ $mat['exam_note'] !== null ? number_format($mat['exam_note'], 1) : '-' }}</td>
                <td class="exam-col">{{ $mat['exam_poids'] ?? '-' }}%</td>
                <td><span class="{{ ($mat['note_finale'] ?? 0) >= 10 ? 'note-ok' : 'note-ko' }}"><strong>{{ $mat['note_finale'] !== null ? number_format($mat['note_finale'], 2) : '-' }}</strong></span></td>
                <td>{{ $mat['coefficient'] }}</td>
                <td><strong>{{ $mat['note_coef'] !== null ? number_format($mat['note_coef'], 2) : '-' }}</strong></td>
            </tr>
            @endforeach
            @endforeach
            <tr class="total-row">
                <td colspan="3" class="left" style="padding-left:6px;">TOTAL</td>
                <td colspan="6"></td>
                <td></td>
                <td><strong>{{ $totalCoef }}</strong></td>
                <td><strong>{{ number_format($totalNoteCoef, 2) }}</strong></td>
            </tr>
        </tbody>
    </table>

    <table style="width:100%;"><tr>
        <td style="width:30%;">
            @php $pass = ($moyenne ?? 0) >= 10; @endphp
            <div class="box" style="border-color:{{ $pass ? '#28a745' : '#dc3545' }}; width:100%;">
                <span class="sm">Moyenne Générale</span>
                <span class="big" style="color:{{ $pass ? '#28a745' : '#dc3545' }};">{{ number_format($moyenne ?? 0, 2) }}/20</span>
            </div>
        </td>
        <td style="width:25%; padding-left:8px;">
            <div class="box" style="width:100%;">
                <span class="sm">Crédits Validés</span>
                <span class="big" style="color:#0B5697;">{{ $creditsObtenus ?? 0 }}/{{ $totalCredits ?? 0 }}</span>
            </div>
        </td>
        <td style="width:45%; padding-left:8px;">
            <div class="box" style="width:100%;">
                <span class="sm">Mention</span>
                <span class="big" style="color:#0B5697; font-size:14px;">{{ $mention ?? '-' }}</span>
            </div>
        </td>
    </tr></table>

    <table class="sigs"><tr>
        <td><div class="sig-t">Le Chef de Département</div><div style="height:22px;"></div>Signature</td>
        <td><div class="sig-t">Le Doyen</div><div style="height:22px;"></div>Signature</td>
        <td><div class="sig-t">Le Recteur</div><div style="height:22px;"></div>Fait le {{ $date }}</td>
    </tr></table>
    <div class="footer">AGREE SIKUL — Relevé CC + Galop + Examen — {{ $date }}</div>
</div>
</body>
</html>
