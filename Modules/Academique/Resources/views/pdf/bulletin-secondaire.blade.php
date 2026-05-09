<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Bulletin - {{ $data['apprenant']['prenoms'] }} {{ $data['apprenant']['nom'] }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1a1a1a; }
        .page { padding: 15px 25px; }

        /* EN-TÊTE */
        .header { width: 100%; border-bottom: 3px solid #0B5697; padding-bottom: 8px; margin-bottom: 10px; }
        .header table { width: 100%; }
        .logo-cell { width: 80px; vertical-align: middle; }
        .logo-placeholder { width: 65px; height: 65px; border: 2px solid #0B5697; border-radius: 8px; text-align: center; line-height: 65px; font-size: 10px; color: #0B5697; font-weight: bold; }
        .header-center { text-align: center; vertical-align: middle; }
        .ecole-nom { font-size: 16px; font-weight: 800; color: #0B5697; }
        .ecole-sub { font-size: 9px; color: #666; margin-top: 2px; }
        .photo-cell { width: 70px; vertical-align: middle; text-align: right; }
        .photo-placeholder { width: 60px; height: 75px; border: 1px solid #ccc; text-align: center; line-height: 75px; font-size: 8px; color: #aaa; }

        /* TITRE */
        .title-bar { text-align: center; margin: 8px 0; padding: 6px; background: linear-gradient(90deg, #0B5697, #0FBCAF); color: white; font-size: 13px; font-weight: 800; letter-spacing: 2px; border-radius: 4px; }

        /* INFO ÉLÈVE */
        .student-info { margin: 8px 0; }
        .student-info table { width: 100%; border: 1px solid #0B5697; border-collapse: collapse; }
        .student-info td { padding: 4px 8px; border: 1px solid #d0d0d0; font-size: 9px; }
        .student-info .lbl { font-weight: 700; color: #0B5697; width: 22%; background: #f0f7ff; }

        /* TABLEAU NOTES */
        .notes-table { width: 100%; border-collapse: collapse; margin: 8px 0; font-size: 9px; }
        .notes-table th { background: #0B5697; color: white; padding: 5px 6px; text-align: center; font-size: 8px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; border: 1px solid #094578; }
        .notes-table td { padding: 4px 6px; border: 1px solid #d0d0d0; text-align: center; }
        .notes-table .mat-name { text-align: left; font-weight: 600; }
        .notes-table .note-val { color: #E5590C; font-weight: 700; }
        .notes-table .total-row { background: #f0f7ff; font-weight: 800; }
        .notes-table tr:nth-child(even) { background: #fafcfe; }

        /* RÉSUMÉ */
        .summary { margin: 10px 0; }
        .summary table { width: 100%; }
        .moy-box { width: 180px; border: 2px solid #E5590C; border-radius: 8px; text-align: center; padding: 8px; }
        .moy-label { font-size: 8px; color: #E5590C; font-weight: 700; text-transform: uppercase; }
        .moy-value { font-size: 22px; font-weight: 900; color: #E5590C; }
        .rang-box { width: 100px; border: 2px solid #0FBCAF; border-radius: 8px; text-align: center; padding: 8px; margin-left: 10px; }
        .rang-label { font-size: 8px; color: #0FBCAF; font-weight: 700; text-transform: uppercase; }
        .rang-value { font-size: 22px; font-weight: 900; color: #0FBCAF; }
        .stats-table { border-collapse: collapse; font-size: 9px; }
        .stats-table td { padding: 3px 10px; border: 1px solid #d0d0d0; }
        .stats-table .stat-lbl { font-weight: 700; color: #0B5697; }

        /* DÉCISION */
        .decision-box { margin: 10px 0; border: 2px solid #0FBCAF; border-radius: 6px; padding: 10px; }
        .decision-title { font-weight: 800; color: #0B5697; font-size: 10px; margin-bottom: 4px; }
        .decision-value { font-size: 14px; font-weight: 900; }
        .appreciation { font-style: italic; color: #555; margin-top: 4px; font-size: 9px; }

        /* ABSENCES */
        .absences { margin: 8px 0; font-size: 9px; }
        .absences table { border-collapse: collapse; }
        .absences td { padding: 3px 8px; border: 1px solid #d0d0d0; }

        /* SIGNATURES */
        .signatures { margin-top: 25px; width: 100%; font-size: 9px; text-align: center; }
        .signatures td { width: 33%; padding: 10px; vertical-align: top; }
        .sig-role { font-weight: 700; color: #0B5697; border-top: 1px solid #333; padding-top: 5px; margin-top: 30px; }

        /* DISTINCTIONS */
        .distinctions { margin: 8px 0; font-size: 9px; }
        .distinctions table { width: 100%; border-collapse: collapse; }
        .distinctions td { padding: 3px 8px; border: 1px solid #d0d0d0; }
        .dist-label { font-weight: 700; color: #0B5697; background: #f0f7ff; width: 40%; }
    </style>
</head>
<body>
<div class="page">
    <!-- EN-TÊTE ÉCOLE -->
    <div class="header">
        <table>
            <tr>
                <td class="logo-cell"><div class="logo-placeholder">LOGO</div></td>
                <td class="header-center">
                    <div class="ecole-nom">{{ $data['ecole']['nom'] ?? 'AGREE SIKUL' }}</div>
                    <div class="ecole-sub">Tél : {{ $data['ecole']['telephone'] ?? '' }} | Email : {{ $data['ecole']['email'] ?? '' }}</div>
                </td>
                <td class="photo-cell"><div class="photo-placeholder">Photo</div></td>
            </tr>
        </table>
    </div>

    <!-- TITRE BULLETIN -->
    <div class="title-bar">BULLETIN DE NOTES — {{ strtoupper($data['periode_libelle'] ?? $data['periode'] ?? '') }}</div>

    <!-- INFORMATIONS ÉLÈVE -->
    <div class="student-info">
        <table>
            <tr>
                <td class="lbl">Nom et Prénoms</td>
                <td colspan="3">{{ $data['apprenant']['prenoms'] }} {{ $data['apprenant']['nom'] }}</td>
            </tr>
            <tr>
                <td class="lbl">Matricule</td>
                <td>{{ $data['apprenant']['matricule'] ?? '—' }}</td>
                <td class="lbl">Classe</td>
                <td>{{ $data['classe']['nom'] ?? '—' }}</td>
            </tr>
            <tr>
                <td class="lbl">Date de Naissance</td>
                <td>{{ isset($data['apprenant']['date_naissance']) ? \Carbon\Carbon::parse($data['apprenant']['date_naissance'])->format('d/m/Y') : '—' }}</td>
                <td class="lbl">Année Scolaire</td>
                <td>{{ $data['annee_scolaire'] ?? '—' }}</td>
            </tr>
            <tr>
                <td class="lbl">Sexe</td>
                <td>{{ $data['apprenant']['sexe'] ?? '—' }}</td>
                <td class="lbl">Effectif Classe</td>
                <td>{{ $data['effectif_classe'] ?? '—' }} élèves</td>
            </tr>
        </table>
    </div>

    <!-- TABLEAU DES NOTES -->
    <table class="notes-table">
        <thead>
            <tr>
                <th style="text-align:left; width:28%;">Disciplines</th>
                <th style="width:10%;">Moy./20</th>
                <th style="width:8%;">Coef.</th>
                <th style="width:12%;">Moy. × Coef.</th>
                <th style="width:8%;">Rang</th>
                <th style="width:34%;">Appréciation</th>
            </tr>
        </thead>
        <tbody>
            @php $totalCoef = 0; $totalMoyCoef = 0; @endphp
            @forelse($data['moyennes_matieres'] as $m)
                @php
                    $coef = $m['coefficient'] ?? 1;
                    $note = $m['note'] ?? $m['moyenne'] ?? 0;
                    $moyCoef = round($note * $coef, 2);
                    $totalCoef += $coef;
                    $totalMoyCoef += $moyCoef;
                @endphp
                <tr>
                    <td class="mat-name">{{ $m['matiere'] }}</td>
                    <td class="note-val">{{ number_format($note, 2) }}</td>
                    <td>{{ $coef }}</td>
                    <td><strong>{{ number_format($moyCoef, 2) }}</strong></td>
                    <td>{{ $m['rang'] ?? '—' }}</td>
                    <td style="text-align:left; font-size:8px;">{{ $m['appreciation'] ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="6" style="text-align:center; color:#999;">Aucune note enregistrée</td></tr>
            @endforelse
            <tr class="total-row">
                <td class="mat-name">TOTAL</td>
                <td></td>
                <td>{{ $totalCoef }}</td>
                <td><strong>{{ number_format($totalMoyCoef, 2) }}</strong></td>
                <td></td>
                <td></td>
            </tr>
        </tbody>
    </table>

    <!-- RÉSUMÉ : MOYENNE + RANG + STATS -->
    <div class="summary">
        <table>
            <tr>
                <td style="vertical-align:top;">
                    <div class="moy-box">
                        <div class="moy-label">Moyenne Générale</div>
                        <div class="moy-value">{{ number_format($data['moyenne_generale'] ?? 0, 2) }}/20</div>
                    </div>
                </td>
                <td style="vertical-align:top;">
                    <div class="rang-box">
                        <div class="rang-label">Rang</div>
                        <div class="rang-value">{{ $data['rang'] ?? '—' }}</div>
                        <div style="font-size:8px; color:#0FBCAF;">/{{ $data['effectif_classe'] ?? '—' }}</div>
                    </div>
                </td>
                <td style="vertical-align:top; padding-left:15px;">
                    <table class="stats-table">
                        <tr><td class="stat-lbl">Moyenne de la classe</td><td>{{ number_format($data['moyenne_classe'] ?? $data['moyenne_generale'] ?? 0, 1) }}/20</td></tr>
                        <tr><td class="stat-lbl">Premier de la classe</td><td>{{ number_format($data['max_classe'] ?? $data['moyenne_generale'] ?? 0, 2) }}/20</td></tr>
                        <tr><td class="stat-lbl">Dernier de la classe</td><td>{{ number_format($data['min_classe'] ?? $data['moyenne_generale'] ?? 0, 2) }}/20</td></tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>

    <!-- DISTINCTIONS & SANCTIONS -->
    <div class="distinctions">
        <table>
            <tr>
                <td class="dist-label">Distinctions</td>
                <td>
                    @if(($data['moyenne_generale'] ?? 0) >= 16) Félicitations
                    @elseif(($data['moyenne_generale'] ?? 0) >= 14) Tableau d'honneur
                    @elseif(($data['moyenne_generale'] ?? 0) >= 12) Encouragements
                    @else —
                    @endif
                </td>
            </tr>
            <tr>
                <td class="dist-label">Sanctions</td>
                <td>
                    @if(($data['moyenne_generale'] ?? 0) < 8) Avertissement travail
                    @else —
                    @endif
                </td>
            </tr>
        </table>
    </div>

    <!-- HEURES D'ABSENCE -->
    <div class="absences">
        <table>
            <tr>
                <td style="font-weight:700; color:#0B5697; background:#f0f7ff;">Heures d'absence {{ $data['periode_libelle'] ?? '' }}</td>
                <td>Justifiées : {{ $data['absences_justifiees'] ?? 0 }}h</td>
                <td>Non justifiées : {{ $data['absences_non_justifiees'] ?? 0 }}h</td>
            </tr>
        </table>
    </div>

    <!-- DÉCISION DU CONSEIL -->
    <div class="decision-box">
        <div class="decision-title">Décision du Conseil de Classe :</div>
        <div class="decision-value">{{ ucfirst(str_replace('_', ' ', $data['decision_conseil'] ?? 'en attente')) }}</div>
        <div class="appreciation">Appréciation générale : {{ $data['appreciation_generale'] ?? '—' }}</div>
    </div>

    <!-- SIGNATURES -->
    <table class="signatures">
        <tr>
            <td><div class="sig-role">Le Professeur Principal</div></td>
            <td><div class="sig-role">Le Parent / Tuteur</div></td>
            <td><div class="sig-role">Le Directeur</div></td>
        </tr>
    </table>

    <div style="text-align:center; margin-top:15px; font-size:7px; color:#999;">
        Bulletin généré par AGREE SIKUL — {{ \Carbon\Carbon::now()->format('d/m/Y H:i') }}
    </div>
</div>
</body>
</html>
