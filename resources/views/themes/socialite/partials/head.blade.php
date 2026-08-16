
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="color-scheme" content="dark">

    <!-- Favicon -->
    @include('partials.favicon')

    <!-- SEO meta -->
    @include('partials.seo-meta')

    <!-- HNT theme lock: ignore browser/system dark-mode toggles and keep the app palette stable. -->
    <script>
        (function () {
            try {
                window.localStorage.setItem('theme', 'hnt');
            } catch (error) {}
            document.documentElement.classList.remove('dark');
            document.documentElement.dataset.hntTheme = 'locked';
        })();
    </script>
   
    <!-- css files -->
    <link rel="stylesheet" href="/assets/socialite/css/tailwind.css">
    <link rel="stylesheet" href="/assets/vikinger/fonts/phosphor/regular/style.css?v=482">
    <link rel="stylesheet" href="/assets/socialite/css/style.css?v=440">  
    <link rel="stylesheet" href="/assets/socialite/css/hnt-app-palette.css?v=586">
    <link rel="stylesheet" href="/assets/hnt/crowns/crowns-cosmetics.css?v=571">
    
    <!-- google font -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@200;300;400;500;600;700;800&display=swap" rel="stylesheet">
 
