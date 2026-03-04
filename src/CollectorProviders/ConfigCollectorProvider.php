<?php

declare(strict_types=1);

namespace Fruitcake\LaravelDebugbar\CollectorProviders;

use Fruitcake\LaravelDebugbar\DataCollector\ConfigCollector;

class ConfigCollectorProvider extends AbstractCollectorProvider
{
    public function __invoke(array $options): void
    {
        $configCollector = new ConfigCollector();
        $masked = ['key', 'previous_keys'];
        $configCollector->addMaskedKeys(array_merge($masked, $options['masked'] ?? []));
        $this->addCollector($configCollector);
    }
}
