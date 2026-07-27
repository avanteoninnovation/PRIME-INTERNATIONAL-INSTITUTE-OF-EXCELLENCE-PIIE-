<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Exam;
use App\Models\Gradebook;
use App\Models\Programme;
use App\Models\Subject;
use App\Support\Export\ExcelExportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use PDF;

/**
 * "Courses" for HEI Programmes — deliberately a separate controller from
 * the K-12 Subject CRUD in AdminController (admin/subject/*), but backed
 * by the same `subjects` table/model (no duplicate module). A Course is a
 * Subject row with `programme_id` set; the K-12 screens only ever touch
 * rows with `class_id` set and never populate the Course-only fields
 * added below, so the two paths don't interfere with each other.
 */
class CourseController extends Controller
{
    private $school_id;

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $this->school_id = Auth::user()->school_id;
            return $next($request);
        });
    }

    private function baseQuery()
    {
        return Subject::where('school_id', $this->school_id)->whereNotNull('programme_id');
    }

    private function rules(): array
    {
        return [
            'code'         => 'required|max:30',
            'name'         => 'required|max:255',
            'programme_id' => [
                'required',
                Rule::exists('programmes', 'id')->where('school_id', $this->school_id),
            ],
            'credits'      => 'required|integer|min:1|max:10',
            'course_type'  => 'required|in:' . implode(',', Subject::TYPES),
            'level'        => 'required|in:' . implode(',', array_merge(Programme::LEVELS, Programme::LEVELS_LEGACY)),
            'cats_marks'   => 'required|integer|min:0|max:100',
            'exam_marks'   => 'required|integer|min:0|max:100',
            'pass_mark'    => 'required|integer|min:0|max:100',
        ];
    }

    private function validateMarksSplit(Request $request): void
    {
        $total = (int) $request->input('cats_marks', 0) + (int) $request->input('exam_marks', 0);
        if ($total !== 100) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'cats_marks' => [get_phrase('CATS marks + EXAM marks must add up to 100')],
            ]);
        }
    }

    public function index(Request $request)
    {
        $search   = $request->search ?? '';
        $courses  = $this->baseQuery()
            ->when($search, fn ($q) => $q->where('name', 'like', "%$search%")->orWhere('code', 'like', "%$search%"))
            ->with('programme')
            ->orderBy('name')
            ->paginate(20);
        $programmes = Programme::where('school_id', $this->school_id)->orderBy('name')->get();

        return view('admin.course.list', compact('courses', 'search', 'programmes'));
    }

    public function openModal(Request $request)
    {
        $id         = $request->id;
        $course     = $id ? $this->baseQuery()->findOrFail($id) : null;
        $programmes = Programme::where('school_id', $this->school_id)->orderBy('name')->get();

        return view('admin.course.modal', compact('course', 'programmes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules());
        $this->validateMarksSplit($request);

        $validated['school_id'] = $this->school_id;
        $course = Subject::create($validated);
        AuditLog::record('create', 'Courses', "Created course: {$course->name}");

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['status' => 'success', 'message' => get_phrase('Course created successfully')]);
        }

        return redirect()->route('admin.courses.index')->with('success', get_phrase('Course created successfully'));
    }

    public function update(Request $request, $id)
    {
        $course = $this->baseQuery()->findOrFail($id);
        $validated = $request->validate($this->rules());
        $this->validateMarksSplit($request);

        $course->update($validated);
        AuditLog::record('update', 'Courses', "Updated course: {$course->name}");

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['status' => 'success', 'message' => get_phrase('Course updated successfully')]);
        }

        return redirect()->route('admin.courses.index')->with('success', get_phrase('Course updated successfully'));
    }

    public function destroy($id)
    {
        $course = $this->baseQuery()->findOrFail($id);

        $hasExams = Exam::where('subject_id', $course->id)->where('school_id', $this->school_id)->exists();
        $hasMarks = Gradebook::where('school_id', $this->school_id)
            ->get()
            ->contains(function ($record) use ($course) {
                $marks = is_string($record->marks) ? json_decode($record->marks, true) : [];
                return is_array($marks) && array_key_exists($course->id, $marks);
            });

        if ($hasExams || $hasMarks) {
            return redirect()->back()->with('error', get_phrase('This course has related exams or marks and cannot be deleted'));
        }

        AuditLog::record('delete', 'Courses', "Deleted course: {$course->name}");
        $course->delete();

        return redirect()->back()->with('success', get_phrase('Course deleted'));
    }

    private function filteredCourses(string $search)
    {
        return $this->baseQuery()
            ->when($search, fn ($q) => $q->where('name', 'like', "%$search%")->orWhere('code', 'like', "%$search%"))
            ->with('programme')
            ->orderBy('name')
            ->get();
    }

    public function exportCsv(Request $request)
    {
        $search = $request->search ?? '';
        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="courses_' . date('Y-m-d') . '.csv"',
        ];

        $callback = function () use ($search) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['#', 'Code', 'Name', 'Programme', 'Credit', 'Type', 'Level', 'CATS Marks', 'EXAM Marks', 'Pass Mark']);
            $this->filteredCourses($search)->each(function ($c, $i) use ($out) {
                fputcsv($out, [$i + 1, $c->code, $c->name, optional($c->programme)->name, $c->credits, $c->course_type, $c->level, $c->cats_marks, $c->exam_marks, $c->pass_mark]);
            });
            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportExcel(Request $request)
    {
        $rows = $this->filteredCourses($request->search ?? '')->map(fn ($c, $i) => [
            $i + 1,
            $c->code,
            $c->name,
            optional($c->programme)->name,
            $c->credits,
            $c->course_type,
            $c->level,
            $c->cats_marks,
            $c->exam_marks,
            $c->pass_mark,
        ])->values();

        return ExcelExportService::download(
            'courses_' . date('Y-m-d'),
            ['#', 'Code', 'Name', 'Programme', 'Credit', 'Type', 'Level', 'CATS Marks', 'EXAM Marks', 'Pass Mark'],
            $rows
        );
    }

    public function printPdf(Request $request)
    {
        $courses = $this->filteredCourses($request->search ?? '');
        $pdf = PDF::loadView('admin.course.pdf', ['courses' => $courses]);
        $filename = 'courses_' . date('Y-m-d') . '.pdf';

        return $request->boolean('inline') ? $pdf->stream($filename) : $pdf->download($filename);
    }
}
