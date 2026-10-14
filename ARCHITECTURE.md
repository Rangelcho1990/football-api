# Architecture

## Current state

Football API is a Symfony 7.4 application organized into domain, application, infrastructure, and HTTP controller layers. User registration is implemented through an invokable controller, a Symfony request validator, an immutable application DTO, a use-case handler, and a repository interface with a Doctrine implementation. JWT login is configured. League, Team, Game, and User have XML ORM mappings and an initial MySQL/MariaDB migration.

This is an early implementation of a football prediction API. Predictions, scoring, rankings, football data imports, and game/league/team endpoints are not implemented. Configured firewall prefixes do not create endpoints.

## Structure and dependency boundaries

| Path | Current responsibility |
| --- | --- |
| `public/index.php`, `bin/console`, `src/Kernel.php` | Symfony HTTP/CLI bootstrap and kernel |
| `src/Controller/User/UserController.php` | Decode registration JSON, call the request validator and handler, map malformed JSON to 400, validation failures to 422, and duplicate-user failures to 409 |
| `src/Application/CreateUserHandler.php` | Construct a User, hash its password, activate it, and persist it through the repository contract |
| `src/Controller/User/Validation/UserRequestValidator.php` | Validate and normalize HTTP input using Symfony constraints, then construct the application DTO |
| `src/Application/DTO/UserRequestDTO.php` | Framework-independent `final readonly` input DTO containing username, email, password, and integer avatar |
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

The HTTP boundary owns JSON decoding, input validation, and HTTP responses. The handler accepts a typed application DTO and coordinates registration through the domain repository contract. The DTO contains only data and has no Symfony dependencies. Business invariants should be enforced in domain entities and value objects regardless of the entry point; see [DDD.md](DDD.md).

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

## Domain model

| Model | State and current behavior |
| --- | --- |
| User | Generated ID, username, password hash, email, creation timestamp, points, avatar, active flag; implements Symfony authentication interfaces |
| League | Generated ID, name, slug, logo, external league API ID; getters/setters |
| Team | Generated ID, name, logo; getters/setters |
| Game | Generated ID, nullable string score, home/away teams, game time, league; getters/setters |

User defaults to avatar 1, zero points, current immutable creation time, and inactive status. Registration overrides avatar and activates the account. `setUsername()` rejects only the empty string using `InvalidArgumentException`; other business invariants are largely absent. Constructors leave required properties uninitialized until setters or Doctrine hydration populate them.

There is no Guess/Prediction model or relationship. There are no scoring rules, prediction deadlines, ranking queries, or checks preventing a team from playing itself. Negative points and invalid domain field values are not prevented. External IDs exist on League, but no external provider integration exists.

## Exceptions and HTTP boundaries

HTTP validation uses Symfony’s `ValidationFailedException` in the controller layer; it is not a domain exception. The controller maps its field violations to HTTP 422.

Business exceptions belong beside their domain, for example `Domain/User/Exception/` and, when needed, `Domain/League/Exception/`. Only `UserAlreadyExistsException` exists today; League exceptions and a shared exception subscriber are proposed additions, not implemented features.

`UserAlreadyExistsException` extends PHP's `DomainException`, retains the previous technical exception, and contains no HTTP or Symfony dependencies. The repository contract declares it, the handler propagates it, and the controller maps it to 409. Other expected domain failures should receive specific types and explicit HTTP mappings at the presentation boundary.

**Current defect:** the Doctrine repository catches every `Exception` and converts it to `UserAlreadyExistsException`. Database connection failures and unrelated persistence failures can therefore appear as duplicate-user conflicts. Catch `Doctrine\DBAL\Exception\UniqueConstraintViolationException` and translate only constraints that represent duplicate user details; let unexpected failures propagate to logging and a generic server-error response.

## Persistence and development data

Doctrine DBAL reads `DATABASE_URL`. ORM loads XML from `config/doctrine` for `App\Domain`, directly mapping the four domain models. The initial migration targets MySQL/MariaDB and creates `league`, `team`, `game`, and `user`; Doctrine also maintains migration history. See [DATABASE.md](DATABASE.md) for database setup and migration commands.

User username and email have separate unique constraints. Game requires league and home/away team foreign keys, restricts deletion of referenced records, and has a unique home-team/away-team/game-time combination. Score is nullable before a match ends. Tables use utf8mb4. IDs use database identity generation.

Fixtures create a Premier League record and an active demo user with a hashed password. They do not create teams or games. Fixture loading purges data and is intended for development.

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

Review checks on 2026-10-14:

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
6. Align Compose and README setup instructions with the actual database and implemented features. README still contains obsolete claims that controllers, routes, mappings, migrations, and development tools are absent.

No API specification, CI pipeline, application Docker image, production web-server/deployment configuration, queue, or external football data client is present. Framework sessions are enabled globally although application firewalls are stateless. These are follow-up decisions, not functionality supplied by the current implementation.
