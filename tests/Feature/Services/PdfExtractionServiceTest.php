<?php

use App\Services\PdfExtractionService;
use Smalot\PdfParser\Parser;

test('pdf extraction service extracts text from a text pdf', function () {
    $path = tempnam(sys_get_temp_dir(), 'requirements').'.pdf';

    file_put_contents($path, textPdf('Build login, dashboard, reporting, and export features.'));

    try {
        $text = app(PdfExtractionService::class)->extract($path);

        expect($text)->toContain('Build login')
            ->and($text)->toContain('export features');
    } finally {
        unlink($path);
    }
});

test('pdf extraction service rejects pdfs without extractable text', function () {
    $parser = Mockery::mock(Parser::class);
    $parser->shouldReceive('parseFile->getPages')->andReturn([]);

    app()->instance(Parser::class, $parser);

    app(PdfExtractionService::class)->extract('/tmp/scanned.pdf');
})->throws(RuntimeException::class, 'No extractable text');

function textPdf(string $text): string
{
    $content = "BT\n/F1 24 Tf\n100 700 Td\n({$text}) Tj\nET";
    $objects = [
        '<< /Type /Catalog /Pages 2 0 R >>',
        '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
        '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>',
        '<< /Length '.strlen($content)." >>\nstream\n{$content}\nendstream",
        '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
    ];

    $pdf = "%PDF-1.4\n";
    $offsets = [0];

    foreach ($objects as $index => $object) {
        $offsets[] = strlen($pdf);
        $number = $index + 1;
        $pdf .= "{$number} 0 obj\n{$object}\nendobj\n";
    }

    $xref = strlen($pdf);
    $pdf .= "xref\n0 ".(count($objects) + 1)."\n";
    $pdf .= "0000000000 65535 f \n";

    foreach (array_slice($offsets, 1) as $offset) {
        $pdf .= sprintf("%010d 00000 n \n", $offset);
    }

    $pdf .= "trailer\n<< /Size ".(count($objects) + 1)." /Root 1 0 R >>\n";
    $pdf .= "startxref\n{$xref}\n%%EOF\n";

    return $pdf;
}
