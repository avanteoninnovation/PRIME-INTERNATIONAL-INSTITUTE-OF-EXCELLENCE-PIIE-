@extends('accountant.navigation')
@section('content')
<div class="mainSection-title"><div class="row"><div class="col-12">
    <div class="d-flex justify-content-between align-items-center flex-wrap gr-15">
        <div class="d-flex flex-column">
            <h4>{{ get_phrase('Sync Invoices') }}</h4>
            <ul class="d-flex align-items-center eBreadcrumb-2">
                <li><a href="{{ route('accountant.dashboard') }}">{{ get_phrase('Home') }}</a></li>
                <li><a href="{{ route('accountant.fee_manager.list') }}">{{ get_phrase('Fee Manager') }}</a></li>
                <li><a href="#">{{ get_phrase('Sync Invoices') }}</a></li>
            </ul>
        </div>
    </div>
</div></div></div>

@if(session('message'))<div class="alert alert-success">{{ session('message') }}</div>@endif
@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
@endif

<div class="row">
    <div class="col-12">
        <div class="eSection-wrap">
            <p class="text-muted">
                {{ get_phrase('Generates missing fee invoices for students who never got one — either admitted before a Fee Structure existed for their class/programme, or added through a path that skips auto-invoicing. Reads the mandatory Fee Structure rows for the selected class or programme and creates one invoice per fee a student does not already have for the running session. Safe to run more than once: existing invoices are never duplicated or changed.') }}
            </p>

            <form method="POST" action="{{ route('accountant.fee_manager.sync.generate') }}" class="row g-3">
                @csrf
                <div class="col-md-3">
                    <label class="eForm-label">{{ get_phrase('Target') }}</label>
                    <select name="target_type" id="target_type" class="form-select eForm-select" required>
                        <option value="class">{{ get_phrase('Class') }}</option>
                        <option value="programme">{{ get_phrase('Programme') }}</option>
                    </select>
                </div>
                <div class="col-md-5" id="class_field">
                    <label class="eForm-label">{{ get_phrase('Class') }}</label>
                    <select name="class_id" class="form-select eForm-select">
                        <option value="">{{ get_phrase('Select a class') }}</option>
                        @foreach($classes as $class)
                            <option value="{{ $class->id }}">{{ $class->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-5" id="programme_field" style="display:none;">
                    <label class="eForm-label">{{ get_phrase('Programme') }}</label>
                    <select name="programme_id" class="form-select eForm-select">
                        <option value="">{{ get_phrase('Select a programme') }}</option>
                        @foreach($programmes as $programme)
                            <option value="{{ $programme->id }}">{{ $programme->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="eBtn eBtn-primary w-100">{{ get_phrase('Sync') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    "use strict";
    document.addEventListener('DOMContentLoaded', function () {
        var targetType = document.getElementById('target_type');
        var classField = document.getElementById('class_field');
        var programmeField = document.getElementById('programme_field');
        function sync() {
            var isClass = targetType.value === 'class';
            classField.style.display = isClass ? '' : 'none';
            programmeField.style.display = isClass ? 'none' : '';
        }
        targetType.addEventListener('change', sync);
        sync();
    });
</script>
@endsection
