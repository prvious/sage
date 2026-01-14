# Guidelines for Automated Test Generation

1. **Creating tests**
    - Tests should live in the appropriate namespace and directory. If a we're testing a feature in the `App/Jobs/SendEmail.php` file, the test should be in `tests/Feature/Jobs/SendEmailTest.php`.
    - Use the same naming conventions for Unit tests, not just Feature tests.
    - Create Feature tests for tests that cover a single feature/file (eg: a job, a helper class, a service class, an Action class, etc).

2. **Iterative test workflow**
    - After generating a test, run it immediately.
    - If it passes, continue.
    - If it fails, fix the test or the related implementation until it passes.

3. **Factory usage**
    - If a model is missing a factory, create one.
    - Define model relationships inside factories by passing the factory instance of the related model (e.g. Team::factory()).
    - Let Laravel handle cascading relationships; don't manually over-nest factory calls.
    - When testing an implementation that requires a single model and the model has multiple relationships, use `Model::factory()->create()` to create it. do not manually create each model to pass as relationship unless the test specifically requires it.

4. **Test clarity**
    - Name tests descriptively to explain their purpose.
    - Keep each test method focused on a single behavior or responsibility.

5. **Pest best practices**
    - Prefer Pest helper functions instead of PHPUnit's `$this->` (e.g., `assertDatabaseHas()`, `assertDatabaseCount()`, `actingAs()`).
    - Use Pest's higher-order proxy feature for cleaner assertions when possible.
