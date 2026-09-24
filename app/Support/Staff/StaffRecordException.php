<?php

namespace App\Support\Staff;

/**
 * A staff record change that is authorized but invalid as submitted (bad
 * dates, duplicate NIN, rejected file…). User-facing; never contains a NIN,
 * a file path or file contents.
 */
class StaffRecordException extends \RuntimeException
{
    public function __construct(string $message, private array $errors = [])
    {
        parent::__construct($message);
    }

    /** Field => message, for form validation feedback. */
    public function errors(): array
    {
        return $this->errors ?: ['staff' => $this->getMessage()];
    }
}
