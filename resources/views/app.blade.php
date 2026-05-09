<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="ltr">
<head>
    <!-- META DATA -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Firebase Config -->
    <meta name="firebase-api-key" content="{{ config('services.firebase.api_key') }}">
    <meta name="firebase-auth-domain" content="{{ config('services.firebase.auth_domain') }}">
    <meta name="firebase-project-id" content="{{ config('services.firebase.project_id') }}">
    <meta name="firebase-messaging-sender-id" content="{{ config('services.firebase.messaging_sender_id') }}">
    <meta name="firebase-app-id" content="{{ config('services.firebase.app_id') }}">
    <meta name="firebase-vapid-key" content="{{ config('services.firebase.vapid_key') }}">

    <!-- FAVICON -->
    <link rel="shortcut icon" type="image/x-icon" href="{{ url('assets/images/smile/icone-3.png') }}" />

    <!-- TITLE -->
    <title inertia>{{ config('app.name', 'SmilPay') }}</title>

    <!-- ============================================
         CSS DEPUIS PUBLIC (Template existant)
         ============================================ -->
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@100;200;300;400;500;600;700;800;900&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900&display=swap" rel="stylesheet">

    <!-- BOOTSTRAP CSS -->
    <link href="{{ url('assets/plugins/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet" />

    <!-- STYLE CSS (Template principal) -->
    <link href="{{ url('assets/css/style.css') }}" rel="stylesheet" />
    <link href="{{ url('assets/css/skin-modes.css') }}" rel="stylesheet" />
    <link href="{{ url('assets/css/dark-style.css') }}" rel="stylesheet" />
    <link href="{{ url('assets/css/app.css') }}" rel="stylesheet" />

    <!-- CUSTOM SCROLL BAR CSS -->
    <link href="{{ url('assets/plugins/scroll-bar/jquery.mCustomScrollbar.css') }}" rel="stylesheet" />

    <!-- FONT-ICONS CSS -->
    <link href="{{ url('assets/css/icons.css') }}" rel="stylesheet" />

    <!-- COLOR SKIN CSS -->
    <link id="theme" rel="stylesheet" type="text/css" media="all" href="{{ url('assets/colors/color1.css') }}" />

    <!-- FRONTEND CSS -->
    <link rel="stylesheet" href="{{ url('frontend/css/fontawesome-all.min.css') }}">
    <link rel="stylesheet" href="{{ url('frontend/css/line-awesome.min.css') }}">
    <link rel="stylesheet" href="{{ url('frontend/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ url('frontend/css/animate.css') }}">
    <link rel="stylesheet" href="{{ url('frontend/css/nice-select.css') }}">
    <link rel="stylesheet" href="{{ url('frontend/css/virtual-card.css') }}">
    <link rel="stylesheet" href="{{ url('frontend/css/style.css') }}">

    <!-- BACKEND CSS -->
    <link rel="stylesheet" href="{{ url('backend/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ url('backend/library/popup/magnific-popup.css') }}">

    <!-- Box Icons -->
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">

    <!-- jQuery UI -->
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">

    <!-- Fancybox -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/fancybox@3.5.7/dist/jquery.fancybox.min.css">

    <!-- Single Page CSS (pour login) -->
    <link href="{{ url('assets/plugins/single-page/css/main.css') }}" rel="stylesheet" type="text/css">

    <!-- ============================================
         INERTIA/VITE SCRIPTS & CSS
         ============================================ -->
    @routes
    @vite(['resources/js/app.js', 'resources/css/app.css'])
    @inertiaHead

    <!-- Variables CSS dynamiques depuis Laravel -->
    @php
        $baseColor = $basic_settings->base_color ?? '#000';
    @endphp
    <style>
        :root {
            --primary-color: {{ $baseColor }};
        }
    </style>
</head>

<body class="app sidebar-mini font-sans antialiased">
    <!-- App Vue/Inertia -->
    @inertia

    <!-- ============================================
         SCRIPTS DEPUIS PUBLIC
         ============================================ -->
    
    <!-- jQuery -->
    <script src="{{ url('assets/js/jquery.min.js') }}"></script>
    
    <!-- jQuery UI -->
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>

    <!-- Bootstrap JS -->
    <script src="{{ url('assets/plugins/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ url('assets/plugins/bootstrap/js/popper.min.js') }}"></script>

    <!-- Custom Scroll Bar -->
    <script src="{{ url('assets/plugins/scroll-bar/jquery.mCustomScrollbar.concat.min.js') }}"></script>

    <!-- Fancybox -->
    <script src="https://cdn.jsdelivr.net/npm/@fancyapps/fancybox@3.5.7/dist/jquery.fancybox.min.js"></script>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Scripts personnalisés -->
    <script src="{{ url('js/confirm.js') }}"></script>

    <!-- Firebase initialization moved to NotificationManager.vue component -->
    <!-- No duplicate Firebase initialization here to avoid conflicts -->

    <!-- Service Worker -->
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function () {
                navigator.serviceWorker.register('/firebase-messaging-sw.js', { updateViaCache: 'none' })
                    .then(function (registration) {
                        registration.update();
                    }).catch(function (err) {
                        // Silent fail
                    });
            });
        }
    </script>
</body>
</html>