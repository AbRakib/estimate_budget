<?php

namespace App\Http\Controllers;

use App\Http\Requests\RetryProjectEstimateRequest;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Jobs\ProcessProjectEstimate;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    public function index(Request $request): Response
    {
        $projects = Project::query()
            ->whereBelongsTo($request->user())
            ->with('estimate')
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Projects/Index', [
            'projects' => ProjectResource::collection($projects),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Projects/Upload', [
            'defaultHourlyRate' => (float) config('estimator.default_hourly_rate'),
            'defaultCountry' => config('estimator.default_country', 'BD'),
            'countries' => collect(config('estimator.countries', []))
                ->map(fn (array $country, string $code) => [
                    'code' => $code,
                    'name' => $country['name'],
                    'currency' => $country['currency'],
                ])
                ->values(),
        ]);
    }

    public function store(StoreProjectRequest $request): RedirectResponse
    {
        $project = DB::transaction(function () use ($request) {
            $path = $request->file('requirements_pdf')->store('project-requirements');

            $project = new Project;
            $project->user_id = $request->user()->id;
            $project->title = $request->validated('title');
            $project->file_path = $path;
            $project->status = 'pending';
            $project->hourly_rate = $request->validated('hourly_rate') ?: config('estimator.default_hourly_rate');
            $project->country = $request->validated('country');
            $project->save();

            return $project;
        });

        ProcessProjectEstimate::dispatch($project->id);

        return to_route('projects.show', $project);
    }

    public function show(Request $request, Project $project): Response
    {
        abort_unless($project->user_id === $request->user()->id, 403);

        return Inertia::render('Projects/Show', [
            'project' => ProjectResource::make($project->load(['features', 'estimate'])),
        ]);
    }

    public function status(Request $request, Project $project): JsonResponse
    {
        abort_unless($project->user_id === $request->user()->id, 403);

        return response()->json([
            'data' => ProjectResource::make($project->load(['features', 'estimate']))->resolve(),
        ]);
    }

    public function retry(RetryProjectEstimateRequest $request, Project $project): RedirectResponse
    {
        $project->update([
            'status' => 'pending',
            'failure_reason' => null,
        ]);

        ProcessProjectEstimate::dispatch($project->id);

        return to_route('projects.show', $project);
    }

    public function destroy(Request $request, Project $project): RedirectResponse
    {
        abort_unless($project->user_id === $request->user()->id, 403);

        Storage::disk('local')->delete($project->file_path);
        $project->delete();

        return to_route('projects.index');
    }
}
