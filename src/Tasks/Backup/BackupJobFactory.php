<?php

namespace Spatie\Backup\Tasks\Backup;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Spatie\Backup\BackupDestination\BackupDestinationFactory;

class BackupJobFactory
{
    public static function createFromArray(array $config): BackupJob
    {
        return (new BackupJob())
            ->setFileSelection(static::createFileSelection($config['backup']['source']['files']))
            ->setDbDumpers(static::createDbDumpers(static::getSourceDatabaseConnections($config['backup']['source'])))
            ->setBackupDestinations(BackupDestinationFactory::createFromArray($config['backup']));
    }

    protected static function createFileSelection(array $sourceFiles): FileSelection
    {
        return FileSelection::create($sourceFiles['include'])
            ->excludeFilesFrom($sourceFiles['exclude'])
            ->shouldFollowLinks(isset($sourceFiles['follow_links']) && $sourceFiles['follow_links'])
            ->shouldIgnoreUnreadableDirs(Arr::get($sourceFiles, 'ignore_unreadable_directories', false));
    }

    protected static function createDbDumpers(array $dbConnectionNames): Collection
    {
        return collect($dbConnectionNames)->mapWithKeys(
            fn (string $dbConnectionName) => [$dbConnectionName => DbDumperFactory::createFromConnection($dbConnectionName)]
        );
    }

    protected static function getSourceDatabaseConnections(array $sourceConfig): array
    {
        $generatorClass = Arr::get($sourceConfig, 'database_generator');

        if ($generatorClass === null) {
            return $sourceConfig['databases'];
        }

        return (new $generatorClass)();
    }
}
