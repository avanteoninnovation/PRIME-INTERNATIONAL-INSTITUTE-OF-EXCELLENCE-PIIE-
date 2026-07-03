@extends('admin.navigation')

@section('content')
@php
    $schoolId = auth()->user()->school_id;
    $totalAssets = DB::table('assets')->where('school_id', $schoolId)->count();
    $availableAssets = DB::table('assets')->where('school_id', $schoolId)->where('status', 'available')->count();
    $allocatedAssets = DB::table('assets')->where('school_id', $schoolId)->where('status', 'allocated')->count();
    $categories = DB::table('asset_categories')->where('school_id', $schoolId)->count();
@endphp

<div class="mainSection-title">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center flex-wrap gr-15">
                <div class="d-flex flex-column">
                    <h4 class="title">{{ get_phrase('Store Keeper Dashboard') }}</h4>
                    <ul class="breadcrumb-link">
                        <li><a href="{{ route('admin.dashboard') }}">{{ get_phrase('Dashboard') }}</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mt-1">
    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-circle bg-primary bg-opacity-10 p-3">
                    <i class="bi bi-archive-fill fs-3 text-primary"></i>
                </div>
                <div>
                    <div class="fs-4 fw-bold">{{ $totalAssets }}</div>
                    <div class="text-muted small">{{ get_phrase('Total Assets') }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-circle bg-success bg-opacity-10 p-3">
                    <i class="bi bi-check-circle-fill fs-3 text-success"></i>
                </div>
                <div>
                    <div class="fs-4 fw-bold">{{ $availableAssets }}</div>
                    <div class="text-muted small">{{ get_phrase('Available') }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-circle bg-warning bg-opacity-10 p-3">
                    <i class="bi bi-arrow-right-circle-fill fs-3 text-warning"></i>
                </div>
                <div>
                    <div class="fs-4 fw-bold">{{ $allocatedAssets }}</div>
                    <div class="text-muted small">{{ get_phrase('Allocated') }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-circle bg-info bg-opacity-10 p-3">
                    <i class="bi bi-tags-fill fs-3 text-info"></i>
                </div>
                <div>
                    <div class="fs-4 fw-bold">{{ $categories }}</div>
                    <div class="text-muted small">{{ get_phrase('Categories') }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mt-2">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">{{ get_phrase('Quick Actions') }}</div>
            <div class="card-body">
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('admin.assets.index') }}" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-box-seam me-1"></i>{{ get_phrase('Manage Assets') }}
                    </a>
                    <a href="{{ route('admin.asset_categories.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-tags me-1"></i>{{ get_phrase('Categories') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
