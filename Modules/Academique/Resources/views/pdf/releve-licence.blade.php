<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Relevé - {{ $data['apprenant']['prenoms'] }} {{ $data['apprenant']['nom'] }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #1a1a1a; }
        .page { padding: 15px 20px; }

        .header table { width: 100%; margin-bottom: 8px; }
        .republic { text-align: center; font-size: 10px; font-weight: 700; color: #333; }
        .ministry { text-align: center; font-size: 9px; color: #555; }
        .etablissement { text-align: center; font-size: 13px; font-weight: 900; color: #4A148C; margin: 5px 0; }

        .title-bar { text-align: center; padding: 6px; background: #4A148C; color: white; font-size: 12px; font-weight: 800; letter-spacing: 1px; margin: 8px 0; }

        .student-info table { width: 100%; margin: 8px 0; }
        .student-info td { padding: 3px 6px; font-size: 9px; }
        .student-info .lbl { font-weight: 700; color: #4A148C; width: 20%; }
        .student-info .val { border-bottom: 1px dotted #999; }

        .semester-title { background: #EDE7F6; padding: 5px 10px; font-weight: 800; color: #4A148C; font-size: 10px; margin: 10px 0 5px; border-left: 4px solid #4A148C; }

        .ue-table { width: 100%; border-collapse: collapse; margin: 5px 0; }
        .ue-table th { background: #4A148C; color: white; padding: 4px 6px; font-size: 8px; border: 1px solid #311B92; }
        .ue-table td { padding: 3px 6px; border: 1px solid #d1c4e9; font-size: 8.5px; text-align: center; }
        .ue-table .ue-group { background: #EDE7F6; font-weight: 800; color: #4A148C; text-align: left; }
        .ue-table .mat-name { text-align: left; padding-left: 15px; }
        .ue-table .note-val { color: #E65100; font-weight: 700; }
        .ue-table .credits-ok { color: #2E7D32; font-weight: 700; }
        .ue-table .credits-ko { color: #D32F2F; font-weight: 700; }
        .ue-type-f { border-left: 3px solid #4A148C; }
        .ue-type-d { border-left: 3px solid #0FBCAF; }
        .ue-type-t { border-left: 3px solid #E5590C; }

        .bottom-summary { margin: 10px 0; border: 2px solid #4A148C; padding: 8px; border-radius: 4px; }
        .bottom-summary table { width: 100%; }
        .bottom-summary .lbl { font-weight: 700; color: #4A148C; }

        .signatures { margin-top: 20px; width: 100%; font-size: 9px; }
        .signatures td { width: 50%; vertical-align: top; }
        .sig-role { font-weight: 700; color: #4A148C; margin-top: 25px; }
    </style>
</head>
<body>
<div class="page">
    <!-- EN-TÊTE RÉPUBLIQUE -->
    <div class="header">
        <table>
            <tr>
                <td style="width:50%;">
                    <div class="republic">REPUBLIQUE DU SENEGAL</div>
                    <div class="ministry">MINISTERE DE L'ENSEIGNEMENT SUPERIEUR</div>
                </td>
                <td style="width:50%; text-align:right;">
                    <div style="font-size:8px; color:#666;">Année Universitaire : {{ $data['annee_scolaire'] ?? '—' }}</div>
                </td>
            </tr>
        </table>
        <div class="etablissement">{{ $data['ecole']['nom'] ?? 'AGREE SIKUL' }}</div>
        <div style="text-align:center; font-size:9px; color:#666;">Diplôme préparé : Licence</div>
    </div>

    <div class="title-bar">RELEVÉ DE NOTES</div>

    <!-- INFO ÉTUDIANT -->
    <div class="student-info">
        <table>
            <tr>
                <td class="lbl">Nom :</td>
                <td class="val"><strong>{{ $data['apprenant']['nom'] }}</strong></td>
                <td class="lbl">Prénom :</td>
                <td class="val"><strong>{{ $data['apprenant']['prenoms'] }}</strong></td>
            </tr>
            <tr>
                <td class="lbl">Date de naissance :</td>
                <td class="val">{{ isset($data['apprenant']['date_naissance']) ? \Carbon\Carbon::parse($data['apprenant']['date_naissance'])->format('d/m/Y') : '—' }}</td>
                <td class="lbl">Spécialité :</td>
                <td class="val">{{ $data['classe']['nom'] ?? '—' }}</td>
            </tr>
            <tr>
                <td class="lbl">Département :</td>
                <td class="val">{{ $data['departement'] ?? '—' }}</td>
                <td class="lbl">Mention :</td>
                <td class="val">
                    @php $moy = $data['moyenne_generale'] ?? 0; @endphp
                    {{ $moy >= 16 ? 'Très Bien' : ($moy >= 14 ? 'Bien' : ($moy >= 12 ? 'Assez Bien' : ($moy >= 10 ? 'Passable' : 'Insuffisant'))) }}
                </td>
            </tr>
        </table>
    </div>

    @php
        // Grouper les matières en UE (Fondamentale, Découverte, Transversale)
        $ueGroups = [
            'UEF' => ['label' => 'U.E. Fondamentales', 'class' => 'ue-type-f', 'items' => []],
            'UED' => ['label' => 'U.E. Découverte', 'class' => 'ue-type-d', 'items' => []],
            'UET' => ['label' => 'U.E. Transversales', 'class' => 'ue-type-t', 'items' => []],
        ];
        $matieres = $data['moyennes_matieres'] ?? [];
        $nbMat = count($matieres);
        // Répartition automatique : 60% fondamentales, 25% découverte, 15% transversales
        $cutF = (int)ceil($nbMat * 0.6);
        $cutD = (int)ceil($nbMat * 0.85);
        foreach ($matieres as $i => $m) {
            if ($i < $cutF) $ueGroups['UEF']['items'][] = $m;
            elseif ($i < $cutD) $ueGroups['UED']['items'][] = $m;
            else $ueGroups['UET']['items'][] = $m;
        }
        $totalCreditsReq = 0; $totalCreditsAcq = 0;
    @endphp

    <!-- SEMESTRE -->
    <div class="semester-title">{{ $data['periode_libelle'] ?? 'Semestre' }}</div>

    <table class="ue-table">
        <thead>
            <tr>
                <th style="text-align:left; width:8%;">Code</th>
                <th style="text-align:left; width:32%;">Matière</th>
                <th style="width:8%;">Coef.</th>
                <th style="width:10%;">Résultat</th>
                <th style="width:10%;">Crédits requis</th>
                <th style="width:10%;">Crédits acquis</th>
            </tr>
        </thead>
        <tbody>
            @foreach($ueGroups as $ueKey => $ueGroup)
                @if(!empty($ueGroup['items']))
                    <tr class="ue-group">
                        <td colspan="6" class="{{ $ueGroup['class'] }}">{{ $ueGroup['label'] }}</td>
                    </tr>
                    @foreach($ueGroup['items'] as $j => $m)
                        @php
                            $note = $m['note'] ?? $m['moyenne'] ?? 0;
                            $coef = $m['coefficient'] ?? 1;
                            $creditsReq = $coef * 2;
                            $creditsAcq = $note >= 10 ? $creditsReq : 0;
                            $totalCreditsReq += $creditsReq;
                            $totalCreditsAcq += $creditsAcq;
                        @endphp
                        <tr>
                            <td>{{ $ueKey }}{{ str_pad($j + 1, 2, '0', STR_PAD_LEFT) }}</td>
                            <td class="mat-name">{{ $m['matiere'] }}</td>
                            <td>{{ $coef }}</td>
                            <td class="note-val">{{ number_format($note, 2) }}/20</td>
                            <td>{{ $creditsReq }}</td>
                            <td class="{{ $creditsAcq > 0 ? 'credits-ok' : 'credits-ko' }}">{{ $creditsAcq }}</td>
                        </tr>
                    @endforeach
                @endif
            @endforeach
        </tbody>
    </table>

    <!-- RÉSUMÉ -->
    <div class="bottom-summary">
        <table>
            <tr>
                <td class="lbl">Moyenne du semestre :</td>
                <td><strong>{{ number_format($data['moyenne_generale'] ?? 0, 2) }}/20</strong></td>
                <td class="lbl">Crédits obtenus :</td>
                <td><strong>{{ $totalCreditsAcq }}/{{ $totalCreditsReq }}</strong></td>
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
            <td><div class="sig-role">Le Chef de Département</div></td>
            <td style="text-align:right;">
                <div style="font-size:8px; color:#666;">Fait à Dakar, le {{ \Carbon\Carbon::now()->format('d/m/Y') }}</div>
                <div class="sig-role">Le Directeur</div>
            </td>
        </tr>
    </table>

    <div style="text-align:center; margin-top:12px; font-size:7px; color:#999;">
        Relevé généré par AGREE SIKUL — {{ \Carbon\Carbon::now()->format('d/m/Y H:i') }}
    </div>
</div>
</body>
</html>
