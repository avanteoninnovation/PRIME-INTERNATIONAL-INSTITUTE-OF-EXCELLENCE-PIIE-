<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $websiteSeo->meta_title ?? 'PIIE - Prime International Institute of Excellence' }}</title>
    <meta name="description" content="{{ $websiteSeo->meta_description ?? 'Prime International Institute of Excellence (PIIE) - internationally benchmarked higher education through flexible, technology-enabled learning.' }}">
    <meta name="keywords" content="{{ $websiteSeo->meta_keywords ?? 'PIIE, Prime International Institute of Excellence, ODeL Uganda, higher education Uganda' }}">
    @if(!empty($websiteSeo) && !empty($websiteSeo->canonical_url))
        <link rel="canonical" href="{{ $websiteSeo->canonical_url }}">
    @endif

    @include('frontend.include_top')

</head>

<body data-bs-spy="scroll" data-bs-target=".header-area" data-bs-offset="50" tabindex="0">

    @yield('content')

    @include('external_plugin')
    
    @include('frontend.include_buttom')
    
</body>
</html>