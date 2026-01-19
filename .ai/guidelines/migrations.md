# Database Migrations

## Column Defaults

- Never use `->default()` on column definitions in migrations
- Always handle default values in your application code:
    - Model attributes/accessors
    - Form request defaults
    - Controller logic
    - Frontend form defaults
- Database-level defaults can cause inconsistencies across environments and make application behavior less transparent

## Enums

- Never use database enum types (e.g., `->enum()` in migrations)
- Instead, use PHP enums and store them in `app/Enums/`
- Use string or integer columns in the database with application-level validation
- Database enums are difficult to modify and can cause deployment issues
