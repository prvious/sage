<?php

use App\Services\AgentOutputParser;

beforeEach(function () {
    $this->parser = new AgentOutputParser;
});

describe('parseUsage', function () {
    it('returns null for empty output', function () {
        $result = $this->parser->parseUsage('');

        expect($result)->toBeNull();
    });

    it('returns null for output without usage data', function () {
        $output = 'Hello, this is some random output without any token information.';

        $result = $this->parser->parseUsage($output);

        expect($result)->toBeNull();
    });

    it('parses JSON formatted usage data', function () {
        $output = 'Some output {"usage": {"input_tokens": 1500, "output_tokens": 750}} more text';

        $result = $this->parser->parseUsage($output);

        expect($result)->not->toBeNull();
        expect($result['input_tokens'])->toBe(1500);
        expect($result['output_tokens'])->toBe(750);
    });

    it('parses JSON with cache tokens', function () {
        $output = '{"usage": {"input_tokens": 2000, "output_tokens": 1000, "cache_creation_input_tokens": 500, "cache_read_input_tokens": 300}}';

        $result = $this->parser->parseUsage($output);

        expect($result)->not->toBeNull();
        expect($result['input_tokens'])->toBe(2000);
        expect($result['output_tokens'])->toBe(1000);
        expect($result['cache_creation_input_tokens'])->toBe(500);
        expect($result['cache_read_input_tokens'])->toBe(300);
    });

    it('parses standalone JSON token fields', function () {
        $output = 'Result: "input_tokens": 3000, "output_tokens": 1500';

        $result = $this->parser->parseUsage($output);

        expect($result)->not->toBeNull();
        expect($result['input_tokens'])->toBe(3000);
        expect($result['output_tokens'])->toBe(1500);
    });

    it('parses text format with total tokens', function () {
        $output = 'Task completed. Total tokens: 5000 (input: 3500, output: 1500)';

        $result = $this->parser->parseUsage($output);

        expect($result)->not->toBeNull();
        expect($result['input_tokens'])->toBe(3500);
        expect($result['output_tokens'])->toBe(1500);
    });

    it('parses text format with comma separator', function () {
        $output = 'Total tokens: 10000 (input: 7000, output: 3000)';

        $result = $this->parser->parseUsage($output);

        expect($result)->not->toBeNull();
        expect($result['input_tokens'])->toBe(7000);
        expect($result['output_tokens'])->toBe(3000);
    });

    it('parses text format with separate token lines', function () {
        $output = "Processing complete.\ninput_tokens: 5000\noutput_tokens: 2500\nDone.";

        $result = $this->parser->parseUsage($output);

        expect($result)->not->toBeNull();
        expect($result['input_tokens'])->toBe(5000);
        expect($result['output_tokens'])->toBe(2500);
    });

    it('parses text format with colon separator', function () {
        $output = "input tokens: 4000\noutput tokens: 2000";

        $result = $this->parser->parseUsage($output);

        expect($result)->not->toBeNull();
        expect($result['input_tokens'])->toBe(4000);
        expect($result['output_tokens'])->toBe(2000);
    });

    it('prefers JSON format over text format', function () {
        $output = 'Total tokens: 1000 (input: 500, output: 500) {"usage": {"input_tokens": 2000, "output_tokens": 1000}}';

        $result = $this->parser->parseUsage($output);

        expect($result)->not->toBeNull();
        expect($result['input_tokens'])->toBe(2000);
        expect($result['output_tokens'])->toBe(1000);
    });

    it('handles large token numbers', function () {
        $output = '{"usage": {"input_tokens": 1500000, "output_tokens": 750000}}';

        $result = $this->parser->parseUsage($output);

        expect($result)->not->toBeNull();
        expect($result['input_tokens'])->toBe(1500000);
        expect($result['output_tokens'])->toBe(750000);
    });

    it('sets cache tokens to null when not present', function () {
        $output = '{"usage": {"input_tokens": 1000, "output_tokens": 500}}';

        $result = $this->parser->parseUsage($output);

        expect($result)->not->toBeNull();
        expect($result['cache_creation_input_tokens'])->toBeNull();
        expect($result['cache_read_input_tokens'])->toBeNull();
    });

    it('handles case insensitivity in text parsing', function () {
        $output = "INPUT_TOKENS: 3000\nOUTPUT_TOKENS: 1500";

        $result = $this->parser->parseUsage($output);

        expect($result)->not->toBeNull();
        expect($result['input_tokens'])->toBe(3000);
        expect($result['output_tokens'])->toBe(1500);
    });

    it('returns null when only input tokens are present', function () {
        $output = 'input_tokens: 5000';

        $result = $this->parser->parseUsage($output);

        expect($result)->toBeNull();
    });

    it('returns null when only output tokens are present', function () {
        $output = 'output_tokens: 2500';

        $result = $this->parser->parseUsage($output);

        expect($result)->toBeNull();
    });
});
