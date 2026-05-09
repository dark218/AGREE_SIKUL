<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="ltr">
<head>
    <!-- META DATA -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- FAVICON -->
    <link rel="shortcut icon" type="image/x-icon" href="{{ url('assets/images/smile/icone-3.png') }}" />

    <!-- TITLE -->
    <title inertia>{{ config('app.name', 'SmilPay') }} - POS</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Font Awesome (local) - Version moderne avec classes fas/far/fab -->
    <link rel="stylesheet" href="{{ url('frontend/css/fontawesome-all.min.css') }}" />

    <!-- BOOTSTRAP CSS (minimal pour POS) -->
    <link href="{{ url('assets/plugins/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet" />

    <!-- Box Icons (local) -->
    <link href="{{ url('assets/iconfonts/boxicons/boxicons.css') }}" rel="stylesheet">

    <!-- POS CSS -->
    <link href="{{ url('assets/css/pos-caissier.css') }}" rel="stylesheet" />

    <!-- INERTIA/VITE SCRIPTS & CSS -->
    @routes
    @vite(['resources/js/app.js', 'resources/css/app.css'])
    @inertiaHead

    <!-- Variables CSS dynamiques -->
    @php
        $baseColor = $basic_settings->base_color ?? '#000';
    @endphp
    <style>
        :root {
            --primary-color: {{ $baseColor }};
            --bs-primary: {{ $baseColor }};
            --pos-primary: #000;
            --pos-success: #2ECC71;
            --pos-danger: #E74C3C;
            --pos-warning: #F39C12;
            --pos-info: #3498DB;
            --pos-bg: #F1F3F6;
            --pos-card: #FFFFFF;
            --pos-text: #2D3748;
            --pos-text-muted: #718096;
            --pos-border: #E2E8F0;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            height: 100%;
            width: 100%;
            overflow: hidden;
        }

        body {
            background-color: var(--pos-bg);
            font-family: 'Poppins', sans-serif;
            color: var(--pos-text);
        }

        /* Reset des styles du template principal */
        .pos-app {
            min-height: 100vh;
            width: 100%;
            display: flex;
            flex-direction: column;
        }

        /* Scrollbar personnalisée */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 3px;
        }

        ::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 3px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #a1a1a1;
        }

        /* Animations globales */
        .fade-enter-active,
        .fade-leave-active {
            transition: opacity 0.2s ease;
        }

        .fade-enter-from,
        .fade-leave-to {
            opacity: 0;
        }

        /* Print styles */
        @media print {
            body {
                background: white !important;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>

<body class="pos-app">
    <!-- App Vue/Inertia -->
    @inertia

    <!-- SCRIPTS (minimal pour POS) -->
    <script src="{{ url('assets/js/jquery.min.js') }}"></script>
    <script src="{{ url('assets/plugins/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
</body>
</html>
