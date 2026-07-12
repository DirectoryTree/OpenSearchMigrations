<h1 align="center">OpenSearch Migrations</h1>

<p align="center">OpenSearch index migrations for Laravel.</p>

<p align="center">
<a href="https://github.com/DirectoryTree/OpenSearchMigrations/actions"><img src="https://img.shields.io/github/actions/workflow/status/DirectoryTree/OpenSearchMigrations/run-tests.yml?branch=master&style=flat-square"></a>
<a href="https://packagist.org/packages/directorytree/opensearch-migrations"><img src="https://img.shields.io/packagist/v/directorytree/opensearch-migrations.svg?style=flat-square"></a>
<a href="https://packagist.org/packages/directorytree/opensearch-migrations"><img src="https://img.shields.io/packagist/dt/directorytree/opensearch-migrations.svg?style=flat-square"></a>
<a href="https://packagist.org/packages/directorytree/opensearch-migrations"><img src="https://img.shields.io/packagist/l/directorytree/opensearch-migrations.svg?style=flat-square"></a>
</p>

---

## Installation

Install the package with Composer:

```bash
composer require directorytree/opensearch-migrations
```

Publish the OpenSearch client configuration:

```bash
php artisan vendor:publish --provider="DirectoryTree\OpenSearchClient\OpenSearchClientServiceProvider"
```

Publish the migration and deployment configuration:

```bash
php artisan vendor:publish --provider="DirectoryTree\OpenSearchMigrations\OpenSearchMigrationsServiceProvider"
```

## Configuration

Migration history and index naming are configured in `config/opensearch-migrations.php`:

```php
'table' => env('OPENSEARCH_MIGRATIONS_TABLE', 'opensearch_migrations'),

'connection' => env('OPENSEARCH_MIGRATIONS_CONNECTION'),

'storage_directory' => env('OPENSEARCH_MIGRATIONS_DIRECTORY', base_path('opensearch/migrations')),

'index_name_prefix' => env('OPENSEARCH_MIGRATIONS_INDEX_NAME_PREFIX', env('SCOUT_PREFIX', '')),

'alias_name_prefix' => env('OPENSEARCH_MIGRATIONS_ALIAS_NAME_PREFIX', env('SCOUT_PREFIX', '')),
```

Deployment storage is configured separately in `config/opensearch-deployments.php`:

```php
'table' => env('OPENSEARCH_DEPLOYMENTS_TABLE', 'opensearch_deployments'),

'connection' => env(
    'OPENSEARCH_DEPLOYMENTS_CONNECTION',
    env('OPENSEARCH_MIGRATIONS_CONNECTION')
),
```

## Creating Migrations

Create a migration:

```bash
php artisan opensearch:make:migration create_posts_index
```

Migration files are stored in `opensearch/migrations` by default:

```php
use DirectoryTree\OpenSearchAdapter\Indices\Mapping;
use DirectoryTree\OpenSearchAdapter\Indices\Settings;
use DirectoryTree\OpenSearchMigrations\Facades\Index;
use DirectoryTree\OpenSearchMigrations\MigrationInterface;

class CreatePostsIndex implements MigrationInterface
{
    public function up(): void
    {
        Index::create('posts', function (Mapping $mapping, Settings $settings) {
            $mapping->text('title');
            $mapping->text('body');
        });
    }

    public function down(): void
    {
        Index::dropIfExists('posts');
    }
}
```

## Running Migrations

Run all pending migrations:

```bash
php artisan opensearch:migrate
```

Run a single migration:

```bash
php artisan opensearch:migrate 2026_01_01_000000_create_posts_index
```

Roll back the last batch:

```bash
php artisan opensearch:migrate:rollback
```

Roll back every migrated file:

```bash
php artisan opensearch:migrate:reset
```

Roll back and rerun every migration:

```bash
php artisan opensearch:migrate:refresh
```

Drop all indexes and rerun every migration:

```bash
php artisan opensearch:migrate:fresh
```

Show migration status:

```bash
php artisan opensearch:migrate:status
```

## Zero-Downtime Index Deployments

The deployer creates versioned physical indexes and tracks their lifecycle in the `opensearch_deployments` table. Applications remain responsible for backfilling and validating candidate documents.

First, create a stable alias for the index in a migration:

```php
Index::putAlias('posts', 'posts_search');
```

Start a deployment with the latest mapping and settings:

```php
use DirectoryTree\OpenSearchAdapter\Indices\Mapping;
use DirectoryTree\OpenSearchAdapter\Indices\Settings;
use DirectoryTree\OpenSearchMigrations\Deployer;

$deployer = app(Deployer::class);

$deployment = $deployer->start(
    name: 'posts',
    alias: 'posts_search',
    configure: function (Mapping $mapping, Settings $settings) {
        $mapping->text('title');
        $mapping->text('body');
    },
);
```

The returned deployment exposes the physical candidate index for backfilling:

```php
$deployment->candidateIndex;
```

While backfilling, send live writes and deletions to every deployment write index:

```php
$deployment->writeIndexes();
```

After application-specific validation succeeds, mark the candidate ready and atomically move the alias:

```php
$deployer->markReady('posts');
$deployer->cutover('posts');
```

Cancel and delete a candidate that should not be promoted:

```php
$deployer->cancel('posts');
```

The previous index remains in the deployment's write indexes during the rollback window:

```php
$deployer->rollback('posts');
```

Once the new index is verified in production, delete the previous physical index and complete the deployment:

```php
$deployer->retire('posts');
```

`opensearch:migrate:fresh` deletes all deployment records because it drops all physical indexes. Migration reset and refresh commands refuse to run while managed deployments exist.

## Credits

This package builds on a lot of the foundation and prior work from [Ivan Babenko](https://github.com/babenkoivan) and his Elasticsearch Laravel ecosystem packages.

We're grateful for the work he has shared with the Laravel community. If this package helps your work, consider supporting Ivan through [GitHub Sponsors](https://github.com/sponsors/babenkoivan).
