<?php

namespace App\Actions\Env;

final readonly class CompareEnvFiles
{
    /**
     * Compare two sets of environment variables
     */
    public function handle(array $source, array $target): array
    {
        $differences = [
            'added' => [],
            'removed' => [],
            'changed' => [],
            'unchanged' => [],
        ];

        $sourceKeys = array_keys($source);
        $targetKeys = array_keys($target);

        // Find added variables (in target but not in source)
        $added = array_diff($targetKeys, $sourceKeys);
        foreach ($added as $key) {
            $differences['added'][$key] = $target[$key];
        }

        // Find removed variables (in source but not in target)
        $removed = array_diff($sourceKeys, $targetKeys);
        foreach ($removed as $key) {
            $differences['removed'][$key] = $source[$key];
        }

        // Find changed variables
        $common = array_intersect($sourceKeys, $targetKeys);
        foreach ($common as $key) {
            if ($source[$key]['value'] !== $target[$key]['value']) {
                $differences['changed'][$key] = [
                    'source' => $source[$key],
                    'target' => $target[$key],
                ];
            } else {
                $differences['unchanged'][$key] = $source[$key];
            }
        }

        return $differences;
    }
}
