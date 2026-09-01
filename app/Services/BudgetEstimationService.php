<?php

namespace App\Services;

use Illuminate\Http\Client\Factory as HttpFactory;
use RuntimeException;

class BudgetEstimationService
{
    public function __construct(private readonly HttpFactory $http) {}

    /**
     * @return array{features: array<int, array<string, mixed>>, summary: array<string, mixed>, raw_response: array<string, mixed>}
     */
    public function estimate(string $requirementsText, float $hourlyRate, ?string $country = null): array
    {
        $apiKey = config('estimator.openai.api_key');
        $currency = $this->currency($country);

        if (! $apiKey) {
            throw new RuntimeException('OpenAI API key is not configured.');
        }

        $lastErrorMessage = '';

        for ($attempt = 1; $attempt <= 2; $attempt++) {
            try {
                $response = $this->http
                    ->withHeaders([
                        'Authorization' => 'Bearer '.$apiKey,
                    ])
                    ->acceptJson()
                    ->asJson()
                    ->timeout(60)
                    ->post(rtrim((string) config('estimator.openai.base_url'), '/').'/chat/completions', [
                        'model' => config('estimator.openai.model'),
                        'max_tokens' => (int) config('estimator.openai.max_tokens'),
                        'messages' => [
                            [
                                'role' => 'user',
                                'content' => $this->prompt($requirementsText, $hourlyRate, $currency),
                            ],
                        ],
                        'response_format' => [
                            'type' => 'json_schema',
                            'json_schema' => [
                                'name' => 'project_budget_estimate',
                                'strict' => true,
                                'schema' => $this->schema(),
                            ],
                        ],
                    ]);

                if ($response->failed()) {
                    throw new RuntimeException('OpenAI API request failed: '.$response->body());
                }

                $rawResponse = $response->json();
                $decoded = $this->decodeJson((string) data_get($rawResponse, 'choices.0.message.content', ''));

                return $this->validateEstimate($decoded, is_array($rawResponse) ? $rawResponse : [], $hourlyRate, $currency);
            } catch (RuntimeException $exception) {
                $lastErrorMessage = $exception->getMessage();
            }
        }

        throw new RuntimeException('Unable to parse a valid budget estimate from OpenAI after retrying. '.$lastErrorMessage);
    }

    private function prompt(string $requirementsText, float $hourlyRate, string $currency): string
    {
        return <<<PROMPT
You are a senior software project estimator. Analyze the project requirements and return only valid JSON, with no markdown.

Use this exact JSON shape:
{
  "features": [
    {
      "name": "Short feature name",
      "description": "What must be built",
      "category": "frontend|backend|fullstack",
      "estimated_hours": 12.5,
      "complexity": "low|medium|high"
    }
  ],
  "summary": {
    "currency": "{$currency}",
    "ai_notes": "Concise assumptions, risks, and exclusions"
  }
}

Rules:
- Break requirements into implementation features.
- Use decimal hours.
- Choose only frontend, backend, or fullstack for category.
- Choose only low, medium, or high for complexity.
- Do not include estimated_cost; the application will calculate it using hourly rate {$hourlyRate}.
- Return valid JSON only.

Requirements:
{$requirementsText}
PROMPT;
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJson(string $content): array
    {
        $json = trim($content);

        if (preg_match('/```(?:json)?\s*(.*?)```/is', $json, $matches)) {
            $json = trim($matches[1]);
        }

        $decoded = json_decode($json, true);

        if (! is_array($decoded)) {
            throw new RuntimeException('OpenAI returned malformed JSON.');
        }

        return $decoded;
    }

    /**
     * @param  array<string, mixed>  $decoded
     * @param  array<string, mixed>  $rawResponse
     * @return array{features: array<int, array<string, mixed>>, summary: array<string, mixed>, raw_response: array<string, mixed>}
     */
    private function validateEstimate(array $decoded, array $rawResponse, float $hourlyRate, string $currency): array
    {
        $features = [];

        foreach ($decoded['features'] ?? [] as $index => $feature) {
            $category = $feature['category'] ?? '';
            $complexity = $feature['complexity'] ?? '';
            $hours = (float) ($feature['estimated_hours'] ?? 0);

            if (! in_array($category, ['frontend', 'backend', 'fullstack'], true)) {
                throw new RuntimeException('OpenAI returned an invalid feature category.');
            }

            if (! in_array($complexity, ['low', 'medium', 'high'], true)) {
                throw new RuntimeException('OpenAI returned an invalid complexity value.');
            }

            if ($hours <= 0) {
                throw new RuntimeException('OpenAI returned a feature with invalid estimated hours.');
            }

            $features[] = [
                'name' => (string) ($feature['name'] ?? 'Feature '.($index + 1)),
                'description' => (string) ($feature['description'] ?? ''),
                'category' => $category,
                'estimated_hours' => round($hours, 2),
                'estimated_cost' => round($hours * $hourlyRate, 2),
                'complexity' => $complexity,
                'sort_order' => $index + 1,
            ];
        }

        if ($features === []) {
            throw new RuntimeException('OpenAI did not return any estimate features.');
        }

        return [
            'features' => $features,
            'summary' => [
                'total_hours' => round(array_sum(array_column($features, 'estimated_hours')), 2),
                'total_cost' => round(array_sum(array_column($features, 'estimated_cost')), 2),
                'currency' => $currency,
                'ai_notes' => (string) data_get($decoded, 'summary.ai_notes', ''),
            ],
            'raw_response' => $rawResponse,
        ];
    }

    private function currency(?string $country = null): string
    {
        if ($country) {
            return (string) data_get(config('estimator.countries', []), "{$country}.currency", config('estimator.currency', 'USD'));
        }

        return config('estimator.currency', 'USD');
    }

    /**
     * @return array<string, mixed>
     */
    private function schema(): array
    {
        return [
            'type' => 'object',
            'additionalProperties' => false,
            'required' => ['features', 'summary'],
            'properties' => [
                'features' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'required' => ['name', 'description', 'category', 'estimated_hours', 'complexity'],
                        'properties' => [
                            'name' => ['type' => 'string'],
                            'description' => ['type' => 'string'],
                            'category' => ['type' => 'string', 'enum' => ['frontend', 'backend', 'fullstack']],
                            'estimated_hours' => ['type' => 'number', 'exclusiveMinimum' => 0],
                            'complexity' => ['type' => 'string', 'enum' => ['low', 'medium', 'high']],
                        ],
                    ],
                ],
                'summary' => [
                    'type' => 'object',
                    'additionalProperties' => false,
                    'required' => ['currency', 'ai_notes'],
                    'properties' => [
                        'currency' => ['type' => 'string'],
                        'ai_notes' => ['type' => 'string'],
                    ],
                ],
            ],
        ];
    }
}
