<?php

use App\Services\BudgetEstimationService;
use Illuminate\Support\Facades\Http;

test('budget estimation service parses openai json response', function () {
    config([
        'estimator.openai.api_key' => 'test-key',
        'estimator.openai.base_url' => 'https://api.openai.com/v1',
    ]);

    Http::fake([
        'api.openai.com/*' => Http::response([
            'choices' => [
                [
                    'message' => [
                        'content' => json_encode([
                            'features' => [
                                [
                                    'name' => 'Dashboard',
                                    'description' => 'Build project overview dashboard',
                                    'category' => 'frontend',
                                    'estimated_hours' => 8,
                                    'complexity' => 'medium',
                                ],
                            ],
                            'summary' => [
                                'currency' => 'USD',
                                'ai_notes' => 'Assumes existing authentication.',
                            ],
                        ]),
                    ],
                ],
            ],
        ]),
    ]);

    $estimate = app(BudgetEstimationService::class)->estimate('Build a dashboard', 100, 'BD');

    expect($estimate['features'])->toHaveCount(1)
        ->and($estimate['features'][0]['estimated_cost'])->toBe(800.0)
        ->and($estimate['summary']['total_hours'])->toBe(8.0)
        ->and($estimate['summary']['total_cost'])->toBe(800.0)
        ->and($estimate['summary']['currency'])->toBe('BDT')
        ->and($estimate['summary']['ai_notes'])->toBe('Assumes existing authentication.');
});

test('budget estimation service retries malformed json once', function () {
    config([
        'estimator.openai.api_key' => 'test-key',
        'estimator.openai.base_url' => 'https://api.openai.com/v1',
    ]);

    Http::fakeSequence('api.openai.com/*')
        ->push(['choices' => [['message' => ['content' => 'not json']]]])
        ->push([
            'choices' => [
                [
                    'message' => [
                        'content' => json_encode([
                            'features' => [
                                [
                                    'name' => 'API',
                                    'description' => 'Build backend API',
                                    'category' => 'backend',
                                    'estimated_hours' => 4,
                                    'complexity' => 'low',
                                ],
                            ],
                            'summary' => ['currency' => 'USD'],
                        ]),
                    ],
                ],
            ],
        ]);

    $estimate = app(BudgetEstimationService::class)->estimate('Build API', 50);

    expect($estimate['features'][0]['estimated_cost'])->toBe(200.0);
    Http::assertSentCount(2);
});
