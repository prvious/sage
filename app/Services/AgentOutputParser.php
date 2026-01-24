<?php

namespace App\Services;

class AgentOutputParser
{
    /**
     * Parse agent output for token usage information.
     *
     * Claude CLI output typically includes stats like:
     * - "Total tokens: 12345 (input: 1000, output: 11345)"
     * - Or JSON output with usage data
     *
     * @return array{input_tokens: int, output_tokens: int, cache_creation_input_tokens?: int, cache_read_input_tokens?: int}|null
     */
    public function parseUsage(string $output): ?array
    {
        if ($usage = $this->parseJsonUsage($output)) {
            return $usage;
        }

        if ($usage = $this->parseTextUsage($output)) {
            return $usage;
        }

        return null;
    }

    /**
     * Try to parse JSON formatted usage data from agent output.
     *
     * @return array{input_tokens: int, output_tokens: int, cache_creation_input_tokens?: int, cache_read_input_tokens?: int}|null
     */
    protected function parseJsonUsage(string $output): ?array
    {
        if (preg_match('/\{"usage":\s*\{[^}]+\}\}/s', $output, $matches)) {
            $data = json_decode($matches[0], true);

            if (isset($data['usage']['input_tokens'], $data['usage']['output_tokens'])) {
                return [
                    'input_tokens' => (int) $data['usage']['input_tokens'],
                    'output_tokens' => (int) $data['usage']['output_tokens'],
                    'cache_creation_input_tokens' => isset($data['usage']['cache_creation_input_tokens'])
                        ? (int) $data['usage']['cache_creation_input_tokens']
                        : null,
                    'cache_read_input_tokens' => isset($data['usage']['cache_read_input_tokens'])
                        ? (int) $data['usage']['cache_read_input_tokens']
                        : null,
                ];
            }
        }

        if (preg_match('/"input_tokens":\s*(\d+)/', $output, $inputMatch) &&
            preg_match('/"output_tokens":\s*(\d+)/', $output, $outputMatch)) {
            return [
                'input_tokens' => (int) $inputMatch[1],
                'output_tokens' => (int) $outputMatch[1],
                'cache_creation_input_tokens' => null,
                'cache_read_input_tokens' => null,
            ];
        }

        return null;
    }

    /**
     * Try to parse text formatted usage data from agent output.
     *
     * @return array{input_tokens: int, output_tokens: int, cache_creation_input_tokens?: int, cache_read_input_tokens?: int}|null
     */
    protected function parseTextUsage(string $output): ?array
    {
        if (preg_match('/Total tokens:\s*(\d+)\s*\(input:\s*(\d+),?\s*output:\s*(\d+)\)/i', $output, $matches)) {
            return [
                'input_tokens' => (int) $matches[2],
                'output_tokens' => (int) $matches[3],
                'cache_creation_input_tokens' => null,
                'cache_read_input_tokens' => null,
            ];
        }

        if (preg_match('/input[_\s]?tokens[:\s]+(\d+)/i', $output, $inputMatch) &&
            preg_match('/output[_\s]?tokens[:\s]+(\d+)/i', $output, $outputMatch)) {
            return [
                'input_tokens' => (int) $inputMatch[1],
                'output_tokens' => (int) $outputMatch[1],
                'cache_creation_input_tokens' => null,
                'cache_read_input_tokens' => null,
            ];
        }

        return null;
    }
}
