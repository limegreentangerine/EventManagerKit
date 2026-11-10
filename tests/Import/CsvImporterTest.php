<?php

declare(strict_types=1);

namespace EventManagerKit\Tests\Import;

use PHPUnit\Framework\TestCase;
use EventManagerKit\Import\{CsvImporter, ImportPlan};

final class CsvImporterTest extends TestCase
{
    private const LOCALES = ['en_GB', 'cy_GB'];

    private function parse(string $csv): ImportPlan
    {
        $h = fopen('php://memory', 'r+');
        fwrite($h, $csv);
        rewind($h);

        return (new CsvImporter())->parse($h, self::LOCALES);
    }

    public function testParsesPerLocaleRow(): void
    {
        $plan = $this->parse(<<<CSV
        start,end,name_en_GB,name_cy_GB,location_en_GB,location_cy_GB,sub_location_en_GB,type_en_GB,type_cy_GB,tags_en_GB,tags_cy_GB
        15/05/2026 10:00,15/05/2026 12:30,Open day,Diwrnod agored,Main hall,Neuadd,Stage,Workshop,Gweithdy,Year 1;Year 2,Blwyddyn 1;Blwyddyn 2
        CSV);

        $this->assertSame([], $plan->errors);
        $row = $plan->rows[0];
        $this->assertSame(['en_GB' => 'Open day', 'cy_GB' => 'Diwrnod agored'], $row->names);
        $this->assertSame('2026-05-15 10:00', $row->start->format('Y-m-d H:i'));
        $this->assertFalse($row->allDay);
        $this->assertSame(['en_GB' => 'Stage'], $row->subLocation);
        $this->assertSame(['en_GB' => 'Year 2', 'cy_GB' => 'Blwyddyn 2'], $row->tags[1]);
    }

    public function testDateOnlyRowsAreAllDay(): void
    {
        $plan = $this->parse("start,end,name_en_GB\n2026-05-15,2026-05-16,Fete\n");

        $this->assertTrue($plan->rows[0]->allDay);
    }

    public function testReportsEveryBadRowWithItsLineNumber(): void
    {
        $plan = $this->parse(<<<CSV
        start,end,name_en_GB
        2026-05-15 10:00,2026-05-15 11:00,Good
        nonsense,2026-05-15 11:00,Bad date
        2026-05-16 10:00,2026-05-15 11:00,Backwards
        2026-05-17 10:00,2026-05-17 11:00,
        CSV);

        $this->assertCount(1, $plan->rows);
        $this->assertSame([3, 4, 5], array_column($plan->errors, 'line'));
    }

    public function testImpossibleDatesAreRejected(): void
    {
        $plan = $this->parse("start,end,name_en_GB\n31/02/2026,01/03/2026,Nope\n");

        $this->assertSame([], $plan->rows);
        $this->assertNotEmpty($plan->errors);
    }

    public function testHeaderProblemsStopTheImport(): void
    {
        $plan = $this->parse("start,name_fr_FR\n2026-05-15,Fete\n");

        $messages = implode(' | ', array_column($plan->errors, 'message'));
        $this->assertStringContainsString('"end"', $messages);
        $this->assertStringContainsString('fr_FR', $messages);
        $this->assertSame([], $plan->rows);
    }

    public function testMismatchedTagCountsAcrossLocalesAreRejected(): void
    {
        $plan = $this->parse("start,end,name_en_GB,tags_en_GB,tags_cy_GB\n2026-05-15,2026-05-15,X,a;b,x\n");

        $this->assertSame([], $plan->rows);
        $this->assertStringContainsString('same number of tags', $plan->errors[0]['message']);
    }

    public function testEmptyFile(): void
    {
        $this->assertSame('The file is empty.', $this->parse('')->errors[0]['message']);
    }

    public function testHandleFor(): void
    {
        $this->assertSame('year_1', CsvImporter::handleFor(' Year 1! '));
        $this->assertSame('main_hall', CsvImporter::handleFor('Main Hall'));
    }
}
