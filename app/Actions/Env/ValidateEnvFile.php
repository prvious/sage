<?php

namespace App\Actions\Env;

final readonly class ValidateEnvFile
{
    /**
     * Validate environment variables
     */
    public function handle(array $variables): array
    {
        $errors = [];

        foreach ($variables as $key => $data) {
            // Validate key format
            if (! $this->isValidKey($key)) {
                $errors[$key][] = 'Key must be uppercase with underscores only';
            }

            // Check if value needs quotes
            $value = $data['value'];
            if ($this->needsQuotes($value)) {
                $errors[$key][] = 'Value contains special characters and may need quotes';
            }
        }

        // Check for required variables
        $missing = $this->checkRequired($variables);
        if (! empty($missing)) {
            $errors['_required'] = 'Missing required variables: '.implode(', ', $missing);
        }

        return $errors;
    }

    /**
     * Check if key is valid format
     */
    private function isValidKey(string $key): bool
    {
        return preg_match('/^[A-Z_][A-Z0-9_]*$/', $key) === 1;
    }

    /**
     * Check if value needs quotes
     */
    private function needsQuotes(string $value): bool
    {
        return str_contains($value, ' ') || str_contains($value, '#');
    }

    /**
     * Check for required variables
     */
    public function checkRequired(array $variables): array
    {
        $required = config('sage.required_env_variables', [
            'APP_NAME',
            'APP_ENV',
            'APP_KEY',
            'APP_URL',
            'DB_CONNECTION',
        ]);

        $missing = [];

        foreach ($required as $key) {
            if (! isset($variables[$key]) || empty($variables[$key]['value'])) {
                $missing[] = $key;
            }
        }

        return $missing;
    }
}
