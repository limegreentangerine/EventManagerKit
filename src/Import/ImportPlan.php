<?php

declare(strict_types=1);

namespace EventManagerKit\Import;

/**
 * Result of parsing a CSV: rows that are safe to import, and a per-line error
 * report for those that are not. Nothing is written until the plan is applied.
 */
final class ImportPlan
{
    /** @var list<ImportRow> */
    public array $rows = [];

    /** @var list<array{line:int,message:string}> */
    public array $errors = [];

    public function addError(int $line, string $message): void
    {
        $this->errors[] = ['line' => $line, 'message' => $message];
    }
}
