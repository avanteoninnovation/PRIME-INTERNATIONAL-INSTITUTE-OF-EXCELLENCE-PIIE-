<style>
    .notice-scroll-wrapper {
        max-height: 600px;
        overflow-y: auto;
    }

    /* Card compact */
    .notice-scroll-wrapper .card {
        margin-bottom: 10px;
        margin-top: 10px;
    }

    .notice-scroll-wrapper .card-body {
        padding: 20px 16px;
    }

    .notice-scroll-wrapper h2 {
        font-size: 16px;
        margin-bottom: 4px;
    }

    .notice-scroll-wrapper p {
        font-size: 13px;
        line-height: 1.4;
        margin-bottom: 6px;
    }

    .notice-scroll-wrapper img {
        max-height: 180px;
        object-fit: cover;
    }
</style>

@extends('teacher.navigation')

@section('content')
    <div class="mainSection-title">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center flex-wrap gr-15">
                    <div class="d-flex flex-column">
                        <h4>{{ get_phrase('Notice') }}</h4>
                        <ul class="d-flex align-items-center eBreadcrumb-2">
                            <li><a href="#">{{ get_phrase('Home') }}</a></li>
                            <li><a href="#">{{ get_phrase('Back Office') }}</a></li>
                            <li><a href="#">{{ get_phrase('Club') }}</a></li>
                        </ul>
                    </div>
                    <div class="export-btn-area">
                        <a href="javascript:;" class="export_btn"
                            onclick="rightModal('{{ route('teacher.club.notice.create', $club->id) }}', '{{ get_phrase('Create Notice') }}')">
                            <i class="bi bi-plus"></i>{{ get_phrase('Create Notice') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="notice-scroll-wrapper">
        @foreach ($notices as $notice)
            <div class="card mb-3">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2">

                        {{-- School / teacher Image --}}
                        <div class="me-2">
                            @php
                                $school = App\Models\School::find(auth()->user()->school_id);
                            @endphp

                            <img src="{{ $school && $school->school_logo
                                ? asset('assets/uploads/school_logo/' . $school->school_logo)
                                : asset('assets/images/id_logo.png') }}"
                                width="36" height="36" class="rounded-circle object-fit-cover">
                        </div>

                        <div class="d-flex flex-column lh-sm">
                            <strong class="mb-0" style="font-size: 14px;">
                                {{ optional($notice->creator())->name }}
                            </strong>
                            <small class="text-muted" style="font-size: 11px;">
                                {{ \Carbon\Carbon::parse($notice->notice_date)->format('d M Y') }}
                            </small>
                        </div>

                        <div class="ms-auto">
                            <div class="teacherTable-action">
                                <button type="button" class="eBtn eBtn-black dropdown-toggle table-action-btn-2"
                                    data-bs-toggle="dropdown">
                                    {{ get_phrase('Actions') }}
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <a class="dropdown-item" href="javascript:;"
                                            onclick="rightModal('{{ route('teacher.club.notice.edit', $notice->id) }}','{{ get_phrase('Edit Notice') }}')">
                                            {{ get_phrase('Edit') }}
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="javascript:;"
                                            onclick="confirmModal('{{ route('teacher.club.notice.delete', $notice->id) }}','undefined')">
                                            {{ get_phrase('Delete') }}
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    {{-- <span class=" {{ $notice->status ? 'bg-success' : 'bg-danger' }}">
                        {{ $notice->status ? 'Active' : 'Inactive' }}
                    </span> --}}

                    <h2>{{ $notice->title }}</h2>
                    <p>{{ $notice->description }}</p>

                    @if ($notice->image)
                        <img src="{{ asset('assets/uploads/club/' . $notice->image) }}" class="w-100 rounded mb-2"
                            style="max-height:20%; object-fit: 100%;">
                    @endif
                </div>
            </div>
        @endforeach
    </div>
@endsection
