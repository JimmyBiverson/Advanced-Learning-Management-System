<?php

use App\Enums\UserType;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Certification\Models\CertificateTemplate;

beforeEach(function () {
    Schema::dropIfExists('certificate_templates');
    Schema::dropIfExists('users');

    Schema::create('users', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('email')->unique();
        $table->timestamp('email_verified_at')->nullable();
        $table->string('password');
        $table->string('role')->nullable();
        $table->rememberToken();
        $table->timestamps();
    });

    Schema::create('certificate_templates', function (Blueprint $table) {
        $table->id();
        $table->string('type')->default('course');
        $table->string('name');
        $table->string('logo_path')->nullable();
        $table->json('template_data');
        $table->boolean('is_active')->default(false);
        $table->timestamps();
    });
});

afterEach(function () {
    Schema::dropIfExists('certificate_templates');
    Schema::dropIfExists('users');
});

it('persists certificate template text and metadata on update', function () {
    $admin = User::factory()->create([
        'role' => UserType::ADMIN->value,
    ]);

    $template = CertificateTemplate::query()->create([
        'type' => 'course',
        'name' => 'Original',
        'logo_path' => null,
        'template_data' => [
            'primaryColor' => '#000000',
            'secondaryColor' => '#111111',
            'backgroundColor' => '#222222',
            'borderColor' => '#333333',
            'titleText' => 'Original Title',
            'descriptionText' => 'Original description',
            'completionText' => 'Original completion',
            'footerText' => 'Original footer',
            'fontFamily' => 'serif',
        ],
        'is_active' => false,
    ]);

    $newTemplateData = [
        'primaryColor' => '#1e40af',
        'secondaryColor' => '#475569',
        'backgroundColor' => '#eff6ff',
        'borderColor' => '#2563eb',
        'titleText' => 'Certificate of Achievement',
        'descriptionText' => 'This certificate is proudly presented to',
        'completionText' => 'for successfully completing the course',
        'footerText' => 'Authorized and Certified',
        'fontFamily' => 'sans-serif',
    ];

    $response = $this->actingAs($admin)->post(
        route('certificate.templates.update', $template->id),
        [
            'type' => 'course',
            'name' => 'Professional Blue',
            'template_data' => $newTemplateData,
        ],
    );

    $response->assertRedirect(route('certificate.templates.index'));

    $template->refresh();

    expect($template->name)->toBe('Professional Blue')
        ->and($template->template_data)->toMatchArray($newTemplateData);
});
