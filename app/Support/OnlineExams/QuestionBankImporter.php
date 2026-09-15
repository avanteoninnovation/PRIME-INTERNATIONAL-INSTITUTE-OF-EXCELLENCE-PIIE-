<?php

namespace App\Support\OnlineExams;

use App\Models\QuestionBank;
use App\Models\Subject;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Bulk question-bank import from a CSV or Excel file.
 *
 * Mirrors OnlineExamController::storeBankQuestion()'s validation exactly —
 * a row that would be rejected by the single-question form is rejected here
 * too, just reported by row number instead of a form error. Bad rows are
 * skipped rather than failing the whole file, since a 200-row import
 * shouldn't be thrown away over one typo; an unmatched subject name is a
 * warning (question still imports, unassigned to a subject), not a hard
 * failure, since subject_id is nullable on this table.
 */
class QuestionBankImporter
{
    public const TEMPLATE_HEADERS = ['question', 'type', 'option_a', 'option_b', 'option_c', 'option_d', 'correct_ans', 'marks', 'difficulty', 'subject'];

    private const VALID_TYPES = ['mcq', 'true_false', 'short', 'essay'];
    private const VALID_DIFFICULTIES = ['easy', 'medium', 'hard'];

    /** @return array{imported: int, errors: string[], warnings: string[]} */
    public static function import(UploadedFile $file, int $schoolId, ?int $createdBy): array
    {
        $rows = self::parseRows($file);

        $errors = [];
        $warnings = [];
        $toInsert = [];

        $subjectsByName = Subject::where('school_id', $schoolId)
            ->get(['id', 'name'])
            ->keyBy(fn ($s) => mb_strtolower(trim($s->name)));

        foreach ($rows as $i => $row) {
            $rowNumber = $i + 2; // +1 for 0-index, +1 for the header row

            [$data, $rowErrors, $rowWarning] = self::normalizeRow($row, $schoolId, $createdBy, $subjectsByName);

            if ($rowErrors) {
                foreach ($rowErrors as $error) {
                    $errors[] = "Row {$rowNumber}: {$error}";
                }
                continue;
            }

            if ($rowWarning) {
                $warnings[] = "Row {$rowNumber}: {$rowWarning}";
            }

            $toInsert[] = $data;
        }

        if ($toInsert) {
            DB::transaction(function () use ($toInsert) {
                foreach ($toInsert as $data) {
                    QuestionBank::create($data);
                }
            });
        }

        return ['imported' => count($toInsert), 'errors' => $errors, 'warnings' => $warnings];
    }

    /** @return array{0: array, 1: string[], 2: ?string} [data, blocking errors, non-blocking warning] */
    private static function normalizeRow(array $row, int $schoolId, ?int $createdBy, $subjectsByName): array
    {
        $errors = [];
        $warning = null;

        $question = trim((string) ($row['question'] ?? ''));
        if ($question === '') {
            $errors[] = 'question is required.';
        }

        $type = self::normalizeType((string) ($row['type'] ?? ''));
        if (! $type) {
            $errors[] = 'type must be one of: mcq, true_false, short, essay (or multiple_choice, true/false, short_answer).';
        }

        $optionA = self::blankToNull($row['option_a'] ?? null);
        $optionB = self::blankToNull($row['option_b'] ?? null);
        $optionC = self::blankToNull($row['option_c'] ?? null);
        $optionD = self::blankToNull($row['option_d'] ?? null);
        $correctAns = self::blankToNull($row['correct_ans'] ?? null);

        if ($type === 'mcq') {
            $optionCount = collect([$optionA, $optionB, $optionC, $optionD])->filter()->count();
            if ($optionCount < 2) {
                $errors[] = 'multiple choice requires at least two non-empty options (option_a..option_d).';
            }
            if (! $correctAns) {
                $errors[] = 'multiple choice requires correct_ans (e.g. a, b, c or d).';
            }
        }

        if ($type === 'true_false' && ! $correctAns) {
            $errors[] = 'true_false requires correct_ans (true or false).';
        }

        $marksRaw = $row['marks'] ?? null;
        $marks = is_numeric($marksRaw) ? (int) $marksRaw : null;
        if ($marks === null || $marks < 1) {
            $errors[] = 'marks must be a whole number of at least 1.';
        }

        $difficulty = mb_strtolower(trim((string) ($row['difficulty'] ?? '')));
        if ($difficulty === '') {
            $difficulty = 'medium';
        } elseif (! in_array($difficulty, self::VALID_DIFFICULTIES, true)) {
            $errors[] = 'difficulty must be easy, medium or hard (or left blank for medium).';
        }

        $subjectId = null;
        $subjectName = trim((string) ($row['subject'] ?? ''));
        if ($subjectName !== '') {
            $match = $subjectsByName->get(mb_strtolower($subjectName));
            if ($match) {
                $subjectId = $match->id;
            } else {
                $warning = "subject '{$subjectName}' not found — imported without a subject.";
            }
        }

        if ($errors) {
            return [[], $errors, null];
        }

        return [[
            'school_id'   => $schoolId,
            'subject_id'  => $subjectId,
            'question'    => $question,
            'type'        => $type,
            'option_a'    => $optionA,
            'option_b'    => $optionB,
            'option_c'    => $optionC,
            'option_d'    => $optionD,
            'correct_ans' => $correctAns,
            'marks'       => $marks,
            'difficulty'  => $difficulty,
            'created_by'  => $createdBy,
        ], [], $warning];
    }

    private static function normalizeType(string $type): ?string
    {
        $type = mb_strtolower(trim($type));

        $map = [
            'mcq' => 'mcq', 'multiple_choice' => 'mcq', 'multiple choice' => 'mcq',
            'true_false' => 'true_false', 'true/false' => 'true_false', 'true false' => 'true_false',
            'short' => 'short', 'short_answer' => 'short', 'short answer' => 'short',
            'essay' => 'essay',
        ];

        return $map[$type] ?? (in_array($type, self::VALID_TYPES, true) ? $type : null);
    }

    private static function blankToNull($value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    /** @return array<int, array<string, mixed>> Rows keyed by lowercased, trimmed header. */
    private static function parseRows(UploadedFile $file): array
    {
        $extension = mb_strtolower($file->getClientOriginalExtension());

        return $extension === 'csv' || $extension === 'txt'
            ? self::parseCsv($file)
            : self::parseSpreadsheet($file);
    }

    private static function parseCsv(UploadedFile $file): array
    {
        $handle = fopen($file->getRealPath(), 'r');
        if (! $handle) {
            return [];
        }

        $header = fgetcsv($handle);
        if (! $header) {
            fclose($handle);
            return [];
        }

        $header = array_map(fn ($h) => mb_strtolower(trim((string) $h)), $header);
        $rows = [];

        while (($line = fgetcsv($handle)) !== false) {
            if (count(array_filter($line, fn ($v) => trim((string) $v) !== '')) === 0) {
                continue; // skip blank lines
            }
            $rows[] = array_combine($header, array_pad(array_slice($line, 0, count($header)), count($header), null));
        }

        fclose($handle);

        return $rows;
    }

    private static function parseSpreadsheet(UploadedFile $file): array
    {
        $spreadsheet = IOFactory::load($file->getRealPath());
        $sheet = $spreadsheet->getActiveSheet();
        $data = $sheet->toArray(null, true, true, false);

        if (! $data) {
            return [];
        }

        $header = array_map(fn ($h) => mb_strtolower(trim((string) $h)), array_shift($data));
        $rows = [];

        foreach ($data as $line) {
            if (count(array_filter($line, fn ($v) => trim((string) $v) !== '')) === 0) {
                continue;
            }
            $rows[] = array_combine($header, array_pad(array_slice($line, 0, count($header)), count($header), null));
        }

        return $rows;
    }
}
