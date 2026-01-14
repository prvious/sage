<?php

namespace App\Support;

class SpecPrompts
{
    /**
     * Generate prompt for feature specification.
     */
    public static function feature(string $idea): string
    {
        return <<<PROMPT
        You are a product manager helping to create a detailed feature specification.

        Transform this rough idea into a comprehensive spec:
        "{$idea}"

        Include:
        1. Feature Overview (2-3 sentences describing what this feature does and why it matters)
        2. User Stories (As a [user], I want [goal] so that [benefit] - provide 3-5 stories)
        3. Acceptance Criteria (specific, testable checklist of what must be true when done)
        4. Technical Considerations (implementation notes, dependencies, performance concerns)
        5. Edge Cases and Error Handling (what could go wrong and how to handle it)
        6. Implementation Checklist (step-by-step breakdown of development tasks)

        Format as clean markdown. Be specific and thorough. Think through edge cases carefully.
        PROMPT;
    }

    /**
     * Generate prompt for API specification.
     */
    public static function api(string $idea): string
    {
        return <<<PROMPT
        Create a comprehensive API specification for:
        "{$idea}"

        Include:
        1. API Overview (what this API does and its purpose)
        2. Endpoints (method, path, description for each endpoint)
        3. Request Format (parameters, body schema with types)
        4. Response Format (success responses with status codes, error responses)
        5. Authentication Requirements (what auth is needed, how to pass credentials)
        6. Rate Limiting (limits and headers)
        7. Example Requests/Responses (realistic curl examples or code snippets)

        Format as markdown with proper code blocks. Use JSON for examples.
        PROMPT;
    }

    /**
     * Generate prompt for refactoring specification.
     */
    public static function refactor(string $idea): string
    {
        return <<<PROMPT
        Create a refactoring specification for:
        "{$idea}"

        Include:
        1. Current State (what exists now and why it needs refactoring)
        2. Goals (what we want to achieve - performance, maintainability, etc.)
        3. Proposed Changes (specific changes to make)
        4. Migration Strategy (how to transition from old to new)
        5. Testing Strategy (how to ensure nothing breaks)
        6. Rollback Plan (what if something goes wrong)
        7. Success Criteria (how we measure if refactor succeeded)

        Format as markdown. Be practical and risk-aware.
        PROMPT;
    }

    /**
     * Generate prompt for bug fix specification.
     */
    public static function bug(string $idea): string
    {
        return <<<PROMPT
        Create a bug fix specification for:
        "{$idea}"

        Include:
        1. Bug Description (what's broken and how it manifests)
        2. Steps to Reproduce (exact steps to see the bug)
        3. Expected Behavior (what should happen)
        4. Actual Behavior (what actually happens)
        5. Root Cause Analysis (why this bug exists)
        6. Proposed Fix (how to fix it)
        7. Testing Strategy (how to verify the fix works)
        8. Regression Risks (what else might break)

        Format as markdown. Be thorough in analysis.
        PROMPT;
    }

    /**
     * Refine an existing specification with user feedback.
     */
    public static function refine(string $currentSpec, string $feedback): string
    {
        return <<<PROMPT
        You are refining a feature specification based on user feedback.

        Current Specification:
        {$currentSpec}

        User Feedback:
        {$feedback}

        Update the specification to address the feedback. Keep the same structure and format.
        Only change the parts that need improvement based on the feedback.
        Return the complete, refined specification in markdown format.
        PROMPT;
    }
}
