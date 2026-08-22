<?php

use App\Services\Plugins\PluginModuleDiscovery;
use Nwidart\Modules\Facades\Module;
use Nwidart\Modules\FileRepository;

it('clears the nwidart static module cache so new folders can be discovered', function () {
    expect(Module::all())->not->toBeEmpty();

    $reflection = new ReflectionClass(FileRepository::class);
    $property = $reflection->getProperty('modules');
    $property->setAccessible(true);

    expect($property->getValue())->not->toBeEmpty();

    app(PluginModuleDiscovery::class)->refresh();

    expect($property->getValue())->toBe([]);
});
