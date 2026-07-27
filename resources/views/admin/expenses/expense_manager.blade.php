@extends('admin.navigation')
   
@section('content')
<div class="mainSection-title">
    <div class="row">
        <div class="col-12">
            <div
              class="d-flex justify-content-between align-items-center flex-wrap gr-15"
            >
                <div class="d-flex flex-column">
                    <h4>{{ get_phrase('Expense') }}</h4>
                    <ul class="d-flex align-items-center eBreadcrumb-2">
                        <li><a href="#">{{ get_phrase('Home') }}</a></li>
                        <li><a href="#">{{ get_phrase('Accounting') }}</a></li>
                        <li><a href="#">{{ get_phrase('Expense Manager') }}</a></li>
                    </ul>
                </div>
                <div class="export-btn-area">
                    <a href="javascript:;" class="export_btn" onclick="rightModal('{{ route('admin.expenses.open_modal') }}', '{{ get_phrase('Create Expense') }}')"><i class="bi bi-plus"></i>{{ get_phrase('Add New Expense') }}</a>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-12">
        <div class="eSection-wrap-2">
            <form method="GET" enctype="multipart/form-data" class="d-block ajaxForm" action="{{ route('admin.expense.list') }}">
                <div class="row">
                    <div class="row justify-content-md-center">

                        @if(isset($selected_category) && $selected_category != "")
                            <div class="col-xl-3 mb-3">
                                <input type="text" class="form-control eForm-control" name="eDateRange"
                                    value="{{ date('m/d/Y', $date_from).' - '.date('m/d/Y', $date_to) }}" />
                            </div>

                            <div class="col-xl-4 mb-3">
                                <select class="form-select eForm-select eChoice-multiple-with-remove" name="expense_category_id" id="expense_category_id">
                                    <option value="all">{{ get_phrase('Expense category') }}</option>
                                    @foreach ($expense_categories as $expense_category)
                                        <option value="{{ $expense_category->id }}" {{ $selected_category->id == $expense_category->id ?  'selected':'' }}>{{ $expense_category->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @else
                            <div class="col-xl-3 mb-3">
                                <input type="text" class="form-control eForm-control" name="eDateRange"
                                    value="{{ date('m/d/Y', $date_from).' - '.date('m/d/Y', $date_to) }}" />
                            </div>

                            <div class="col-xl-4 mb-3">
                                <select class="form-select eForm-select eChoice-multiple-with-remove" name="expense_category_id" id="expense_category_id">
                                    <option value="all">{{ get_phrase('Select expense category') }}</option>
                                    @foreach ($expense_categories as $expense_category)
                                        <option value="{{ $expense_category->id }}">{{ $expense_category->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        <div class="col-xl-2 mb-3">
                            <button type="submit" class="eBtn eBtn btn-secondary form-control">{{ get_phrase('Filter') }}</button>
                        </div>
                    </div>
                </div>
            </form>
            <div class="expense_content">
                @include('admin.expenses.list')
            </div>
        </div>
    </div>
</div>
@endsection
