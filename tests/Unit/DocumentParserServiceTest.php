<?php

use App\Services\DocumentParserService;

it('cleans and normalizes extracted resume text', function () {
    $parser = new DocumentParserService();

    $raw = "John Doe\r\n\r\n\r\nSoftware   Engineer\t\twith 5 years   experience.\x00\x08";
    $cleaned = $parser->cleanExtractedText($raw);

    expect($cleaned)->not->toContain("\r");
    expect($cleaned)->not->toContain("\x00");
    expect($cleaned)->toContain("John Doe");
    expect($cleaned)->toContain("Software Engineer");
});

it('rejects unsupported file formats', function () {
    $parser = new DocumentParserService();
    $file = \Illuminate\Http\UploadedFile::fake()->create('malicious.exe', 100);

    expect(fn() => $parser->extractTextFromFile($file))
        ->toThrow(\Exception::class, 'Unsupported file format');
});
