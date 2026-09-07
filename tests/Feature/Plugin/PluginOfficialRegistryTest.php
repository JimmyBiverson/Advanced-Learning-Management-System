<?php

use App\Services\Plugins\PluginOfficialRegistry;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->registry = app(PluginOfficialRegistry::class);
    $this->registryPath = base_path('Modules/official-plugins.json');
});

it('recognises an official plugin name', function () {
    expect($this->registry->isOfficial('AIAssistant'))->toBeTrue();
});

it('rejects an unofficial plugin name', function () {
    expect($this->registry->isOfficial('SomePiratedPlugin'))->toBeFalse();
});

it('returns false when the registry file is missing', function () {
    $backup = null;

    if (File::exists($this->registryPath)) {
        $backup = File::get($this->registryPath);
        File::delete($this->registryPath);
    }

    try {
        expect($this->registry->isOfficial('AIAssistant'))->toBeFalse();
    } finally {
        if ($backup !== null) {
            File::put($this->registryPath, $backup);
        }
    }
});

it('returns all official plugin names', function () {
    expect($this->registry->officialPluginNames())
        ->toBeArray()
        ->toContain('AIAssistant');
});
