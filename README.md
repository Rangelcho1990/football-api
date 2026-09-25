# Football API

A PHP/Symfony backend scaffold for a football prediction application. The domain currently describes leagues, teams, games, and registered players, including fields for guesses and player points.

**Status:** early development. There are no application controllers, API routes, mapped Doctrine entities, migrations, or automated tests yet. JWT and database configuration are present, but registration, login, game access, and rankings are not implemented. The development error-preview route is the only route currently registered.

See [ARCHITECTURE.md](ARCHITECTURE.md) for the code structure, domain model, security configuration, and implementation gaps.

## Stack and requirements

- PHP 8.2 or newer, Composer 2, and the PHP extensions required by the locked dependencies (`composer check-platform-reqs`).
- Symfony 7.4, Doctrine ORM 3.7, and LexikJWTAuthenticationBundle 3.2 (dependency constraints in `composer.json`).
- PostgreSQL 16 by default in Docker Compose. PHP needs `pdo_pgsql` to connect to PostgreSQL; Composer's platform check alone does not verify this database driver.
- Docker with Compose for the supplied database service, or an independently provisioned PostgreSQL server.

Compose runs only PostgreSQL. Run PHP and Composer on the host; no application container is supplied.

## Local setup

Run commands from the project root.

1. Install dependencies:

   ```bash
   composer install
   composer check-platform-reqs
   ```

2. Start the database and discover its host port:

   ```bash
   docker compose up -d database
   docker compose port database 5432
   ```

   The Compose override publishes container port 5432 on a dynamically assigned host port. Use the reported port in `DATABASE_URL`. The database name, user, and password come from `POSTGRES_DB`, `POSTGRES_USER`, and `POSTGRES_PASSWORD` in Compose; their local defaults are `app`, `app`, and `!ChangeMe!`. Compose creates the database on first initialization and stores it in the `database_data` volume.

3. Create `.env.local` with local overrides. Replace all placeholders below before running database or JWT commands:

   ```dotenv
   APP_ENV=dev
   APP_SECRET=replace_with_a_random_secret
   DEFAULT_URI=http://127.0.0.1:8000
   DATABASE_URL="postgresql://app:URL_ENCODED_PASSWORD@127.0.0.1:HOST_PORT/app?serverVersion=16&charset=utf8"
   JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
   JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
   JWT_PASSPHRASE=replace_with_a_random_passphrase
   ```

   Generate separate random values with `php -r 'echo bin2hex(random_bytes(32)), PHP_EOL;'`. URL-encode the database password in the connection URL and match `serverVersion` to your PostgreSQL version. `.env.local` is ignored by Git. Symfony reads the committed `.env` and environment-specific files as defaults; real environment variables take precedence over dotenv files.

4. Generate JWT signing keys:

   ```bash
   php bin/console lexik:jwt:generate-keypair --skip-if-exists
   ```

   PEM files under `config/jwt/` are ignored by Git. Keep the configured passphrase consistent with the private key. Generating keys prepares authentication infrastructure; it does not create a working login endpoint.

5. Start a local development server:

   ```bash
   php -S 127.0.0.1:8000 -t public public/index.php
   ```

   This server is for local development. Requests to `/` or the planned API paths currently return 404 because no application routes exist. There is no database schema to install yet, and no migration command is supplied by this project.

## Available checks

```bash
composer validate --no-check-publish
composer check-platform-reqs
php bin/console lint:yaml config
php bin/console lint:container
php bin/console debug:router
php bin/console doctrine:mapping:info
```

The mapping command currently reports no mapped entities. There is no PHPUnit dependency, test suite, static-analysis configuration, or CI workflow in the checkout. These checks validate configuration and dependency compatibility, not end-to-end application behavior.

## Configuration and development

| Setting | Purpose |
| --- | --- |
| `APP_ENV` | Symfony environment, such as `dev`, `test`, or `prod` |
| `APP_SECRET` | Framework secret |
| `DEFAULT_URI` | Base URI used when generating URLs outside HTTP requests |
| `DATABASE_URL` | Doctrine database connection and server version |
| `JWT_SECRET_KEY`, `JWT_PUBLIC_KEY` | JWT private/public key paths |
| `JWT_PASSPHRASE` | Private-key passphrase |
| `APP_SHARE_DIR` | Default shared directory setting in `.env`; no application code currently uses it |

Place HTTP handlers in `src/Controller/`. Route attributes are imported through `config/routes.yaml`, and application services are autowired through `config/services.yaml`. Review the persistence and security gaps in [ARCHITECTURE.md](ARCHITECTURE.md) before adding endpoints.

Stop the local database with `docker compose stop database`. Its named volume retains data.

## License

`composer.json` declares this project proprietary. No separate license file is included.
