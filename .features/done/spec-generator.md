---
name: spec-generator
description: AI-assisted feature specification generator from rough ideas
depends_on: database-and-models
---

## Detailed Description

This feature provides an AI-powered tool that transforms rough feature ideas into detailed, structured specifications. It helps developers clarify requirements, identify edge cases, and create comprehensive feature documents before implementation.

### Key Capabilities

- Input rough feature idea (text or voice-to-text)
- AI generates detailed specification with:
    - Feature overview
    - User stories
    - Acceptance criteria
    - Technical considerations
    - Implementation checklist
    - Edge cases and error handling
- Edit and refine generated spec
- Save specs to database
- Associate specs with tasks
- Export specs as markdown
- Template system for different spec types (API, UI, refactor, etc.)

### User Stories

1. As a developer, I want to quickly turn a rough idea into a detailed spec
2. As a developer, I want AI to help me identify edge cases I might miss
3. As a developer, I want to save specs for future reference
4. As a developer, I want to create tasks from specs
5. As a PM, I want consistent spec format across features

### Spec Structure

```markdown
# Feature: [Name]

## Overview

Brief description of the feature

## User Stories

- As a [user], I want [goal] so that [benefit]

## Acceptance Criteria

- [ ] Criterion 1
- [ ] Criterion 2

## Technical Considerations

- Implementation notes
- Dependencies
- Performance concerns

## Edge Cases

- Case 1: Expected behavior
- Case 2: Expected behavior

## Implementation Checklist

- [ ] Step 1
- [ ] Step 2
```

## Detailed Implementation Plan

### Step 1: Create Spec Generator Service

```bash
php artisan make:class Services/SpecGeneratorService --no-interaction
```

**Methods:**

```php
public function generate(string $idea, string $type = 'feature'): string
{
    // Call AI API (Claude/OpenAI) with structured prompt
    // Parse response
    // Return formatted markdown
}

public function refine(string $currentSpec, string $feedback): string
{
    // Send current spec + user feedback to AI
    // Generate refined version
    // Return updated spec
}

private function buildPrompt(string $idea, string $type): string
{
    // Build structured prompt based on spec type
    // Include formatting instructions
    // Return prompt
}
```

### Step 2: Create AI Client Configuration

Add to `config/services.php`:

```php
'anthropic' => [
    'api_key' => env('ANTHROPIC_API_KEY'),
    'model' => env('ANTHROPIC_MODEL', 'claude-sonnet-4-20250514'),
],
```

### Step 3: Create Spec Generator Controller

```bash
php artisan make:controller SpecController --no-interaction
```

**Methods:**

- `index()` - List all specs
- `create()` - Show generator form
- `generate()` - Generate spec from idea
- `store()` - Save spec
- `show()` - Display spec
- `edit()` - Edit spec
- `update()` - Update spec
- `destroy()` - Delete spec

### Step 4: Create Spec Generator Page

```typescript
// resources/js/Pages/Specs/Create.tsx
```

**Layout:**

- Idea input (textarea or voice input)
- Spec type selector (Feature, API, Refactor, Bug Fix)
- Generate button with loading state
- Generated spec display (editable)
- Refine button (for iterative improvements)
- Save button

### Step 5: Implement Voice Input (Optional)

Use Web Speech API:

```typescript
const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
const recognition = new SpeechRecognition();

recognition.onresult = (event) => {
    const transcript = event.results[0][0].transcript;
    setIdea(transcript);
};
```

Add voice input button to trigger recording.

### Step 6: Create Spec Prompt Templates

```bash
php artisan make:class Support/SpecPrompts --no-interaction
```

**Templates for different spec types:**

**Feature Spec:**

```php
public static function feature(string $idea): string
{
    return <<<PROMPT
    You are a product manager helping to create a detailed feature specification.

    Transform this rough idea into a comprehensive spec:
    "{$idea}"

    Include:
    1. Feature Overview (2-3 sentences)
    2. User Stories (As a [user], I want [goal] so that [benefit])
    3. Acceptance Criteria (checklist format)
    4. Technical Considerations
    5. Edge Cases and Error Handling
    6. Implementation Checklist

    Format as markdown. Be specific and thorough.
    PROMPT;
}
```

**API Spec:**

```php
public static function api(string $idea): string
{
    return <<<PROMPT
    Create an API specification for:
    "{$idea}"

    Include:
    1. Endpoints (method, path, description)
    2. Request format (parameters, body schema)
    3. Response format (success and error cases)
    4. Authentication requirements
    5. Rate limiting
    6. Example requests/responses

    Format as markdown with code blocks.
    PROMPT;
}
```

### Step 7: Implement Spec Generation Logic

In SpecGeneratorService:

```php
public function generate(string $idea, string $type = 'feature'): string
{
    $prompt = SpecPrompts::{$type}($idea);

    $response = Http::withHeaders([
        'x-api-key' => config('services.anthropic.api_key'),
        'anthropic-version' => '2023-06-01',
        'content-type' => 'application/json',
    ])->post('https://api.anthropic.com/v1/messages', [
        'model' => config('services.anthropic.model'),
        'max_tokens' => 4096,
        'messages' => [
            ['role' => 'user', 'content' => $prompt],
        ],
    ]);

    return $response->json('content.0.text');
}
```

### Step 8: Add Spec Editor Component

```typescript
// resources/js/Components/SpecEditor.tsx
```

Use Monaco Editor for markdown editing:

```typescript
<Editor
    height="70vh"
    defaultLanguage="markdown"
    value={spec}
    onChange={setSpec}
    theme="vs-dark"
/>
```

### Step 9: Implement Refinement Feature

Add refinement form:

```typescript
// resources/js/Components/SpecRefinement.tsx
```

**UI:**

- Feedback textarea: "What would you like to improve?"
- Examples: "Add more edge cases", "Include API examples", "More detailed checklist"
- Refine button (sends current spec + feedback to AI)
- Show diff between versions

### Step 10: Create Spec List Page

```typescript
// resources/js/Pages/Specs/Index.tsx
```

**Display:**

- Grid/list of saved specs
- Search and filter by type
- Quick preview on hover
- Actions: View, Edit, Delete, Create Task from Spec

### Step 11: Create Spec Detail Page

```typescript
// resources/js/Pages/Specs/Show.tsx
```

**Layout:**

- Rendered markdown (read-only)
- Metadata: Created date, last modified, type
- Actions: Edit, Create Task, Export, Delete
- Version history (if tracking changes)

### Step 12: Implement Create Task from Spec

Add button on spec detail page:

```typescript
const createTaskFromSpec = () => {
    router.post('/tasks', {
        title: spec.title,
        description: spec.content,
        project_id: spec.project_id,
    });
};
```

Pre-fill task form with spec content.

### Step 13: Add Export Functionality

Export as:

- Markdown file (.md)
- PDF (using library like jsPDF)
- Copy to clipboard

```typescript
const exportAsMarkdown = () => {
    const blob = new Blob([spec.content], { type: 'text/markdown' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `${spec.title}.md`;
    a.click();
};
```

### Step 14: Create Form Request Validation

```bash
php artisan make:request GenerateSpecRequest --no-interaction
php artisan make:request StoreSpecRequest --no-interaction
```

**Validation Rules:**

- `idea` - required, string, min:10, max:5000
- `type` - required, in:feature,api,refactor,bug
- `title` - required, string, max:255
- `content` - required, string
- `project_id` - required, exists:projects,id

### Step 15: Add Rate Limiting

Prevent abuse of AI API:

```php
Route::post('/specs/generate')
    ->middleware('throttle:10,1'); // 10 requests per minute
```

Track API usage per user/session.

### Step 16: Create Feature Tests

Test coverage:

- `it('can generate spec from idea')`
- `it('validates idea input')`
- `it('can save generated spec')`
- `it('can refine spec with feedback')`
- `it('can list all specs')`
- `it('can delete spec')`
- `it('rate limits generation requests')`

**Note:** Mock AI API responses in tests.

### Step 17: Create Browser Tests

E2E test coverage:

- `it('can open spec generator')`
- `it('can input idea and generate spec')`
- `it('displays generated spec')`
- `it('can edit generated spec')`
- `it('can save spec')`
- `it('can create task from spec')`

Use FakeAgentDriver equivalent for AI service.

### Step 18: Add Spec Templates

Create pre-built spec templates:

```bash
php artisan make:class Support/SpecTemplates --no-interaction
```

**Templates:**

- CRUD Feature
- REST API
- Authentication Flow
- Payment Integration
- Admin Dashboard

Users can start from template instead of generating.

### Step 19: Implement Spec Versioning (Optional)

Track changes to specs over time:

- Save versions on each update
- Show diff between versions
- Restore previous version

### Step 20: Format Code

```bash
vendor/bin/pint --dirty
pnpm run format
```
