<?php

namespace App\Services;

use RuntimeException;
use Smalot\PdfParser\Parser;

class PdfExtractionService
{
    public function __construct(private readonly Parser $parser) {}

    public function extract(string $path): string
    {
        $pdf = $this->parser->parseFile($path);
        $text = trim(collect($pdf->getPages())
            ->map(fn ($page) => trim($page->getText()))
            ->filter()
            ->implode("\n\n"));

        if ($text === '') {
            throw new RuntimeException('No extractable text was found in this PDF. It may be scanned or image-based.');
        }

        return $text;
    }
}
