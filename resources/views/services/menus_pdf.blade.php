<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #333;
        }
        h1 {
            text-align: center;
            color: #1a2344;
            border-bottom: 3px solid #f97316;
            padding-bottom: 10px;
        }
        .info {
            text-align: center;
            margin: 10px 0;
            font-size: 11px;
            color: #666;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th {
            background-color: #1a2344;
            color: white;
            padding: 8px;
            text-align: left;
            font-weight: bold;
            border: 1px solid #333;
        }
        td {
            padding: 6px 8px;
            border: 1px solid #ddd;
        }
        .jour-header {
            background-color: #f97316;
            color: white;
            font-weight: bold;
            padding: 8px;
            border: 1px solid #333;
        }
        .empty-day {
            background-color: #f9f9f9;
            text-align: center;
            color: #999;
        }
        .statut-actif {
            color: #22c55e;
            font-weight: bold;
        }
        .statut-inactif {
            color: #ef4444;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <h1>{{ __('common.menu') }} - {{ $menu->week_name }}</h1>
    <div class="info">
        <strong>{{ __('common.week_start_date') }}:</strong> {{ \Carbon\Carbon::parse($menu->week_start_date)->format('d/m/Y') }}
        @if($menu->week_end_date)
        | <strong>{{ __('common.week_end_date') }}:</strong> {{ \Carbon\Carbon::parse($menu->week_end_date)->format('d/m/Y') }}
        @endif
        | <strong>{{ __('common.statut') }}:</strong> <span class="statut-{{ $menu->statut }}">{{ ucfirst($menu->statut) }}</span>
    </div>

    @foreach($jours as $jour)
        @if(isset($weekMenus[$jour]) && $weekMenus[$jour]->count() > 0)
        <div style="margin-bottom: 15px;">
            <table>
                <tr>
                    <td colspan="4" class="jour-header">{{ strtoupper($jour) }}</td>
                </tr>
                <tr>
                    <th style="width: 25%;">{{ __('common.entree') }}</th>
                    <th style="width: 35%;">{{ __('common.plat') }}</th>
                    <th style="width: 25%;">{{ __('common.dessert') }}</th>
                    <th style="width: 15%;">{{ __('common.remarks') }}</th>
                </tr>
                @foreach($weekMenus[$jour] as $dayMenu)
                <tr>
                    <td>{{ $dayMenu->accompagnement ?? '-' }}</td>
                    <td>{{ $dayMenu->plat_principal ?? '-' }}</td>
                    <td>{{ $dayMenu->dessert ?? '-' }}</td>
                    <td>{{ $dayMenu->remarques ?? '' }}</td>
                </tr>
                @endforeach
            </table>
        </div>
        @else
        <div style="margin-bottom: 15px;">
            <table>
                <tr>
                    <td colspan="4" class="jour-header">{{ strtoupper($jour) }}</td>
                </tr>
                <tr>
                    <td colspan="4" class="empty-day">{{ __('common.no_data') }}</td>
                </tr>
            </table>
        </div>
        @endif
    @endforeach

    <div style="text-align: center; margin-top: 30px; font-size: 10px; color: #999; border-top: 1px solid #ddd; padding-top: 10px;">
        {{ __('common.generated_at') }}: {{ now()->format('d/m/Y H:i:s') }}
    </div>
</body>
</html>
