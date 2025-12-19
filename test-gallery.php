<?php
// Simple test script to check if methods exist
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$component = new \App\Livewire\EnhancedImageGallery();

echo "=== Public Methods in EnhancedImageGallery ===\n";
$reflection = new ReflectionClass($component);
foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
    if ($method->class === 'App\Livewire\EnhancedImageGallery') {
        echo "- " . $method->getName() . "\n";
    }
}

echo "\n=== Public Properties ===\n";
foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $prop) {
    if ($prop->class === 'App\Livewire\EnhancedImageGallery') {
        echo "- \$" . $prop->getName() . "\n";
    }
}

