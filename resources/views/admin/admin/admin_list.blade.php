@extends('admin.navigation')
   
@section('content')

<?php use App\Models\School; ?>

<div class="mainSection-title">
    <div class="row">
      <div class="col-12">
        <div
          class="d-flex justify-content-between align-items-center flex-wrap gr-15"
        >
          <div class="d-flex flex-column">
            <h4>{{ get_phrase('Admins') }}</h4>
            <ul class="d-flex align-items-center eBreadcrumb-2">
              <li><a href="#">{{ get_phrase('Home') }}</a></li>
              <li><a href="#">{{ get_phrase('Users') }}</a></li>
              <li><a href="#">{{ get_phrase('Admin') }}</a></li>
            </ul>
          </div>
          <div class="export-btn-area d-flex gap-2">
            <a href="{{ route('admin.admin.export', ['search' => $search]) }}" class="export_btn bg-secondary"><i class="bi bi-download"></i> {{ get_phrase('Export CSV') }}</a>
            <a href="{{ route('admin.admin.export_excel') }}" class="export_btn bg-secondary"><i class="bi bi-download"></i> {{ get_phrase('Export Excel') }}</a>
            <a href="{{ route('admin.admin.list_pdf') }}" target="_blank" class="export_btn bg-secondary"><i class="bi bi-printer"></i> {{ get_phrase('Print / PDF') }}</a>
            <a href="javascript:;" class="export_btn" onclick="rightModal('{{ route('admin.open_modal') }}', 'Create Admin')">{{ get_phrase('Create Admin') }}</a>
          </div>
        </div>
      </div>
    </div>
</div>
<!-- Start Admin area -->
<div class="row">
    <div class="col-12">
        <div class="eSection-wrap-2">
            <div class="search-filter-area d-flex justify-content-md-between justify-content-center align-items-center gr-15">
              <form action="{{ route('admin.admin') }}">
                <div
                  class="search-input d-flex justify-content-start align-items-center" style="margin-right:10px; max-width: 250px;"
                >
                  <span>
                    <svg
                      xmlns="http://www.w3.org/2000/svg"
                      width="16"
                      height="16"
                      viewBox="0 0 16 16"
                    >
                      <path
                        id="Search_icon"
                        data-name="Search icon"
                        d="M2,7A4.951,4.951,0,0,1,7,2a4.951,4.951,0,0,1,5,5,4.951,4.951,0,0,1-5,5A4.951,4.951,0,0,1,2,7Zm12.3,8.7a.99.99,0,0,0,1.4-1.4l-3.1-3.1A6.847,6.847,0,0,0,14,7,6.957,6.957,0,0,0,7,0,6.957,6.957,0,0,0,0,7a6.957,6.957,0,0,0,7,7,6.847,6.847,0,0,0,4.2-1.4Z"
                        fill="#797c8b"
                      />
                    </svg>
                  </span>
                  <input
                    type="text"
                    id="search"
                    name="search"
                    value="{{ $search }}"
                    placeholder="Search Admin"
                    class="form-control"
                  />
                </div>
              </form>
            </div>
            @if(count($admins) > 0)
            <!-- Table -->
            <div class="table-responsive">
              <table class="table eTable eTable-2">
                <thead>
                  <tr>
                    <th scope="col">#</th>
                    <th scope="col">{{ get_phrase('Name') }}</th>
                    <th scope="col">{{ get_phrase('Email') }}</th>
                    <th scope="col">{{ get_phrase('User Info') }}</th>
                    <th scope="col">{{ get_phrase('Staff Info') }}</th>
                    <th scope="col">{{ get_phrase('Account Status') }}</th>
                    <th scope="col">{{ get_phrase('Options') }}</th>
                  </tr>
                </thead>
                <tbody>
                    @foreach($admins as $key => $admin)
                    <?php 
                      $info = json_decode($admin->user_information ?? '') ?: (object) [];
                      $photo = $info->photo ?? '';
                      if(!empty($photo)){
                        $user_image = 'uploads/user-images/'.$photo;
                      }else{
                        $user_image = 'uploads/user-images/thumbnail.png';
                      }

                    ?>
                      <tr>
                        <th scope="row">
                          <p class="row-number">{{ $admins->firstItem() + $key }}</p>
                        </th>
                        <td>
                          <div
                            class="dAdmin_profile d-flex align-items-center min-w-200px"
                          >
                            <div class="dAdmin_profile_img">
                              <img
                                class="img-fluid"
                                width="50"
                                height="50"
                                src="{{ asset('assets') }}/{{ $user_image }}"
                              />
                            </div>
                            <div class="dAdmin_profile_name">
                              <h4>{{ $admin->name }}</h4>
                            </div>
                          </div>
                        </td>
                        <td>
                          <div class="dAdmin_info_name min-w-250px">
                            <p>{{ $admin->email }}</p>
                          </div>
                        </td>
                        <td>
                          <div class="dAdmin_info_name min-w-250px">
                            <p><span>{{ get_phrase('Phone') }}:</span> {{ $info->phone ?? '—' }}</p>
                            <p>
                              <span>{{ get_phrase('Address') }}:</span> {{ $info->address ?? '—' }}
                            </p>
                          </div>
                        </td>
                        <td>
                          <div class="dAdmin_info_name min-w-200px">
                            <p><span>{{ get_phrase('Staff No.') }}:</span> {{ $admin->code ?? '—' }}</p>
                            <p><span>{{ get_phrase('Department') }}:</span> {{ optional($admin->department)->name ?? '—' }}</p>
                            <p><span>{{ get_phrase('Designation') }}:</span> {{ optional($admin->designationRecord)->name ?? '—' }}</p>
                            <p><span>{{ get_phrase('Status') }}:</span> {{ ucfirst($admin->staff_status ?? 'active') }}</p>
                          </div>
                        </td>
                        <td>
                          <div class="dAdmin_info_name min-w-100px">
                            @if(!empty($admin->account_status == 'disable'))
                            <span class="eBadge ebg-soft-danger">{{get_phrase('Disabled')}}</span>
                            @else
                            <span class="eBadge ebg-soft-success">{{get_phrase('Enable')}}</span>
                            @endif
                          </div>
                        </td>
                        <td>
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
                                <a class="dropdown-item" href="javascript:;" onclick="rightModal('{{ route('admin.open_edit_modal', ['id' => $admin->id]) }}', '{{ get_phrase('Edit Admin') }}')">{{ get_phrase('Edit') }}</a>
                              </li>

                              @if ( $key != 0)
                                @if( auth()->user()->school_role == 1)
                                  <li>
                                    <a class="dropdown-item" href="{{ route('admin.admin.menu_permission', ['id' => $admin->id]) }}">{{ get_phrase('Admin Permission') }}</a>
                                    
                                  </li>
                                @endif
                              @endif
                              
                              @if ( $key != 0)
                              <li>
                                <a class="dropdown-item" href="javascript:;" onclick="confirmModal('{{ route('admin.admin.delete', ['id' => $admin->id]) }}', 'undefined');">{{ get_phrase('Delete') }}</a>
                              </li>
                              @endif
                              <li>
                                <a class="dropdown-item" href="javascript:;" onclick="largeModal('{{ route('admin.admin.admin_profile', ['id' => $admin->id]) }}','{{ get_phrase('Admin Profile') }}')">{{ get_phrase('Profile') }}</a>
                              </li>
                              <li>
                                <a class="dropdown-item" href="{{ route('admin.documents', ['id' => $admin->id]) }}">{{ get_phrase('Documents') }}</a>
                              </li>
                              <li>
                                <a class="dropdown-item" href="{{ route('admin.admin.profile_pdf', ['id' => $admin->id]) }}" target="_blank">{{ get_phrase('Profile PDF') }}</a>
                              </li>
                              <li>
                                <a class="dropdown-item" href="javascript:;" onclick="confirmModal('{{ route('admin.admin.reset_password', ['id' => $admin->id]) }}', 'undefined');">{{ get_phrase('Reset Password') }}</a>
                              </li>
                              <li>
                                <a class="dropdown-item" href="javascript:;" onclick="confirmModal('{{ route('admin.admin.resend_activation', ['id' => $admin->id]) }}', 'undefined');">{{ get_phrase('Resend Activation') }}</a>
                              </li>
                              @if(auth()->user()->id != $admin->id && $key != 0)
                                  @if($admin->account_status == 'disable')
                                      <li>
                                          <a class="dropdown-item" href="javascript:;" onclick="confirmModal('{{ route('admin.account_enable', ['id' => $admin->id]) }}', 'undefined');">{{ get_phrase('Enable') }}</a>
                                      </li>
                                  @else
                                      <li>
                                          <a class="dropdown-item" href="javascript:;" onclick="confirmModal('{{ route('admin.account_disable', ['id' => $admin->id]) }}', 'undefined');">{{ get_phrase('Disable') }}</a>
                                      </li>
                                  @endif
                              @endif
                            </ul>
                          </div>
                        </td>
                      </tr>
                    @endforeach
                </tbody>
              </table>

              <div
                  class="admin-tInfo-pagi d-flex justify-content-md-between justify-content-center align-items-center flex-wrap gr-15"
                >
                  <p class="admin-tInfo">{{ get_phrase('Showing').' 1 - '.count($admins).' '.get_phrase('from').' '.$admins->total().' '.get_phrase('data') }}</p>
                  <div class="admin-pagi">
                    {!! $admins->appends(request()->all())->links() !!}
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
</div>

<!-- End Admin area -->
@endsection
