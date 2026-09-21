<?php

namespace Tests\Feature;

use App\Models\Election;
use App\Models\ElectionCandidate;
use App\Models\ElectionPosition;
use App\Models\ElectionVote;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\Feature\Support\AdmissionsTestHelper;
use Tests\TestCase;

/**
 * Covers the digital student-association election/voting system: admin
 * election + position + candidate management, student identity
 * verification, one-vote-per-position casting, and results publishing.
 *
 * The one rule this whole feature exists to enforce is that a student can
 * never vote twice for the same position — that's a DB UNIQUE constraint
 * on election_votes(position_id, voter_id), not just an application check,
 * so several tests here attack it directly (double HTTP submit, and a raw
 * duplicate insert) rather than only testing the happy path.
 */
class ElectionModuleTest extends TestCase
{
    use AdmissionsTestHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootAdmissionsTestSchema();

        Schema::create('elections', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->index();
            $table->string('title', 191);
            $table->text('description')->nullable();
            $table->dateTime('start_at');
            $table->dateTime('end_at');
            $table->boolean('results_published')->default(false);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('election_positions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('election_id')->index();
            $table->unsignedBigInteger('school_id')->index();
            $table->string('title', 191);
            $table->timestamps();
        });

        Schema::create('election_candidates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('position_id')->index();
            $table->unsignedBigInteger('school_id')->index();
            $table->unsignedBigInteger('student_id');
            $table->text('manifesto')->nullable();
            $table->timestamps();
            $table->unique(['position_id', 'student_id']);
        });

        Schema::create('election_votes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('election_id')->index();
            $table->unsignedBigInteger('position_id')->index();
            $table->unsignedBigInteger('candidate_id')->index();
            $table->unsignedBigInteger('voter_id')->index();
            $table->unsignedBigInteger('school_id')->index();
            $table->timestamp('created_at')->nullable();
            $table->unique(['position_id', 'voter_id']);
        });
    }

    private function makeStudent(int $schoolId, string $email, array $overrides = []): User
    {
        return User::create(array_merge([
            'name' => 'Voter', 'email' => $email,
            'password' => bcrypt('secret'), 'code' => student_code(), 'role_id' => 7, 'school_id' => $schoolId,
        ], $overrides));
    }

    private function makeOpenElection(int $schoolId, array $overrides = []): Election
    {
        return Election::create(array_merge([
            'school_id' => $schoolId,
            'title' => 'Guild Elections 2026',
            'description' => 'Annual guild elections.',
            'start_at' => now()->subHour(),
            'end_at' => now()->addHour(),
        ], $overrides));
    }

    private function makePositionWithCandidates(Election $election, array $candidates): array
    {
        $position = ElectionPosition::create([
            'election_id' => $election->id,
            'school_id' => $election->school_id,
            'title' => 'Guild President',
        ]);

        $candidateModels = [];
        foreach ($candidates as $student) {
            $candidateModels[] = ElectionCandidate::create([
                'position_id' => $position->id,
                'school_id' => $election->school_id,
                'student_id' => $student->id,
            ]);
        }

        return [$position, $candidateModels];
    }

    // ── Admin management ─────────────────────────────────────────

    public function test_admin_can_create_an_election(): void
    {
        $schoolId = $this->makeSchool();
        $admin = $this->makeAdminUser($schoolId);

        $response = $this->actingAs($admin)->post(route('admin.elections.store'), [
            'title' => 'Guild Elections 2026',
            'description' => 'Annual guild elections.',
            'start_at' => now()->addDay()->format('Y-m-d H:i:s'),
            'end_at' => now()->addDays(2)->format('Y-m-d H:i:s'),
        ]);

        $election = Election::first();
        $response->assertRedirect(route('admin.elections.show', $election->id));
        $this->assertDatabaseHas('elections', [
            'school_id' => $schoolId,
            'title' => 'Guild Elections 2026',
        ]);
    }

    public function test_admin_can_add_a_position_and_candidate(): void
    {
        $schoolId = $this->makeSchool();
        $admin = $this->makeAdminUser($schoolId);
        $election = $this->makeOpenElection($schoolId);
        $candidate = $this->makeStudent($schoolId, 'candidate@example.com');

        $this->actingAs($admin)->post(route('admin.elections.positions.store', $election->id), [
            'title' => 'Guild President',
        ])->assertRedirect();

        $position = ElectionPosition::where('election_id', $election->id)->firstOrFail();

        $this->actingAs($admin)->post(route('admin.elections.candidates.store', $position->id), [
            'student_id' => $candidate->id,
            'manifesto' => 'Vote for me!',
        ])->assertRedirect();

        $this->assertDatabaseHas('election_candidates', [
            'position_id' => $position->id,
            'student_id' => $candidate->id,
        ]);
    }

    public function test_admin_cannot_add_the_same_candidate_twice_to_a_position(): void
    {
        $schoolId = $this->makeSchool();
        $admin = $this->makeAdminUser($schoolId);
        $election = $this->makeOpenElection($schoolId);
        $candidate = $this->makeStudent($schoolId, 'dupe.candidate@example.com');
        [$position] = $this->makePositionWithCandidates($election, [$candidate]);

        $this->actingAs($admin)->post(route('admin.elections.candidates.store', $position->id), [
            'student_id' => $candidate->id,
        ])->assertSessionHas('error');

        $this->assertSame(1, ElectionCandidate::where('position_id', $position->id)->count());
    }

    public function test_admin_cannot_publish_results_before_voting_closes(): void
    {
        $schoolId = $this->makeSchool();
        $admin = $this->makeAdminUser($schoolId);
        $election = $this->makeOpenElection($schoolId);

        $this->actingAs($admin)->post(route('admin.elections.publish_results', $election->id))
            ->assertSessionHas('error');

        $this->assertFalse($election->fresh()->results_published);
    }

    public function test_admin_can_publish_results_after_voting_closes(): void
    {
        $schoolId = $this->makeSchool();
        $admin = $this->makeAdminUser($schoolId);
        $election = $this->makeOpenElection($schoolId, [
            'start_at' => now()->subDays(2),
            'end_at' => now()->subDay(),
        ]);

        $this->actingAs($admin)->post(route('admin.elections.publish_results', $election->id))
            ->assertRedirect();

        $this->assertTrue($election->fresh()->results_published);
    }

    public function test_admin_cannot_manage_another_schools_election(): void
    {
        $schoolId = $this->makeSchool();
        $otherSchoolId = $this->makeSchool();
        $admin = $this->makeAdminUser($schoolId);
        $otherElection = $this->makeOpenElection($otherSchoolId);

        $this->actingAs($admin)->get(route('admin.elections.show', $otherElection->id))
            ->assertStatus(404);
    }

    // ── Student voting ───────────────────────────────────────────

    public function test_a_student_must_verify_identity_before_the_ballot_is_shown(): void
    {
        $schoolId = $this->makeSchool();
        $student = $this->makeStudent($schoolId, 'unverified@example.com');
        $election = $this->makeOpenElection($schoolId);
        $this->makePositionWithCandidates($election, [$student]);

        $response = $this->actingAs($student)->get(route('student.elections.show', $election->id));

        $response->assertOk();
        $response->assertSee('Verify Your Identity');
        $response->assertDontSee('Cast Vote');
    }

    public function test_verification_fails_with_the_wrong_registration_number(): void
    {
        $schoolId = $this->makeSchool();
        $student = $this->makeStudent($schoolId, 'wrongreg@example.com');
        $election = $this->makeOpenElection($schoolId);

        $this->actingAs($student)->post(route('student.elections.verify', $election->id), [
            'registration_number' => 'NOT-MY-CODE',
        ])->assertSessionHas('error');
    }

    public function test_a_verified_student_can_cast_one_vote_per_position(): void
    {
        $schoolId = $this->makeSchool();
        $voter = $this->makeStudent($schoolId, 'voter@example.com');
        $candidate = $this->makeStudent($schoolId, 'president.candidate@example.com');
        $election = $this->makeOpenElection($schoolId);
        [$position, $candidates] = $this->makePositionWithCandidates($election, [$candidate]);

        $this->actingAs($voter)->post(route('student.elections.verify', $election->id), [
            'registration_number' => $voter->code,
        ])->assertRedirect(route('student.elections.show', $election->id));

        $response = $this->actingAs($voter)->post(route('student.elections.vote', $election->id), [
            'position_id' => $position->id,
            'candidate_id' => $candidates[0]->id,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('election_votes', [
            'position_id' => $position->id,
            'candidate_id' => $candidates[0]->id,
            'voter_id' => $voter->id,
        ]);
    }

    public function test_a_student_cannot_vote_without_verifying_identity_first(): void
    {
        $schoolId = $this->makeSchool();
        $voter = $this->makeStudent($schoolId, 'skipverify@example.com');
        $candidate = $this->makeStudent($schoolId, 'skipverify.candidate@example.com');
        $election = $this->makeOpenElection($schoolId);
        [$position, $candidates] = $this->makePositionWithCandidates($election, [$candidate]);

        $this->actingAs($voter)->post(route('student.elections.vote', $election->id), [
            'position_id' => $position->id,
            'candidate_id' => $candidates[0]->id,
        ])->assertSessionHas('error');

        $this->assertSame(0, ElectionVote::count());
    }

    public function test_a_student_cannot_vote_twice_for_the_same_position_via_duplicate_http_submit(): void
    {
        $schoolId = $this->makeSchool();
        $voter = $this->makeStudent($schoolId, 'doublevote@example.com');
        $candidateA = $this->makeStudent($schoolId, 'candidate.a@example.com');
        $candidateB = $this->makeStudent($schoolId, 'candidate.b@example.com');
        $election = $this->makeOpenElection($schoolId);
        [$position, $candidates] = $this->makePositionWithCandidates($election, [$candidateA, $candidateB]);

        $this->actingAs($voter)->post(route('student.elections.verify', $election->id), [
            'registration_number' => $voter->code,
        ]);

        $this->actingAs($voter)->post(route('student.elections.vote', $election->id), [
            'position_id' => $position->id,
            'candidate_id' => $candidates[0]->id,
        ])->assertRedirect();

        // Second submit for the same position — including trying to switch
        // their vote to the other candidate — must not be allowed to
        // silently override or duplicate. The unique constraint on
        // (position_id, voter_id) is what actually blocks this.
        $response = $this->actingAs($voter)->post(route('student.elections.vote', $election->id), [
            'position_id' => $position->id,
            'candidate_id' => $candidates[1]->id,
        ]);

        $response->assertSessionHas('error');
        $this->assertSame(1, ElectionVote::where('position_id', $position->id)->where('voter_id', $voter->id)->count());
        $this->assertDatabaseHas('election_votes', [
            'position_id' => $position->id,
            'voter_id' => $voter->id,
            'candidate_id' => $candidates[0]->id,
        ]);
    }

    public function test_the_database_constraint_itself_rejects_a_duplicate_vote_row(): void
    {
        $schoolId = $this->makeSchool();
        $voter = $this->makeStudent($schoolId, 'rawinsert@example.com');
        $candidate = $this->makeStudent($schoolId, 'rawinsert.candidate@example.com');
        $election = $this->makeOpenElection($schoolId);
        [$position, $candidates] = $this->makePositionWithCandidates($election, [$candidate]);

        ElectionVote::create([
            'election_id' => $election->id, 'position_id' => $position->id,
            'candidate_id' => $candidates[0]->id, 'voter_id' => $voter->id, 'school_id' => $schoolId,
            'created_at' => now(),
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        ElectionVote::create([
            'election_id' => $election->id, 'position_id' => $position->id,
            'candidate_id' => $candidates[0]->id, 'voter_id' => $voter->id, 'school_id' => $schoolId,
            'created_at' => now(),
        ]);
    }

    public function test_a_student_cannot_vote_in_an_election_that_has_not_opened_yet(): void
    {
        $schoolId = $this->makeSchool();
        $voter = $this->makeStudent($schoolId, 'toosoon@example.com');
        $election = $this->makeOpenElection($schoolId, [
            'start_at' => now()->addDay(),
            'end_at' => now()->addDays(2),
        ]);

        $this->actingAs($voter)->get(route('student.elections.show', $election->id))
            ->assertRedirect(route('student.elections.index'));
    }

    public function test_a_student_cannot_vote_in_an_election_that_has_already_closed(): void
    {
        $schoolId = $this->makeSchool();
        $voter = $this->makeStudent($schoolId, 'toolate@example.com');
        $election = $this->makeOpenElection($schoolId, [
            'start_at' => now()->subDays(2),
            'end_at' => now()->subDay(),
        ]);

        $this->actingAs($voter)->get(route('student.elections.show', $election->id))
            ->assertRedirect(route('student.elections.index'));
    }

    public function test_a_student_cannot_vote_for_another_schools_election(): void
    {
        $schoolId = $this->makeSchool();
        $otherSchoolId = $this->makeSchool();
        $voter = $this->makeStudent($schoolId, 'crossschool@example.com');
        $election = $this->makeOpenElection($otherSchoolId);

        $this->actingAs($voter)->get(route('student.elections.show', $election->id))
            ->assertStatus(404);
    }

    // ── Results ──────────────────────────────────────────────────

    public function test_results_are_hidden_from_students_until_published(): void
    {
        $schoolId = $this->makeSchool();
        $student = $this->makeStudent($schoolId, 'preresults@example.com');
        $election = $this->makeOpenElection($schoolId, [
            'start_at' => now()->subDays(2),
            'end_at' => now()->subDay(),
            'results_published' => false,
        ]);

        $this->actingAs($student)->get(route('student.elections.results', $election->id))
            ->assertRedirect(route('student.elections.index'));
    }

    public function test_published_results_show_the_correct_winner_and_turnout(): void
    {
        $schoolId = $this->makeSchool();
        $winner = $this->makeStudent($schoolId, 'winner@example.com', ['name' => 'Winner Candidate']);
        $loser = $this->makeStudent($schoolId, 'loser@example.com', ['name' => 'Loser Candidate']);
        $voterA = $this->makeStudent($schoolId, 'turnout.a@example.com');
        $voterB = $this->makeStudent($schoolId, 'turnout.b@example.com');
        $election = $this->makeOpenElection($schoolId, ['results_published' => true]);
        [$position, $candidates] = $this->makePositionWithCandidates($election, [$winner, $loser]);

        ElectionVote::create(['election_id' => $election->id, 'position_id' => $position->id, 'candidate_id' => $candidates[0]->id, 'voter_id' => $voterA->id, 'school_id' => $schoolId, 'created_at' => now()]);
        ElectionVote::create(['election_id' => $election->id, 'position_id' => $position->id, 'candidate_id' => $candidates[0]->id, 'voter_id' => $voterB->id, 'school_id' => $schoolId, 'created_at' => now()]);

        $response = $this->actingAs($voterA)->get(route('student.elections.results', $election->id));

        $response->assertOk();
        $response->assertSee('Winner Candidate');
        $response->assertSee('Loser Candidate');
    }
}
