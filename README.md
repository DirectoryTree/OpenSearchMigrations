# OpenSearch Migrations

OpenSearch index migrations for Laravel.

## Installation

Install the package with Composer:

```bash
composer require directorytree/opensearch-migrations
```

Publish the OpenSearch client configuration:

```bash
php artisan vendor:publish --provider="DirectoryTree\OpenSearchClient\OpenSearchClientServiceProvider"
```

Publish the migration configuration:

```bash
php artisan vendor:publish --provider="DirectoryTree\OpenSearchMigrations\OpenSearchMigrationsServiceProvider"
```

## Configuration

The migration configuration is published to `config/opensearch-migrations.php`:

```php
'table' => env('OPENSEARCH_MIGRATIONS_TABLE', 'opensearch_migrations'),

'connection' => env('OPENSEARCH_MIGRATIONS_CONNECTION'),

'storage_directory' => env('OPENSEARCH_MIGRATIONS_DIRECTORY', base_path('opensearch/migrations')),

'index_name_prefix' => env('OPENSEARCH_MIGRATIONS_INDEX_NAME_PREFIX', env('SCOUT_PREFIX', '')),

'alias_name_prefix' => env('OPENSEARCH_MIGRATIONS_ALIAS_NAME_PREFIX', env('SCOUT_PREFIX', '')),
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
