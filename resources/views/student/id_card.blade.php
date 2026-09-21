@extends('student.navigation')
@section('content')

<div class="mainSection-title">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center flex-wrap gr-15">
                <div class="d-flex flex-column">
                    <h4>{{ get_phrase('My ID Card') }}</h4>
                    <ul class="d-flex align-items-center eBreadcrumb-2">
                        <li><a href="{{ route('student.dashboard') }}">{{ get_phrase('Home') }}</a></li>
                        <li><a href="#">{{ get_phrase('My ID Card') }}</a></li>
                    </ul>
                </div>
                <a href="{{ route('student.id_card.pdf') }}" class="eBtn eBtn-primary">
                    <i class="bi bi-download"></i> {{ get_phrase('Download PDF') }}
                </a>
            </div>
        </div>
    </div>
</div>

<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-body" id="printableArea">
        <style>
          .icard {
          text-align: left;
          margin-left: 55px;
          font-size: 11px;
        }
        .icard_st_dt{
          margin-left: 4px;
        }
        .icard_img img{
          border-radius: 50%;
          margin-top: 15px;
        }
        </style>
        <!-- ID CARD STARTS HERE -->
        <div class="id-card-hook"></div>
        <div class="id-card-holder div-sc-one" >
          <div class="id-card div-sc-two">
            <div class="dv-sc-four">
            @php
                $school_data = App\Models\School::where('id', auth()->user()->school_id)->first();
            @endphp

              @if(!empty($school_data->school_logo))
                <img class="im-sc-one" src="{{ asset('assets/uploads/school_logo/'.$school_data->school_logo ) }}">
              @else
                <img class="im-sc-one" src="{{ asset('assets') }}/images/id_logo.png">
              @endif
            </div>
            <div class="school_title div-sc-three">{{ optional($school_data)->title }}</div>
            <div class="icard_img">
              <img src="{{ $student_details['photo'] }}" width="80px" height="80px">
            </div>

            <h2 class="head-sc-one">{{ $student_details['name'] }}</h2>
            <div class="dv-sc-four">
              <div class="icard">
                <span>{{ get_phrase('Code') }} : </span>
                <span class="icard_st_dt">{{ null_checker($student_details['code']) }}</span>
              </div>
              @if($programme)
              <div class="icard">
                <span>{{ get_phrase('Programme') }} : </span>
                <span class="icard_st_dt">{{ $programme->name }}</span>
              </div>
              @else
              <div class="icard">
                <span>{{ get_phrase('Class') }} : </span>
                @if(empty($student_details['class_name']))
                <span class="icard_st_dt">{{get_phrase('Not assigned')}} </span>
                @else
                <span class="icard_st_dt">{{ null_checker($student_details['class_name']) }}</span>
                @endif
              </div>
              <div class="icard">
                <span>{{ get_phrase('Section') }} : </span>
                @if(empty($student_details['section_name']))
                <span class="icard_st_dt">{{get_phrase('Not assigned')}} </span>
                @else
                <span class="icard_st_dt">{{ null_checker($student_details['section_name']) }}</span>
                @endif
              </div>
              @endif
              <div class="icard">
                <span>{{ get_phrase('Blood') }} : </span>
                <span class="icard_st_dt">{{ null_checker(strtoupper((string) $student_details['blood_group'])) }}</span>
              </div>
              <div class="icard">
                <span>{{ get_phrase('Contact') }} : </span>
                <span class="icard_st_dt">{{ null_checker($student_details['phone']) }}</span>
              </div>
            </div>
            <hr>

            </div>
          </div>
          <!-- ID CARD ENDS HERE -->

          <div class="d-print-none mt-4">
            <div class="text-center">
              <input type="button" class="eBtn eBtn btn-primary" onclick="printableDiv('printableArea')" value="{{ get_phrase('Print') }}" />
            </div>
          </div>
          <!-- end buttons -->

        </div> <!-- end card-body-->
      </div> <!-- end card -->
    </div> <!-- end col-->
  </div>

<script type="text/javascript">

  "use strict";

  function printableDiv(printableAreaDivId) {
    var printContents = document.getElementById(printableAreaDivId).innerHTML;
    var originalContents = document.body.innerHTML;

    document.body.innerHTML = printContents;

    window.print();

    document.body.innerHTML = originalContents;
  }
</script>
@endsection
