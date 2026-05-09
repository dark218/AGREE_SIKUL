<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Report Card - {{ $apprenant?->prenoms }} {{ $apprenant?->nom }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #1a1a1a; }
        .page { padding: 12px 18px; }
        .watermark { position: fixed; top: 38%; left: 12%; font-size: 55px; color: rgba(11,86,151,0.03); transform: rotate(-30deg); font-weight: 900; z-index: -1; }

        .header { width: 100%; border-bottom: 3px solid #0B5697; padding-bottom: 8px; margin-bottom: 6px; }
        .header table { width: 100%; }
        .logo-box { width: 55px; height: 55px; border: 2px solid #0B5697; border-radius: 8px; text-align: center; line-height: 55px; font-size: 9px; color: #0B5697; font-weight: 700; }
        .school-name { font-size: 14px; font-weight: 800; color: #0B5697; text-align: center; }
        .school-sub { font-size: 8px; color: #555; text-align: center; }

        .title-bar { text-align: center; margin: 5px 0; padding: 5px; background: linear-gradient(90deg, #0B5697, #E5590C); color: white; font-size: 12px; font-weight: 800; letter-spacing: 2px; }

        .info-tbl { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
        .info-tbl td { padding: 3px 6px; font-size: 9px; border: 1px solid #eee; }
        .info-tbl .lbl { font-weight: 700; color: #0B5697; background: #f8fbff; width: 22%; }

        /* Main subjects table */
        .report-tbl { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
        .report-tbl th { background: #0B5697; color: white; padding: 4px 3px; font-size: 7.5px; font-weight: 700; text-align: center; border: 1px solid #094578; }
        .report-tbl td { padding: 3px; border: 1px solid #ddd; font-size: 8.5px; text-align: center; }
        .report-tbl .left { text-align: left; padding-left: 6px; }
        .report-tbl tr:nth-child(even) td { background: #f8fbff; }

        /* Competence groups */
        .comp-header td { background: #e8f0f8 !important; font-weight: 800; color: #0B5697; font-size: 9px; text-align: left; padding-left: 8px; }
        .partition-cell { padding-left: 20px !important; font-size: 8px; }

        /* Score colors */
        .score-5 { color: #28a745; font-weight: 800; }
        .score-4 { color: #0B5697; font-weight: 700; }
        .score-3 { color: #E5590C; font-weight: 700; }
        .score-2 { color: #dc3545; font-weight: 700; }
        .score-1 { color: #721c24; font-weight: 800; }

        /* Totals */
        .total-row td { background: #eef3f9 !important; font-weight: 800; border-top: 2px solid #0B5697; }

        /* Results section */
        .results { margin-top: 6px; width: 100%; border-collapse: collapse; }
        .results td { padding: 4px 8px; border: 1px solid #ddd; font-size: 9px; }
        .results .lbl { font-weight: 700; color: #0B5697; background: #f8fbff; width: 35%; }

        /* Remarks */
        .remarks { margin-top: 8px; }
        .remark-box { border: 1px solid #ddd; border-radius: 5px; padding: 6px; margin-bottom: 4px; }
        .remark-title { font-weight: 700; color: #0B5697; font-size: 8px; margin-bottom: 3px; }
        .remark-text { font-size: 8px; color: #555; min-height: 25px; }

        /* Signatures */
        .sigs { margin-top: 10px; width: 100%; }
        .sigs td { width: 25%; text-align: center; font-size: 7.5px; vertical-align: top; padding: 4px; }
        .sig-t { font-weight: 700; color: #0B5697; margin-bottom: 20px; font-size: 8px; }
        .sig-date { font-size: 7px; margin-top: 3px; color: #777; }

        .footer { margin-top: 6px; text-align: center; font-size: 6.5px; color: #aaa; border-top: 1px solid #eee; padding-top: 3px; }

        .grade-legend { margin-top: 4px; padding: 4px; background: #f8f9fa; border-radius: 4px; font-size: 7px; }
        .grade-legend span { margin-right: 10px; }
    </style>
</head>
<body>
<div class="watermark">AGREE SIKUL</div>
<div class="page">
    <div class="header">
        <table><tr>
            <td style="width:60px; vertical-align:middle;"><div class="logo-box">LOGO</div></td>
            <td style="text-align:center; vertical-align:middle;">
                <div class="school-name">{{ $ecole ?? 'SCHOOL NAME' }}</div>
                <div class="school-sub">{{ $adresse ?? '' }}</div>
            </td>
            <td style="width:60px; vertical-align:middle; text-align:right;">
                <div style="width:50px; height:60px; border:1px solid #ccc; text-align:center; line-height:60px; font-size:7px; color:#aaa; display:inline-block;">Photo</div>
            </td>
        </tr></table>
    </div>

    <div class="title-bar">REPORT CARD / BULLETIN DE NOTES</div>

    <table class="info-tbl">
        <tr>
            <td class="lbl">Pupil's Name</td>
            <td colspan="3"><strong>{{ $apprenant?->prenoms }} {{ $apprenant?->nom }}</strong></td>
        </tr>
        <tr>
            <td class="lbl">Registration No.</td>
            <td>{{ $apprenant?->matricule }}</td>
            <td class="lbl">Class</td>
            <td><strong>{{ $classe ?? '-' }}</strong></td>
        </tr>
        <tr>
            <td class="lbl">Date of Birth</td>
            <td>{{ $apprenant?->date_naissance?->format('d/m/Y') ?? '-' }}</td>
            <td class="lbl">Academic Year</td>
            <td>{{ $annee ?? '-' }}</td>
        </tr>
        <tr>
            <td class="lbl">Gender</td>
            <td>{{ $apprenant?->sexe ?? '-' }}</td>
            <td class="lbl">Term</td>
            <td><strong>{{ $term ?? '-' }}</strong></td>
        </tr>
    </table>

    <!-- Grade Legend -->
    <div class="grade-legend">
        <strong>Grading Scale:</strong>
        <span class="score-5">5 = Excellent</span>
        <span class="score-4">4 = Very Good</span>
        <span class="score-3">3 = Good</span>
        <span class="score-2">2 = Fair</span>
        <span class="score-1">1 = Poor</span>
    </div>

    <!-- Subjects Table -->
    <table class="report-tbl">
        <thead>
            <tr>
                <th style="width:25%; text-align:left; padding-left:6px;">Subject / Competence</th>
                <th style="width:18%; text-align:left; padding-left:6px;">Partition / Topic</th>
                <th style="width:6%;">Max</th>
                @foreach($months as $month)
                <th style="width:8%;">{{ $month }}</th>
                @endforeach
                <th style="width:9%;">Synthesis</th>
                <th style="width:14%;">Remark</th>
            </tr>
        </thead>
        <tbody>
            @foreach($competences as $comp)
            <tr class="comp-header"><td colspan="{{ 5 + count($months) }}">{{ $comp['name'] }}</td></tr>
            @foreach($comp['partitions'] as $part)
            <tr>
                <td></td>
                <td class="left partition-cell">{{ $part['name'] }}</td>
                <td>{{ $part['max'] }}</td>
                @foreach($months as $mIdx => $month)
                <td>
                    @php
                        $score = $part['scores'][$mIdx] ?? null;
                        $scoreClass = $score >= 4.5 ? 'score-5' : ($score >= 3.5 ? 'score-4' : ($score >= 2.5 ? 'score-3' : ($score >= 1.5 ? 'score-2' : 'score-1')));
                    @endphp
                    <span class="{{ $score !== null ? $scoreClass : '' }}">{{ $score !== null ? $score : '-' }}</span>
                </td>
                @endforeach
                <td><strong>{{ $part['synthesis'] !== null ? $part['synthesis'] : '-' }}</strong></td>
                <td class="left" style="font-size:7px;">{{ $part['remark'] ?? '' }}</td>
            </tr>
            @endforeach
            @endforeach

            <!-- Totals -->
            <tr class="total-row">
                <td class="left" style="padding-left:6px;" colspan="2">Total</td>
                <td>{{ $maxTotal }}</td>
                @foreach($months as $mIdx => $month)
                <td>{{ isset($monthTotals[$mIdx]) ? number_format($monthTotals[$mIdx], 2) : '-' }}</td>
                @endforeach
                <td><strong>{{ number_format($synthesisTotal ?? 0, 2) }}</strong></td>
                <td></td>
            </tr>
            <tr class="total-row">
                <td class="left" style="padding-left:6px;" colspan="2">Average /20</td>
                <td>20</td>
                @foreach($months as $mIdx => $month)
                <td><span class="{{ ($monthAverages[$mIdx] ?? 0) >= 10 ? 'score-4' : 'score-2' }}">{{ isset($monthAverages[$mIdx]) ? number_format($monthAverages[$mIdx], 2) : '-' }}</span></td>
                @endforeach
                <td><strong><span class="{{ ($synthesisAverage ?? 0) >= 10 ? 'score-4' : 'score-2' }}">{{ number_format($synthesisAverage ?? 0, 2) }}</span></strong></td>
                <td></td>
            </tr>
            <tr class="total-row">
                <td class="left" style="padding-left:6px;" colspan="2">Position</td>
                <td>{{ $totalStudents ?? '-' }}</td>
                @foreach($months as $mIdx => $month)
                <td>{{ $monthPositions[$mIdx] ?? '-' }}</td>
                @endforeach
                <td><strong>{{ $synthesisPosition ?? '-' }}</strong></td>
                <td></td>
            </tr>
            <tr>
                <td class="left" style="padding-left:6px;" colspan="2"><strong>Class Average</strong></td>
                <td>20</td>
                @foreach($months as $mIdx => $month)
                <td>{{ isset($classAverages[$mIdx]) ? number_format($classAverages[$mIdx], 2) : '-' }}</td>
                @endforeach
                <td><strong>{{ number_format($classAverageTotal ?? 0, 2) }}</strong></td>
                <td></td>
            </tr>
        </tbody>
    </table>

    <!-- Monthly Remarks & Signatures -->
    <div class="remarks">
        <table style="width:100%;"><tr>
        @foreach($months as $mIdx => $month)
            <td style="width:{{ 100/count($months) }}%; vertical-align:top; padding:3px;">
                <div class="remark-box">
                    <div class="remark-title">{{ $month }} Remarks</div>
                    <div class="remark-text">{{ $monthRemarks[$mIdx] ?? '' }}</div>
                </div>
            </td>
        @endforeach
            <td style="width:{{ 100/count($months) }}%; vertical-align:top; padding:3px;">
                <div class="remark-box">
                    <div class="remark-title">Term Remarks</div>
                    <div class="remark-text">{{ $termRemark ?? '' }}</div>
                </div>
            </td>
        </tr></table>
    </div>

    <!-- Signatures -->
    <table class="sigs"><tr>
        @foreach($months as $month)
        <td>
            <div class="sig-t">{{ $month }}</div>
            <div style="height:18px;"></div>
            <div>Parent's Signature</div>
        </td>
        @endforeach
        <td>
            <div class="sig-t">Term</div>
            <div style="height:18px;"></div>
            <div>Parent's Signature</div>
            <div class="sig-date">{{ $city ?? 'Douala' }}, the .................</div>
        </td>
    </tr></table>

    <div class="footer">AGREE SIKUL — Report Card — {{ $date }}</div>
</div>
</body>
</html>
