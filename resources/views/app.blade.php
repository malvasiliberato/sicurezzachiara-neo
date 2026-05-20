<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title inertia>SicurezzaChiara</title>
    <meta name="description"
        content="SicurezzaChiara aiuta i consulenti a configurare aziende, leggere il profilo rischio e governare misure, scadenze e DVR light.">
    <meta name="keywords"
        content="SicurezzaChiara, sicurezza sul lavoro, rischio aziendale, DPI, formazione, visite mediche, DVR light">
    <meta name="author" content="SicurezzaChiara">

    <!-- Social Media Meta Tags -->
    <meta property="og:title" content="SicurezzaChiara">
    <meta property="og:description"
        content="Piattaforma per il governo continuo del rischio aziendale.">
    <meta property="og:image" content="URL to the template's logo or featured image">
    <meta property="og:url" content="URL to the template's webpage">
    <meta name="twitter:card" content="summary_large_image">

    <!-- App favicon -->
    <link rel="shortcut icon" href="{{ URL::asset('image/favicon.ico') }}">

    <!-- Scripts -->
    @routes
    @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
    @inertiaHead
</head>

<body>
    @inertia
</body>

</html>
