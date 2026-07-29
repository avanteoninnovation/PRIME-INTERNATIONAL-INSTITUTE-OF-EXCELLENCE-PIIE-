<?php

use App\Models\TeacherProgrammeAssignment;

?>

<!-- Table -->
<div class="table-responsive">
	<table class="table eTable eTable-2">
		<thead>
		  <tr>
		    <th scope="col">#</th>
		    <th scope="col">{{ get_phrase('Teacher') }}</th>
		    <th scope="col">{{ get_phrase('Marks') }}</th>
		    <th scope="col">{{ get_phrase('Attendance') }}</th>
		</thead>
		<tbody>
		    @foreach($teachers as $teacher)
		    <?php
		    	$permission = TeacherProgrammeAssignment::where('programme_id', $programme_id)
					->where('teacher_id', $teacher->id)
					->where('school_id', auth()->user()->school_id)
					->first();

				if(empty($permission)){
					$permission['marks'] = 0;
					$permission['attendance'] = 0;
				}
		    ?>
		      <tr>
		        <th scope="row">
		          <p class="row-number">{{ $loop->index + 1 }}</p>
		        </th>
		        <td>
					<div class="dAdmin_info_name">
						<p><span>{{ $teacher->name }}</span></p>
					</div>
		        </td>
		        <td>
		        	<div class="eSwitches">
		        		<div class="form-check form-switch">
                          <input class="form-check-input form-switch-large" type="checkbox" value="{{ $permission['marks'] }}" role="switch" id="prog{{ $teacher['id'].'1' }}" onchange="toggleProgrammePermission(this.id, 'marks', '{{ $teacher['id'] }}')" {{ $permission['marks'] == 1 ? 'checked':'' }} />
                        </div>
		        	</div>
		        </td>
		        <td>
		        	<div class="eSwitches">
		        		<div class="form-check form-switch">
                          <input class="form-check-input form-switch-large" type="checkbox" value="{{ $permission['attendance'] }}" role="switch" id="prog{{ $teacher['id'].'3' }}" onchange="toggleProgrammePermission(this.id, 'attendance', '{{ $teacher['id'] }}')" {{ $permission['attendance'] == 1 ? 'checked':'' }} />
                        </div>
		        	</div>
		        </td>
		      </tr>
		    @endforeach
		</tbody>
	</table>
</div>

<script type="text/javascript">

  	"use strict";

    function toggleProgrammePermission(checkbox_id, column_name, teacher_id){

        var value = $('#'+checkbox_id).prop('checked') ? 1 : 0;
        var programme_id = $('#programme_id').val();

        let url = "{{ route('admin.teacher.modify_programme_permission') }}";

        $.ajax({
            url: url,
            headers: {
            	'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            data: {programme_id : programme_id, teacher_id : teacher_id, column_name : column_name, value : value},
            success: function(response){
                toastr.success('{{ get_phrase('Permission updated successfully.') }}');
            }
        });

    }
</script>
