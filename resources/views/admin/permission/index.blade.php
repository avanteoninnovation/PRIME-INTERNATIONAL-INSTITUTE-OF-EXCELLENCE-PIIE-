@extends('admin.navigation')
   
@section('content')
<div class="mainSection-title">
    <div class="row">
      <div class="col-12">
        <div
          class="d-flex justify-content-between align-items-center flex-wrap gr-15"
        >
          <div class="d-flex flex-column">
            <h4>{{ get_phrase('Assigned Permission For Teacher') }}</h4>
            <ul class="d-flex align-items-center eBreadcrumb-2">
              <li><a href="#">{{ get_phrase('Home') }}</a></li>
              <li><a href="#">{{ get_phrase('Users') }}</a></li>
              <li><a href="#">{{ get_phrase('Teacher Permission') }}</a></li>
            </ul>
          </div>
        </div>
      </div>
    </div>
</div>
<!-- Start Teacher Permission area -->
<div class="row">
    <div class="col-10 offset-md-1">
        <div class="eSection-wrap-2">
        	<form method="GET" class="d-block ajaxForm">
                <div class="row mt-3">

                    <div class="col-md-2"></div>

                    <div class="col-md-3">
                        <select name="class_id" id="class_id" class="form-select eForm-select eChoice-multiple-with-remove" required onchange="classWiseSection(this.value)">
                            <option value="">{{ get_phrase('Select a class') }}</option>
                            @foreach($classes as $class)
                                <option value="{{ $class->id }}" {{ (string)$class->id === (string)$default_class_id ? 'selected' : '' }}>{{ $class->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <select name="section_id" id="section_id" class="form-select eForm-select" required >
                            @if(count($sections) > 0)
                                <option value="">{{ get_phrase('Select a section') }}</option>
                                @foreach($sections as $section)
                                    <option value="{{ $section->id }}" {{ (string)$section->id === (string)$default_section_id ? 'selected' : '' }}>{{ $section->name }}</option>
                                @endforeach
                            @else
                                <option value="">{{ get_phrase('First select a class') }}</option>
                            @endif
                        </select>
                    </div>

                    <div class="col-md-3">
                        <button class="eBtn eBtn btn-secondary" type="button" id = "filter_routine" onclick="filter()">{{ get_phrase('Filter') }}</button>
                    </div>

                    <div class="card-body permission_content">
                        @if(!empty($default_class_id) && !empty($default_section_id))
                            @include('admin.permission.list', ['teachers' => $teachers, 'class_id' => $default_class_id, 'section_id' => $default_section_id])
                        @else
                            <div class="empty_box center">
                                <img class="mb-3" width="150px" src="{{ asset('assets/images/empty_box.png') }}" />
                            </div>
                        @endif
                    </div>

                </div>
            </form>
        </div>
    </div>
</div>
<!-- End Teacher Permission area -->

@if($programmes->count() > 0)
<!-- Start Teacher Programme Permission area -->
<div class="row mt-4">
    <div class="col-10 offset-md-1">
        <div class="eSection-wrap-2">
            <h4 class="mb-3">{{ get_phrase('Assigned Permission For Teacher (Programme-Based)') }}</h4>
            <form method="GET" class="d-block ajaxForm">
                <div class="row mt-3">

                    <div class="col-md-2"></div>

                    <div class="col-md-6">
                        <select name="programme_id" id="programme_id" class="form-select eForm-select eChoice-multiple-with-remove">
                            @foreach($programmes as $programme)
                                <option value="{{ $programme->id }}" {{ (string)$programme->id === (string)$default_programme_id ? 'selected' : '' }}>{{ $programme->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <button class="eBtn eBtn btn-secondary" type="button" onclick="filterProgramme()">{{ get_phrase('Filter') }}</button>
                    </div>

                    <div class="card-body programme_permission_content">
                        @if(!empty($default_programme_id))
                            @include('admin.permission.programme_list', ['teachers' => $teachers, 'programme_id' => $default_programme_id])
                        @else
                            <div class="empty_box center">
                                <img class="mb-3" width="150px" src="{{ asset('assets/images/empty_box.png') }}" />
                            </div>
                        @endif
                    </div>

                </div>
            </form>
        </div>
    </div>
</div>
<!-- End Teacher Programme Permission area -->
<script type="text/javascript">
    "use strict";

    function filterProgramme(){
        var programme_id = $('#programme_id').val();

        if(programme_id == ""){
            toastr.error('{{ get_phrase('Please select a programme') }}');
            return;
        }

        let url = "{{ route('admin.teacher.programme_permission_list', ['programme_id' => ':value']) }}";
        url = url.replace(':value', programme_id);

        $.ajax({
            url: url,
            success: function(response){
                $('.programme_permission_content').html(response);
            }
        });
    }
</script>
@endif

<script type="text/javascript">

  "use strict";


    function classWiseSection(classId, callback) {
        if (classId == "") {
            $('#section_id').html('<option value="">{{ get_phrase('First select a class') }}</option>');
            if (typeof callback === 'function') callback(false);
            return;
        }

        let url = "{{ route('class_wise_sections', ['id' => ":classId"]) }}";
        url = url.replace(":classId", classId);

        $.ajax({
            url: url,
            success: function(response){
                $('#section_id').html(response);

                // Auto-select first available section so filter can run reliably.
                var firstSection = $('<select>' + response + '</select>').find('option[value!=""]').first().val() || '';
                $('#section_id').val(firstSection);

                if (typeof callback === 'function') callback(firstSection);
            },
            error: function() {
                toastr.error('{{ get_phrase('Unable to load sections') }}');
                if (typeof callback === 'function') callback('');
            }
        });
    }

    function filter(){
        var class_id = $('#class_id').val();
        var section_id = $('#section_id').val();

        if(class_id == ""){
            toastr.error('{{ get_phrase('Please select a class and section') }}');
            return;
        }

        const loadPermissionList = function(selectedSectionId) {
            if (selectedSectionId == "") {
                toastr.error('{{ get_phrase('No section found for this class') }}');
                return;
            }

            var value = class_id + '-' + selectedSectionId;
            let url = "{{ route('admin.teacher.permission_list', ['filter' => ":value"]) }}";
            url = url.replace(":value", value);

            $.ajax({
                url: url,
                success: function(response){
                    $('.permission_content').html(response);
                }
            });
        };

        if (section_id == "") {
            classWiseSection(class_id, function(selectedSectionId) {
                if (selectedSectionId != "") {
                    loadPermissionList(selectedSectionId);
                } else {
                    toastr.error('{{ get_phrase('No section found for this class') }}');
                }
            });
        } else {
            loadPermissionList(section_id);
        }
    }

</script>

@endsection
