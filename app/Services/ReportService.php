<?php

namespace App\Services;

class ReportService
{
    public function generateDiagnosticReport($student, $latest, $assessments, $questions)
    {
        $assessment = $assessments->firstWhere('id', $latest['assessment_id']);
        $this->info("{$student['name']} recently completed {$assessment['name']} assessment on {$latest['completed_at']}");
    }

    public function generateProgressReport() {}

    public function generateFeedbackReport() {}
}
