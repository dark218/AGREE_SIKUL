<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Bulletin - {{ $data['apprenant']['prenoms'] }} {{ $data['apprenant']['nom'] }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1a1a1a; }
        .page { padding: 15px 25px; }

        .header { width: 100%; border-bottom: 3px solid #2E7D32; padding-bottom: 8px; margin-bottom: 10px; }
        .header table { width: 100%; }
        .logo-cell { width: 80px; vertical-align: middle; }
        .logo-placeholder { width: 65px; height: 65px; border: 2px solid #2E7D32; border-radius: 50%; text-align: center; line-height: 65px; font-size: 10px; color: #2E7D32; font-weight: bold; }
        .header-center { text-align: center; vertical-align: middle; }
        .ecole-nom { font-size: 16px; font-weight: 800; color: #2E7D32; }
        .ecole-sub { font-size: 9px; color: #666; }
        .photo-cell { width: 70px; vertical-align: middle; text-align: right; }
        .photo-placeholder { width: 55px; height: 70px; border: 1px solid #ccc; border-radius: 5px; text-align: center; line-height: 70px; font-size: 8px; color: #aaa; }

        .title-bar { text-align: center; margin: 8px 0; padding: 8px; background: #2E7D32; color: white; font-size: 13px; font-weight: 800; letter-spacing: 1px; border-radius: 6px; }

        .student-info { margin: 8px 0; }
        .student-info table { width: 100%; border: 2px solid #2E7D32; border-collapse: collapse; border-radius: 6px; }
        .student-info td { padding: 5px 10px; border: 1px solid #c8e6c9; font-size: 10px; }
        .student-info .lbl { font-weight: 700; color: #2E7D32; background: #e8f5e9; width: 22%; }

        /* Tableau des compétences */
        .comp-table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        .comp-table th { background: #2E7D32; color: white; padding: 6px 8px; text-align: center; font-size: 9px; border: 1px solid #1B5E20; }
        .comp-table td { padding: 5px 8px; border: 1px solid #c8e6c9; text-align: center; font-size: 10px; }
        .comp-table .mat-name { text-align: left; font-weight: 600; color: #1B5E20; }
        .comp-table .seq { width: 10%; }
        .comp-table tr:nth-child(even) { background: #f1f8e9; }
        .comp-group { background: #e8f5e9; font-weight: 800; color: #1B5E20; font-size: 10px; }

        /* Notes colorées */
        .note-a { color: #2E7D32; font-weight: 800; } /* >= 16 Excellent */
        .note-b { color: #0B5697; font-weight: 700; } /* >= 14 Très bien */
        .note-c { color: #F57F17; font-weight: 600; } /* >= 10 Bien / Assez bien */
        .note-d { color: #D32F2F; font-weight: 700; } /* < 10 Insuffisant */

        /* Résumé */
        .summary { margin: 10px 0; }
        .moy-circle { width: 120px; height: 120px; border: 4px solid #2E7D32; border-radius: 50%; text-align: center; display: inline-block; }
        .moy-circle .val { font-size: 24px; font-weight: 900; color: #2E7D32; margin-top: 30px; }
        .moy-circle .lbl { font-size: 8px; color: #666; }

        .mention-box { display: inline-block; padding: 8px 20px; border-radius: 20px; font-weight: 800; font-size: 12px; color: white; margin: 5px; }
        .mention-exc { background: #2E7D32; }
        .mention-tb { background: #0B5697; }
        .mention-b { background: #0FBCAF; }
        .mention-ab { background: #F57F17; }
        .mention-pass { background: #FF9800; }
        .mention-insuf { background: #D32F2F; }

        .appreciation-box { margin: 10px 0; padding: 10px; border: 2px solid #2E7D32; border-radius: 8px; background: #f9fdf9; }
        .appreciation-title { font-weight: 800; color: #2E7D32; margin-bottom: 5px; }

        .signatures { margin-top: 20px; width: 100%; font-size: 9px; text-align: center; }
        .signatures td { width: 33%; padding: 10px; vertical-align: top; }
        .sig-role { font-weight: 700; color: #2E7D32; border-top: 1px solid #333; padding-top: 5px; margin-top: 30px; }
    </style>
</head>
<body>
<div class="page">
    <!-- EN-TÊTE -->
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

    <div class="title-bar">BULLETIN SCOLAIRE — {{ strtoupper($data['periode_libelle'] ?? $data['periode'] ?? '') }}</div>

    <!-- INFO ÉLÈVE -->
    <div class="student-info">
        <table>
            <tr>
                <td class="lbl">Nom et Prénoms</td>
                <td colspan="3"><strong>{{ $data['apprenant']['prenoms'] }} {{ $data['apprenant']['nom'] }}</strong></td>
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
        </table>
    </div>

    <!-- TABLEAU DES COMPÉTENCES -->
    <table class="comp-table">
        <thead>
            <tr>
                <th style="text-align:left; width:30%;">Matières</th>
                <th class="seq">Note /20</th>
                <th class="seq">Coef.</th>
                <th style="width:15%;">Total</th>
                <th style="width:25%;">Appréciation</th>
            </tr>
        </thead>
        <tbody>
            @php $totalCoef = 0; $totalPoints = 0; @endphp
            @forelse($data['moyennes_matieres'] as $m)
                @php
                    $note = $m['note'] ?? $m['moyenne'] ?? 0;
                    $coef = $m['coefficient'] ?? 1;
                    $total = round($note * $coef, 2);
                    $totalCoef += $coef;
                    $totalPoints += $total;
                    $noteClass = $note >= 16 ? 'note-a' : ($note >= 14 ? 'note-b' : ($note >= 10 ? 'note-c' : 'note-d'));
                    $mention = $note >= 16 ? 'Excellent' : ($note >= 14 ? 'Très bien' : ($note >= 12 ? 'Bien' : ($note >= 10 ? 'Assez bien' : ($note >= 8 ? 'Passable' : 'Insuffisant'))));
                @endphp
                <tr>
                    <td class="mat-name">{{ $m['matiere'] }}</td>
                    <td class="{{ $noteClass }}">{{ number_format($note, 1) }}</td>
                    <td>{{ $coef }}</td>
                    <td><strong>{{ number_format($total, 2) }}</strong></td>
                    <td style="font-size:9px;">{{ $mention }}</td>
                </tr>
            @empty
                <tr><td colspan="5" style="text-align:center; color:#999;">Aucune note</td></tr>
            @endforelse
            <tr class="comp-group">
                <td>TOTAL</td>
                <td></td>
                <td>{{ $totalCoef }}</td>
                <td>{{ number_format($totalPoints, 2) }}</td>
                <td></td>
            </tr>
        </tbody>
    </table>

    <!-- RÉSUMÉ -->
    <div class="summary">
        <table style="width:100%;">
            <tr>
                <td style="text-align:center; width:30%;">
                    <div style="font-size:8px; color:#2E7D32; font-weight:700; text-transform:uppercase;">Moyenne Générale</div>
                    <div style="font-size:28px; font-weight:900; color:#2E7D32;">{{ number_format($data['moyenne_generale'] ?? 0, 2) }}/20</div>
                </td>
                <td style="text-align:center; width:20%;">
                    <div style="font-size:8px; color:#0FBCAF; font-weight:700; text-transform:uppercase;">Rang</div>
                    <div style="font-size:24px; font-weight:900; color:#0FBCAF;">{{ $data['rang'] ?? '—' }}<span style="font-size:12px;">/{{ $data['effectif_classe'] ?? '—' }}</span></div>
                </td>
                <td style="text-align:center; width:50%;">
                    @php
                        $moy = $data['moyenne_generale'] ?? 0;
                        $mentionGen = $moy >= 16 ? ['Excellent', 'mention-exc'] : ($moy >= 14 ? ['Très bien', 'mention-tb'] : ($moy >= 12 ? ['Bien', 'mention-b'] : ($moy >= 10 ? ['Assez bien', 'mention-ab'] : ($moy >= 8 ? ['Passable', 'mention-pass'] : ['Insuffisant', 'mention-insuf']))));
                    @endphp
                    <div class="mention-box {{ $mentionGen[1] }}">{{ $mentionGen[0] }}</div>
                </td>
            </tr>
        </table>
    </div>

    <!-- APPRÉCIATION -->
    <div class="appreciation-box">
        <div class="appreciation-title">Appréciation générale du maître :</div>
        <div>{{ $data['appreciation_generale'] ?? '—' }}</div>
        <div style="margin-top:5px;">
            <strong>Décision :</strong> {{ ucfirst(str_replace('_', ' ', $data['decision_conseil'] ?? 'en attente')) }}
        </div>
    </div>

    <!-- SIGNATURES -->
    <table class="signatures">
        <tr>
            <td><div class="sig-role">Le Maître / La Maîtresse</div></td>
            <td><div class="sig-role">Le Parent / Tuteur</div></td>
            <td><div class="sig-role">Le Directeur</div></td>
        </tr>
    </table>

    <div style="text-align:center; margin-top:12px; font-size:7px; color:#999;">
        Bulletin généré par AGREE SIKUL — {{ \Carbon\Carbon::now()->format('d/m/Y H:i') }}
    </div>
</div>
</body>
</html>
