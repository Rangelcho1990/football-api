# Football API

A PHP/Symfony backend for a football prediction application, organized into domain, application, infrastructure, and HTTP controller layers.

User registration and league/team imports are implemented. JWT login is configured. Predictions, scoring, rankings, and game/league/team API endpoints remain future work. See [ARCHITECTURE.md](ARCHITECTURE.md) for implementation details and limitations.

## Requirements

- PHP compatible with the locked dependencies, Composer 2, and required extensions (`composer check-platform-reqs`). The project declares PHP >=8.2; individual dependencies can require a newer runtime.
- Symfony 7.4, Doctrine ORM 3.7, and LexikJWTAuthenticationBundle 3.2.
- MySQL/MariaDB and PHP's `pdo_mysql` extension.

The supplied Docker Compose service is a legacy PostgreSQL setup and does not match the current MySQL/MariaDB migration.

## Local setup

Run commands from the project root:

```bash
composer install
composer check-platform-reqs
```

Configure `.env.local` with your database connection, application secret, and JWT settings:

```dotenv
APP_ENV=dev
APP_SECRET=replace_with_a_random_secret
DEFAULT_URI=http://127.0.0.1:8000
DATABASE_URL="mysql://USER:URL_ENCODED_PASSWORD@127.0.0.1:3306/football-api?serverVersion=10.11.14-MariaDB&charset=utf8mb4"
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
JWT_PASSPHRASE=replace_with_a_random_passphrase
```

Use the actual database server version and URL-encode the password. Generate secrets with `php -r 'echo bin2hex(random_bytes(32)), PHP_EOL;'`. Keep `.env.local`, private keys, and passphrases out of version control.

Create the database and apply migrations:

```bash
php bin/console doctrine:database:create --if-not-exists
php bin/console doctrine:migrations:migrate
```

See [DATABASE.md](DATABASE.md) for database administration details. Existing databases need migrations for subsequent schema changes; editing an already executed migration does not update their tables.

Generate JWT keys after setting the passphrase, then start a development server:

```bash
php bin/console lexik:jwt:generate-keypair --skip-if-exists
chmod 600 config/jwt/private.pem
php -S 127.0.0.1:8000 -t public public/index.php
```

Existing keys require their original passphrase. Registration is available at `POST /api/users`; JWT JSON login uses `POST /login_check`.

## Import leagues

```bash
php bin/console app:import-leagues rapid
```

`source` is a required positional argument. Currently `rapid` reads the local `__rapid_leagues` table; it does not call the Rapid API. Provision and populate that staging table separately: the initial migration does not create it.

Each staging row must provide:

- `country_id`: a nonblank integer or digit string used for every league in that row.
- `data`: a JSON array whose entries contain a `league` object with integer `id` and string `name`, `logo`, and `type` fields.

Example `data` value:

```json
[{"league":{"id":569,"name":"Premier League","type":"League","logo":"https://media.api-sports.io/football/leagues/569.png"}}]
```

The source generates a lowercase slug, sets status to `1`, and assigns the current date/time. Country details and seasons inside the JSON are ignored; the country ID comes from the table column.

The handler skips existing API IDs, rejects blank logos for new leagues, and persists each new league. Existing leagues are not updated. The success message reports the fetched count, including skipped duplicates. Imports flush each inserted league separately, so a later failure can leave earlier inserts committed.

Source registration lives in [config/services/league_sources.yaml](config/services/league_sources.yaml), imported by `config/services.yaml`. Add another `LeagueSourceInterface` implementation and register it with the `app.league_source` tag and a distinct `source` value.

## Import teams

Apply the migrations before the first import:

```bash
php bin/console doctrine:migrations:migrate
php bin/console app:import-teams rapid
```

`FetchTeamsCommand` follows the league flow with a separate `TeamSourceRegistry`, application `TeamSourceInterface`/DTO, `ImportTeamsHandler`, and domain repository contract implemented by Doctrine. Register additional team sources in [config/services/team_sources.yaml](config/services/team_sources.yaml) with the `app.team_source` tag and a distinct `source` value.

The Rapid adapter reads `data` and `country_id` from the separately provisioned `__rapid_teams` staging table. Each row requires an integer or digit-string `country_id`. Each `data` value must be a JSON array containing a `team` object with an unsigned integer `id` and string `name`. Optional `logo`, `founded`, and `national` fields accept a string, integer, and boolean respectively, or null:

```json
[{"team":{"id":33,"name":"Manchester United","logo":"https://media.api-sports.io/football/teams/33.png","founded":1878,"national":false}}]
```

The staging row's country ID applies to every team in its JSON array; the JSON country name is ignored. The adapter sets status to `1` and the update timestamp to the current time. Venue and other provider fields are ignored. Malformed staging data fails before any teams are persisted. Existing API IDs are skipped without updates. The fetched count includes duplicates, and inserts flush individually as in the league import.

Migration `Version20261029090000` adds founded year, national flag, required unsigned unique API ID, required country ID, nullable status (default `1`), and nullable `__lastUpdate`. It changes the logo to nullable `VARCHAR(100)`. Apply it to an empty team table or reconcile existing data first. Source IDs currently share one namespace, as with leagues; providers with overlapping IDs need a separate source/external-ID mapping before use.

## Checks

```bash
composer validate --no-check-publish
composer check-platform-reqs
php bin/console lint:yaml config
php bin/console lint:container
php bin/console doctrine:schema:validate --skip-sync
vendor/bin/phpunit
composer phpstan
```

Run `php bin/console doctrine:schema:validate` separately to check synchronization with a live database. Unit tests cover registration validation (including defensive type guards), controller responses and password hashing, domain state, league/team sources and handlers, both import commands, repository delegation/error translation, and fixture construction. Run `composer test:coverage` with Xdebug installed to generate text coverage and an HTML report at `var/coverage/index.html`. Repository and fixture tests use mocks; they do not validate live database behavior. Local team-import verification is documented in [ARCHITECTURE.md](ARCHITECTURE.md).

The development fixtures have not yet been updated to initialize all required League fields. Update them before loading fixtures against the expanded schema.

## Documentation

- [ARCHITECTURE.md](ARCHITECTURE.md): boundaries, flows, and remaining work.
- [DATABASE.md](DATABASE.md): database setup and migration commands.
- [TODO.md](TODO.md): proposed external-source ID mapping table.

## License

`composer.json` declares this project proprietary. No separate license file is included.
