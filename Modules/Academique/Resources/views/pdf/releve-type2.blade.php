<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Relevé Session Normale + Rattrapage</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 8.5px; color: #1a1a1a; }
        .page { padding: 10px 15px; }
        .watermark { position: fixed; top: 38%; left: 12%; font-size: 55px; color: rgba(11,86,151,0.03); transform: rotate(-30deg); font-weight: 900; z-index: -1; }

        .header-row { width: 100%; margin-bottom: 6px; }
        .header-row table { width: 100%; }
        .header-row td { font-size: 8px; }
        .rep { font-weight: 700; }
        .min { text-align: right; font-weight: 700; }

        .info-block { margin-bottom: 6px; }
        .info-block table { width: 100%; }
        .info-block td { font-size: 8.5px; padding: 2px 0; }
        .info-block .lbl { font-weight: 700; color: #0B5697; }

        .title-bar { text-align: center; margin: 5px 0; padding: 4px; background: linear-gradient(90deg, #0B5697, #0FBCAF); color: white; font-size: 11px; font-weight: 800; letter-spacing: 2px; }

        .notes-tbl { width: 100%; border-collapse: collapse; margin-bottom: 5px; }
        .notes-tbl th { background: #0B5697; color: white; padding: 3px 2px; font-size: 6.5px; font-weight: 700; text-align: center; border: 1px solid #094578; }
        .notes-tbl td { padding: 2px; border: 1px solid #ddd; font-size: 7.5px; text-align: center; }
        .notes-tbl .left { text-align: left; padding-left: 4px; }
        .notes-tbl tr:nth-child(even) td { background: #f8fbff; }
        .total-row td { background: #eef3f9 !important; font-weight: 800; border-top: 2px solid #0B5697; }
        .sem-row td { background: #e8f0f8 !important; font-weight: 800; color: #0B5697; font-size: 8px; text-align: left; padding-left: 6px; }
        .ue-row td { background: #f0f5fa !important; font-weight: 700; font-size: 7.5px; }

        .note-ok { color: #28a745; font-weight: 700; }
        .note-ko { color: #dc3545; font-weight: 700; }

        .session-normal { background: #e8f5e9 !important; }
        .session-rattrapage { background: #fff3e0 !important; }

        .resume { margin-top: 5px; width: 100%; }
        .box { border: 2px solid #0B5697; border-radius: 5px; padding: 5px; text-align: center; }
        .box .big { font-size: 18px; font-weight: 900; display: block; }
        .box .sm { font-size: 6.5px; color: #666; text-transform: uppercase; }

        .sigs { margin-top: 10px; width: 100%; }
        .sigs td { width: 33%; text-align: center; font-size: 7.5px; vertical-align: top; }
        .sig-t { font-weight: 700; color: #0B5697; margin-bottom: 20px; }
        .footer { margin-top: 6px; text-align: center; font-size: 6.5px; color: #aaa; border-top: 1px solid #eee; padding-top: 3px; }
    </style>
</head>
<body>
<div class="watermark">AGREE SIKUL</div>
<div class="page">
    <div class="header-row">
        <table><tr>
            <td class="rep">REPUBLIQUE DU {{ strtoupper($pays ?? 'CAMEROUN') }}</td>
            <td class="min">MINISTERE DE L'ENSEIGNEMENT SUPERIEUR</td>
        </tr></table>
    </div>

    <div class="info-block">
        <table>
            <tr><td><span class="lbl">Établissement :</span> {{ $ecole ?? '-' }}</td></tr>
            <tr><td><span class="lbl">Faculté / Institut :</span> {{ $faculte ?? '-' }}</td><td><span class="lbl">Département :</span> {{ $departement ?? '-' }}</td></tr>
        </table>
    </div>

    <div class="title-bar">RELEVÉ DE NOTES</div>

    <div class="info-block">
        <table>
            <tr>
                <td><span class="lbl">Nom :</span> {{ $apprenant?->nom }}</td>
                <td><span class="lbl">Prénom :</span> {{ $apprenant?->prenoms }}</td>
            </tr>
            <tr>
                <td><span class="lbl">Année Universitaire :</span> {{ $annee ?? '-' }}</td>
                <td><span class="lbl">Filière :</span> {{ $filiere ?? '-' }}</td>
            </tr>
            <tr>
                <td><span class="lbl">Domaine :</span> {{ $domaine ?? '-' }}</td>
                <td><span class="lbl">Spécialité :</span> {{ $specialite ?? '-' }}</td>
            </tr>
            <tr>
                <td><span class="lbl">Mention :</span> {{ $mention ?? '-' }}</td>
                <td><span class="lbl">Diplôme préparé :</span> {{ $diplome ?? 'Licence' }}</td>
            </tr>
        </table>
    </div>

    <table class="notes-tbl">
        <thead>
            <tr>
                <th rowspan="2" style="width:6%;">Nature</th>
                <th rowspan="2" style="width:18%; text-align:left; padding-left:4px;">Code et Intitulé UE</th>
                <th rowspan="2" style="width:5%;">Créd.</th>
                <th rowspan="2" style="width:5%;">Coef.</th>
                <th rowspan="2" style="width:16%; text-align:left; padding-left:4px;">Matière(s)</th>
                <th rowspan="2" style="width:4%;">Cr.</th>
                <th rowspan="2" style="width:4%;">Co.</th>
                <th colspan="3" class="session-normal">Session Normale</th>
                <th colspan="3" class="session-rattrapage">Session Rattrapage</th>
            </tr>
            <tr>
                <th class="session-normal">Mat.</th>
                <th class="session-normal">UE</th>
                <th class="session-normal">Cr.</th>
                <th class="session-rattrapage">Mat.</th>
                <th class="session-rattrapage">UE</th>
                <th class="session-rattrapage">Cr.</th>
            </tr>
        </thead>
        <tbody>
            @foreach($semestres as $sem)
            <tr class="sem-row"><td colspan="13">{{ $sem['label'] }}</td></tr>
            @foreach($sem['ues'] as $ue)
            <tr class="ue-row">
                <td>{{ $ue['nature'] }}</td>
                <td class="left">{{ $ue['code'] }} - {{ $ue['intitule'] }}</td>
                <td>{{ $ue['credits'] }}</td>
                <td>{{ $ue['coef'] }}</td>
                <td class="left" colspan="2"></td>
                <td></td>
                <td></td>
                <td><span class="{{ ($ue['note_ue_normal'] ?? 0) >= 10 ? 'note-ok' : 'note-ko' }}">{{ $ue['note_ue_normal'] !== null ? number_format($ue['note_ue_normal'], 2) : '' }}</span></td>
                <td>{{ $ue['credits_normal'] ?? '' }}</td>
                <td></td>
                <td><span class="{{ ($ue['note_ue_rattrapage'] ?? 0) >= 10 ? 'note-ok' : 'note-ko' }}">{{ $ue['note_ue_rattrapage'] !== null ? number_format($ue['note_ue_rattrapage'], 2) : '' }}</span></td>
                <td>{{ $ue['credits_rattrapage'] ?? '' }}</td>
            </tr>
            @foreach($ue['matieres'] as $mat)
            <tr>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td class="left">{{ $mat['intitule'] }}</td>
                <td>{{ $mat['credits'] }}</td>
                <td>{{ $mat['coef'] }}</td>
                <td><span class="{{ ($mat['note_normal'] ?? 0) >= 10 ? 'note-ok' : 'note-ko' }}">{{ $mat['note_normal'] !== null ? number_format($mat['note_normal'], 2) : '-' }}</span></td>
                <td></td>
                <td></td>
                <td><span class="{{ ($mat['note_rattrapage'] ?? 0) >= 10 ? 'note-ok' : 'note-ko' }}">{{ $mat['note_rattrapage'] !== null ? number_format($mat['note_rattrapage'], 2) : '-' }}</span></td>
                <td></td>
                <td></td>
            </tr>
            @endforeach
            @endforeach
            <tr class="total-row">
                <td colspan="2" class="left" style="padding-left:6px;">Total {{ $sem['label'] }}</td>
                <td>{{ $sem['total_credits'] }}</td>
                <td colspan="4"></td>
                <td colspan="2">Moy: <strong>{{ number_format($sem['moyenne_normal'], 2) }}</strong></td>
                <td></td>
                <td colspan="2">Moy: <strong>{{ number_format($sem['moyenne_rattrapage'] ?? 0, 2) }}</strong></td>
                <td></td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <table class="sigs"><tr>
        <td><div class="sig-t">Le Chef de Département</div><div style="height:20px;"></div>Signature</td>
        <td><div class="sig-t">Le Doyen</div><div style="height:20px;"></div>Signature</td>
        <td><div class="sig-t">Le Recteur</div><div style="height:20px;"></div>Fait le {{ $date }}</td>
    </tr></table>
    <div class="footer">AGREE SIKUL — Relevé de Notes Session Normale + Rattrapage — {{ $date }}</div>
</div>
</body>
</html>
