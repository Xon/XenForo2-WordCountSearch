<?php

namespace SV\WordCountSearch;

use SV\StandardLib\InstallerHelper;
use SV\WordCountSearch\Job\PostWordCount as PostWordCountJob;
use SV\WordCountSearch\Job\ThreadWordCount as ThreadWordCountJob;
use SV\WordCountSearch\Repository\WordCount as WordCountRepo;
use XF\AddOn\AbstractSetup;
use XF\AddOn\StepRunnerInstallTrait;
use XF\AddOn\StepRunnerUninstallTrait;
use XF\AddOn\StepRunnerUpgradeTrait;
use XF\Db\Schema\Alter;
use XF\Db\Schema\Create;
use XF\Job\Atomic as AtomicJob;

/**
 * Class Setup
 *
 * @package SV\WordCountSearch
 */
class Setup extends AbstractSetup
{
    use InstallerHelper;
    use StepRunnerInstallTrait;
    use StepRunnerUpgradeTrait;
    use StepRunnerUninstallTrait;

    public function installStep1(): void
    {
        $sm = $this->schemaManager();

        foreach ($this->getTables() as $tableName => $callback)
        {
            $sm->createTable($tableName, $callback);
            $sm->alterTable($tableName, $callback);
        }
    }

    /** @noinspection SqlConstantExpression */
    public function installStep2(): void
    {
        // legacy support, in-case XF1 version was uninstalled and columns not removed
        $db = $this->db();
        $sm = $this->schemaManager();
        if ($sm->columnExists('xf_thread', 'word_count'))
        {
            $db->query('UPDATE xf_thread SET word_count = 0 WHERE word_count IS NULL');
        }
        if ($sm->columnExists('xf_search_index', 'word_count'))
        {
            $db->query('UPDATE xf_search_index SET word_count = 0 WHERE word_count IS NULL');
        }
        foreach ($this->getAlterTables() as $tableName => $callback)
        {
            $sm->alterTable($tableName, $callback);
        }
    }

    public function upgrade2010000Step1(): void
    {
        $this->installStep1();
    }

    public function upgrade2010000Step2(): void
    {
        $this->installStep2();
    }

    public function uninstallStep1(): void
    {
        $sm = $this->schemaManager();

        foreach ($this->getTables() as $tableName => $callback)
        {
            $sm->dropTable($tableName);
        }
    }

    public function uninstallStep2(): void
    {
        $sm = $this->schemaManager();

        foreach ($this->getRemoveAlterTables() as $tableName => $callback)
        {
            $sm->alterTable($tableName, $callback);
        }
    }

    protected function getTables(): array
    {
        $tables = [];

        $tables['xf_post_words'] = function ($table) {
            /** @var Create|Alter $table */
            $this->addOrChangeColumn($table, 'post_id', 'int')->primaryKey();
            $this->addOrChangeColumn($table, 'word_count', 'int');
        };

        return $tables;
    }

    protected function getAlterTables(): array
    {
        $tables = [];

        $tables['xf_thread'] = function (Alter $table) {
            $this->addOrChangeColumn($table, 'word_count', 'int')->setDefault(0)->nullable(false);
        };

        $tables['xf_search_index'] = function (Alter $table) {
            $this->addOrChangeColumn($table, 'word_count', 'int')->setDefault(0)->nullable(false);
        };

        return $tables;
    }

    protected function getRemoveAlterTables(): array
    {
        $tables = [];

        $tables['xf_thread'] = function (Alter $table) {
            $table->dropColumns(['word_count']);
        };

        $tables['xf_search_index'] = function (Alter $table) {
            $table->dropColumns(['word_count']);
        };

        return $tables;
    }

    protected function rebuildWordCount(): void
    {
        $this->app->jobManager()->enqueueUnique(
            'svWCSPostWordCountRebuild',
            'SV\WordCountSearch:PostWordCount'
        );
    }

    public function postInstall(array &$stateChanges): void
    {
        parent::postInstall($stateChanges);
        $this->rebuildWordCount();
    }

    public function postUpgrade($previousVersion, array &$stateChanges): void
    {
        $previousVersion = (int)$previousVersion;
        parent::postUpgrade($previousVersion, $stateChanges);
        if ($previousVersion < 2060500)
        {
            \XF::app()->jobManager()->enqueueUnique('pruneWordCount', 'SV\WordCountSearch:PrunePostWordCount');
        }

        $atomicJobs = [];
        $wordCountRepo = WordCountRepo::get();
        if ($wordCountRepo->isThreadWordCountSupported())
        {
            if ($previousVersion < 2040402)
            {
                $atomicJobs[] = [PostWordCountJob::class, ['threadmarks-only' => true]];
            }
            if ($previousVersion < 2060701)
            {
                $atomicJobs[] = [ThreadWordCountJob::class, ['threadmarks-only' => true]];
            }
        }

        if ($atomicJobs)
        {
            \XF::app()->jobManager()->enqueueUnique(
                'threadmark-installer',
                AtomicJob::class, ['execute' => $atomicJobs]
            );
        }
    }
}