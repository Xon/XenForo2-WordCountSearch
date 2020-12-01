<?php

namespace SV\WordCountSearch;

use SV\StandardLib\InstallerHelper;
use XF\AddOn\AbstractSetup;
use XF\AddOn\StepRunnerInstallTrait;
use XF\AddOn\StepRunnerUninstallTrait;
use XF\AddOn\StepRunnerUpgradeTrait;
use XF\Db\Schema\Alter;
use XF\Db\Schema\Create;

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

    public function installStep1()
    {
        $sm = $this->schemaManager();

        foreach ($this->getTables() as $tableName => $callback)
        {
            $sm->createTable($tableName, $callback);
            $sm->alterTable($tableName, $callback);
        }
    }

    public function installStep2()
    {
        // legacy support, in-case XF1 version was uninstalled and columns not removed
        $db = $this->db();
        $sm = $this->schemaManager();
        if ($sm->columnExists('xf_thread','word_count'))
        {
            $db->query('update xf_thread set word_count = 0 where word_count is null');
        }
        if ($sm->columnExists('xf_search_index','word_count'))
        {
            $db->query('update xf_search_index set word_count = 0 where word_count is null');
        }
        foreach ($this->getAlterTables() as $tableName => $callback)
        {
            $sm->alterTable($tableName, $callback);
        }
    }

    public function upgrade2010000Step1()
    {
        $this->installStep1();
    }

    public function upgrade2010000Step2()
    {
        $this->installStep2();
    }

    public function uninstallStep1()
    {
        $sm = $this->schemaManager();

        foreach ($this->getTables() as $tableName => $callback)
        {
            $sm->dropTable($tableName);
        }
    }

    public function uninstallStep2()
    {
        $sm = $this->schemaManager();

        foreach ($this->getRemoveAlterTables() as $tableName => $callback)
        {
            $sm->alterTable($tableName, $callback);
        }
    }

    /**
     * @return array
     */
    protected function getTables()
    {
        $tables = [];

        $tables['xf_post_words'] = function ($table) {
            /** @var Create|Alter $table */
            $this->addOrChangeColumn($table,'post_id','int')->primaryKey();
            $this->addOrChangeColumn($table,'word_count', 'int');
        };

        return $tables;
    }

    /**
     * @return array
     */
    protected function getAlterTables()
    {
        $tables = [];

        $tables['xf_thread'] = function (Alter $table) {
            $this->addOrChangeColumn($table,'word_count', 'int')->setDefault(0)->nullable(false);
        };

        $tables['xf_search_index'] = function (Alter $table) {
            $this->addOrChangeColumn($table,'word_count', 'int')->setDefault(0)->nullable(false);
        };

        return $tables;
    }

    /**
     * @return array
     */
    protected function getRemoveAlterTables()
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

    protected function rebuildWordCount()
    {
        $this->app->jobManager()->enqueueUnique(
            'svWCSPostWordCountRebuild',
            'SV\WordCountSearch:PostWordCount'
        );
    }

    /**
     * @param array $stateChanges
     */
    public function postInstall(array &$stateChanges)
    {
        $this->rebuildWordCount();
    }

    /**
     * @param       $previousVersion
     * @param array $stateChanges
     */
    public function postUpgrade($previousVersion, array &$stateChanges)
    {
        if ($previousVersion < 2060500)
        {
            \XF::app()->jobManager()->enqueueUnique('pruneWordCount', 'SV\WordCountSearch:PrunePostWordCount');
        }

        $atomicJobs = [];
        /** @var \SV\WordCountSearch\Repository\WordCount $wordCountRepo */
        $wordCountRepo = $this->app->repository('SV\WordCountSearch:WordCount');
        if ($wordCountRepo->isThreadWordCountSupported())
        {
            if ($previousVersion < 2040402)
            {
                $atomicJobs[] = ['SV\WordCountSearch:PostWordCount', ['threadmarks-only' => true]];
            }
            if ($previousVersion < 2060701)
            {
                $atomicJobs[] = ['SV\WordCountSearch:ThreadWordCount', ['threadmarks-only' => true]];
            }
        }

        if ($atomicJobs)
        {
            \XF::app()->jobManager()->enqueueUnique(
                'threadmark-installer',
                'XF:Atomic', ['execute' => $atomicJobs]
            );
        }
    }
}