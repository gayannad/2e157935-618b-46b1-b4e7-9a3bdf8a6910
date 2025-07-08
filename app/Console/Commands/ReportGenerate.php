<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;

class ReportGenerate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'report-generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a student assessment report';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $studentId = $this->ask('Please Enter The Student ID?');
        $reportType = $this->choice('Select Report Type', ['Diagnostic', 'Progress', 'Feedback'], 0);

        $students = collect(json_decode(file_get_contents(resource_path('data/students.json')), true));
        $assessments = collect(json_decode(file_get_contents(resource_path('data/assessments.json')), true));
        $questions = collect(json_decode(file_get_contents(resource_path('data/questions.json')), true));
        $responses = collect(json_decode(file_get_contents(resource_path('data/student-responses.json')), true));

        $student = $students->firstWhere('id', $studentId);

        if (! $student) {
            $this->error('Student not found.');

            return;
        }

        $studentResponses = $responses
            ->filter(fn ($r) => isset($r['student']['id']) &&
                $r['student']['id'] === $studentId &&
                ! empty($r['completed'])
            )
            ->sortByDesc(fn ($r) => Carbon::createFromFormat('d/m/Y H:i:s', $r['completed'])->timestamp
            )
            ->values();

        if ($studentResponses->isEmpty()) {
            $this->warn('No completed assessments found.');

            return;
        }

        match ($reportType) {
            'Diagnostic' => $this->generateDiagnosticReport($student, $studentResponses->first(), $assessments, $questions),
            'Progress' => $this->generateProgressReport($student, $studentResponses, $assessments, $questions),
            'Feedback' => $this->generateFeedbackReport($student, $studentResponses->first(), $assessments, $questions),
        };
    }

    /**
     *generate diagnostic report
     */
    private function generateDiagnosticReport($student, $latest, $assessments, $questions)
    {
        $assessment = $assessments->firstWhere('id', $latest['assessmentId']);
        $completedAt = Carbon::createFromFormat('d/m/Y H:i:s', $latest['completed']);
        $this->info("{$student['firstName']} {$student['lastName']} recently completed {$assessment['name']} assessment on {$completedAt->format('jS F Y h:i A')}");

        $correctCount = 0;
        $totalCount = count($latest['responses']);
        $byStrand = [];

        foreach ($latest['responses'] as $response) {
            $question = $questions->firstWhere('id', $response['questionId']);
            $strand = $question['strand'];
            $correctOption = $question['config']['key'];
            $studentOption = $response['response'];

            $isCorrect = $studentOption === $correctOption;

            $byStrand[$strand]['total'] = ($byStrand[$strand]['total'] ?? 0) + 1;
            if ($isCorrect) {
                $byStrand[$strand]['correct'] = ($byStrand[$strand]['correct'] ?? 0) + 1;
                $correctCount++;
            }
        }

        $this->info("He got {$correctCount} questions right out of {$totalCount}. Details by strand given below:");
        foreach ($byStrand as $strand => $data) {
            $correct = $data['correct'] ?? 0;
            $total = $data['total'] ?? 0;
            $this->line("{$strand}: {$correct} out of {$total} correct");
        }
    }

    /**
     *generate progress report
     */
    private function generateProgressReport($student, $responses, $assessments, $questions)
    {
        $firstAssessment = $assessments->firstWhere('id', $responses->first()['assessmentId']);
        $this->info("{$student['firstName']} {$student['lastName']} has completed {$firstAssessment['name']} assessment {$responses->count()} times. Date and raw score given below:\n");

        $scores = [];

        foreach ($responses->sortBy('completed') as $response) {
            $completedAt = Carbon::createFromFormat('d/m/Y H:i:s', $response['completed']);
            $correct = collect($response['responses'])->filter(function ($res) use ($questions) {
                $question = $questions->firstWhere('id', $res['questionId']);

                return $res['response'] === $question['config']['key'];
            })->count();

            $total = count($response['responses']);
            $scores[] = ['date' => $completedAt->format('jS F Y h:i A'), 'score' => "{$correct} out of {$total}"];
            $this->line("Date: {$completedAt->format('jS F Y h:i A')}, Raw Score: {$correct} out of {$total}");
        }

        $diff = (int) explode(' ', $scores[array_key_last($scores)]['score'])[0] -
            (int) explode(' ', $scores[0]['score'])[0];

        $this->info("{$student['firstName']} {$student['lastName']} got {$diff} more correct in the recent completed assessment than the oldest.");
    }

    /**
     *generate feedback report
     */
    private function generateFeedbackReport($student, $latest, $assessments, $questions)
    {
        $assessment = $assessments->firstWhere('id', $latest['assessmentId']);
        $completedAt = Carbon::createFromFormat('d/m/Y H:i:s', $latest['completed']);
        $this->info("{$student['firstName']} {$student['lastName']} recently completed {$assessment['name']} assessment on {$completedAt->format('jS F Y h:i A')}:\n");

        foreach ($latest['responses'] as $response) {
            $question = $questions->firstWhere('id', $response['questionId']);
            $correctOptionId = $question['config']['key'];
            $studentOptionId = $response['response'];

            if ($studentOptionId !== $correctOptionId) {
                $options = collect($question['config']['options']);
                $correctOption = $options->firstWhere('id', $correctOptionId);
                $studentOption = $options->firstWhere('id', $studentOptionId);

                $this->line("Question: {$question['stem']}");
                $this->line("Your answer: {$studentOption['label']} with value {$studentOption['value']}");
                $this->line("Correct answer: {$correctOption['label']} with value {$correctOption['value']}");
                $this->line("Hint: {$question['config']['hint']}\n");
            }
        }
    }
}
