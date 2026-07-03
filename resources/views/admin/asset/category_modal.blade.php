<div class="eoff-form">
    <form method="POST" class="d-block ajaxForm" action="{{ route('admin.asset_categories.store') }}">
        @csrf
        <div class="form-row">
            <div class="fpb-7"><label class="eForm-label">{{ get_phrase('Category Name') }} *</label>
                <input type="text" class="form-control eForm-control" name="name" required></div>
            <div class="fpb-7 mt-2"><label class="eForm-label">{{ get_phrase('Description') }}</label>
                <textarea class="form-control eForm-control" name="description" rows="2"></textarea></div>
            <div class="fpb-7 pt-3">
                <button class="btn-form" type="submit">{{ get_phrase('Save Category') }}</button>
            </div>
        </div>
    </form>
</div>
