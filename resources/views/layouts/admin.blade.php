<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-app-theme="{{ $appTheme['appearance_mode'] ?? 'light' }}">



<head>
    <meta charset="UTF-8">
    {{-- Peint immédiatement le fond sombre, avant le chargement des feuilles de style. --}}
    <style>
        html[data-app-theme="dark"] { color-scheme: dark; background-color: #14171c; }
        html[data-app-theme="dark"] body { min-height: 100vh; background-color: #14171c !important; color: #edf1f5; }
    </style>
    <title>TENACE COSMETIQUE</title>
    @php
        $squareAdminPages = true;
    @endphp

    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}?v=20261019.1">
    <link rel="stylesheet" href="{{ asset('assets/css/customer-service-notifications.css') }}?v=20261019.1">
    @if (request()->routeIs('service-cases.*'))
        <link rel="stylesheet" href="{{ asset('assets/css/customer-service-cases.css') }}?v=20261019.1">
    @endif
    @if (request()->routeIs('customer-loyalty.*'))
        <link rel="stylesheet" href="{{ asset('assets/css/customer-loyalty.css') }}?v=20261010.2">
    @endif
    @if (request()->routeIs('stock-control.*'))
        <link rel="stylesheet" href="{{ asset('assets/css/stock-control.css') }}?v=20261017.4">
    @endif
    @if (request()->routeIs('show-product'))
        <link rel="stylesheet" href="{{ asset('assets/css/product-detail.css') }}?v=20261019.2">
    @endif
    <link rel="stylesheet" href="{{ asset('assets/css/legacy-square-pages.css') }}?v=20261019.1">

    <style>
        :root {
            --app-primary: {{ $appTheme['primary_color'] }};
            --app-secondary: {{ $appTheme['secondary_color'] }};
            --app-accent: {{ $appTheme['accent_color'] }};
            --app-background: {{ $appTheme['background_color'] }};
            --app-surface: {{ $appTheme['surface_color'] }};
            --app-text: {{ $appTheme['text_color'] }};
            --app-primary-contrast: {{ $appTheme['primary_contrast_color'] }};
            --app-accent-contrast: {{ $appTheme['accent_contrast_color'] }};
            --app-primary-hover: color-mix(in srgb, var(--app-primary) 84%, #000);
            --app-primary-soft: color-mix(in srgb, var(--app-primary) 8%, #fff);
            --app-primary-border: color-mix(in srgb, var(--app-primary) 30%, #fff);
            --app-radius: {{ ['square' => '0px', 'soft' => '8px', 'rounded' => '16px'][$appTheme['corner_style']] ?? '8px' }};
            --primary: var(--app-primary);
            --indigo: var(--app-primary);
            --secondary: var(--app-secondary);
            --success: var(--app-accent);
        }
        body { color: var(--app-text); background-color: var(--app-background); }
        .main-wrapper, .right-area, .right-area .main-content { background-color: var(--app-background); }
        .main-wrapper .sidebar { background-color: var(--app-surface); }
        .main-wrapper .sidebar .navigation > ul li a.active {
            border-radius: var(--app-radius) !important;
            background: var(--app-primary) !important;
            color: var(--app-primary-contrast) !important;
            box-shadow: inset 3px 0 0 rgba(255,255,255,.45) !important;
        }
        .main-wrapper .sidebar .navigation > ul li a.active .icon,
        .main-wrapper .sidebar .navigation > ul li a.active .caret,
        .main-wrapper .sidebar .navigation > ul li a.active .text { color: var(--app-primary-contrast) !important; }
        .main-wrapper .sidebar .navigation > ul li ul li a[href*="stock-control"].active,
        .main-wrapper .sidebar .navigation > ul li > a[href="#adminStockControlMenu"].active,
        .main-wrapper .sidebar .navigation > ul li > a[href="#mngStockControlMenu"].active {
            border-radius: var(--app-radius) !important;
            background: var(--app-primary) !important;
            color: var(--app-primary-contrast) !important;
            box-shadow: inset 3px 0 0 rgba(255,255,255,.45) !important;
        }
        .main-wrapper .sidebar .navigation > ul li ul li a[href*="stock-control"].active .icon,
        .main-wrapper .sidebar .navigation > ul li ul li a[href*="stock-control"].active .text,
        .main-wrapper .sidebar .navigation > ul li > a[href="#adminStockControlMenu"].active .icon,
        .main-wrapper .sidebar .navigation > ul li > a[href="#adminStockControlMenu"].active .text,
        .main-wrapper .sidebar .navigation > ul li > a[href="#adminStockControlMenu"].active .caret,
        .main-wrapper .sidebar .navigation > ul li > a[href="#mngStockControlMenu"].active .icon,
        .main-wrapper .sidebar .navigation > ul li > a[href="#mngStockControlMenu"].active .text,
        .main-wrapper .sidebar .navigation > ul li > a[href="#mngStockControlMenu"].active .caret { color: var(--app-primary-contrast) !important; }
        .right-area > header { background-color: var(--app-primary) !important; color: var(--app-primary-contrast) !important; }
        .right-area > header a:not(.dropdown-item), .right-area > header button:not(.dropdown-item), .right-area > header .btn:not(.dropdown-item) { color: var(--app-primary-contrast) !important; }
        .right-area > header .dropdown-menu { background: var(--app-surface) !important; color: var(--app-text) !important; }
        .right-area > header .dropdown-menu .dropdown-item,
        .right-area > header .dropdown-menu .dropdown-item-text,
        .right-area > header .dropdown-menu a,
        .right-area > header .dropdown-menu span { color: var(--app-text) !important; }
        .right-area > header .dropdown-menu a:hover,
        .right-area > header .dropdown-menu a:focus { background: var(--app-primary-soft) !important; color: var(--app-primary) !important; }
        .btn-primary, .badge-primary, .page-item.active .page-link {
            background-color: var(--app-primary) !important;
            border-color: var(--app-primary) !important;
            color: var(--app-primary-contrast) !important;
        }
        .btn-outline-primary { color: var(--app-primary) !important; border-color: var(--app-primary) !important; }
        .btn-outline-primary:hover { color: var(--app-primary-contrast) !important; background-color: var(--app-primary) !important; }
        .btn-primary:hover, .btn-primary:focus, .page-item.active .page-link:hover { background-color: var(--app-primary-hover) !important; border-color: var(--app-primary-hover) !important; }
        .right-area .main-content a.product-detail-edit { color: var(--app-primary-contrast) !important; }
        .btn-outline-primary:focus, .form-check-input:focus { box-shadow: 0 0 0 .2rem color-mix(in srgb, var(--app-primary) 25%, transparent) !important; }
        ::selection { background: color-mix(in srgb, var(--app-primary) 24%, transparent); }
        .btn-secondary { background-color: var(--app-secondary) !important; border-color: var(--app-secondary) !important; }
        .btn-success, .badge-success { background-color: var(--app-accent) !important; border-color: var(--app-accent) !important; color: var(--app-accent-contrast) !important; }
        .text-primary, .right-area .main-content a:not(.btn):not(.nav-link) { color: var(--app-primary); }
        .card, .modal-content, .dropdown-menu, .list-group-item { background-color: var(--app-surface); color: var(--app-text); }
        .card, .modal-content, .dropdown-menu, .form-control, .custom-select, .btn, .badge,
        .page-link, .input-group-text, .nav-tabs .nav-link, .rounded, .rounded-sm, .rounded-lg {
            border-radius: var(--app-radius) !important;
        }
        .form-control:focus, .custom-select:focus { border-color: var(--app-primary); box-shadow: 0 0 0 .15rem color-mix(in srgb, var(--app-primary) 18%, transparent); }
    </style>

    <link rel="shortcut icon" href="{{ asset('assets/images/tena.png') }}" type="image/png">
    <link rel="icon" href="{{ asset('assets/images/tena.png') }}" type="image/png">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">




    <link rel="stylesheet" href="{{ asset('cdnjs.cloudflare.com/ajax/libs/apexcharts/3.23.1/apexcharts.min.css') }}">

    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.dataTables.min.css
    ">

    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

    @livewireStyles

    @include('partials.pwa-head')

    <style>
        html[data-app-theme="dark"] {
            color-scheme: dark;
            --app-background: #14171c;
            --app-surface: #1e232b;
            --app-text: #edf1f5;
            --app-muted: #a8b1bd;
            --app-border: #373f4b;
            --app-primary-soft: color-mix(in srgb, var(--app-primary) 20%, #1e232b);
            --app-primary-border: color-mix(in srgb, var(--app-primary) 58%, #1e232b);
        }
        html[data-app-theme="dark"] body,
        html[data-app-theme="dark"] .main-wrapper,
        html[data-app-theme="dark"] .right-area,
        html[data-app-theme="dark"] .right-area .main-content { background-color: var(--app-background) !important; color: var(--app-text); }
        html[data-app-theme="dark"] .main-wrapper .sidebar,
        html[data-app-theme="dark"] .card,
        html[data-app-theme="dark"] .modal-content,
        html[data-app-theme="dark"] .dropdown-menu,
        html[data-app-theme="dark"] .list-group-item,
        html[data-app-theme="dark"] .page-header,
        html[data-app-theme="dark"] .product-detail-hero,
        html[data-app-theme="dark"] .product-detail-workspace,
        html[data-app-theme="dark"] .product-detail-metrics article,
        html[data-app-theme="dark"] .stock-control-card,
        html[data-app-theme="dark"] .stock-control-table-wrap { background-color: var(--app-surface) !important; color: var(--app-text) !important; border-color: var(--app-border) !important; }
        html[data-app-theme="dark"] .card-header,
        html[data-app-theme="dark"] .card-footer,
        html[data-app-theme="dark"] .table thead th,
        html[data-app-theme="dark"] .table tfoot th,
        html[data-app-theme="dark"] .table .thead-light th,
        html[data-app-theme="dark"] .product-detail-tabs,
        html[data-app-theme="dark"] .product-detail-table th { background-color: #252b34 !important; color: var(--app-muted) !important; border-color: var(--app-border) !important; }
        html[data-app-theme="dark"] .table,
        html[data-app-theme="dark"] .table tbody tr,
        html[data-app-theme="dark"] .table td,
        html[data-app-theme="dark"] .product-detail-table td,
        html[data-app-theme="dark"] .dropdown-item,
        html[data-app-theme="dark"] .dropdown-item-text { background-color: transparent; color: var(--app-text) !important; border-color: var(--app-border) !important; }
        html[data-app-theme="dark"] .table-hover tbody tr:hover,
        html[data-app-theme="dark"] .dropdown-item:hover,
        html[data-app-theme="dark"] .dropdown-item:focus { background-color: var(--app-primary-soft) !important; color: var(--app-text) !important; }
        html[data-app-theme="dark"] .table-striped tbody tr:nth-of-type(odd),
        html[data-app-theme="dark"] .dataTable tbody tr:nth-of-type(odd) { background-color: #222831 !important; }
        html[data-app-theme="dark"] .dataTables_wrapper,
        html[data-app-theme="dark"] .dataTables_wrapper label,
        html[data-app-theme="dark"] .dataTables_info,
        html[data-app-theme="dark"] .dataTables_length,
        html[data-app-theme="dark"] .dataTables_filter { color: var(--app-muted) !important; }
        html[data-app-theme="dark"] .main-wrapper .sidebar .navigation > ul li a:not(.active),
        html[data-app-theme="dark"] .main-wrapper .sidebar .navigation > ul li a:not(.active) .text,
        html[data-app-theme="dark"] .main-wrapper .sidebar .navigation > ul li a:not(.active) .icon { color: var(--app-text) !important; }
        html[data-app-theme="dark"] .form-control,
        html[data-app-theme="dark"] .custom-select,
        html[data-app-theme="dark"] select,
        html[data-app-theme="dark"] textarea,
        html[data-app-theme="dark"] input:not([type="color"]):not([type="checkbox"]):not([type="radio"]),
        html[data-app-theme="dark"] .select2-container--default .select2-selection--single,
        html[data-app-theme="dark"] .select2-dropdown { background-color: #171b21 !important; color: var(--app-text) !important; border-color: var(--app-border) !important; }
        html[data-app-theme="dark"] input::placeholder,
        html[data-app-theme="dark"] textarea::placeholder { color: #929dab !important; opacity: 1; }
        html[data-app-theme="dark"] .text-dark,
        html[data-app-theme="dark"] .text-body,
        html[data-app-theme="dark"] .product-detail-title h1,
        html[data-app-theme="dark"] .product-detail-section-head h2 { color: var(--app-text) !important; }
        html[data-app-theme="dark"] .text-muted,
        html[data-app-theme="dark"] .product-detail-title p,
        html[data-app-theme="dark"] .product-detail-metrics small { color: var(--app-muted) !important; }
        html[data-app-theme="dark"] .border,
        html[data-app-theme="dark"] .border-top,
        html[data-app-theme="dark"] .border-bottom { border-color: var(--app-border) !important; }
        html[data-app-theme="dark"] .bg-white,
        html[data-app-theme="dark"] .bg-light { background-color: var(--app-surface) !important; color: var(--app-text) !important; }
        html[data-app-theme="dark"] .settings-tabs,
        html[data-app-theme="dark"] .theme-presets,
        html[data-app-theme="dark"] .theme-preview { background-color: var(--app-surface); border-color: var(--app-border); }
        html[data-app-theme="dark"] .theme-preview-card { background-color: #171b21; border-color: var(--app-border); }
        html[data-app-theme="dark"] .theme-preview-card span,
        html[data-app-theme="dark"] .theme-preview-content > small { color: var(--app-muted); }
        html[data-app-theme="dark"] .theme-preview-card b,
        html[data-app-theme="dark"] .theme-preview-content > strong { color: var(--app-text); }
        /* Surfaces partagées des différents modules */
        html[data-app-theme="dark"] [class$="-card"]:not(.btn):not(.badge),
        html[data-app-theme="dark"] [class*="-panel"]:not(.dropdown-panel),
        html[data-app-theme="dark"] [class*="-section-card"],
        html[data-app-theme="dark"] [class$="-hero"],
        html[data-app-theme="dark"] [class$="-header"],
        html[data-app-theme="dark"] .well,
        html[data-app-theme="dark"] .jumbotron,
        html[data-app-theme="dark"] .popover { background-color: var(--app-surface) !important; color: var(--app-text) !important; border-color: var(--app-border) !important; }
        html[data-app-theme="dark"] [class$="-card"] p,
        html[data-app-theme="dark"] [class$="-card"] label,
        html[data-app-theme="dark"] [class$="-card"] h1,
        html[data-app-theme="dark"] [class$="-card"] h2,
        html[data-app-theme="dark"] [class$="-card"] h3,
        html[data-app-theme="dark"] [class$="-card"] h4,
        html[data-app-theme="dark"] [class$="-panel"] p,
        html[data-app-theme="dark"] [class$="-panel"] label,
        html[data-app-theme="dark"] [class$="-panel"] h1,
        html[data-app-theme="dark"] [class$="-panel"] h2,
        html[data-app-theme="dark"] [class$="-panel"] h3,
        html[data-app-theme="dark"] [class$="-panel"] h4,
        html[data-app-theme="dark"] [class$="-hero"] h1,
        html[data-app-theme="dark"] [class$="-hero"] h2,
        html[data-app-theme="dark"] [class$="-hero"] p { color: var(--app-text) !important; }
        html[data-app-theme="dark"] [class$="-card"] small,
        html[data-app-theme="dark"] [class$="-panel"] small,
        html[data-app-theme="dark"] [class$="-header"] small { color: var(--app-muted) !important; }
        html[data-app-theme="dark"] .table,
        html[data-app-theme="dark"] table,
        html[data-app-theme="dark"] .dataTable { color: var(--app-text) !important; border-color: var(--app-border) !important; }
        html[data-app-theme="dark"] .table thead,
        html[data-app-theme="dark"] .table tfoot,
        html[data-app-theme="dark"] table thead,
        html[data-app-theme="dark"] table tfoot { background-color: #252b34 !important; }
        html[data-app-theme="dark"] .table th,
        html[data-app-theme="dark"] .table td,
        html[data-app-theme="dark"] table th,
        html[data-app-theme="dark"] table td,
        html[data-app-theme="dark"] .dataTable th,
        html[data-app-theme="dark"] .dataTable td { color: var(--app-text) !important; border-color: var(--app-border) !important; }
        html[data-app-theme="dark"] table tbody td { background-color: transparent !important; }
        html[data-app-theme="dark"] table tbody tr,
        html[data-app-theme="dark"] .dataTable tbody tr { background-color: var(--app-surface) !important; }
        html[data-app-theme="dark"] .table-striped tbody tr:nth-of-type(odd),
        html[data-app-theme="dark"] .dataTable tbody tr:nth-of-type(odd) { background-color: #252b34 !important; }
        html[data-app-theme="dark"] .table-responsive,
        html[data-app-theme="dark"] [class*="table-wrap"],
        html[data-app-theme="dark"] [class*="table-container"] { background-color: transparent !important; border-color: var(--app-border) !important; }
        html[data-app-theme="dark"] .form-control,
        html[data-app-theme="dark"] .custom-select,
        html[data-app-theme="dark"] select,
        html[data-app-theme="dark"] textarea,
        html[data-app-theme="dark"] input:not([type="color"]):not([type="checkbox"]):not([type="radio"]):not([type="submit"]):not([type="button"]):not([type="reset"]) { background-color: #171b21 !important; color: var(--app-text) !important; border-color: var(--app-border) !important; }
        html[data-app-theme="dark"] .form-control:disabled,
        html[data-app-theme="dark"] .custom-select:disabled,
        html[data-app-theme="dark"] input:disabled,
        html[data-app-theme="dark"] textarea:disabled { background-color: #252b34 !important; color: var(--app-muted) !important; }
        html[data-app-theme="dark"] .modal-header,
        html[data-app-theme="dark"] .modal-footer,
        html[data-app-theme="dark"] .list-group-item,
        html[data-app-theme="dark"] .nav-tabs,
        html[data-app-theme="dark"] .dropdown-divider { border-color: var(--app-border) !important; }
        html[data-app-theme="dark"] .btn-light,
        html[data-app-theme="dark"] .btn-outline-secondary,
        html[data-app-theme="dark"] .page-link { background-color: #252b34 !important; color: var(--app-text) !important; border-color: var(--app-border) !important; }
        html[data-app-theme="dark"] .btn-light:hover,
        html[data-app-theme="dark"] .btn-outline-secondary:hover,
        html[data-app-theme="dark"] .page-link:hover { background-color: var(--app-primary-soft) !important; color: var(--app-text) !important; }
        html[data-app-theme="dark"] .text-dark,
        html[data-app-theme="dark"] .text-body { color: var(--app-text) !important; }
        html[data-app-theme="dark"] .text-muted,
        html[data-app-theme="dark"] small.text-muted,
        html[data-app-theme="dark"] .form-text { color: var(--app-muted) !important; }
        html[data-app-theme="dark"] .right-area .main-content a:not(.btn):not(.nav-link):not(.dropdown-item) { color: color-mix(in srgb, var(--app-primary) 45%, #fff) !important; }
        html[data-app-theme="dark"] .right-area .main-content a:not(.btn):not(.nav-link):not(.dropdown-item):hover { color: #fff !important; }
        html[data-app-theme="dark"] .select2-container--default .select2-selection--single,
        html[data-app-theme="dark"] .select2-dropdown,
        html[data-app-theme="dark"] .select2-results__option { background-color: #171b21 !important; color: var(--app-text) !important; border-color: var(--app-border) !important; }
        html[data-app-theme="dark"] .select2-container--default .select2-results__option--highlighted[aria-selected] { background-color: var(--app-primary) !important; color: var(--app-primary-contrast) !important; }

        /* Contrastes des anciennes vues qui imposent leurs couleurs en ligne */
        html[data-app-theme="dark"] .right-area .main-content [style*="color: black"],
        html[data-app-theme="dark"] .right-area .main-content [style*="color:black"],
        html[data-app-theme="dark"] .right-area .main-content [style*="color: #000"],
        html[data-app-theme="dark"] .right-area .main-content [style*="color:#000"] { color: var(--app-text) !important; }
        html[data-app-theme="dark"] .right-area .main-content [style*="color: #212529"],
        html[data-app-theme="dark"] .right-area .main-content [style*="color:#212529"],
        html[data-app-theme="dark"] .right-area .main-content [style*="color: #333"],
        html[data-app-theme="dark"] .right-area .main-content [style*="color:#333"] { color: var(--app-text) !important; }
        html[data-app-theme="dark"] .right-area .main-content [style*="background-color: white"],
        html[data-app-theme="dark"] .right-area .main-content [style*="background: white"],
        html[data-app-theme="dark"] .right-area .main-content [style*="background-color: #fff"],
        html[data-app-theme="dark"] .right-area .main-content [style*="background-color:#fff"],
        html[data-app-theme="dark"] .right-area .main-content [style*="background: #fff"],
        html[data-app-theme="dark"] .right-area .main-content [style*="background:#fff"] { background-color: var(--app-surface) !important; color: var(--app-text) !important; border-color: var(--app-border) !important; }
        html[data-app-theme="dark"] .right-area .main-content [style*="background-color: #ffffff"],
        html[data-app-theme="dark"] .right-area .main-content [style*="background-color:#ffffff"],
        html[data-app-theme="dark"] .right-area .main-content [style*="background: #ffffff"],
        html[data-app-theme="dark"] .right-area .main-content [style*="background:#ffffff"] { background-color: var(--app-surface) !important; color: var(--app-text) !important; border-color: var(--app-border) !important; }
        html[data-app-theme="dark"] .right-area .main-content hr { border-color: var(--app-border) !important; }
        html[data-app-theme="dark"] .right-area .main-content .pagination .page-link,
        html[data-app-theme="dark"] .right-area .main-content .dataTables_wrapper .paginate_button { background: #252b34 !important; color: var(--app-text) !important; border-color: var(--app-border) !important; }
        html[data-app-theme="dark"] .right-area .main-content .pagination .page-item.active .page-link,
        html[data-app-theme="dark"] .right-area .main-content .dataTables_wrapper .paginate_button.current { background: var(--app-primary) !important; color: var(--app-primary-contrast) !important; }
        html[data-app-theme="dark"] .right-area .main-content .dataTables_wrapper .dt-buttons .dt-button,
        html[data-app-theme="dark"] .right-area .main-content .dataTables_wrapper button.dt-button,
        html[data-app-theme="dark"] .right-area .main-content .dataTables_wrapper div.dt-button,
        html[data-app-theme="dark"] .right-area .main-content .dataTables_wrapper a.dt-button,
        html[data-app-theme="dark"] .right-area .main-content .dataTables_wrapper input.dt-button { background:#252b34 !important; background-image:none !important; color:var(--app-text) !important; border:1px solid var(--app-border) !important; box-shadow:none !important; text-shadow:none !important; }
        html[data-app-theme="dark"] .right-area .main-content .dataTables_wrapper .dt-buttons .dt-button:hover,
        html[data-app-theme="dark"] .right-area .main-content .dataTables_wrapper .dt-buttons .dt-button:focus,
        html[data-app-theme="dark"] .right-area .main-content .dataTables_wrapper .dt-buttons .dt-button:active { background:var(--app-primary) !important; background-image:none !important; color:var(--app-primary-contrast) !important; border-color:var(--app-primary) !important; box-shadow:none !important; text-shadow:none !important; }

        /* ApexCharts: améliorer les axes et infobulles sans recolorer les séries */
        html[data-app-theme="dark"] .apexcharts-tooltip,
        html[data-app-theme="dark"] .apexcharts-xaxistooltip,
        html[data-app-theme="dark"] .apexcharts-yaxistooltip { background: #1e232b !important; color: var(--app-text) !important; border-color: var(--app-border) !important; }
        html[data-app-theme="dark"] .apexcharts-tooltip-title { background: #252b34 !important; color: var(--app-text) !important; border-color: var(--app-border) !important; }
        html[data-app-theme="dark"] .apexcharts-text,
        html[data-app-theme="dark"] .apexcharts-legend-text,
        html[data-app-theme="dark"] .apexcharts-title-text,
        html[data-app-theme="dark"] .apexcharts-subtitle-text { fill: var(--app-muted) !important; color: var(--app-muted) !important; }
        html[data-app-theme="dark"] .apexcharts-gridline,
        html[data-app-theme="dark"] .apexcharts-xaxis line,
        html[data-app-theme="dark"] .apexcharts-yaxis line { stroke: #3b434f !important; }
        html[data-app-theme="dark"] .apexcharts-tooltip * { color: var(--app-text) !important; }
    </style>

</head>

<body class="{{ $squareAdminPages ? 'legacy-square-pages' : '' }}">
    <div class="overlay-mask"></div>
    <div class="main-wrapper ">

        @include('partials.aside')

        <div class="right-area">
            @include('partials.header')

            <div class="main-content container">
                <div class="page-header">
                    <div>
                        {{-- <span class="h2" style="text-align: center">TENACE COSMETIQUE TOGO | {{ Auth::user()->name}}</span> --}}

                    </div>
                </div>
                @yield('content')

            </div>

        </div>
    </div>



    @livewireScripts


    <script src="{{ asset('assets/js/vendor.bundle.js') }}"></script>


    <script src="{{ asset('assets/js/app.bundle.js') }}?v=20261012.2"></script>



    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@2.8.0"></script>

    <script>
        if (document.documentElement.dataset.appTheme === 'dark' && window.Chart && Chart.defaults && Chart.defaults.global) {
            Chart.defaults.global.defaultFontColor = '#a8b1bd';
            Chart.defaults.global.defaultColor = '#a8b1bd';
        }
    </script>


    <script src="{{ asset('assets/js/demo/dashboard.bundle.js') }}"></script>



    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@2.8.0"></script>



    <script src="https://code.jquery.com/jquery-3.5.1.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.print.min.js"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.5.0/Chart.min.js"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.5.0/Chart.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        (function () {
            if (document.documentElement.dataset.appTheme !== 'dark' || typeof Chart === 'undefined') return;

            var muted = '#a8b1bd';
            var grid = '#3b434f';
            if (Chart.defaults) {
                if (Chart.defaults.global) Chart.defaults.global.defaultFontColor = muted;
                Chart.defaults.color = muted;
            }

            // Met à jour aussi les graphiques déjà créés dans les anciennes vues.
            var instances = Chart.instances ? Object.values(Chart.instances) : [];
            instances.forEach(function (chart) {
                if (!chart || !chart.options) return;
                chart.options.defaultColor = muted;
                chart.options.color = muted;
                var scales = chart.options.scales || {};
                ['xAxes', 'yAxes'].forEach(function (axisName) {
                    (scales[axisName] || []).forEach(function (axis) {
                        axis.ticks = axis.ticks || {};
                        axis.ticks.fontColor = muted;
                        axis.gridLines = axis.gridLines || {};
                        axis.gridLines.color = grid;
                        if (axis.scaleLabel) axis.scaleLabel.fontColor = muted;
                    });
                });
                Object.keys(scales).forEach(function (axisName) {
                    var axis = scales[axisName];
                    if (!axis || Array.isArray(axis)) return;
                    axis.ticks = axis.ticks || {};
                    axis.ticks.color = muted;
                    axis.grid = axis.grid || {};
                    axis.grid.color = grid;
                    if (axis.title) axis.title.color = muted;
                });
                if (chart.update) chart.update();
            });
        })();

        $(document).ready(function() {
            $('#example').DataTable({
                dom: 'Bfrtip',
                buttons: [
                    'copy', 'csv', 'excel', 'pdf', 'print'
                ]
            });
        });
        $(document).ready(function() {
            $('#examples').DataTable({
                dom: 'Bfrtip',
                buttons: [
                    'copy', 'csv', 'excel', 'pdf', 'print'
                ]
            });
        });
        $(document).ready(function() {
            var $customerTable = $('#customer-table');
            if (!$customerTable.length) return;

            $customerTable.DataTable({
                processing: true,
                serverSide: true,
                pageLength: 10,
                ajax: $customerTable.data('source'),
                dom: 'Bfrtip',
                buttons: ['copy', 'csv', 'excel', 'pdf', 'print'],
                order: [[1, 'asc']],
                columns: [
                    { data: 'number', orderable: false, searchable: false },
                    { data: 'name', name: 'name' },
                    { data: 'phone', name: 'phone' },
                    { data: 'email', name: 'email' },
                    { data: 'address', name: 'adresse' },
                    { data: 'actions', orderable: false, searchable: false }
                ]
            });
        });


        function printDiv(divName) {
            var printContents = document.getElementById(divName).innerHTML;
            var originalContents = document.body.innerHTML;

            document.body.innerHTML = printContents;

            window.print();

            document.body.innerHTML = originalContents;
        }


        // In your Javascript (external .js resource or <script> tag)
        $(document).ready(function() {
            $('.js-example-basic-single').select2();
            $('.js-customer-search').each(function() {
                var $customerSelect = $(this);
                $customerSelect.select2({
                    placeholder: 'Rechercher par nom ou téléphone',
                    minimumInputLength: 2,
                    ajax: {
                        url: $customerSelect.data('ajax-url'),
                        dataType: 'json',
                        delay: 250,
                        data: function(params) {
                            return { q: params.term || '', page: params.page || 1 };
                        },
                        processResults: function(data) { return data; }
                    }
                });
            });
        });



        function extraireNumeroMois() {
            // Obtenez la valeur de la date depuis l'élément d'entrée
            var dateInput = document.getElementById("dateInput").value;

            // Créez un objet de date à partir de la valeur de l'entrée
            var date = new Date(dateInput);

            // Obtenez le numéro du mois (de 0 à 11, où 0 représente janvier et 11 représente décembre)
            var numeroMois = date.getMonth() + 1;

            // Affichez le numéro du mois dans la console (vous pouvez le traiter comme vous le souhaitez)
            console.log("Numéro du mois : " + numeroMois);
        }
    </script>

    @include('partials.pwa-scripts')
    @if (request()->routeIs('stock-control.*'))
        <dialog class="stock-confirm-dialog" id="stockConfirmDialog" aria-labelledby="stockConfirmTitle" aria-describedby="stockConfirmMessage">
            <div class="stock-confirm-dialog-head"><span class="material-icons" aria-hidden="true">fact_check</span><button type="button" data-stock-confirm-cancel aria-label="Fermer">×</button></div>
            <h2 id="stockConfirmTitle">Confirmer l’action</h2>
            <p id="stockConfirmMessage"></p>
            <div class="stock-confirm-dialog-actions"><button type="button" class="stock-control-button" data-stock-confirm-cancel>Annuler</button><button type="button" class="stock-control-button stock-control-button-primary" id="stockConfirmAccept">Confirmer</button></div>
        </dialog>
        <script>
            (() => {
                const timers = new WeakMap();
                document.addEventListener('input', (event) => {
                    const input = event.target;
                    if (!(input instanceof HTMLInputElement) || !input.matches('.stock-live-search')) return;
                    const form = input.form;
                    if (!form) return;
                    window.clearTimeout(timers.get(input));
                    timers.set(input, window.setTimeout(() => {
                        if (typeof form.requestSubmit === 'function') form.requestSubmit();
                        else form.submit();
                    }, 550));
                });

                const dialog = document.getElementById('stockConfirmDialog');
                const accept = document.getElementById('stockConfirmAccept');
                let pendingForm = null;
                if (!dialog || !accept) return;
                document.addEventListener('submit', (event) => {
                    const form = event.target;
                    if (!(form instanceof HTMLFormElement) || !form.hasAttribute('data-stock-confirm-title')) return;
                    event.preventDefault();
                    pendingForm = form;
                    dialog.querySelector('#stockConfirmTitle').textContent = form.dataset.stockConfirmTitle || 'Confirmer l’action';
                    dialog.querySelector('#stockConfirmMessage').textContent = form.dataset.stockConfirmMessage || '';
                    dialog.showModal();
                });
                dialog.querySelectorAll('[data-stock-confirm-cancel]').forEach((button) => button.addEventListener('click', () => dialog.close()));
                dialog.addEventListener('click', (event) => { if (event.target === dialog) dialog.close(); });
                dialog.addEventListener('close', () => { pendingForm = null; });
                accept.addEventListener('click', () => {
                    if (!pendingForm) return;
                    const form = pendingForm;
                    pendingForm = null;
                    form.removeAttribute('data-stock-confirm-title');
                    form.removeAttribute('data-stock-confirm-message');
                    dialog.close();
                    form.requestSubmit();
                });
            })();
        </script>
    @endif
    <style>
        /* Dernière couche de contraste : elle corrige aussi les styles locaux des anciennes vues. */
        html[data-app-theme="dark"] .right-area .main-content h1,
        html[data-app-theme="dark"] .right-area .main-content h2,
        html[data-app-theme="dark"] .right-area .main-content h3,
        html[data-app-theme="dark"] .right-area .main-content h4,
        html[data-app-theme="dark"] .right-area .main-content h5,
        html[data-app-theme="dark"] .right-area .main-content h6,
        html[data-app-theme="dark"] .right-area .main-content p,
        html[data-app-theme="dark"] .right-area .main-content li,
        html[data-app-theme="dark"] .right-area .main-content dt,
        html[data-app-theme="dark"] .right-area .main-content dd,
        html[data-app-theme="dark"] .right-area .main-content label,
        html[data-app-theme="dark"] .right-area .main-content th,
        html[data-app-theme="dark"] .right-area .main-content td,
        html[data-app-theme="dark"] .right-area .main-content caption,
        html[data-app-theme="dark"] .right-area .main-content legend { color:var(--app-text) !important; }
        html[data-app-theme="dark"] .right-area .main-content small,
        html[data-app-theme="dark"] .right-area .main-content .text-muted,
        html[data-app-theme="dark"] .right-area .main-content .form-text { color:var(--app-muted) !important; }
        html[data-app-theme="dark"] .right-area .main-content span:not(.material-icons):not(.badge):not(.text-success):not(.text-danger):not(.text-warning):not(.text-info):not(.text-primary):not(.stock-available):not(.stock-low):not(.stock-out):not(.badge *):not(.btn *):not(a *):not(button *):not([class*="status"] *) { color:var(--app-text) !important; }
        html[data-app-theme="dark"] .right-area .main-content strong,
        html[data-app-theme="dark"] .right-area .main-content b { color:var(--app-text) !important; }
        html[data-app-theme="dark"] .right-area .main-content .card,
        html[data-app-theme="dark"] .right-area .main-content [class$="-card"],
        html[data-app-theme="dark"] .right-area .main-content [class$="-panel"],
        html[data-app-theme="dark"] .right-area .main-content [class*="-section-card"] { background-color:var(--app-surface) !important; border-color:var(--app-border) !important; }
        html[data-app-theme="dark"] .right-area .main-content .card-header,
        html[data-app-theme="dark"] .right-area .main-content .card-footer { background-color:#252b34 !important; border-color:var(--app-border) !important; }

        /* Contraste final des écrans métier listés (leurs styles locaux sont chargés avant cette couche). */
        html[data-app-theme="dark"] .right-area .main-content :is(
            .stock-control-page, .product-audit-page, .order-list-page, .orders-page-shell,
            .product-cart-page, .customer-directory-page, .service-cases, .loyalty-page,
            .legacy-users-page, .livrable-page
        ) { color:var(--app-text) !important; }
        html[data-app-theme="dark"] .right-area .main-content :is(
            .stock-control-page, .product-audit-page, .order-list-page, .orders-page-shell,
            .product-cart-page, .customer-directory-page, .service-cases, .loyalty-page,
            .legacy-users-page, .livrable-page
        ) :is(.card, [class$="-card"], [class$="-panel"], [class*="-section"],
              .order-card, .finance-box, .cart-panel, .audit-report-content,
              .customer-directory-table-wrap, .service-cases-table-wrap,
              .loyalty-mobile-card, .sc-modal, .loyalty-modal, .modal-content) {
            background-color:var(--app-surface) !important;
            color:var(--app-text) !important;
            border-color:var(--app-border) !important;
        }
        html[data-app-theme="dark"] .right-area .main-content :is(
            .stock-control-page, .product-audit-page, .order-list-page, .orders-page-shell,
            .product-cart-page, .customer-directory-page, .service-cases, .loyalty-page,
            .legacy-users-page, .livrable-page
        ) :is(h1,h2,h3,h4,h5,h6,p,label,th,td,li,dt,dd,legend,caption,strong,b,
              .order-card-title, .customer-name, .product-name, .audit-product-name,
              .sc-modal-title, .loyalty-section-heading, .cart-line-title) {
            color:var(--app-text) !important;
        }
        html[data-app-theme="dark"] .right-area .main-content :is(
            .stock-control-page, .product-audit-page, .order-list-page, .orders-page-shell,
            .product-cart-page, .customer-directory-page, .service-cases, .loyalty-page,
            .legacy-users-page, .livrable-page
        ) :is(small,.text-muted,.form-text,[class*="-muted"],.subtitle,.description,.helper-text) {
            color:var(--app-muted) !important;
        }
        html[data-app-theme="dark"] .right-area .main-content :is(
            .stock-control-page, .product-audit-page, .order-list-page, .orders-page-shell,
            .product-cart-page, .customer-directory-page, .service-cases, .loyalty-page,
            .legacy-users-page, .livrable-page
        ) :is(input:not([type="checkbox"]):not([type="radio"]):not([type="submit"]), select, textarea,
              .form-control, .custom-select, .select2-selection) {
            background-color:#171b21 !important; color:var(--app-text) !important;
            border-color:var(--app-border) !important;
        }
        html[data-app-theme="dark"] .right-area .main-content :is(
            .stock-control-page, .product-audit-page, .order-list-page, .orders-page-shell,
            .product-cart-page, .customer-directory-page, .service-cases, .loyalty-page,
            .legacy-users-page, .livrable-page
        ) :is(table, thead, tfoot, th, td, tr, .table-responsive, [class*="table-wrap"]) {
            border-color:var(--app-border) !important;
        }
        html[data-app-theme="dark"] .right-area .main-content :is(
            .stock-control-page, .product-audit-page, .order-list-page, .orders-page-shell,
            .product-cart-page, .customer-directory-page, .service-cases, .loyalty-page,
            .legacy-users-page, .livrable-page
        ) :is(thead, tfoot, .order-card-header, .order-card-footer, .card-header, .card-footer,
              .audit-table th, .customer-directory-card-heading, .sc-modal-head, .sc-modal-foot) {
            background-color:#252b34 !important; color:var(--app-text) !important;
        }
        html[data-app-theme="dark"] .right-area .main-content :is(
            .stock-control-page, .product-audit-page, .order-list-page, .orders-page-shell,
            .product-cart-page, .customer-directory-page, .service-cases, .loyalty-page,
            .legacy-users-page, .livrable-page
        ) :is(.btn-primary, .stock-control-button-primary, .stock-control-hero-action,
              .customer-primary-action, .cart-primary-action, .sc-primary-btn,
              .loyalty-settings-button, .loyalty-mobile-link, .audit-primary-action) {
            background-color:var(--app-primary) !important;
            border-color:var(--app-primary) !important;
            color:var(--app-primary-contrast) !important;
        }
        html[data-app-theme="dark"] .right-area .main-content :is(
            .stock-control-page, .product-audit-page, .order-list-page, .orders-page-shell,
            .product-cart-page, .customer-directory-page, .service-cases, .loyalty-page,
            .legacy-users-page, .livrable-page
        ) :is(.btn-primary, .stock-control-button-primary, .stock-control-hero-action,
              .customer-primary-action, .cart-primary-action, .sc-primary-btn,
              .loyalty-settings-button, .loyalty-mobile-link, .audit-primary-action) * {
            color:var(--app-primary-contrast) !important;
        }
        html[data-app-theme="dark"] .right-area .main-content :is(
            .stock-control-page, .product-audit-page, .order-list-page, .orders-page-shell,
            .product-cart-page, .customer-directory-page, .service-cases, .loyalty-page,
            .legacy-users-page, .livrable-page
        ) :is(.btn-light, .btn-outline-secondary, .stock-control-button:not(.stock-control-button-primary),
              .sc-secondary-btn, .customer-secondary-action, .cart-secondary-action,
              .loyalty-secondary-button, .loyalty-clear-selection) {
            background-color:#252b34 !important; color:var(--app-text) !important;
            border-color:var(--app-border) !important;
        }
        html[data-app-theme="dark"] .right-area .main-content :is(.product-cart-page,.order-list-page,.orders-page-shell) :is(.cart-shell,.cart-layout,.order-card-body,.order-card-header,.order-card-footer,.finance-box) {
            background-color:var(--app-surface) !important; color:var(--app-text) !important;
        }
        html[data-app-theme="dark"] .right-area .main-content .product-audit-page .audit-print-header,
        html[data-app-theme="dark"] .right-area .main-content .product-audit-page .audit-print-summary { background-color:var(--app-surface) !important; color:var(--app-text) !important; }
        html[data-app-theme="dark"] .right-area .main-content .product-audit-page .audit-table tbody tr:nth-child(odd),
        html[data-app-theme="dark"] .right-area .main-content .loyalty-page tbody tr:nth-child(odd) { background-color:#252b34 !important; }
        html[data-app-theme="dark"] .right-area .main-content .loyalty-page :is(.loyalty-filter-bar,.loyalty-filter-panel,.loyalty-rules-card,.loyalty-section-heading,.loyalty-modal),
        html[data-app-theme="dark"] .right-area .main-content .service-cases :is(.sc-toolbar,.sc-filter-panel,.sc-detail-panel,.sc-modal) { background-color:var(--app-surface) !important; color:var(--app-text) !important; border-color:var(--app-border) !important; }
        html[data-app-theme="dark"] .right-area .main-content .service-cases :is(.sc-status,.sc-priority,.sc-type),
        html[data-app-theme="dark"] .right-area .main-content .loyalty-page :is(.loyalty-category,.loyalty-mobile-segment) { color:inherit; }

        /* Les actions principales de ces modules suivent aussi la couleur choisie dans Apparence. */
        .right-area .main-content :is(.stock-control-page,.product-audit-page,.order-list-page,.orders-page-shell,
            .product-cart-page,.customer-directory-page,.service-cases,.loyalty-page,.legacy-users-page,.livrable-page)
            :is(.stock-control-button-primary,.stock-control-hero-action,.customer-primary-action,
                .cart-primary-action,.cart-primary-btn,.cart-empty a,.orders-new-link,.sc-primary-btn,
                .loyalty-settings-button,.audit-primary-action,.audit-print) {
            background-color:var(--app-primary) !important; border-color:var(--app-primary) !important;
            color:var(--app-primary-contrast) !important;
        }
        .right-area .main-content :is(.stock-control-page,.product-audit-page,.order-list-page,.orders-page-shell,
            .product-cart-page,.customer-directory-page,.service-cases,.loyalty-page,.legacy-users-page,.livrable-page)
            :is(.stock-control-button-primary,.stock-control-hero-action,.customer-primary-action,
                .cart-primary-action,.cart-primary-btn,.cart-empty a,.orders-new-link,.sc-primary-btn,
                .loyalty-settings-button,.audit-primary-action,.audit-print) * {
            color:var(--app-primary-contrast) !important;
        }
        .right-area .main-content .stock-control-page { --sc-red:var(--app-primary); }
        .right-area .main-content .product-audit-page { --audit-red:var(--app-primary); }
        .right-area .main-content .order-list-page { --orders-red:var(--app-primary); --orders-wine:var(--app-primary); }
        .right-area .main-content .product-cart-page { --cart-wine:var(--app-primary); }
        .right-area .main-content .customer-directory-page { --customer-red:var(--app-primary); }
        .right-area .main-content .loyalty-page { --loyalty-red:var(--app-primary); }
        .right-area .main-content .stock-control-page > .stock-control-hero {
            background:var(--app-primary) !important; border-color:var(--app-primary) !important;
            color:var(--app-primary-contrast) !important;
        }
        .right-area .main-content .stock-control-page > .stock-control-hero :is(h1,p,.stock-control-kicker,.stock-control-hero-action) { color:var(--app-primary-contrast) !important; }
        .right-area .main-content .stock-control-page > .stock-control-hero .stock-control-hero-action { border-color:color-mix(in srgb,var(--app-primary-contrast) 55%,transparent) !important; }
        .right-area .main-content .stock-control-page .stock-control-intro-icon,
        .right-area .main-content .stock-control-page .stock-location-symbol,
        .right-area .main-content .stock-control-page .stock-balance-summary article > .material-icons,
        .right-area .main-content .stock-control-page .receipt-ref-icon,
        .right-area .main-content .stock-control-page .transfer-ref > .material-icons { color:var(--app-primary) !important; }
        .right-area .main-content .stock-control-page :is(.stock-rule-toggle input,.stock-control-field input[type="checkbox"]) { accent-color:var(--app-primary) !important; }
        .right-area .main-content .order-detail { --od-red:var(--app-primary); --od-ink:var(--app-text); --od-muted:var(--app-muted); --od-line:var(--app-border); }
        .right-area .main-content .order-detail .od-btn-primary { background:var(--app-primary) !important; border-color:var(--app-primary) !important; color:var(--app-primary-contrast) !important; }
        .right-area .main-content .order-detail .od-btn-primary * { color:var(--app-primary-contrast) !important; }
        html[data-app-theme="dark"] .right-area .main-content .order-detail,
        html[data-app-theme="dark"] .right-area .main-content .order-detail :is(.od-paper,.od-panel,.od-logo) {
            background-color:var(--app-surface) !important; color:var(--app-text) !important; border-color:var(--app-border) !important;
        }
        html[data-app-theme="dark"] .right-area .main-content .order-detail :is(.od-title,.od-brand h2,.od-info h2,.od-brand-name,.od-info strong,.od-items-heading h2,.od-table td,.od-table th) { color:var(--app-text) !important; }
        html[data-app-theme="dark"] .right-area .main-content .order-detail :is(.od-subtitle,.od-eyebrow,.od-brand p,.od-info p,.od-items-heading span,.od-product small) { color:var(--app-muted) !important; }
        html[data-app-theme="dark"] .right-area .main-content .order-detail .od-btn:not(.od-btn-primary) { background:#252b34 !important; color:var(--app-text) !important; border-color:var(--app-border) !important; }
        html[data-app-theme="dark"] .right-area .main-content .order-detail .od-table th { background:#252b34 !important; }
        html[data-app-theme="dark"] .right-area .main-content .order-detail :is(.od-shipping-card,.od-package-photos,.od-photo-card,.od-payment-form) { background:var(--app-surface) !important; color:var(--app-text) !important; border-color:var(--app-border) !important; }
        html[data-app-theme="dark"] .right-area .main-content .order-detail .od-photo-card img { background:#171b21 !important; }
        html[data-app-theme="dark"] .right-area .main-content .order-detail .od-payment-status { background:#432629 !important; color:#ffc4c4 !important; border-left-color:#db7777 !important; }
        html[data-app-theme="dark"] .right-area .main-content .order-detail .od-payment-status strong { color:#ffe1e1 !important; }
        html[data-app-theme="dark"] .right-area .main-content .order-detail .od-payment-status[style*="background:#effaf3"] { background:#173b2c !important; color:#b7ebcb !important; border-left-color:#65b886 !important; }
        html[data-app-theme="dark"] .right-area .main-content .order-detail .od-payment-status[style*="background:#effaf3"] strong { color:#d8f5e3 !important; }
        html[data-app-theme="dark"] .right-area .main-content .order-detail :is(.od-total-final,.od-total-final strong) { color:color-mix(in srgb,var(--app-primary) 42%,#fff) !important; }
        @media print {
            html[data-app-theme="dark"] .right-area .main-content .order-detail :is(.od-paper,.od-panel,.od-shipping-card,.od-package-photos,.od-photo-card) { background:#fff !important; color:#292d38 !important; border-color:#e8eaf0 !important; }
            html[data-app-theme="dark"] .right-area .main-content .order-detail :is(.od-title,.od-brand h2,.od-info h2,.od-brand-name,.od-info strong,.od-items-heading h2,.od-table td,.od-table th,.od-total-row strong) { color:#292d38 !important; }
            html[data-app-theme="dark"] .right-area .main-content .order-detail :is(.od-brand p,.od-info p,.od-product small,.od-note,.od-total-row) { color:#747b89 !important; }
            html[data-app-theme="dark"] .right-area .main-content .order-detail .od-table th { background:#f7f8fa !important; color:#747b89 !important; }
        }
        html[data-app-theme="dark"] .right-area .main-content :is(.orders-new-link,.cart-primary-btn,.audit-print) {
            background:var(--app-primary) !important; border-color:var(--app-primary) !important; color:var(--app-primary-contrast) !important;
        }
        html[data-app-theme="dark"] .right-area .main-content :is(.orders-new-link,.cart-primary-btn,.audit-print) * { color:var(--app-primary-contrast) !important; }

        /* Écrans du contrôle des stocks : les anciennes couleurs claires étaient codées dans stock-control.css. */
        html[data-app-theme="dark"] .right-area .main-content .stock-control-page {
            --sc-ink:var(--app-text); --sc-muted:var(--app-muted); --sc-line:var(--app-border);
            --sc-soft:#252b34; color:var(--app-text) !important;
        }
        html[data-app-theme="dark"] .right-area .main-content .stock-control-page > .stock-control-hero { background:var(--app-primary) !important; }
        html[data-app-theme="dark"] .right-area .main-content .stock-control-page > .stock-control-hero :is(h1,p,.stock-control-kicker,.stock-control-hero-action) { color:var(--app-primary-contrast) !important; }
        html[data-app-theme="dark"] .right-area .main-content .stock-control-page :is(
            .stock-control-intro,.stock-control-create-form,.stock-location-item,.stock-rules-readonly-list > div,
            .stock-alert-summary article,.receipt-product-line,.receipt-metric-grid article,.receipt-event-line,
            .stock-balance-summary article,.transfer-overview-grid article,.stock-count-opening,
            .stock-audit-event,.stock-second-count-all,.stock-second-count-line,
            .stock-count-reconciliation-table-wrap,.stock-count-reconciliation-clear,
            .receipt-attachment-preview,.receipt-attachment-file,.stock-alert-details-panel,
            .stock-approval-dialog,.stock-confirm-dialog
        ) { background:var(--app-surface) !important; color:var(--app-text) !important; border-color:var(--app-border) !important; }
        html[data-app-theme="dark"] .right-area .main-content .stock-control-page :is(
            .receipt-location-display,.receipt-internal-note,.receipt-attachments,.receipt-validation-note,
            .stock-control-count,.stock-control-readonly,.stock-approval-dialog-quantity,
            .adjustment-reason-box,.stock-count-help,.stock-second-count-form,
            .stock-count-reconciliation-reminder,.stock-count-counter-guide,.receipt-awaiting-info,
            .transfer-policy-note,.stock-balance-notice,.stock-control-section-head
        ) { background-color:#252b34 !important; color:var(--app-text) !important; border-color:var(--app-border) !important; }
        html[data-app-theme="dark"] .right-area .main-content .stock-control-page :is(
            .stock-control-intro h2,.stock-control-intro p,.stock-control-create-form h3,
            .stock-location-name,.stock-location-meta code,.stock-control-section-head h2,
            .stock-rules-readonly-list strong,.stock-approval-dialog h2,.stock-approval-dialog p,
            .stock-approval-dialog-quantity strong,.adjustment-reason-box p,.receipt-location-display,
            .stock-count-help,.stock-count-reconciliation-caption,.stock-count-counter-guide
        ) { color:var(--app-text) !important; }
        html[data-app-theme="dark"] .right-area .main-content .stock-control-page :is(
            .stock-control-intro p,.stock-control-form-footer small,.stock-location-meta,
            .stock-location-responsible span,.stock-control-empty p,.stock-rule-toggle small,
            .stock-rules-readonly-list span,.stock-control-field > span,.stock-control-field small,
            .stock-approval-dialog-quantity span,.stock-waiting-note,.stock-control-page small.text-muted
        ) { color:var(--app-muted) !important; }
        html[data-app-theme="dark"] .right-area .main-content .stock-control-page :is(
            .stock-control-button:not(.stock-control-button-primary),.stock-approval-dialog-close,
            .stock-confirm-dialog-head button,.stock-balance-open,.stock-alert-row > a
        ) { background:#252b34 !important; color:var(--app-text) !important; border-color:var(--app-border) !important; }
        html[data-app-theme="dark"] .right-area .main-content .stock-control-page :is(
            .stock-control-button-primary,.stock-control-hero-action
        ) { background:var(--app-primary) !important; border-color:var(--app-primary) !important; color:var(--app-primary-contrast) !important; }
        html[data-app-theme="dark"] .right-area .main-content .stock-control-page :is(
            .stock-control-button-primary,.stock-control-hero-action
        ) * { color:var(--app-primary-contrast) !important; }
        html[data-app-theme="dark"] .right-area .main-content .stock-control-page :is(
            .receipt-list-row,.transfer-list-row,.ledger-row,.stock-location-edit summary,
            .receipt-attachments a,.receipt-attachment-file,.adjustment-reject-button
        ) { color:color-mix(in srgb,var(--app-primary) 48%,#fff) !important; }
        html[data-app-theme="dark"] .right-area .main-content .stock-control-page :is(
            .receipt-list-row:hover,.transfer-list-row:hover,.ledger-row:hover
        ) { background:var(--app-primary-soft) !important; color:var(--app-text) !important; }
        html[data-app-theme="dark"] .right-area .main-content .stock-control-page :is(
            .stock-control-table-wrap,.stock-count-table-wrap,.stock-balance-table-wrap,
            .stock-count-reconciliation-table-wrap,.ledger-list,.receipt-list,.transfer-list
        ) { background:var(--app-surface) !important; border-color:var(--app-border) !important; }
        html[data-app-theme="dark"] .right-area .main-content .stock-control-page :is(
            .stock-control-table th,.stock-count-table th,.stock-balance-table th,.stock-audit-lines th,
            .stock-count-reconciliation-table th
        ) { background:#252b34 !important; color:var(--app-muted) !important; }
        html[data-app-theme="dark"] .right-area .main-content .stock-control-page :is(
            .stock-control-alert-success,.stock-control-alert-error,.stock-control-state,
            .stock-balance-state,.receipt-status-pill,.stock-count-status,.stock-control-system-tag
        ) { filter:brightness(.82) saturate(.9); }
        .right-area .main-content .stock-history-page .stock-history-header { border-top-color:var(--app-primary) !important; }
        .right-area .main-content .stock-history-page :is(.stock-history-download,.stock-history-filter-actions button) { background:var(--app-primary) !important; color:var(--app-primary-contrast) !important; }
        .right-area .main-content .stock-history-page :is(.stock-history-download,.stock-history-filter-actions button) * { color:var(--app-primary-contrast) !important; }
        .right-area .main-content .stock-history-page :is(.stock-history-header>div>span,.stock-history-details summary,.stock-history-filter-actions a) { color:var(--app-primary) !important; }
        html[data-app-theme="dark"] .right-area .main-content .stock-history-page { color:var(--app-text) !important; }
        html[data-app-theme="dark"] .right-area .main-content .stock-history-page :is(.stock-history-header,.stock-history-filter,.stock-history-list,.stock-history-row,.stock-history-detail-grid>div,.stock-history-line) {
            background:var(--app-surface) !important; color:var(--app-text) !important; border-color:var(--app-border) !important;
        }
        html[data-app-theme="dark"] .right-area .main-content .stock-history-page :is(h1,h2,h3,p,strong,time,.stock-history-reference,.stock-history-title-line h3,.stock-history-detail-grid span) { color:var(--app-text) !important; }
        html[data-app-theme="dark"] .right-area .main-content .stock-history-page :is(.stock-history-header p,.stock-history-list-title>span,.stock-history-date,.stock-history-date small,.stock-history-people small,.stock-history-empty) { color:var(--app-muted) !important; }
        html[data-app-theme="dark"] .right-area .main-content .stock-history-page :is(input,select) { background:#171b21 !important; color:var(--app-text) !important; border-color:var(--app-border) !important; }
        html[data-app-theme="dark"] .right-area .main-content .stock-history-page :is(.stock-history-download,.stock-history-filter-actions button) { background:var(--app-primary) !important; color:var(--app-primary-contrast) !important; }

        html[data-app-theme="dark"] .right-area .main-content .order-list-page { --orders-ink:var(--app-text); --orders-muted:var(--app-muted); --orders-line:var(--app-border); --orders-red:var(--app-primary); color:var(--app-text) !important; }
        html[data-app-theme="dark"] .right-area .main-content .order-list-page :is(.orders-board,.orders-board-top,.order-card,.order-mobile-card,.finance-box,.order-card-header,.order-card-body,.order-card-footer,.order-list-tab-content,.orders-tab-scroll,.order-history-filter,.modal-content) { background:var(--app-surface) !important; color:var(--app-text) !important; border-color:var(--app-border) !important; }
        html[data-app-theme="dark"] .right-area .main-content .order-list-page :is(.order-list-tabs .nav-link,.orders-tab-description,.orders-page-heading p,.order-card p,.finance-box span) { color:var(--app-muted) !important; }
        html[data-app-theme="dark"] .right-area .main-content .order-list-page .order-list-tabs .nav-link.active { background:#252b34 !important; color:var(--app-text) !important; }
        html[data-app-theme="dark"] .right-area .main-content .order-list-page .order-list-tabs .nav-link:not(.active) { background:transparent !important; color:var(--app-muted) !important; }
        html[data-app-theme="dark"] .right-area .main-content .order-list-page .orders-eyebrow { color:var(--app-primary) !important; }
        html[data-app-theme="dark"] .right-area .main-content .order-list-page .order-list-tabs .nav-link.active:after { background:var(--app-primary) !important; }
        html[data-app-theme="dark"] .right-area .main-content .order-list-page :is(.date-chip,.date-chip-badge,.pill-badge,.orders-tab-count) { background:#252b34 !important; color:var(--app-muted) !important; border-color:var(--app-border) !important; }
        html[data-app-theme="dark"] .right-area .main-content .order-list-page .date-chip.is-active { background:var(--app-primary) !important; color:var(--app-primary-contrast) !important; }

        html[data-app-theme="dark"] .right-area .main-content .product-cart-page { --cart-wine:var(--app-primary); --cart-ink:var(--app-text); --cart-muted:var(--app-muted); --cart-line:var(--app-border); --cart-bg:var(--app-background); color:var(--app-text) !important; }
        html[data-app-theme="dark"] .right-area .main-content .product-cart-page :is(.cart-panel,.cart-anonymize-dialog,.cart-customer-mode,.cart-customer-anonymize,.cart-anonymize-progress,.cart-anonymize-progress-error,.cart-field.anonymous-customer-help,.cart-anonymize-dialog-warning) { background:var(--app-surface) !important; color:var(--app-text) !important; border-color:var(--app-border) !important; }
        html[data-app-theme="dark"] .right-area .main-content .product-cart-page :is(.cart-step,.cart-anonymize-dialog-icon,.cart-customer-mode.is-selected) { background:var(--app-primary-soft) !important; color:var(--app-primary) !important; border-color:var(--app-primary-border) !important; }
        html[data-app-theme="dark"] .right-area .main-content .product-cart-page :is(.cart-table th,.cart-panel-head,.cart-anonymize-dialog-head,.cart-anonymize-dialog-actions,.cart-anonymize-progress) { border-color:var(--app-border) !important; }
        html[data-app-theme="dark"] .right-area .main-content .product-cart-page .cart-table th { background:#252b34 !important; color:var(--app-muted) !important; }
        html[data-app-theme="dark"] .right-area .main-content .product-cart-page :is(.cart-summary-note,.cart-quantity button,.cart-customer-mode,.cart-customer-anonymize,.cart-anonymize-progress,.cart-anonymize-dialog-actions button:not(.cart-anonymize-confirm)) { background:#252b34 !important; color:var(--app-text) !important; border-color:var(--app-border) !important; }
        html[data-app-theme="dark"] .right-area .main-content .product-cart-page :is(.cart-product-thumb,.cart-empty-icon) { background:var(--app-primary-soft) !important; color:var(--app-primary) !important; }
        html[data-app-theme="dark"] .right-area .main-content .product-cart-page .cart-remove { background:#4a2527 !important; color:#ffc0c0 !important; }
        html[data-app-theme="dark"] .right-area .main-content .product-cart-page .cart-paid-field { background:#3e2929 !important; color:var(--app-text) !important; border-color:#714244 !important; }
        html[data-app-theme="dark"] .right-area .main-content .product-cart-page .cart-summary-line.total strong { color:color-mix(in srgb,var(--app-primary) 45%,#fff) !important; }
        html[data-app-theme="dark"] .right-area .main-content .product-cart-page :is(.cart-empty a,.cart-primary-btn) { background:var(--app-primary) !important; color:var(--app-primary-contrast) !important; border-color:var(--app-primary) !important; }
        html[data-app-theme="dark"] .right-area .main-content .product-cart-page :is(.cart-empty a,.cart-primary-btn) * { color:var(--app-primary-contrast) !important; }
        html[data-app-theme="dark"] .right-area .main-content .product-cart-page .cart-anonymize-dialog :is(button:not(.cart-anonymize-confirm),.cart-anonymize-dialog-close) { background:#252b34 !important; color:var(--app-text) !important; border-color:var(--app-border) !important; }
        html[data-app-theme="dark"] .right-area .main-content .product-cart-page .cart-anonymize-confirm { background:var(--app-primary) !important; color:var(--app-primary-contrast) !important; border-color:var(--app-primary) !important; }

        html[data-app-theme="dark"] .right-area .main-content .customer-directory-page { --customer-red:var(--app-primary); --customer-ink:var(--app-text); --customer-muted:var(--app-muted); --customer-line:var(--app-border); color:var(--app-text) !important; }
        html[data-app-theme="dark"] .right-area .main-content .customer-directory-page :is(.customer-directory-card,.customer-directory-card-heading,.customer-directory-table-wrap) { background:var(--app-surface) !important; color:var(--app-text) !important; border-color:var(--app-border) !important; }
        html[data-app-theme="dark"] .right-area .main-content .customer-directory-page .customer-directory-card-icon { background:var(--app-primary-soft) !important; color:var(--app-primary) !important; }
        html[data-app-theme="dark"] .right-area .main-content .customer-directory-page .customer-secondary-action { background:#252b34 !important; color:var(--app-text) !important; border-color:var(--app-border) !important; }

        html[data-app-theme="dark"] .right-area .main-content .service-cases { --sc-ink:var(--app-text); --sc-muted:var(--app-muted); --sc-line:var(--app-border); --sc-soft:#252b34; --sc-brand:var(--app-primary); color:var(--app-text) !important; }
        html[data-app-theme="dark"] .right-area .main-content .service-cases :is(.sc-hero,.sc-metric,.sc-panel,.sc-detail-card,.sc-modal,.sc-assignee-results,.sc-selected-assignee,.sc-history-card,.sc-new-customer-fields,.sc-archive-confirm-note,.sc-support-form,.sc-security-note,.sc-inline-notice,.sc-case-tabs,.sc-response-suggestions,.sc-milestone-choices) { background:var(--app-surface) !important; color:var(--app-text) !important; border-color:var(--app-border) !important; }
        html[data-app-theme="dark"] .right-area .main-content .service-cases :is(.sc-panel-heading,.sc-modal-head,.sc-modal-foot,.sc-table thead th,.sc-case-tabs>button.is-active) { background:#252b34 !important; color:var(--app-text) !important; border-color:var(--app-border) !important; }
        html[data-app-theme="dark"] .right-area .main-content .service-cases :is(.sc-modal h1,.sc-modal h2,.sc-modal h3,.sc-panel-heading h2,.sc-card-heading h2,.sc-metric-copy strong,.sc-customer-name,.sc-table td,.sc-modal label) { color:var(--app-text) !important; }
        html[data-app-theme="dark"] .right-area .main-content .service-cases :is(.sc-panel-heading p,.sc-modal-head p,.sc-metric-copy small,.sc-metric-caption,.sc-customer-phone,.sc-no-customer,.sc-help) { color:var(--app-muted) !important; }
        html[data-app-theme="dark"] .right-area .main-content .service-cases :is(
            .sc-dashboard,.sc-metric,.sc-detail-card,.sc-type-breakdown,.sc-agent-breakdown,
            .sc-workflow-actions button,.sc-inline-form,.sc-plan-summary,.sc-resolution-banner,
            .sc-customer-results,.sc-customer-results button,.sc-selected-customer,
            .sc-product-option,.sc-new-customer-fields,.sc-support-form,.sc-security-note,
            .sc-notification-panel,.sc-notification-list article,.sc-rating-options label span,
            .sc-case-archived-label,.sc-archive-confirm-note,.sc-history-card
        ) { background:var(--app-surface) !important; color:var(--app-text) !important; border-color:var(--app-border) !important; }
        html[data-app-theme="dark"] .right-area .main-content .service-cases :is(
            .sc-dashboard-total,.sc-result-count,.sc-history-count,.sc-milestone-marker,
            .sc-status-transition span:first-child,.sc-status-transition strong,
            .sc-next-date,.sc-case-tab-count,.sc-attachments a,.sc-archive-action,
            .sc-soft-btn,.sc-icon-button,.sc-open-link,.sc-milestone-action
        ) { background:#303742 !important; color:var(--app-text) !important; border-color:var(--app-border) !important; }
        html[data-app-theme="dark"] .right-area .main-content .service-cases :is(
            .sc-eyebrow,.sc-case-link,.sc-section-title>span,.sc-satisfaction-score,
            .sc-milestone-action,.sc-notification-panel .material-icons
        ) { color:color-mix(in srgb,var(--app-primary) 45%,#fff) !important; }
        html[data-app-theme="dark"] .right-area .main-content .service-cases :is(
            .sc-metric-icon,.sc-section-title>span,.sc-avatar,.sc-agent-avatar,.sc-timeline-icon,
            .sc-satisfaction-score
        ) { background:var(--app-primary-soft) !important; border-color:var(--app-primary-border) !important; }
        html[data-app-theme="dark"] .right-area .main-content .service-cases .sc-case-tabs>button.is-active {
            background:var(--app-primary) !important; color:var(--app-primary-contrast) !important;
        }
        html[data-app-theme="dark"] .right-area .main-content .service-cases .sc-case-tabs>button.is-active * { color:var(--app-primary-contrast) !important; }
        html[data-app-theme="dark"] .right-area .main-content .service-cases :is(.sc-inline-notice,.sc-flash-warning,.sc-next-date.is-overdue) { background:#49391b !important; color:#f3d28b !important; border-color:#765e30 !important; }
        html[data-app-theme="dark"] .right-area .main-content .service-cases :is(.sc-flash,.sc-resolution-banner,.sc-selected-customer,.sc-milestone.is-complete .sc-milestone-marker) { background:#173b2c !important; color:#b7ebcb !important; border-color:#285a42 !important; }
        html[data-app-theme="dark"] .right-area .main-content .service-cases :is(.sc-priority-low,.sc-priority-normal,.sc-priority-high,.sc-priority-urgent,.sc-status-new,.sc-status-analysis,.sc-status-waiting,.sc-status-internal,.sc-status-support,.sc-status-follow,.sc-status-resolved,.sc-status-closed) { background:#303742 !important; color:#e2e7ed !important; border:1px solid var(--app-border) !important; }
        html[data-app-theme="dark"] .right-area .main-content .service-cases .sc-status-resolved,
        html[data-app-theme="dark"] .right-area .main-content .service-cases .sc-priority-normal { background:#173b2c !important; color:#b7ebcb !important; }
        html[data-app-theme="dark"] .right-area .main-content .service-cases .sc-priority-urgent { background:#4a2527 !important; color:#ffc0c0 !important; }
        html[data-app-theme="dark"] .right-area .main-content .service-cases .sc-row-overdue { background:#292821 !important; box-shadow:inset 3px 0 #b99042 !important; }
        html[data-app-theme="dark"] .right-area .main-content .service-cases .sc-overdue-date { color:#e6bf71 !important; }
        html[data-app-theme="dark"] .right-area .main-content .service-cases .sc-milestone.is-late .sc-milestone-marker { background:#432629 !important; }
        html[data-app-theme="dark"] .right-area .main-content .service-cases :is(.sc-metric-copy strong,.sc-type-row>div:first-child strong,.sc-selected-customer strong,.sc-workflow-actions button strong) { color:var(--app-text) !important; }
        html[data-app-theme="dark"] .right-area .main-content .service-cases :is(.sc-metric-copy small,.sc-metric-caption,.sc-type-row>div:first-child span,.sc-attachments a,.sc-security-note,.sc-notification-panel) { color:var(--app-muted) !important; }
        html[data-app-theme="dark"] .right-area .main-content .service-cases :is(.sc-workflow-actions button:hover,.sc-case-tabs>button:hover,.sc-archive-action:hover,.sc-product-option:has(input:checked),.sc-milestone-choices input:checked+span,.sc-activity-toggle input:checked+span) { background:var(--app-primary-soft) !important; color:var(--app-text) !important; border-color:var(--app-primary-border) !important; }
        html[data-app-theme="dark"] .right-area .main-content .service-cases .sc-modal :is(input,select,textarea,.form-control) { background:#171b21 !important; color:var(--app-text) !important; border-color:var(--app-border) !important; }
        html[data-app-theme="dark"] .right-area .main-content .service-cases :is(.sc-table td,.sc-customer-name,.sc-customer-phone,.sc-case-link,.sc-history-footer>small) { color:var(--app-text) !important; }
        html[data-app-theme="dark"] .right-area .main-content .service-cases .sc-table tbody tr:nth-child(even) { background:#252b34 !important; }
        html[data-app-theme="dark"] .right-area .main-content .service-cases .sc-product-list > span {
            background:#303742 !important; color:#e2e7ed !important; border-color:var(--app-border) !important;
        }

        html[data-app-theme="dark"] .right-area .main-content .loyalty-page { --loyalty-red:var(--app-primary); --loyalty-ink:var(--app-text); --loyalty-muted:var(--app-muted); --loyalty-border:var(--app-border); color:var(--app-text) !important; }
        html[data-app-theme="dark"] .right-area .main-content .loyalty-page :is(.loyalty-header,.loyalty-summary-card,.loyalty-rules-card,.loyalty-list-card,.loyalty-mobile-card,.loyalty-custom-segment,.loyalty-customer-suggestions,.loyalty-category-strip) { background:var(--app-surface) !important; color:var(--app-text) !important; border-color:var(--app-border) !important; }
        html[data-app-theme="dark"] .right-area .main-content .loyalty-page :is(.loyalty-table thead th,.loyalty-section-heading,.loyalty-filter-panel,.loyalty-customer-picker) { background:#252b34 !important; color:var(--app-text) !important; border-color:var(--app-border) !important; }
        html[data-app-theme="dark"] .right-area .main-content .loyalty-page :is(.loyalty-category-strip button,.loyalty-secondary-button,.loyalty-clear-selection) { background:#252b34 !important; color:var(--app-text) !important; border-color:var(--app-border) !important; }
        html[data-app-theme="dark"] .right-area .main-content .loyalty-page :is(.loyalty-category-strip button.is-selected,.loyalty-settings-button,.loyalty-primary-button) { background:var(--app-primary) !important; color:var(--app-primary-contrast) !important; border-color:var(--app-primary) !important; }
        html[data-app-theme="dark"] .right-area .main-content .loyalty-page :is(.category-vip,.category-faithful,.category-regular,.category-inactive,.category-new,.loyalty-category,.loyalty-mobile-segment) { background:#252b34 !important; color:var(--app-text) !important; border:1px solid var(--app-border) !important; }
        html[data-app-theme="dark"] .right-area .main-content .loyalty-page .loyalty-score-track { background:#353d49 !important; }
        html[data-app-theme="dark"] .right-area .main-content .loyalty-page :is(
            .loyalty-filters,.loyalty-performance-item,.loyalty-chart-column,.loyalty-settings-intro,
            .loyalty-repurchase,.loyalty-list-count,.loyalty-filter-footer,.loyalty-mobile-referral,
            .loyalty-mobile-stats,.loyalty-customer-searchbox,.loyalty-customer-picker,
            .loyalty-form-section,.loyalty-loading,.loyalty-empty
        ) { background:#252b34 !important; color:var(--app-text) !important; border-color:var(--app-border) !important; }
        html[data-app-theme="dark"] .right-area .main-content .loyalty-page :is(
            .loyalty-chart-column:hover,.loyalty-chart-column.is-selected,
            .loyalty-category-tile:hover,.loyalty-category-tile.is-selected
        ) { background:var(--app-primary-soft) !important; border-color:var(--app-primary-border) !important; }
        html[data-app-theme="dark"] .right-area .main-content .loyalty-page :is(
            .loyalty-summary-icon,.loyalty-avatar,.loyalty-customer-searchbox .material-icons
        ) { background:var(--app-primary-soft) !important; color:var(--app-primary) !important; border-color:var(--app-primary-border) !important; }
        html[data-app-theme="dark"] .right-area .main-content .loyalty-page :is(
            .loyalty-summary-card small,.loyalty-summary-card em,.loyalty-category-tile strong,
            .category-tile-top,.category-tile-share,.loyalty-performance-title,
            .loyalty-chart-value,.loyalty-chart-label,.loyalty-chart-count,
            .loyalty-chart-caption,.loyalty-mobile-stats small,.loyalty-mobile-referral small,
            .loyalty-mobile-name small,.loyalty-customer-suggestions small,.loyalty-help
        ) { color:var(--app-muted) !important; }
        html[data-app-theme="dark"] .right-area .main-content .loyalty-page :is(.loyalty-summary-card strong,.loyalty-category-tile strong,.loyalty-mobile-stats strong,.loyalty-mobile-referral strong) { color:var(--app-text) !important; }
        html[data-app-theme="dark"] .right-area .main-content .loyalty-page :is(.loyalty-search,.loyalty-customer-searchbox,.loyalty-customer-suggestions,.loyalty-customer-suggestions button) { background:#171b21 !important; color:var(--app-text) !important; border-color:var(--app-border) !important; }
        html[data-app-theme="dark"] .right-area .main-content .loyalty-page .loyalty-customer-suggestions button:hover { background:var(--app-primary-soft) !important; }
        html[data-app-theme="dark"] .right-area .main-content .loyalty-page :is(.category-vip,.category-tile-vip .loyalty-category) { background:#3d2e50 !important; color:#e2c8ff !important; }
        html[data-app-theme="dark"] .right-area .main-content .loyalty-page :is(.category-faithful,.category-tile-faithful .loyalty-category) { background:#203d30 !important; color:#a6dfbf !important; }
        html[data-app-theme="dark"] .right-area .main-content .loyalty-page :is(.category-regular,.category-tile-regular .loyalty-category) { background:#20364a !important; color:#acd4f1 !important; }
        html[data-app-theme="dark"] .right-area .main-content .loyalty-page :is(.category-inactive,.category-tile-inactive .loyalty-category) { background:#4a2527 !important; color:#ffc0c0 !important; }
        html[data-app-theme="dark"] .right-area .main-content .loyalty-page :is(.category-new,.category-tile-new .loyalty-category) { background:#49391b !important; color:#f3d28b !important; }
        html[data-app-theme="dark"] .right-area .main-content .loyalty-page .loyalty-segments>span { background:#303742 !important; color:#d8dee7 !important; border-color:var(--app-border) !important; }
        html[data-app-theme="dark"] .right-area .main-content .loyalty-page .loyalty-chart-stage { border-color:var(--app-border) !important; background:repeating-linear-gradient(to top,transparent 0,transparent calc(25% - 1px),#353d49 calc(25% - 1px),#353d49 25%) !important; }
        html[data-app-theme="dark"] .right-area .main-content .loyalty-page .loyalty-chart-column { border-color:var(--app-border) !important; }
        html[data-app-theme="dark"] .right-area .main-content .loyalty-page .loyalty-chart-column.is-selected .loyalty-chart-label { color:var(--app-text) !important; }

        html[data-app-theme="dark"] .right-area .main-content .legacy-users-page :is(.users-page-toolbar,.users-role-filter,.card,.modal-content) { background:var(--app-surface) !important; color:var(--app-text) !important; border-color:var(--app-border) !important; }
        html[data-app-theme="dark"] .right-area .main-content .legacy-users-page :is(.users-page-toolbar p,.users-role-filter label) { color:var(--app-muted) !important; }
        html[data-app-theme="dark"] .right-area .main-content .legacy-users-page .btn-dark { background:#343b46 !important; color:var(--app-text) !important; border-color:var(--app-border) !important; }

        .right-area .main-content .top-costumers-page { --tc-red:var(--app-primary); }
        .right-area .main-content .top-costumers-page :is(.top-costumers-apply,.top-costumers-back:hover) { background:var(--app-primary) !important; border-color:var(--app-primary) !important; color:var(--app-primary-contrast) !important; }
        .right-area .main-content .top-costumers-page .top-costumers-apply .material-icons { color:var(--app-primary-contrast) !important; }
        html[data-app-theme="dark"] .right-area .main-content .top-costumers-page { --tc-ink:var(--app-text); --tc-muted:var(--app-muted); --tc-line:var(--app-border); color:var(--app-text) !important; }
        html[data-app-theme="dark"] .right-area .main-content .top-costumers-page :is(.top-costumers-filters,.top-costumers-stat,.top-costumers-table-card,.top-costumers-back) {
            background:var(--app-surface) !important; color:var(--app-text) !important; border-color:var(--app-border) !important;
        }
        html[data-app-theme="dark"] .right-area .main-content .top-costumers-page .top-costumers-table-heading,
        html[data-app-theme="dark"] .right-area .main-content .top-costumers-page .top-costumers-table thead th { background:#252b34 !important; color:var(--app-muted) !important; border-color:var(--app-border) !important; }
        html[data-app-theme="dark"] .right-area .main-content .top-costumers-page :is(.top-costumers-heading h1,.top-costumers-filter-intro h2,.top-costumers-table-heading h2,.top-costumers-stat strong,.top-costumers-table tbody td,.top-costumers-name,.top-costumers-activity>span:nth-child(2) strong) { color:var(--app-text) !important; }
        html[data-app-theme="dark"] .right-area .main-content .top-costumers-page :is(.top-costumers-eyebrow,.top-costumers-heading p,.top-costumers-filter-intro p,.top-costumers-field>span,.top-costumers-field>span small,.top-costumers-stat small,.top-costumers-table-heading p,.top-costumers-empty,.top-costumers-table tbody td:nth-child(4),.top-costumers-table tbody td:nth-child(5)) { color:var(--app-muted) !important; }
        html[data-app-theme="dark"] .right-area .main-content .top-costumers-page :is(.top-costumers-filter-icon,.top-costumers-stat-icon,.top-costumers-period,.top-costumers-rank,.top-costumers-view) { background:var(--app-primary-soft) !important; color:var(--app-primary) !important; border-color:var(--app-primary-border) !important; }
        html[data-app-theme="dark"] .right-area .main-content .top-costumers-page .top-costumers-rank-first,
        html[data-app-theme="dark"] .right-area .main-content .top-costumers-page .top-costumers-best { background:#49391b !important; color:#f3d28b !important; }
        html[data-app-theme="dark"] .right-area .main-content .top-costumers-page .top-costumers-amount { color:#9be0b9 !important; }
        html[data-app-theme="dark"] .right-area .main-content .top-costumers-page .top-costumers-phone { color:color-mix(in srgb,var(--app-primary) 45%,#fff) !important; }
        html[data-app-theme="dark"] .right-area .main-content .top-costumers-page .top-costumers-table tbody tr,
        html[data-app-theme="dark"] .right-area .main-content .top-costumers-page .top-costumers-table tbody tr:hover { background:var(--app-surface) !important; }
        html[data-app-theme="dark"] .right-area .main-content .top-costumers-page .top-costumers-table tbody tr:nth-child(even) { background:#252b34 !important; }
        html[data-app-theme="dark"] .right-area .main-content .top-costumers-page :is(.top-costumers-apply,.dataTables_wrapper .dt-buttons .dt-button.current,.dataTables_wrapper .dataTables_paginate .paginate_button.current) { background:var(--app-primary) !important; border-color:var(--app-primary) !important; color:var(--app-primary-contrast) !important; }
        html[data-app-theme="dark"] .right-area .main-content .top-costumers-page .top-costumers-back:not(:hover),
        html[data-app-theme="dark"] .right-area .main-content .top-costumers-page .top-costumers-view { background:#252b34 !important; color:var(--app-text) !important; border-color:var(--app-border) !important; }
        html[data-app-theme="dark"] .right-area .main-content .top-costumers-page .dataTables_wrapper :is(label,.dataTables_info,.dataTables_length,.dataTables_filter) { color:var(--app-muted) !important; }
        html[data-app-theme="dark"] .right-area .main-content .top-costumers-page .dataTables_wrapper .dt-buttons .dt-button { background:#252b34 !important; color:var(--app-text) !important; border-color:var(--app-border) !important; }
        html[data-app-theme="dark"] .right-area .main-content .top-costumers-page .dataTables_wrapper :is(.dataTables_paginate .paginate_button,.dataTables_paginate .paginate_button:hover) { background:#252b34 !important; color:var(--app-text) !important; border-color:var(--app-border) !important; }
        html[data-app-theme="dark"] .right-area .main-content .top-costumers-page .dataTables_wrapper :is(.dataTables_filter input,.dataTables_length select) { background:#171b21 !important; color:var(--app-text) !important; border-color:var(--app-border) !important; }

        /* Les libellés de statut gardent leur sens visuel sur fond sombre. */
        html[data-app-theme="dark"] .right-area .main-content .stock-control-page .stock-balance-state.available,
        html[data-app-theme="dark"] .right-area .main-content .stock-control-page .stock-count-status.status-completed { background:#173b2c !important; color:#9be0b9 !important; filter:none !important; }
        html[data-app-theme="dark"] .right-area .main-content .stock-control-page :is(.stock-balance-state.low,.stock-count-status.status-in_progress,.receipt-status-pill.pending,.receipt-status-pill.partial) { background:#49391b !important; color:#f3d28b !important; filter:none !important; }
        html[data-app-theme="dark"] .right-area .main-content .stock-control-page :is(.stock-balance-state.out,.stock-balance-state.blocked,.stock-count-status.status-rejected) { background:#4a2527 !important; color:#ffc0c0 !important; filter:none !important; }
        html[data-app-theme="dark"] .right-area .main-content .stock-control-page :is(.stock-control-state.is-active,.receipt-status-pill.validated,.stock-count-status.status-completed) { background:#173b2c !important; color:#9be0b9 !important; filter:none !important; }
        html[data-app-theme="dark"] .right-area .main-content .stock-control-page :is(.stock-control-state.is-inactive,.receipt-status-pill.neutral,.stock-count-status) { background:#303742 !important; color:#c2cad4 !important; filter:none !important; }
        html[data-app-theme="dark"] .right-area .main-content .service-cases :is(.sc-status-new,.sc-status-analysis,.sc-status-waiting,.sc-status-internal,.sc-status-support,.sc-status-follow,.sc-status-resolved,.sc-status-closed,.sc-priority-low,.sc-priority-normal,.sc-priority-high,.sc-priority-urgent) { background:#303742 !important; color:#e2e7ed !important; }
        html[data-app-theme="dark"] .right-area .main-content .service-cases :is(.sc-status-transition span:first-child,.sc-status-transition strong) { background:#303742 !important; color:#e2e7ed !important; }
        @media screen {
            html[data-app-theme="dark"] .right-area .main-content .product-audit-page {
                --audit-red:var(--app-primary); --audit-ink:var(--app-text); --audit-muted:var(--app-muted); --audit-border:var(--app-border);
                color:var(--app-text) !important;
            }
            html[data-app-theme="dark"] .right-area .main-content .product-audit-page :is(.audit-card,.audit-kpi) {
                background:var(--app-surface) !important; color:var(--app-text) !important; border-color:var(--app-border) !important;
            }
            html[data-app-theme="dark"] .right-area .main-content .product-audit-page :is(.audit-card-heading,.audit-results-heading,.audit-table th,.audit-table tfoot td) {
                background:#252b34 !important; color:var(--app-text) !important; border-color:var(--app-border) !important;
            }
            html[data-app-theme="dark"] .right-area .main-content .product-audit-page :is(.audit-heading-icon,.audit-kpi-icon,.audit-period-chip,.audit-results-count,.audit-qty) {
                background:var(--app-primary-soft) !important; color:var(--app-text) !important; border-color:var(--app-primary-border) !important;
            }
            html[data-app-theme="dark"] .right-area .main-content .product-audit-page :is(.audit-hero h1,.audit-card-heading h2,.audit-kpi strong,.audit-results-heading h2,.audit-table td,.audit-product-name,.audit-table tfoot td) { color:var(--app-text) !important; }
            html[data-app-theme="dark"] .right-area .main-content .product-audit-page :is(.audit-eyebrow,.audit-hero p,.audit-card-heading p,.audit-kpi-top,.audit-kpi small,.audit-results-heading p,.audit-empty,.audit-pagination p) { color:var(--app-muted) !important; }
            html[data-app-theme="dark"] .right-area .main-content .product-audit-page .audit-amount { color:#9be0b9 !important; }
            html[data-app-theme="dark"] .right-area .main-content .product-audit-page .audit-table tbody tr,
            html[data-app-theme="dark"] .right-area .main-content .product-audit-page .audit-table tbody tr:hover { background:var(--app-surface) !important; border-color:var(--app-border) !important; }
            html[data-app-theme="dark"] .right-area .main-content .product-audit-page .audit-table tbody tr:nth-child(even) { background:#252b34 !important; }
            html[data-app-theme="dark"] .right-area .main-content .product-audit-page .audit-pagination .page-link { background:#252b34 !important; color:var(--app-text) !important; border-color:var(--app-border) !important; }
            html[data-app-theme="dark"] .right-area .main-content .product-audit-page .audit-pagination .page-item.active .page-link { background:var(--app-primary) !important; color:var(--app-primary-contrast) !important; border-color:var(--app-primary) !important; }
            html[data-app-theme="dark"] .right-area .main-content .product-audit-page .audit-print { background:var(--app-primary) !important; color:var(--app-primary-contrast) !important; border-color:var(--app-primary) !important; }
            html[data-app-theme="dark"] .right-area .main-content .product-audit-page .audit-print * { color:var(--app-primary-contrast) !important; }
        }
    </style>
</body>


</html>
