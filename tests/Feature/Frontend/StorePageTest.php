<?php

use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    Storage::disk('public')->put('installed', '1');
});

afterEach(function () {
    $filePath = storage_path('app/page-data/pest-test-page.json');

    if (file_exists($filePath)) {
        unlink($filePath);
    }
});

it('stores page data as a json file when the application is installed', function () {
    $pageData = [['id' => 'section-1', 'type' => 'section']];

    $response = $this->withoutMiddleware()->postJson('/api/store-page/pest-test-page', [
        'pageData' => $pageData,
    ]);

    $response->assertSuccessful()
        ->assertJson([
            'success' => true,
            'message' => "Page data for 'pest-test-page' saved successfully",
        ]);

    $filePath = storage_path('app/page-data/pest-test-page.json');

    expect(file_exists($filePath))->toBeTrue();

    $saved = json_decode(file_get_contents($filePath), true);

    expect($saved)->toBe($pageData);
});

it('rejects store page requests without page data', function () {
    $response = $this->withoutMiddleware()->postJson('/api/store-page/pest-test-page', []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['pageData']);
});
