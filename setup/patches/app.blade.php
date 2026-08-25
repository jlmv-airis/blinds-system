<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" style="overflow-y: auto;">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="description" content= "Red Lanson Shades" />
        <meta name="robots" content= "index, follow">
        <link rel="shortcut icon" href="/img/icon.png" />
        <link rel="stylesheet" href="/v/0.3.16/css/app.css"/>
        <link rel="stylesheet" href="/v/0.3.16/css/materialdesignicons.css"/>

        <meta http-equiv="Expires" content="0">
        <meta http-equiv="Last-Modified" content="0">
        <meta http-equiv="Cache-Control" content="no-cache, mustrevalidate">
        <meta http-equiv="Pragma" content="no-cache">

        <title>Lanson Shades</title>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100;300;400;500;600;700&display=swap" rel="stylesheet">


        <style>
            * {
                text-transform: none !important;
            }
            #app {
                font-family: 'Inter', sans-serif;
                width: 100% !important;
            }
            .imgLogo img {
                max-width: 220px !important;
                height: auto !important;
            }
        </style>
    </head>
    <body>
        <div id="app">
            <App/>
        </div>
    </body>
    <script src="{{ mix('v/0.3.16/js/app.js') }}"  ></script>
</html>