@extends('admin.navigation')
   
@section('content')
<div class="mainSection-title">
    <div class="row">
        <div class="col-12">
            <div
              class="d-flex justify-content-between align-items-center flex-wrap gr-15"
            >
                <div class="d-flex flex-column">
                    <h4>{{ get_phrase('Book Issue') }}</h4>
                    <ul class="d-flex align-items-center eBreadcrumb-2">
                        <li><a href="#">{{ get_phrase('Home') }}</a></li>
                        <li><a href="#">{{ get_phrase('Back Office') }}</a></li>
                        <li><a href="#">{{ get_phrase('Book Issue Report') }}</a></li>
                    </ul>
                </div>
                <div class="export-btn-area">
                    <a href="javascript:;" class="export_btn" onclick="rightModal('{{ route('admin.book_issue.open_modal') }}', '{{ get_phrase('Issue Book') }}')"><i class="bi bi-plus"></i>{{ get_phrase('Issue Book') }}</a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="eSection-wrap-2">
            <div class="search-filter-area d-flex justify-content-md-between justify-content-center align-items-center flex-wrap gr-15">
              <form method="GET" action="{{ route('admin.book_issue.list') }}">
                <div
                  class="row"
                >
                    <div class="col-xl-8">
                        <input type="text" class="form-control eForm-control" name="eDateRange"
                            value="{{ date('m/d/Y', $date_from).' - '.date('m/d/Y', $date_to) }}" />
                    </div>

                    <div class="col-xl-2">
                        <button type="submit" class="eBtn eBtn btn-secondary form-control">{{ get_phrase('Filter') }}</button>
                    </div>
                </div>
              </form>
            </div>

            <div class="book_issue_content">
                @include('admin.book_issue.list')
            </div>
        </div>
    </div>
</div>

@endsection
