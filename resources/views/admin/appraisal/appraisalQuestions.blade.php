@extends('admin.navigation')

@section('content')

    <div class="mainSection-title">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center flex-wrap gr-15">
                    <div class="d-flex flex-column">
                        <h4>{{ get_phrase('Appraisal') }}</h4>
                        <ul class="d-flex align-items-center eBreadcrumb-2">
                            <li><a href="#">{{ get_phrase('Home') }}</a></li>
                            <li><a href="#">{{ get_phrase('Appraisal') }}</a></li>
                            <li><a href="#">{{ get_phrase('Appraisal Question') }}</a></li>
                        </ul>
                    </div>
                    <div class="export-btn-area">
                        <a href="javascript:;" class="export_btn" onclick="rightModal('{{ route('admin.appraisal.createQuestion') }}', '{{ get_phrase('Create Question') }}')"><i class="bi bi-plus"></i>{{ get_phrase('Add New Question') }}</a>
                    </div>
                </div>
            </div>
        </div>
    </div>



    <!-- Table -->
    <div class="row">
        <div class="col-12">
            @if (count($appraisals) > 0)
                <div class="eSection-wrap-2">
                    <div class="table-responsive">
                        <table class="table eTable eTable-2">
                            <thead>
                                <tr>
                                    <th scope="col">#</th>
                                    <th scope="col">{{ get_phrase('Title') }}</th>
                                    <th scope="col">{{ get_phrase('Teachers') }}</th>
                                    <th scope="col">{{ get_phrase('Questions') }}</th>
                                    <th scope="col">{{ get_phrase('Ans Type') }}</th>
                                    <th scope="col">{{ get_phrase('Status') }}</th>
                                    <th scope="col">{{ get_phrase('Action') }}</th>
                            </thead>
                            <tbody>
                                @foreach ($appraisals as $appraisal)
                                    <?php
                                    // Get class details
                                    $classes = DB::table('classes')->where('id', $appraisal->class_id)->first();
                                    
                                    // Get teacher IDs and names
                                    $teacher_ids = json_decode($appraisal->teacher_id);
                                    $teacher_names = [];
                                    foreach ($teacher_ids as $teacher_id) {
                                        $teacher = DB::table('users')->where('id', $teacher_id)->first();
                                        if ($teacher) {
                                            $teacher_names[] = $teacher->name;
                                        }
                                    }
                                    $teacher_list = implode(', ', $teacher_names); // Join all teacher names with a comma
                                    
                                    // Get questions list
                                    $questions = json_decode($appraisal->question);
                                    ?>

                                    <tr>
                                        <th scope="row">
                                            <p class="row-number">{{ $loop->index + 1 }}</p>
                                        </th>
                                        <td>
                                            <div class="dAdmin_profile d-flex align-items-center">
                                                <div class="dAdmin_profile_name dAdmin_info_name">
                                                    <h4>{{ $appraisal->title }}</h4>
                                                    <p>
                                                        @if (empty($classes->name))
                                                            <span>{{ get_phrase('Class') }}:</span> {{ get_phrase('removed') }}
                                                        @else
                                                            <span>{{ get_phrase('Class') }}:</span> {{ $classes->name }}
                                                        @endif
                                                    </p>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="dAdmin_info_name">
                                                <div class="teacher-list-container">
                                                    <ol class="teacher-list">
                                                        @foreach ($teacher_names as $teacher_name)
                                                            <li>{{ $loop->index + 1 }}. {{ $teacher_name }}</li>
                                                        @endforeach
                                                    </ol>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="dAdmin_info_name">
                                                @if (!empty($questions))
                                                    <div class="question-list-container">
                                                        <ol class="question-list">
                                                            @foreach ($questions as $question)
                                                                <li>{{ $loop->index + 1 }}. {{ $question }}</li>
                                                            @endforeach
                                                        </ol>
                                                    </div>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            <div class="dAdmin_info_name">
                                                <p>{{ ucwords($appraisal->ans_type) }}</p>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="dAdmin_info_name">
                                                @if ($appraisal->status == 1)
                                                    <span class="eBadge ebg-soft-success">{{ get_phrase('Active') }}</span>
                                                @else
                                                    <span class="eBadge ebg-soft-danger">{{ get_phrase('Archived') }}</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            <div class="dAdmin_info_name">
                                                <div class="adminTable-action">
                                                    <button type="button" class="eBtn eBtn-black dropdown-toggle table-action-btn-2" data-bs-toggle="dropdown" aria-expanded="false" style="color: #797c8b">
                                                        {{ get_phrase('Actions') }}
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-menu-end eDropdown-menu-2 eDropdown-table-action">
                                                        <li>
                                                            <a class="dropdown-item" href="javascript:;" onclick="rightModal('{{ route('admin.appraisal.appraisalQuestionEdit', ['id' => $appraisal->id]) }}', '{{ get_phrase('Edit Question') }}')">{{ get_phrase('Edit') }}</a>
                                                        </li>
                                                        <li>
                                                            <a class="dropdown-item" href="javascript:;" onclick="confirmModal('{{ route('admin.appraisal.appraisalQuestionDelete', ['id' => $appraisal->id]) }}', 'undefined');">{{ get_phrase('Delete') }}</a>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach

                            </tbody>
                        </table>
                        <div class="admin-tInfo-pagi d-flex justify-content-md-between justify-content-center align-items-center flex-wrap gr-15">
                            <p class="admin-tInfo">{{ get_phrase('Showing') . ' 1 - ' . count($appraisals) . ' ' . get_phrase('from') . ' ' . $appraisals->total() . ' ' . get_phrase('data') }}</p>
                            <div class="admin-pagi">
                                {!! $appraisals->appends(request()->all())->links() !!}
                            </div>
                        </div>
                    </div>
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



@endsection
