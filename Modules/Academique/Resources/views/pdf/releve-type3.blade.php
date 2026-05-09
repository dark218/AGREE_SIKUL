<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Relevé Semestre - {{ $semestreLabel ?? '' }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #1a1a1a; }
        .page { padding: 12px 18px; }
        .watermark { position: fixed; top: 38%; left: 12%; font-size: 55px; color: rgba(11,86,151,0.03); transform: rotate(-30deg); font-weight: 900; z-index: -1; }

        .header-row table { width: 100%; margin-bottom: 6px; }
        .header-row .rep { font-weight: 700; font-size: 8px; }
        .header-row .min { text-align: right; font-weight: 700; font-size: 8px; }
        .info-line { font-size: 8.5px; padding: 2px 0; }
        .info-line .lbl { font-weight: 700; color: #0B5697; }

        .title-bar { text-align: center; margin: 5px 0; padding: 5px; background: linear-gradient(90deg, #0B5697, #0FBCAF); color: white; font-size: 11px; font-weight: 800; letter-spacing: 2px; }
        .sem-label { text-align: center; font-size: 10px; font-weight: 700; color: #0B5697; margin: 4px 0; border-bottom: 2px solid #0FBCAF; padding-bottom: 3px; }

        .tbl { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
        .tbl th { background: #0B5697; color: white; padding: 4px 3px; font-size: 7px; font-weight: 700; text-align: center; border: 1px solid #094578; }
        .tbl td { padding: 3px; border: 1px solid #ddd; font-size: 8px; text-align: center; }
        .tbl .left { text-align: left; padding-left: 6px; }
        .tbl tr:nth-child(even) td { background: #f8fbff; }
        .total-row td { background: #eef3f9 !important; font-weight: 800; border-top: 2px solid #0B5697; }
        .ue-row td { background: #f0f5fa !important; font-weight: 700; }

        .note-ok { color: #28a745; font-weight: 700; }
        .note-ko { color: #dc3545; font-weight: 700; }

        .box { border: 2px solid #0B5697; border-radius: 5px; padding: 6px; text-align: center; display: inline-block; }
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
            <td style="font-size:8.5px;"><span class="lbl">Nom :</span> {{ $apprenant?->nom }} | <span class="lbl">Prénom :</span> {{ $apprenant?->prenoms }}</td>
            <td style="font-size:8.5px;"><span class="lbl">Filière :</span> {{ $filiere ?? '-' }}</td>
        </tr>
        <tr>
            <td style="font-size:8.5px;"><span class="lbl">Domaine :</span> {{ $domaine ?? '-' }} | <span class="lbl">Spécialité :</span> {{ $specialite ?? '-' }}</td>
            <td style="font-size:8.5px;"><span class="lbl">Diplôme :</span> {{ $diplome ?? 'Licence' }}</td>
        </tr>
    </table>

    <div class="sem-label">{{ $semestreLabel ?? 'Semestre' }}</div>

    <table class="tbl">
        <thead>
            <tr>
                <th style="width:7%;">Nature</th>
                <th style="width:22%; text-align:left; padding-left:5px;">Code et Intitulé UE</th>
                <th style="width:5%;">Cr.</th>
                <th style="width:5%;">Co.</th>
                <th style="width:20%; text-align:left; padding-left:5px;">Matière(s)</th>
                <th style="width:5%;">Cr.</th>
                <th style="width:5%;">Co.</th>
                <th style="width:7%;">Note</th>
                <th style="width:6%;">Crédits</th>
                <th style="width:8%;">Sess/An</th>
                <th style="width:5%;">UE</th>
                <th style="width:5%;">Cr.UE</th>
            </tr>
        </thead>
        <tbody>
            @foreach($ues as $ue)
            <tr class="ue-row">
                <td>{{ $ue['nature'] }}</td>
                <td class="left">{{ $ue['code'] }} - {{ $ue['intitule'] }}</td>
                <td>{{ $ue['credits'] }}</td>
                <td>{{ $ue['coef'] }}</td>
                <td colspan="4"></td>
                <td></td>
                <td></td>
                <td><span class="{{ ($ue['note_ue'] ?? 0) >= 10 ? 'note-ok' : 'note-ko' }}">{{ $ue['note_ue'] !== null ? number_format($ue['note_ue'], 2) : '' }}</span></td>
                <td>{{ $ue['credits_obtenus'] ?? '' }}</td>
            </tr>
            @foreach($ue['matieres'] as $mat)
            <tr>
                <td></td><td></td><td></td><td></td>
                <td class="left">{{ $mat['intitule'] }}</td>
                <td>{{ $mat['credits'] }}</td>
                <td>{{ $mat['coef'] }}</td>
                <td><span class="{{ ($mat['note'] ?? 0) >= 10 ? 'note-ok' : 'note-ko' }}">{{ $mat['note'] !== null ? number_format($mat['note'], 2) : '-' }}</span></td>
                <td>{{ $mat['credits_obtenus'] ?? '-' }}</td>
                <td>{{ $mat['session'] ?? '-' }}</td>
                <td></td><td></td>
            </tr>
            @endforeach
            @endforeach
            <tr class="total-row">
                <td colspan="2" class="left" style="padding-left:6px;">TOTAL SEMESTRE</td>
                <td>{{ $totalCredits }}</td>
                <td colspan="4"></td>
                <td></td>
                <td>{{ $creditsObtenus }}</td>
                <td></td>
                <td colspan="2">Moy: <strong>{{ number_format($moyenne ?? 0, 2) }}/20</strong></td>
            </tr>
        </tbody>
    </table>

    <table style="width:100%;"><tr>
        <td style="width:30%;">
            @php $pass = ($moyenne ?? 0) >= 10; @endphp
            <div class="box" style="border-color:{{ $pass ? '#28a745' : '#dc3545' }}; width:100%;">
                <span class="sm">Moyenne Semestre</span>
                <span class="big" style="color:{{ $pass ? '#28a745' : '#dc3545' }};">{{ number_format($moyenne ?? 0, 2) }}/20</span>
            </div>
        </td>
        <td style="width:25%; padding-left:8px;">
            <div class="box" style="width:100%;">
                <span class="sm">Crédits Validés</span>
                <span class="big" style="color:#0B5697;">{{ $creditsObtenus }}/{{ $totalCredits }}</span>
            </div>
        </td>
        <td style="width:45%; padding-left:8px;">
            <div class="box" style="width:100%;">
                <span class="sm">Décision</span>
                <span class="big" style="color:#0B5697; font-size:12px;">{{ $decision ?? '-' }}</span>
            </div>
        </td>
    </tr></table>

    <table class="sigs"><tr>
        <td><div class="sig-t">Le Chef de Département</div><div style="height:22px;"></div>Signature</td>
        <td><div class="sig-t">Le Doyen</div><div style="height:22px;"></div>Signature</td>
        <td><div class="sig-t">Le Recteur</div><div style="height:22px;"></div>Fait le {{ $date }}</td>
    </tr></table>
    <div class="footer">AGREE SIKUL — Relevé de Notes par Semestre — {{ $date }}</div>
</div>
</body>
</html>
