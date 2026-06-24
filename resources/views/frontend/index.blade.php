<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $websiteSeo->meta_title ?? 'TDIIBT - Twinehs Divine Integrated Institute of Business and Technology' }}</title>
    <meta name="description" content="{{ $websiteSeo->meta_description ?? 'Twinehs Divine Integrated Institute of Business and Technology (TDIIBT) - Quality Education, Practical Skills, Professional Excellence. Fully online programs in Uganda.' }}">
    <meta name="keywords" content="{{ $websiteSeo->meta_keywords ?? 'TDIIBT, online education Uganda, business technology institute, online degrees Uganda, Kampala institute' }}">
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