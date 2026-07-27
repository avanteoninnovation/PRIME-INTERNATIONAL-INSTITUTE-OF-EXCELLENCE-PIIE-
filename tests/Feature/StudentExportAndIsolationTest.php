<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Export\ExcelExportService;
use Tests\Feature\Support\AdmissionsTestHelper;
use Tests\TestCase;

class StudentExportAndIsolationTest extends TestCase
{
    use AdmissionsTestHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootAdmissionsTestSchema();
    }

    public function test_excel_export_service_produces_a_genuine_xlsx_not_a_renamed_csv(): void
    {
        $response = ExcelExportService::download('unit_test_export', ['Name', 'Email'], [
            ['Jane Doe', 'jane@example.com'],
        ]);

        $content = '';
        ob_start();
        $response->sendContent();
        $content = ob_get_clean();

        // A genuine .xlsx is a ZIP container — it must start with the ZIP
        // local-file-header magic bytes "PK". A CSV-renamed-to-.xlsx would
        // start with plain text ("Name,Email...") instead.
        $this->assertSame("PK", substr($content, 0, 2), 'Exported file must be a real .xlsx (ZIP) container, not a renamed CSV.');
        $this->assertStringContainsString(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            $response->headers->get('Content-Type')
        );
    }

    public function test_admin_can_only_export_students_belonging_to_their_own_school(): void
    {
        $schoolA = $this->makeSchool(['title' => 'School A']);
        $schoolB = $this->makeSchool(['title' => 'School B']);

        $adminA = $this->makeAdminUser($schoolA);

        $studentA = User::create([
            'name' => 'Student A', 'email' => 'student.a@example.com',
            'password' => bcrypt('secret'), 'code' => student_code(), 'role_id' => 7, 'school_id' => $schoolA,
        ]);
        $studentB = User::create([
            'name' => 'Student B', 'email' => 'student.b@example.com',
            'password' => bcrypt('secret'), 'code' => student_code(), 'role_id' => 7, 'school_id' => $schoolB,
        ]);

        \Illuminate\Support\Facades\DB::table('enrollment')->insert([
            ['user_id' => $studentA->id, 'class_id' => 1, 'section_id' => 1, 'school_id' => $schoolA, 'created_at' => now(), 'updated_at' => now()],
            ['user_id' => $studentB->id, 'class_id' => 1, 'section_id' => 1, 'school_id' => $schoolB, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $response = $this->actingAs($adminA)->get(route('admin.student.export_excel'));

        $response->assertStatus(200);

        // Read the generated workbook back and confirm only School A's
        // student appears — School B's student must never be exposed.
        $tmp = tempnam(sys_get_temp_dir(), 'xlsx');
        file_put_contents($tmp, $response->streamedContent());
        $sheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($tmp)->getActiveSheet();
        $rows = $sheet->toArray();
        unlink($tmp);

        $emails = array_column($rows, 3); // column D = Email
        $this->assertContains('student.a@example.com', $emails);
        $this->assertNotContains('student.b@example.com', $emails, 'A school admin must never see another school\'s students in their export.');
    }
}
