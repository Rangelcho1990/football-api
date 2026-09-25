# Architecture

## Current state

Football API is a Symfony 7.4 application with an initial football prediction domain model. The executable application currently consists of the framework bootstrap and configuration. Domain classes contain fields but almost no behavior; application use cases, HTTP controllers, and persistence mappings remain to be implemented.

The directory layout suggests separation between domain, application, and infrastructure code. This is an intended organization, not an enforced dependency boundary: domain classes already import Doctrine collections, and `Player` implements Symfony security interfaces.

## Structure and runtime

| Path | Current responsibility |
| --- | --- |
| `public/index.php` | HTTP front controller; loads Symfony Runtime and creates the kernel |
| `bin/console` | CLI entry point for Symfony and bundle commands |
| `src/Kernel.php` | Framework kernel using `MicroKernelTrait` |
| `src/Domain/` | `Game`, `League`, `Player`, and `Team` classes |
| `src/Controller/` | Empty location for HTTP controllers |
| `src/Application/` | Empty location for use cases |
| `src/Infrastructure/` | Empty location for infrastructure integrations |
| `src/Entity/` | Empty directory currently configured as Doctrine's entity mapping source |
| `src/Repository/` | Empty location for repositories |
| `config/packages/` | Framework, Doctrine, security, JWT, cache, and routing configuration |
| `config/routes.yaml` | Imports controller attribute routes |
| `config/services.yaml` | Autowiring and autoconfiguration for the `App` namespace |
| `compose.yaml`, `compose.override.yaml` | PostgreSQL service, persistent volume, and dynamic host-port publication |
| `var/` | Generated runtime files such as cache and logs; ignored by Git |

Composer maps `App\` to `src/`. The HTTP entry point delegates request handling to the Symfony kernel, which loads routes, services, and security configuration. Once controllers exist, matched requests can pass through the configured firewall and access rules to their handlers. Today, no application route resolves to a handler; only the development error-preview route is registered.

## Domain model

| Class | Declared state and behavior |
| --- | --- |
| `League` | `id`, `name`, `leagueNameSlugged`, `log`, `leagueApiId`; no methods. `log` is the literal field name in the source. |
| `Team` | `id`, `name`, `logo`; no methods. |
| `Game` | `id`, string `score`, home and away `Team` references, `gameTime`, `guesses`, and a `League` reference; no methods. |
| `Player` | Registered application user with `id`, `username`, `password`, `email`, `createdAt`, `point`, `avatar`, `isActive`, and `guesses`. Implements Symfony user/password interfaces. |

`Game` references two teams and one league through PHP property types. Both `Game` and `Player` declare a Doctrine `ArrayCollection` for guesses, but there is no guess class, element type declaration, or collection initialization.

The `Player` constructor sets avatar to `1`, points to `0`, creation time to the current immutable timestamp, and active status to `false`. Its other typed properties remain uninitialized. `getPassword()` and `getUserIdentifier()` read those properties directly, so they cannot safely be called on a newly constructed player. No setters, factories, validation, prediction rules, or points calculations exist yet.

`leagueApiId` hints at an external league identifier; no external football provider or import integration is implemented.

## Persistence

Doctrine DBAL reads its connection from `DATABASE_URL`. Compose defaults to PostgreSQL 16 Alpine and persists database files in the `database_data` volume. The application runs outside Compose and connects through the published host port.

Doctrine ORM uses attribute mapping for `App\Entity` in `src/Entity/`, with underscore naming and PostgreSQL identity generation. The existing classes live in `App\Domain`, have no ORM mapping attributes, and are outside the configured mapping namespace. Consequently, there are currently **no mapped entities or application tables defined by this code**.

There are no repositories, migrations, fixtures, or Doctrine Migrations bundle. Before adding persistence, choose whether domain classes will be mapped directly or whether separate persistence entities will translate to and from domain objects. Update mapping configuration and add a migration workflow to match that decision.

The test environment appends `_test` and an optional `TEST_TOKEN` to the database name. Production configures Doctrine query/result cache pools backed by Symfony cache. The default application cache uses the filesystem.

## Authentication and access rules

Security configuration is preparatory. The `users` provider tries to load `App\Domain\Player\Player` by `username` through Doctrine, but that class is not mapped. Password hashing specifies bcrypt for `Player` and the automatic hasher for other password-authenticated user implementations.

Firewalls are checked in order:

| Matching prefix | Configured behavior |
| --- | --- |
| `/_profiler`, `/_wdt`, `/assets/`, `/build/` | Development firewall bypasses security |
| `/login` | Stateless JSON login with `/login_check` as the check path and Lexik JWT success/failure handlers |
| `/api/players` | Stateless public firewall |
| `/api/games` | Stateless public firewall |
| `/api/top-players` | Stateless public firewall |
| Remaining `/api` paths | Stateless JWT authentication using the `users` provider |

Access rules allow public access to the login, player, ranking, and game prefixes, then require full authentication for remaining `/api` paths. These are regular-expression prefixes without HTTP-method restrictions. Any future route beneath a public prefix inherits that broad public policy unless the configuration changes. Those public firewalls also take precedence over the general JWT firewall.

**Firewall patterns do not create endpoints.** There is no `/login_check` route, registration controller, protected API handler, or token refresh/logout workflow. Authentication additionally requires mapped users, initialized credentials, and generated signing keys.

`Player::getRoles()` currently returns `PUBLIC_ACCESS`. That value is used as an access-control attribute in the configuration; the model does not yet establish a conventional application role scheme. The `isActive` field is not enforced by a custom user checker or application logic.

## Configuration and operational boundaries

Symfony Dotenv loads environment defaults; use ignored local dotenv files or deployment environment variables for machine-specific values and secrets. JWT PEM files, dependencies, and runtime output are ignored by Git. See [README.md](README.md) for setup commands and environment variables.

The repository does not provide an application Docker image, production web-server configuration, deployment pipeline, API specification, queue, external data client, or automated test suite. Framework sessions are enabled globally even though the configured application firewalls are stateless.

## Suggested implementation sequence

1. Define domain construction, validation, guess relationships, and scoring rules; initialize required properties and collections.
2. Decide on ORM mapping boundaries, implement repositories, and add migrations.
3. Complete user creation, password handling, account-status enforcement, roles, and login routing.
4. Implement HTTP routes and application use cases, reviewing access policy for every method and path.
5. Add domain tests and database/authentication integration tests, then document actual endpoint contracts and deployment behavior.

These are proposed next steps, not features currently available in the project.
