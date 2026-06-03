<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="stylesheet" href="{{ asset('css/public_style.css') }}">
    <title>@yield('title', 'Frequenters')</title>
</head>
<body>
    <div class="video-container">
        <video autoplay muted loop playsinline>
            <source src="{{ asset('media/frequenters_background.mp4') }}" type="video/mp4">
            Your browser does not support the video tag.
        </video>
        <div class="overlay"></div>
    </div>
    <main>
        @yield('content')
    </main>
</body>
</html>
