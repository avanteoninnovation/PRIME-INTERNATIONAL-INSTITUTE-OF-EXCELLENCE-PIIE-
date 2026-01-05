@extends('student.navigation')
@section('content')

<!--title-->
<div class="mainSection-title">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center flex-wrap gr-15">
                <div class="d-flex flex-column">
                    <h4>{{ get_phrase('Appraisal') }}</h4>
                    <ul class="d-flex align-items-center eBreadcrumb-2">
                        <li><a href="#">{{ get_phrase('Home') }}</a></li>
                        <li><a href="#">{{ get_phrase('Appraisal') }}</a></li>
                        <li><a href="#">{{ get_phrase('Appraisal List') }}</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        @if(count($appraisals) > 0)
        <div class="eSection-wrap-2">
            <div class="table-responsive">
                <table class="table eTable eTable-2">
                  <thead>
                    <tr>
                      <th scope="col">#</th>
                      <th scope="col">{{ get_phrase('Title') }}</th>
                      <th scope="col">{{ get_phrase('Ans Type') }}</th>
                      <th scope="col">{{ get_phrase('Status') }}</th>
                      <th scope="col">{{ get_phrase('Action') }}</th>
                  </thead>
                    <tbody>
                        @foreach($appraisals as $appraisal)
                            <?php 
                                // Get class details
                                $classes = DB::table('classes')->where('id', $appraisal->class_id)->first();
                    
                                // Get teacher IDs and names
                                $teacher_ids = json_decode($appraisal->teacher_id);
                                $teacher_names = [];
                                foreach($teacher_ids as $teacher_id) {
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
                                        </div>
                                    </div>
                                </td>
                                
                                <td>
                                    <div class="dAdmin_info_name">
                                        <p>{{ucwords($appraisal->ans_type)}}</p>
                                    </div>
                                </td>
                                <td>
                                    <div class="dAdmin_info_name">
                                        @if($appraisal->status == 1)
                                            <span class="eBadge ebg-soft-success">{{ get_phrase('Active') }}</span>
                                        @else
                                            <span class="eBadge ebg-soft-danger">{{ get_phrase('Archived') }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <div class="export-btn-area  min-w-200px">
                                        <a href="{{route('student.appraisal.singleAppraisal', ['id' => $appraisal->id])}}" class="export_btn">{{get_phrase('Start Appraisal')}}</a>
                                      </div>
                                </td>
                            </tr>
                        @endforeach
                        
                    </tbody>
                </table>
                <div class="admin-tInfo-pagi d-flex justify-content-md-between justify-content-center align-items-center flex-wrap gr-15">
                    <p class="admin-tInfo">{{ get_phrase('Showing').' 1 - '.count($appraisals).' '.get_phrase('from').' '.$appraisals->total().' '.get_phrase('data') }}</p>
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