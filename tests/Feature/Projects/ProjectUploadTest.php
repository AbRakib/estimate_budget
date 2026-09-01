<?php

use App\Jobs\ProcessProjectEstimate;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('users can upload a pdf project requirement file', function () {
    Queue::fake();
    Storage::fake('local');

    $user = User::factory()->create();
    config(['estimator.default_hourly_rate' => 80]);

    $response = $this->actingAs($user)->post(route('projects.store'), [
        'title' => 'Inventory portal',
        'country' => 'BD',
        'requirements_pdf' => UploadedFile::fake()->create('requirements.pdf', 100, 'application/pdf'),
    ]);

    $project = Project::firstOrFail();

    $response->assertRedirect(route('projects.show', $project));
    expect($project->status)->toBe('pending')
        ->and((float) $project->hourly_rate)->toBe(80.0)
        ->and($project->country)->toBe('BD')
        ->and($project->user_id)->toBe($user->id);

    Storage::disk('local')->assertExists($project->file_path);
    Queue::assertPushed(ProcessProjectEstimate::class, fn ($job) => $job->projectId === $project->id);
});

test('project upload requires a pdf under ten megabytes', function () {
    Queue::fake();
    Storage::fake('local');

    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('projects.store'), [
            'title' => 'Inventory portal',
            'country' => 'BD',
            'requirements_pdf' => UploadedFile::fake()->create('requirements.txt', 1, 'text/plain'),
        ])
        ->assertSessionHasErrors('requirements_pdf');

    $this->actingAs($user)
        ->post(route('projects.store'), [
            'title' => 'Inventory portal',
            'country' => 'BD',
            'requirements_pdf' => UploadedFile::fake()->create('requirements.pdf', 10_241, 'application/pdf'),
        ])
        ->assertSessionHasErrors('requirements_pdf');

    Queue::assertNothingPushed();
});

test('project upload requires a supported country', function () {
    Queue::fake();
    Storage::fake('local');

    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('projects.store'), [
            'title' => 'Inventory portal',
            'country' => 'ZZ',
            'requirements_pdf' => UploadedFile::fake()->create('requirements.pdf', 100, 'application/pdf'),
        ])
        ->assertSessionHasErrors('country');

    Queue::assertNothingPushed();
});
