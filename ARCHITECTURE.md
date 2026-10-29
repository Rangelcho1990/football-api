# Architecture

## Current state

Football API is a Symfony 7.4 application organized into domain, application, infrastructure, and HTTP controller layers. User registration is implemented through an invokable controller, a Symfony request validator, an immutable application DTO, a use-case handler, and a repository interface with a Doctrine implementation. JWT login is configured. League, Team, Game, and User have XML ORM mappings and an initial MySQL/MariaDB migration.

This is an early implementation of a football prediction API. League imports from a local Rapid staging table are implemented. Predictions, scoring, rankings, and game/league/team endpoints are not implemented. Configured firewall prefixes do not create endpoints.

## Structure and dependency boundaries

| Path | Current responsibility |
| --- | --- |
| `public/index.php`, `bin/console`, `src/Kernel.php` | Symfony HTTP/CLI bootstrap and kernel |
| `src/Controller/User/UserController.php` | Decode registration JSON, call the request validator and handler, map malformed JSON to 400, validation failures to 422, and duplicate-user failures to 409 |
| `src/Application/User/CreateUserHandler.php` | Construct a User, hash its password, activate it, and persist it through the repository contract |
| `src/Controller/User/Validation/UserRequestValidator.php` | Validate and normalize HTTP input using Symfony constraints, then construct the application DTO |
| `src/Application/User/DTO/UserRequestDTO.php` | Framework-independent `final readonly` input DTO containing username, email, password, and integer avatar |
| `src/Domain/User/Exception/` | Business exceptions; currently only `UserAlreadyExistsException` |
| `src/Domain/User/UserRepositoryInterface.php` | User persistence contract, including documented duplicate-user failure |
| `src/Domain/{User,League,Team,Game}/` | Domain models, currently primarily state and getters/setters |
| `src/Infrastructure/Doctrine/UserRepository.php` | Doctrine implementation of the User repository |
| `src/DataFixtures/AppFixtures.php` | Development League and User seed data |
| `config/doctrine/` | External XML mappings for the domain models |
| `migrations/` | Database schema migration and rollback |
| `config/packages/`, `config/routes.yaml`, `config/services.yaml` | Framework, security, routes, service wiring, and repository alias |
| `tests/`, `phpunit.dist.xml`, `phpstan.dist.neon` | Test bootstrap, request-validator and controller unit tests, and analysis configuration |
| `compose.yaml`, `compose.override.yaml` | Legacy PostgreSQL service; incompatible with the current MySQL/MariaDB migration |

Composer maps `App\` to `src/`. The application handler depends on the domain repository interface; the service container aliases it to the Doctrine repository. ORM mappings are external, so domain entities do not need Doctrine mapping attributes.

The HTTP boundary owns JSON decoding, input validation, and HTTP responses. The handler accepts a typed application DTO and coordinates registration through the domain repository contract. The DTO contains only data and has no Symfony dependencies. Business invariants should be enforced in domain entities and value objects regardless of the entry point.

The boundaries remain pragmatic rather than strict DDD isolation: User implements Symfony security interfaces and the handler depends on Symfony's password hasher. Domain invariants are still limited; HTTP validation does not replace domain enforcement.

Services are autowired and autoconfigured. Application DTOs are excluded from service discovery because their values come from requests rather than dependency injection. Currently `controller.service_arguments` is applied to all `App\` services. Scope that tag to controllers and review other non-service data classes for exclusion where appropriate.

## Registration flow and API contract

`POST /api/users` is explicitly mapped to `App\Controller\User\UserController`. Symfony calls its `__invoke(Request $request)` method.

1. The controller reads JSON using `Request::toArray()`; invalid or non-array JSON returns HTTP 400 with a failure flag and message.
2. `UserRequestValidator` trims string usernames and emails, then validates all fields using Symfony `Collection` and `Sequentially` constraints. Passwords are preserved unchanged.
3. Missing fields, unexpected fields, incorrect types, and constraint violations throw `ValidationFailedException`. The controller returns HTTP 422 with `{"success":false,"errors":{"field":["message"]}}`.
4. After validation, explicit scalar type guards support static analysis before constructing `UserRequestDTO`. A guard failure indicates an internal programming error and throws `LogicException`.
5. `CreateUserHandler::handler(UserRequestDTO)` constructs User, assigns its fields, hashes the password, and sets active status to true.
6. `UserRepositoryInterface::createUser()` delegates to Doctrine persist/flush.
7. Success returns HTTP 200 with `{"success":true}`. `UserAlreadyExistsException` returns HTTP 409 with a failure flag and message.

All four input fields are required. Username must be a nonblank string of at most 50 characters. Email must be a nonblank string of at most 100 characters and satisfy Symfony's email constraint. Password must be a nonblank string. Avatar must be a positive integer; numeric strings are rejected. Extra fields are rejected (`allowExtraFields: false`). No password-strength policy or avatar existence check is implemented.

The DTO's scalar properties cannot be reassigned after construction. Validation occurs at the HTTP boundary; the DTO itself does not enforce business rules. Other entry points need appropriate input validation and domain enforcement. A shared JSON error response policy, HTTP 201, and a created-resource identifier remain possible improvements.

## League import flow

```text
FetchLeaguesCommand -> LeagueSourceRegistry -> LeagueSourceInterface
                                               ^ RapidLeagueSource
FetchLeaguesCommand -> ImportLeaguesHandler -> LeagueRepositoryInterface
                                               ^ Doctrine LeagueRepository
```

`php bin/console app:import-leagues rapid` requires a positional source argument. The registry resolves implementations by the `source` attribute of the `app.league_source` service tag. Definitions live in `config/services/league_sources.yaml`, imported by `config/services.yaml`; explicitly configured sources and the registry are excluded from broad service discovery to preserve their definitions. Application DTOs are also excluded.

The league source contract lives in `src/Application/League/LeagueSourceInterface.php`; the registry and provider adapters live in `src/Infrastructure/Services/LeagueSource/`. `LeagueSourceInterface::fetchLeagues()` returns an iterable of `SourceLeagueDTO`. `RapidLeagueSource` uses Doctrine DBAL to select `data` and `country_id` from `__rapid_leagues`. It decodes each JSON array, validates league fields, and maps entries into DTOs. It generates lowercase slugs with `AsciiSlugger`, sets status to `1`, and uses the current date/time for the update timestamp. It ignores JSON country details and seasons; country ID comes from the staging row. Missing/null/blank country IDs and invalid ID types are rejected. Query results are freed even when reading or mapping fails.

The command materializes DTOs before calling `ImportLeaguesHandler::handler()`. Unknown sources return an invalid-command result; database, JSON, and payload failures during fetching return failure with a message. The handler checks `LeagueRepositoryInterface::findByApiId()` first and skips existing records. For new leagues it rejects blank logos with `LeagueMissingLogoException`, maps every DTO field into a League, and calls `createLeague()` to persist and flush it. Handler exceptions propagate through Symfony Console. The final count is fetched rows, not inserted rows.

Domain persistence uses XML mappings, including `league_type`, `country_id`, `status`, and `__lastUpdate`. Inserts flush individually without a transaction covering the entire import; earlier inserts can remain after a later failure. Duplicate detection currently uses API ID alone and does not guarantee uniqueness under concurrent imports. Before enabling providers with overlapping IDs, introduce the source/external-ID mapping described in [TODO.md](TODO.md).

`LeagueRepository` currently wraps all persistence exceptions in `LeagueAlreadyExistsException`; narrow this to relevant unique-constraint failures. Unit tests cover source mapping/resource cleanup and handler duplicate skipping, logo rejection, and entity construction. Live import/database integration is not yet verified.

## Team import flow

`FetchTeamsCommand` exposes `app:import-teams <source>` and mirrors the league import with its own `TeamSourceRegistry`, application `TeamSourceInterface` and `SourceTeamDTO`, `RapidTeamSource`, `ImportTeamsHandler`, and domain `TeamRepositoryInterface` with a Doctrine implementation. Tagged team sources are configured separately in `config/services/team_sources.yaml`.

The Rapid adapter selects `data` and `country_id` from `__rapid_teams`, validates each JSON list entry's unsigned `team.id`, `team.name`, and optional nullable `team.logo`, `team.founded`, and `team.national`, and always frees the DBAL result. The staging row's country ID applies to every team in its payload. Status is initialized to `1` and the update timestamp to the current time. The command materializes DTOs before importing so fetching failures do not persist partially read payloads. The handler skips existing API IDs and persists each new team separately, including all metadata. Only unique-constraint failures are translated into `TeamAlreadyExistsException`; unrelated database failures propagate.

Migration `Version20261029090000` adds founded year, national flag, required unsigned unique API ID, required country ID, nullable status defaulting to `1`, and nullable `__lastUpdate`; logo becomes nullable `VARCHAR(100)`. Existing data must be reconciled before applying the migration, or the team table must be empty. Duplicate checks currently use the API ID alone, so overlapping provider IDs require future source/external-ID mapping. The staging table is owned by the separate fetching project. Unit tests cover source validation and cleanup, metadata and nullable-field mapping, repeated team imports, source selection, and command failure behavior.

Local verification on 2026-10-29 recreated the empty team table while restoring both game foreign keys, recorded the existing initial schema in migration history, and applied the revised team migration. The import populated 18,126 teams; all imported metadata matched staging, the Team ORM mapping matched the live table, and a second import left the count unchanged.

## Domain model

| Model | State and current behavior |
| --- | --- |
| User | Generated ID, username, password hash, email, creation timestamp, points, avatar, active flag; implements Symfony authentication interfaces |
| League | Generated ID, name, slug, logo, type, external API ID, country ID, status, last-update timestamp; getters/setters |
| Team | Generated ID, name, nullable logo/founded/national, required unsigned unique API ID and country ID, nullable status and last-update timestamp; getters/setters |
| Game | Generated ID, nullable string score, home/away teams, game time, league; getters/setters |

User defaults to avatar 1, zero points, current immutable creation time, and inactive status. Registration overrides avatar and activates the account. `setUsername()` rejects only the empty string using `InvalidArgumentException`; other business invariants are largely absent. Constructors leave required properties uninitialized until setters or Doctrine hydration populate them.

There is no Guess/Prediction model or relationship. There are no scoring rules, prediction deadlines, ranking queries, or checks preventing a team from playing itself. Negative points and invalid domain field values are not prevented. League imports read provider data from a local staging table; there is no external HTTP client.

## Exceptions and HTTP boundaries

HTTP validation uses Symfony’s `ValidationFailedException` in the controller layer; it is not a domain exception. The controller maps its field violations to HTTP 422.

Business exceptions belong beside their domain, for example `Domain/User/Exception/` and, when needed, `Domain/League/Exception/`. `UserAlreadyExistsException`, `LeagueAlreadyExistsException`, and `LeagueMissingLogoException` are implemented. A shared exception subscriber is not implemented.

`UserAlreadyExistsException` extends PHP's `DomainException`, retains the previous technical exception, and contains no HTTP or Symfony dependencies. The repository contract declares it, the handler propagates it, and the controller maps it to 409. Other expected domain failures should receive specific types and explicit HTTP mappings at the presentation boundary.

**Current defect:** the Doctrine repository catches every `Exception` and converts it to `UserAlreadyExistsException`. Database connection failures and unrelated persistence failures can therefore appear as duplicate-user conflicts. Catch `Doctrine\DBAL\Exception\UniqueConstraintViolationException` and translate only constraints that represent duplicate user details; let unexpected failures propagate to logging and a generic server-error response.

## Persistence and development data

Doctrine DBAL reads `DATABASE_URL`. ORM loads XML from `config/doctrine` for `App\Domain`, directly mapping the four domain models. The initial migration targets MySQL/MariaDB and creates `league`, `team`, `game`, and `user`; Doctrine also maintains migration history. See [DATABASE.md](DATABASE.md) for database setup and migration commands.

User username and email have separate unique constraints. Game requires league and home/away team foreign keys, restricts deletion of referenced records, and has a unique home-team/away-team/game-time combination. Score is nullable before a match ends. Tables use utf8mb4. IDs use database identity generation.

Fixtures attempt to create a Premier League record and an active demo user with a hashed password. The League fixture does not yet initialize the new required type, country, status, and timestamp fields and needs updating. Fixture loading purges data and is intended for development.

Tests append `_test` and optional `TEST_TOKEN` to the database name. Production configures Doctrine cache pools. The supplied Compose database remains PostgreSQL; it must be aligned with MySQL/MariaDB or the migrations must be deliberately ported before using it for this schema.

## Authentication and authorization

The Doctrine security provider loads User by username. User passwords use bcrypt; the generic password-authenticated interface has the automatic hasher configured. `POST /login_check` is registered without a controller because the JSON-login firewall handles authentication and Lexik JWT success/failure responses.

| Prefix | Current security behavior |
| --- | --- |
| Development profiler/assets paths | Security bypass |
| `/login` | Stateless JSON login; check path `/login_check` |
| `/api/users` | Stateless public firewall and public access rule |
| `/api/games` | Stateless public firewall and public access rule; no route implemented |
| `/api/top-players` | Stateless public firewall and public access rule; no route implemented |
| Other `/api` paths | Stateless JWT firewall and full-authentication requirement |

Public rules are broad regex prefixes without method restrictions. Future user-management routes beneath `/api/users` would inherit public access and bypass the JWT firewall. Restrict public registration to the intended method/path before adding protected user endpoints.

User roles currently contain `PUBLIC_ACCESS`, rather than a conventional application role such as `ROLE_USER`. Active status is not enforced through a custom user checker. There is no refresh-token, logout/revocation, or account verification workflow. JWT operation requires correctly configured readable signing keys and a matching passphrase; configuration alone is not end-to-end verification.

## Verification and remaining work

Historical registration checks on 2026-10-14 (the test totals below predate league import tests):

- Container lint passed.
- Router inspection confirmed `POST /api/users`, `POST /login_check`, and the development error-preview route.
- Doctrine mapping validation passed with `--skip-sync`; live database synchronization was not checked.
- PHPStan level 10 passes using `--debug`; the four DTO constructor type errors are resolved through validation and explicit type narrowing.
- PHPUnit passes: 3 tests and 61 assertions cover valid DTO construction, missing/invalid fields, and controller HTTP 422 responses without password hashing or persistence. These are unit tests, not live HTTP/database integration tests.

Priorities:

1. Extend registration coverage for unexpected fields, normalization, password preservation, and malformed JSON responses.
2. Narrow repository exception translation to duplicate-user constraint failures.
3. Scope controller service tags and review public security prefixes, roles, and inactive-user policy.
4. Add successful registration, duplicate-user, login, and JWT access tests, including HTTP and repository/database integration coverage.
5. Strengthen domain construction and invariants, then implement predictions, scoring, rankings, and football endpoints as needed.
6. Align the legacy Compose database with MySQL/MariaDB, update League fixtures, and keep schema documentation synchronized.

No API specification, CI pipeline, application Docker image, production web-server/deployment configuration, queue, or external football data client is present. Framework sessions are enabled globally although application firewalls are stateless. These are follow-up decisions, not functionality supplied by the current implementation.
