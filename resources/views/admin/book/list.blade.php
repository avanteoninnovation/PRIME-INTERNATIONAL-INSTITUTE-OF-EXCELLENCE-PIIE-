<?php 

use App\Models\BookIssue;

?>

@extends('admin.navigation')
   
@section('content')
<div class="mainSection-title">
    <div class="row">
        <div class="col-12">
            <div
              class="d-flex justify-content-between align-items-center flex-wrap gr-15"
            >
                <div class="d-flex flex-column">
                    <h4>{{ get_phrase('Book') }}</h4>
                    <ul class="d-flex align-items-center eBreadcrumb-2">
                        <li><a href="#">{{ get_phrase('Home') }}</a></li>
                        <li><a href="#">{{ get_phrase('Back Office') }}</a></li>
                        <li><a href="#">{{ get_phrase('Book List Manager') }}</a></li>
                    </ul>
                </div>
                <div class="export-btn-area">
                    <a href="javascript:;" class="export_btn" onclick="rightModal('{{ route('admin.book.open_modal') }}', '{{ get_phrase('Add book') }}')"><i class="bi bi-plus"></i>{{ get_phrase('Add book') }}</a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
  <div class="col-12">
    <div class="eSection-wrap-2">
    	<div class="search-filter-area d-flex justify-content-md-between justify-content-center align-items-center flex-wrap gr-15">
        <form action="{{ route('admin.book.book_list') }}">
          <div
            class="search-input d-flex justify-content-start align-items-center"
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
              placeholder="Search Book"
              class="form-control"
            />
          </div>
        </form>
      </div>
			@if(count($books) > 0)
			<div class="table-responsive">
				<table id="basic-datatable" class="table eTable">
          <thead>
            <tr>
              <th>#</th>
              <th>{{ get_phrase('Book name') }}</th>
							<th>{{ get_phrase('Author') }}</th>
							<th>{{ get_phrase('Copies') }}</th>
							<th>{{ get_phrase('Available copies') }}</th>
							<th class="text-end">{{ get_phrase('Option') }}</th>
		        </tr>
		      </thead>
          <tbody>
            @foreach($books as $key => $book)
              <tr>
              	<td>{{ $books->firstItem() + $key }}</td>
		            <td>{{ $book['name'] }}</td>
		            <td>{{ $book['author'] }}</td>
		            <td>{{ $book['copies'] }}</td>
		            <td>
      						<?php $number_of_issued_book = BookIssue::get()->where('book_id', $book['id'])->where('status', 0); ?>
      						{{ $book['copies'] - count($number_of_issued_book) }}
		            </td>
		            <td class="text-start">
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
                        <a class="dropdown-item" href="javascript:;" onclick="rightModal('{{ route('admin.edit.book', ['id' => $book->id]) }}', '{{ get_phrase('Edit Book') }}')">{{ get_phrase('Edit') }}</a>
                      </li>
                      <li>
                        <a class="dropdown-item" href="javascript:;" onclick="confirmModal('{{ route('admin.book.delete', ['id' => $book->id]) }}', 'undefined');">{{ get_phrase('Delete') }}</a>
                      </li>
                    </ul>
                  </div>
		            </td>
		          </tr>
            @endforeach
          </tbody>
		    </table>
		    {!! $books->appends(request()->all())->links() !!}
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

@endsection
