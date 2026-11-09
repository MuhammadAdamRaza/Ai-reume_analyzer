<?php

use Illuminate\Support\Facades\Http;
use Smalot\PdfParser\Parser;

function validResumeBuilderInput(): array
{
    return [
        'template' => 'modern',
        'resume' => [
            'name' => 'Jordan Lee',
            'target_role' => 'Product Designer',
            'email' => 'jordan@example.com',
            'phone' => '+1 555 0100',
            'location' => 'New York, NY',
            'website' => 'https://jordan.example.com',
            'linkedin' => 'linkedin.com/in/jordan-lee',
            'summary' => 'Product designer focused on accessible web experiences.',
            'experience' => "Product Designer, Example Studio, 2022-Present\n- Designed accessible web interfaces.",
            'education' => 'BFA, Example University, 2022',
            'skills' => 'Figma, prototyping, accessibility',
            'certifications' => 'Accessibility Foundations',
        ],
    ];
}

it('renders four visual resume templates with links to their editors', function () {
    $response = $this->get(route('resume-builder.index'));

    $response->assertOk();
    $response->assertSee('Choose a resume design');
    $response->assertSee('Classic');
    $response->assertSee('Modern');
    $response->assertSee('Compact');
    $response->assertSee('Minimal');
    $response->assertSee(route('resume-builder.edit', ['template' => 'classic']), false);
    $response->assertSee(route('resume-builder.edit', ['template' => 'modern']), false);
    $response->assertSee(route('resume-builder.edit', ['template' => 'compact']), false);
    $response->assertSee(route('resume-builder.edit', ['template' => 'minimal']), false);
    $response->assertDontSee('Download resume PDF');
});

it('opens an editor with the selected template and a live resume preview', function () {
    $response = $this->get(route('resume-builder.edit', ['template' => 'modern']));

    $response->assertOk();
    $response->assertSee('Add your details to the modern layout');
    $response->assertSee('name="template" value="modern"', false);
    $response->assertSee('Live resume preview');
    $response->assertSee('Get AI writing suggestions');
    $response->assertSee('Download resume PDF');
    $response->assertSee(route('resume-builder.assist'), false);
});

it('downloads a text-based PDF using the selected template', function () {
    $response = $this->post(route('resume-builder.download'), validResumeBuilderInput());

    $response->assertOk();
    $response->assertHeader('content-type', 'application/pdf');
    $response->assertHeader('content-disposition', 'attachment; filename="jordan-lee.pdf"');
    expect($response->getContent())->toStartWith('%PDF-1.4');
    expect($response->getContent())->toEndWith('%%EOF');

    $document = (new Parser())->parseContent($response->getContent());
    $resumeText = $document->getText();

    expect($resumeText)->toContain('Jordan Lee');
    expect($resumeText)->toContain('Product Designer');
    expect($resumeText)->toContain('Example Studio');
    expect($resumeText)->toContain('BFA, Example University');
    expect($resumeText)->toContain('Figma, prototyping, accessibility');
});

it('validates resume fields before building or downloading a PDF', function () {
    $response = $this->postJson(route('resume-builder.download'), [
        'template' => 'not-a-template',
        'resume' => [
            'name' => '',
            'target_role' => '',
        ],
    ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['template', 'resume.name', 'resume.target_role', 'resume.summary', 'resume.experience']);
});

it('returns AI writing suggestions based on the submitted resume facts', function () {
    config(['services.gemini.api_key' => 'test-key']);
    Http::fake([
        '*generativelanguage.googleapis.com*' => Http::response([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            [
                                'text' => json_encode([
                                    'improved_summary' => 'Product designer focused on accessible web interfaces.',
                                    'bullet_point_rewrites' => [
                                        [
                                            'original' => 'Designed accessible web interfaces.',
                                            'improved' => 'Designed accessible web interfaces for Example Studio.',
                                        ],
                                    ],
                                ]),
                            ],
                        ],
                    ],
                ],
            ],
        ], 200),
    ]);

    $response = $this->postJson(route('resume-builder.assist'), validResumeBuilderInput());

    $response->assertOk();
    $response->assertJsonPath('summary', 'Product designer focused on accessible web interfaces.');
    $response->assertJsonPath('rewrites.0.original', 'Designed accessible web interfaces.');
    $response->assertJsonPath('rewrites.0.improved', 'Designed accessible web interfaces for Example Studio.');
});
