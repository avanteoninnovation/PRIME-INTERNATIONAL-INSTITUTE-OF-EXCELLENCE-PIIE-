<div class="eoff-form">
    <form method="POST" class="d-block ajaxForm" action="{{ route('admin.procurement.store') }}">
        @csrf
        <div class="form-row">
            <div class="fpb-7"><label class="eForm-label">{{ get_phrase('Item Name') }} *</label>
                <input type="text" class="form-control eForm-control" name="title" required></div>
            <div class="fpb-7 mt-2"><label class="eForm-label">{{ get_phrase('Description') }}</label>
                <textarea class="form-control eForm-control" name="description" rows="2"></textarea></div>
            <div class="row mt-2">
                <div class="col-6 fpb-7"><label class="eForm-label">{{ get_phrase('Quantity') }} *</label>
                    <input type="number" class="form-control eForm-control" name="quantity" min="1" value="1" required></div>
                <div class="col-6 fpb-7"><label class="eForm-label">{{ get_phrase('Estimated Cost') }}</label>
                    <input type="number" class="form-control eForm-control" name="estimated_cost" step="0.01" min="0"></div>
            </div>
            <div class="fpb-7 mt-2"><label class="eForm-label">{{ get_phrase('Urgency') }}</label>
                <select class="form-control eForm-control" name="urgency">
                    <option value="low">Low</option>
                    <option value="medium" selected>Medium</option>
                    <option value="high">High</option>
                </select>
            </div>
            <div class="fpb-7 mt-2"><label class="eForm-label">{{ get_phrase('Justification') }}</label>
                <textarea class="form-control eForm-control" name="justification" rows="2"></textarea></div>
            <div class="fpb-7 pt-3">
                <button class="btn-form" type="submit">{{ get_phrase('Submit Request') }}</button>
            </div>
        </div>
    </form>
</div>
