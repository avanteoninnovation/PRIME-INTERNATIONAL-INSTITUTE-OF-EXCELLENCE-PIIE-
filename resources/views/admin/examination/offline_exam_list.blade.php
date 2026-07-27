@extends('admin.navigation')

@section('content')


<style>
    .hide {
        display: none;
    }
</style>

<div class="mainSection-title">
    <div class="row">
      <div class="col-12">
        <div
          class="d-flex justify-content-between align-items-center flex-wrap gr-15"
        >
          <div class="d-flex flex-column">
            <h4>{{ get_phrase('Offline Exam') }}</h4>
            <ul class="d-flex align-items-center eBreadcrumb-2">
              <li><a href="#">{{ get_phrase('Home') }}</a></li>
              <li><a href="#">{{ get_phrase('Examination') }}</a></li>
              <li><a href="#">{{ get_phrase('Offline Exam') }}</a></li>
            </ul>
          </div>
          <div class="export-btn-area">
            <a href="javascript:;" class="export_btn" onclick="rightModal('{{ route('admin.offline_exam.open_modal') }}', '{{ get_phrase('Create Exam') }}')">{{ get_phrase('Add Exam') }}</a>
          </div>
        </div>
      </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="eSection-wrap">
            <div class="row mt-3">
                <div class="col-md-3"></div>
                <div class="col-md-4">
                    <select name="class_id" id="class_id" class="form-select eForm-select eChoice-multiple-with-remove" required>
                        <option value="">{{ get_phrase('Select a class') }}</option>
                        @foreach($classes as $class)
                            <option value="{{ $class->id }}">{{ $class->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button class="eBtn eBtn btn-secondary" onclick="filter_class()" >{{ get_phrase('Filter') }}</button>
                </div>
                <div class="col-md-1"></div>
                @if(count($exams) > 0)
                <div class="col-md-2">
                    <button class="eBtn-3 float-end" type="button" id="download-button">
                        <span class="pr-10">
                          <svg xmlns="http://www.w3.org/2000/svg" width="12.31" height="10.77" viewBox="0 0 10.771 12.31">
                            <path id="arrow-right-from-bracket-solid" d="M3.847,1.539H2.308a.769.769,0,0,0-.769.769V8.463a.769.769,0,0,0,.769.769H3.847a.769.769,0,0,1,0,1.539H2.308A2.308,2.308,0,0,1,0,8.463V2.308A2.308,2.308,0,0,1,2.308,0H3.847a.769.769,0,1,1,0,1.539Zm8.237,4.39L9.007,9.007A.769.769,0,0,1,7.919,7.919L9.685,6.155H4.616a.769.769,0,0,1,0-1.539H9.685L7.92,2.852A.769.769,0,0,1,9.008,1.764l3.078,3.078A.77.77,0,0,1,12.084,5.929Z" transform="translate(0 12.31) rotate(-90)" fill="#F15F23"></path>
                          </svg>
                        </span>
                        {{ get_phrase('Export CSV') }}
                    </button>
                </div>
                @endif
            </div>
            <div class="card-body exam_content" id="offline_exam_export">
                @if(count($exams) > 0)
                <div class="table-responsive tScrollFix pb-2">
                    <table class="table eTable">
                        <thead>
                            <tr>
                                <th scope="col">#</th>
                                <th scope="col">{{ get_phrase('Exam') }}</th>
                                <th scope="col">{{ get_phrase('Subject') }}</th>
                                <th scope="col">{{ get_phrase('Room Number') }}</th>
                                <th scope="col">{{ get_phrase('Starting Time') }}</th>
                                <th scope="col">{{ get_phrase('Ending Time') }}</th>
                                <th scope="col">{{ get_phrase('Total Marks') }}</th>
                                <th scope="col" class="text-center action-column">{{ get_phrase('Action') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($exams as $exam)
                                @php 
                                $subject_name = App\Models\Subject::where('id', $exam->subject_id)->value('name'); 
                                @endphp
                                <tr>
                                    <td>{{ $loop->index + 1 }}</td>
                                    <td>{{ $exam->name }}</td>
                                    <td>{{ $subject_name }}</td>
                                    <?php $class_room = DB::table('class_rooms')->find($exam->room_number); ?>
                                    @if(!empty($class_room))
                                    <td>{{ $class_room->name }}</td>
                                    @else
                                    <td>{{ get_phrase('No Room Found') }}</td>
                                    @endif
    
                                    <td>{{ date('d M Y - h:i A', $exam->starting_time) }}</td>
                                    <td>{{ date('d M Y - h:i A', $exam->ending_time) }}</td>
                                    <td>{{ $exam->total_marks }}</td>
                                    <td class="text-strat action-row">
                                        <div class="adminTable-action">
                                            <button
                                              type="button"
                                              class="eBtn eBtn-black dropdown-toggle table-action-btn-2"
                                              data-bs-toggle="dropdown"
                                              aria-expanded="false"
                                            >
                                              {{ get_phrase('Actions') }}
                                            </button>
                                            <ul
                                              class="dropdown-menu dropdown-menu-end eDropdown-menu-2 eDropdown-table-action"
                                            >
                                              <li>
                                                <a class="dropdown-item" href="javascript:;" onclick="rightModal('{{ route('admin.edit.offline_exam', ['id' => $exam->id]) }}', '{{ get_phrase('Edit Exam') }}')">{{ get_phrase('Edit') }}</a>
                                              </li>
                                              <li>
                                                <a class="dropdown-item" href="javascript:;" onclick="confirmModal('{{ route('admin.offline_exam.delete', ['id' => $exam->id]) }}', 'undefined');">{{ get_phrase('Delete') }}</a>
                                              </li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="empty_box center">
                    <img class="mb-3" width="150px" src="{{ asset('assets/images/empty_box.png') }}" />
                    <br>
                    <span class="">{{ get_phrase('No data found') }}</span>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>


<script type="text/javascript">

    "use strict";

    function filter_class(){
        var class_id = $('#class_id').val();
        if(class_id != ""){
            showAllExams();
        }else{
            toastr.error('{{ "Please select a class" }}');
        }
    }

    var showAllExams = function () {
        var class_id = $('#class_id').val();
        let url = "{{ route('admin.class_wise_exam_list', ['id' => ":class_id"]) }}";
        url = url.replace(":class_id", class_id);
        if(class_id != ""){
            $.ajax({
                url: url,
                success: function(response){
                    $('.exam_content').html(response);
                    // initDataTable('basic-datatable');
                }
            });
        }
    }


    function downloadCSVFile(csv, filename) {
        var csv_file, download_link;

        csv_file = new Blob([csv], {type: "text/csv"});

        download_link = document.createElement("a");

        download_link.download = filename;

        download_link.href = window.URL.createObjectURL(csv_file);

        download_link.style.display = "none";

        document.body.appendChild(download_link);

        download_link.click();

    }

    var downloadButton = document.getElementById("download-button");
    if (downloadButton) {
        downloadButton.addEventListener("click", function () {
            var html = document.querySelector("#offline_exam_export").outerHTML;
            htmlToCSV(html, "offline_exam.csv");
        });
    }


    function htmlToCSV(html, filename) {
        var data = [];
        var rows = document.querySelectorAll("#offline_exam_export tr");
        for (var i = 0; i < rows.length; i++) {
            var row = [], cols = rows[i].querySelectorAll("td, th");


            for (var j = 0; j < cols.length; j++) {
                row.push(cols[j].innerText);
                console.log(cols[j].innerText)

            }

            data.push(row.join(","));

        }
        downloadCSVFile(data.join("\n"), filename);
    }

    window.onbeforeprint = function () {
        var elementsToHide = document.querySelectorAll('.action-column');
        for (var i = 0; i < elementsToHide.length; i++) {
            elementsToHide[i].style.display = 'none';
        }
        var elementsRowToHide = document.querySelectorAll('.action-row');
        for (var i = 0; i < elementsRowToHide.length; i++) {
            elementsRowToHide[i].style.display = 'none';
        }
    }
</script>
@endsection
