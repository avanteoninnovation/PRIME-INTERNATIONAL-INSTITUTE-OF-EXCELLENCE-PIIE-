<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Election;
use App\Models\ElectionCandidate;
use App\Models\ElectionPosition;
use App\Models\ElectionVote;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Digital student-association elections — admin management (create an
 * election, add positions/candidates, publish results) and student voting
 * (verify identity, cast one vote per position, view published results),
 * on one controller the same way TranscriptController already mixes
 * admin- and student-facing methods for a similarly two-sided feature.
 *
 * The one rule this class exists to enforce correctly: a student can never
 * cast more than one vote for the same position. That's not just checked
 * here (see castVote()) — it's a database UNIQUE constraint on
 * election_votes(position_id, voter_id), so a double-submit racing the
 * check-then-insert can never slip through.
 */
class ElectionController extends Controller
{
    private $school_id;

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $this->school_id = Auth::user()->school_id;
            return $next($request);
        });
    }

    // ── Admin ────────────────────────────────────────────────────

    public function index()
    {
        $elections = Election::forSchool($this->school_id)->withCount('positions')->latest('id')->get();

        return view('admin.elections.index', compact('elections'));
    }

    public function create()
    {
        return view('admin.elections.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:191'],
            'description' => ['nullable', 'string'],
            'start_at' => ['required', 'date'],
            'end_at' => ['required', 'date', 'after:start_at'],
        ]);

        $election = Election::create([
            'school_id' => $this->school_id,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'start_at' => $validated['start_at'],
            'end_at' => $validated['end_at'],
            'created_by' => Auth::id(),
        ]);

        AuditLog::record('create', 'Elections', "Created election: {$election->title}");

        return redirect()->route('admin.elections.show', $election->id)->with('message', get_phrase('Election created. Add positions and candidates next.'));
    }

    public function show($id)
    {
        $election = Election::forSchool($this->school_id)->with('positions.candidates.student')->findOrFail((int) $id);
        $students = User::where('school_id', $this->school_id)->where('role_id', 7)->orderBy('name')->get();
        $results = $this->buildResults($election);

        return view('admin.elections.show', compact('election', 'students', 'results'));
    }

    public function storePosition(Request $request, $electionId)
    {
        $election = Election::forSchool($this->school_id)->findOrFail((int) $electionId);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:191'],
        ]);

        ElectionPosition::create([
            'election_id' => $election->id,
            'school_id' => $this->school_id,
            'title' => $validated['title'],
        ]);

        return redirect()->back()->with('message', get_phrase('Position added.'));
    }

    public function storeCandidate(Request $request, $positionId)
    {
        $position = ElectionPosition::where('school_id', $this->school_id)->findOrFail((int) $positionId);

        $validated = $request->validate([
            'student_id' => ['required', 'exists:users,id'],
            'manifesto' => ['nullable', 'string'],
        ]);

        $alreadyStanding = ElectionCandidate::where('position_id', $position->id)
            ->where('student_id', $validated['student_id'])
            ->exists();

        if ($alreadyStanding) {
            return redirect()->back()->with('error', get_phrase('That student is already a candidate for this position.'));
        }

        ElectionCandidate::create([
            'position_id' => $position->id,
            'school_id' => $this->school_id,
            'student_id' => $validated['student_id'],
            'manifesto' => $validated['manifesto'] ?? null,
        ]);

        return redirect()->back()->with('message', get_phrase('Candidate added.'));
    }

    public function publishResults($id)
    {
        $election = Election::forSchool($this->school_id)->findOrFail((int) $id);

        if ($election->computed_status !== 'closed') {
            return redirect()->back()->with('error', get_phrase('Results can only be published once voting has closed.'));
        }

        $election->update(['results_published' => true]);
        AuditLog::record('update', 'Elections', "Published results for election: {$election->title}");

        return redirect()->back()->with('message', get_phrase('Results published.'));
    }

    // ── Student ──────────────────────────────────────────────────

    public function studentIndex()
    {
        $elections = Election::forSchool($this->school_id)->latest('id')->get();

        return view('student.elections.index', compact('elections'));
    }

    public function studentShow($id)
    {
        $election = Election::forSchool($this->school_id)->with('positions.candidates.student')->findOrFail((int) $id);
        $student = Auth::user();

        if ($election->computed_status !== 'open') {
            return redirect()->route('student.elections.index')->with('error', get_phrase('This election is not currently open for voting.'));
        }

        $verified = (bool) session('election_verified_' . $election->id);
        $votedPositionIds = ElectionVote::where('election_id', $election->id)
            ->where('voter_id', $student->id)
            ->pluck('position_id');

        return view('student.elections.show', compact('election', 'verified', 'votedPositionIds'));
    }

    public function verifyIdentity(Request $request, $id)
    {
        $election = Election::forSchool($this->school_id)->findOrFail((int) $id);
        $student = Auth::user();

        $request->validate(['registration_number' => ['required', 'string']]);

        if (trim((string) $request->input('registration_number')) !== (string) $student->code) {
            return redirect()->back()->with('error', get_phrase('That registration number does not match your account.'));
        }

        session(['election_verified_' . $election->id => true]);

        return redirect()->route('student.elections.show', $election->id)->with('message', get_phrase('Identity verified. You may now vote.'));
    }

    public function castVote(Request $request, $id)
    {
        $election = Election::forSchool($this->school_id)->findOrFail((int) $id);
        $student = Auth::user();

        if ($election->computed_status !== 'open') {
            return redirect()->back()->with('error', get_phrase('This election is not currently open for voting.'));
        }

        if (!session('election_verified_' . $election->id)) {
            return redirect()->back()->with('error', get_phrase('Please verify your identity before voting.'));
        }

        $validated = $request->validate([
            'position_id' => ['required', 'exists:election_positions,id'],
            'candidate_id' => ['required', 'exists:election_candidates,id'],
        ]);

        $position = ElectionPosition::where('election_id', $election->id)->findOrFail($validated['position_id']);
        $candidate = ElectionCandidate::where('position_id', $position->id)->findOrFail($validated['candidate_id']);

        try {
            DB::transaction(function () use ($election, $position, $candidate, $student) {
                ElectionVote::create([
                    'election_id' => $election->id,
                    'position_id' => $position->id,
                    'candidate_id' => $candidate->id,
                    'voter_id' => $student->id,
                    'school_id' => $this->school_id,
                    'created_at' => now(),
                ]);
            });
        } catch (\Illuminate\Database\QueryException $e) {
            // Unique constraint on (position_id, voter_id) — this is the
            // real guard; the session/UI checks above are just for a good
            // error message on the common path, not the actual integrity
            // enforcement, which is why this catch exists at all.
            return redirect()->back()->with('error', get_phrase('You have already voted for this position.'));
        }

        return redirect()->back()->with('message', get_phrase('Your vote has been recorded.'));
    }

    public function studentResults($id)
    {
        $election = Election::forSchool($this->school_id)->with('positions.candidates.student')->findOrFail((int) $id);

        if (!$election->results_published) {
            return redirect()->route('student.elections.index')->with('error', get_phrase('Results have not been published yet.'));
        }

        $results = $this->buildResults($election);

        return view('student.elections.results', compact('election', 'results'));
    }

    // ── Shared ───────────────────────────────────────────────────

    /**
     * Per-position vote tallies + winner, plus overall turnout — the exact
     * same computation feeds the admin live-preview and the student-facing
     * published results, so the two can never show different numbers.
     */
    private function buildResults(Election $election): array
    {
        $positions = $election->positions;
        $tally = [];

        foreach ($positions as $position) {
            $counts = ElectionVote::where('position_id', $position->id)
                ->selectRaw('candidate_id, COUNT(*) as votes')
                ->groupBy('candidate_id')
                ->pluck('votes', 'candidate_id');

            $candidateRows = $position->candidates->map(function ($candidate) use ($counts) {
                return [
                    'candidate' => $candidate,
                    'votes' => (int) ($counts[$candidate->id] ?? 0),
                ];
            })->sortByDesc('votes')->values();

            $tally[$position->id] = [
                'position' => $position,
                'candidates' => $candidateRows,
                'winner' => $candidateRows->first(),
                'total_votes' => (int) $candidateRows->sum('votes'),
            ];
        }

        $totalVoters = ElectionVote::where('election_id', $election->id)->distinct('voter_id')->count('voter_id');
        $registeredStudents = User::where('school_id', $election->school_id)->where('role_id', 7)->count();
        $turnoutPercent = $registeredStudents > 0 ? round($totalVoters / $registeredStudents * 100, 1) : 0;

        return [
            'positions' => $tally,
            'total_voters' => $totalVoters,
            'registered_students' => $registeredStudents,
            'turnout_percent' => $turnoutPercent,
        ];
    }
}
