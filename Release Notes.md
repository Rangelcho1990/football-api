# Release Notes

## Unreleased

- Expanded tests for existing registration, domain objects, league/team imports, repository delegation/error handling, and fixture construction. Added eight defensive UserRequestValidator cases for missing or incorrectly typed fields when the underlying validator incorrectly reports success. This closes the remaining coverage gap: 96 tests and 628 assertions pass, with 100% measured line, method, and class coverage under the existing PHPUnit source configuration. PHPStan and test formatting checks pass. Persistence tests use mocks; live database behavior is not verified. No runtime/API compatibility changes or database migrations are required.
