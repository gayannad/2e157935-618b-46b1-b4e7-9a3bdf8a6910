<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportGenerateTest extends TestCase
{
    public function test_report_generate_command_runs_successfully()
    {
        $choices = ['Diagnostic', 'Progress', 'Feedback'];

        $this->artisan('report-generate')
            ->expectsQuestion('Please Enter The Student ID?', 'student1')
            ->expectsChoice(
                'Select Report Type',
                'Diagnostic',
                $choices
            )
            ->assertExitCode(0);
    }
}
