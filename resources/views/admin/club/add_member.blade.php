<form method="POST" action="{{ route('admin.club.member.store') }}">
    @csrf

    <input type="hidden" name="club_id" value="{{ $club->id }}">

    <div class="mb-3 mt-3">
        <select name="student_id"
                class="form-select eForm-select select2"
                required
                style="width: 100%;">
            <option value="">-- Search & Select Student --</option>
            <option value="test">Test</option>
        </select>
    </div>

    <div class="fpb-7 pt-2">
            <button class="btn-form" type="submit">{{ get_phrase('Add Member') }}</button>
        </div>
</form>

<style>
    .select2-search--dropdown{
        display: block !important;
    }
    input.select2-search__field:focus-visible {
    outline: none;
}
</style>

<script>
$(document).ready(function() {
    $('.select2').select2({
        placeholder: "Search student...",
        ajax: {
            url: '{{ route('admin.club.students.search') }}',
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return {
                    q: params.term,
                    club_id: {{ $club->id }}
                };
            },
            processResults: function(data) {
                return {
                    results: data
                };
            },
            cache: true
        },
    });
});
</script>
