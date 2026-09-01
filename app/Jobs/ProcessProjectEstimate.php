<?php

namespace App\Jobs;

use App\Events\ProjectEstimateCompleted;
use App\Models\Project;
use App\Services\BudgetEstimationService;
use App\Services\PdfExtractionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ProcessProjectEstimate implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public function __construct(public readonly int $projectId) {}

    public function handle(PdfExtractionService $pdfExtraction, BudgetEstimationService $budgetEstimation): void
    {
        $project = Project::query()->findOrFail($this->projectId);

        if ($project->status === 'completed') {
            return;
        }

        $project->update([
            'status' => 'processing',
            'failure_reason' => null,
        ]);

        try {
            $text = $pdfExtraction->extract(Storage::disk('local')->path($project->file_path));
            $estimate = $budgetEstimation->estimate($text, (float) $project->hourly_rate, $project->country);

            DB::transaction(function () use ($project, $text, $estimate) {
                $project = Project::query()->whereKey($project->id)->lockForUpdate()->firstOrFail();
                $project->features()->delete();
                $project->estimate()->delete();

                $project->update([
                    'requirements_text' => $text,
                    'status' => 'completed',
                    'raw_ai_response' => $estimate['raw_response'],
                    'failure_reason' => null,
                ]);

                $project->features()->createMany($estimate['features']);
                $project->estimate()->create($estimate['summary']);
            });

            ProjectEstimateCompleted::dispatch($project->fresh(['features', 'estimate']));
        } catch (Throwable $exception) {
            $project->update([
                'status' => 'failed',
                'failure_reason' => $exception->getMessage(),
            ]);

            ProjectEstimateCompleted::dispatch($project->fresh(['features', 'estimate']));

            throw $exception;
        }
    }
}
