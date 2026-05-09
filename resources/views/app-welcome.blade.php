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
    <title inertia>{{ config('app.name', 'SmilPay') }}</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@100;200;300;400;500;600;700;800;900&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900&display=swap" rel="stylesheet">

    <!-- BOOTSTRAP CSS -->
    <link href="{{ url('assets/plugins/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet" />

    <!-- STYLE CSS -->
    <link href="{{ url('assets/css/style.css') }}" rel="stylesheet" />
    <link href="{{ url('assets/css/skin-modes.css') }}" rel="stylesheet" />
    <link href="{{ url('assets/css/dark-style.css') }}" rel="stylesheet" />

    <!-- CUSTOM SCROLL BAR CSS -->
    <link href="{{ url('assets/plugins/scroll-bar/jquery.mCustomScrollbar.css') }}" rel="stylesheet" />

    <!-- FONT-ICONS CSS -->
    <link href="{{ url('assets/css/icons.css') }}" rel="stylesheet" />

    <!-- COLOR SKIN CSS -->
    <link id="theme" rel="stylesheet" type="text/css" media="all" href="{{ url('assets/colors/color1.css') }}" />

    <!-- SINGLE-PAGE CSS (pour login) -->
    <link href="{{ url('assets/plugins/single-page/css/main.css') }}" rel="stylesheet" type="text/css">

    <!-- Box Icons -->
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">

    <!-- Styles personnalisés pour le formulaire de login -->
    <style>
        .wrap-input100 {
            display: flex;
            align-items: center;
        }
        .input100 {
            flex: 1;
        }
        .select-small {
            width: 80px;
        }
        .input100 {
            height: 38px !important;
        }
        .input-large {
            width: calc(100% - 120px);
            margin-left: 20px;
        }
    </style>

    <!-- INERTIA/VITE -->
    @routes
    @vite(['resources/js/app.js', 'resources/css/app.css'])
    @inertiaHead
</head>

<body class="app sidebar-mini">
    <!-- BACKGROUND-IMAGE -->
    <div class="login-img">
        <!-- PAGE -->
        <div class="page">
            <div class="">
                <!-- App Vue/Inertia - Le logo est géré dans le composant Vue -->
                @inertia
            </div>
        </div>
        <!-- END PAGE -->
    </div>
    <!-- BACKGROUND-IMAGE CLOSED -->

    <!-- jQuery -->
    <script src="{{ url('assets/js/jquery.min.js') }}"></script>
    
    <!-- Bootstrap JS -->
    <script src="{{ url('assets/plugins/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ url('assets/plugins/bootstrap/js/popper.min.js') }}"></script>
</body>
</html>