<section class="mt-4" aria-labelledby="{{ $displayVariant }}-allocations-heading">
    <h6 id="{{ $displayVariant }}-allocations-heading">{{ $heading }}</h6>
    @if($items->isEmpty())
        <p class="text-muted small mb-0">{{ get_phrase('No allocations in this group.') }}</p>
    @else
        <div class="lecturer-desktop">
            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0">
                    <caption class="visually-hidden">{{ $heading }} {{ get_phrase('allocations') }}</caption>
                    <thead><tr><th scope="col">{{ $lecturerLabel }}</th><th scope="col">{{ get_phrase('Staff Number') }}</th><th scope="col">{{ get_phrase('Allocation Role') }}</th><th scope="col">{{ get_phrase('Effective Dates') }}</th><th scope="col">{{ get_phrase('Status') }}</th><th scope="col">{{ get_phrase('Actions') }}</th></tr></thead>
                    <tbody>@foreach($items as $item)
                        <tr>
                            <td><strong>{{ $item->lecturer?->name ?: get_phrase('Former lecturer') }}</strong>
                                @if($item->role === \App\Models\CourseOfferingLecturerAllocation::ROLE_PRIMARY_LECTURER)<span class="badge bg-primary ms-1">{{ get_phrase('Primary') }}</span>@endif
                                @if($item->lecturer?->department || $item->lecturer?->designationRecord || $item->lecturer?->staffProfile?->academic_title)
                                    <div class="small text-muted">{{ $item->lecturer?->department?->name }} @if($item->lecturer?->designationRecord)· {{ $item->lecturer->designationRecord->name }}@endif @if($item->lecturer?->staffProfile?->academic_title)· {{ $item->lecturer->staffProfile->academic_title }}@endif</div>
                                @endif
                            </td>
                            <td>{{ $item->lecturer?->code ?: '—' }}</td>
                            <td>{{ get_phrase($roleLabels[$item->role] ?? $item->role) }}</td>
                            <td>{{ $item->starts_on->format('Y-m-d') }} – {{ $item->ends_on?->format('Y-m-d') ?? get_phrase('No end date') }}</td>
                            <td><span class="badge {{ $item->status === 'active' ? 'bg-success' : ($item->status === 'planned' ? 'bg-warning text-dark' : ($item->status === 'ended' ? 'bg-secondary' : 'bg-dark')) }}">{{ get_phrase(ucfirst($item->status)) }}</span></td>
                            <td>@include('admin.course_offerings.lecturers._actions', ['displayVariant' => $displayVariant.'-desktop'])</td>
                        </tr>
                    @endforeach</tbody>
                </table>
            </div>
        </div>
        <div class="lecturer-mobile">
            @foreach($items as $item)
                <article class="lecturer-mobile-card">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                        <strong>{{ $item->lecturer?->name ?: get_phrase('Former lecturer') }}</strong>
                        <span class="badge {{ $item->status === 'active' ? 'bg-success' : ($item->status === 'planned' ? 'bg-warning text-dark' : ($item->status === 'ended' ? 'bg-secondary' : 'bg-dark')) }}">{{ get_phrase(ucfirst($item->status)) }}</span>
                    </div>
                    @if($item->role === \App\Models\CourseOfferingLecturerAllocation::ROLE_PRIMARY_LECTURER)<span class="badge bg-primary mt-2">{{ get_phrase('Primary Lecturer') }}</span>@endif
                    <dl>
                        <dt>{{ get_phrase('Role') }}</dt><dd>{{ get_phrase($roleLabels[$item->role] ?? $item->role) }}</dd>
                        <dt>{{ get_phrase('Effective Dates') }}</dt><dd>{{ $item->starts_on->format('Y-m-d') }} – {{ $item->ends_on?->format('Y-m-d') ?? get_phrase('No end date') }}</dd>
                        <dt>{{ get_phrase('Staff Number') }}</dt><dd>{{ $item->lecturer?->code ?: '—' }}</dd>
                        @if($item->lecturer?->department)<dt>{{ get_phrase('Department') }}</dt><dd>{{ $item->lecturer->department->name }}</dd>@endif
                        @if($item->lecturer?->designationRecord)<dt>{{ get_phrase('Designation') }}</dt><dd>{{ $item->lecturer->designationRecord->name }}</dd>@endif
                    </dl>
                    @include('admin.course_offerings.lecturers._actions', ['displayVariant' => $displayVariant.'-mobile'])
                </article>
            @endforeach
        </div>
    @endif
</section>
