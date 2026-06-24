@extends('admin.navigation')

@section('content')
    @php($routePrefix = 'admin.website')
    @include('website_management.panel')
@endsection
