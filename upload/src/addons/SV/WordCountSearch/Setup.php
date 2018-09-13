<?php

namespace SV\WordCountSearch;

use SV\Utils\InstallerHelper;
use SV\Utils\InstallerSoftRequire;
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
    // from https://github.com/Xon/XenForo2-Utils cloned to src/addons/SV/Utils
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
        $sm = $this->schemaManager();

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
        // v2.0.0 or any version less than v2.1.0
        if ($previousVersion >= 2000100 && $previousVersion < 2010000)
        {
            /** @var \SV\WordCountSearch\Repository\WordCount $wordCountRepo */
            $wordCountRepo = $this->app->repository('SV\WordCountSearch:WordCount');
            if ($wordCountRepo->getIsThreadmarksSupportEnabled())
            {
                $this->app->jobManager()->enqueueUnique(
                    'svWCSThreadWordCountRebuild',
                    'SV\WordCountSearch:ThreadWordCount'
                );
            }
        }
    }

    use InstallerSoftRequire;
    /**
     * @param array $errors
     * @param array $warnings
     */
    public function checkRequirements(&$errors = [], &$warnings = [])
    {
        $this->checkSoftRequires($errors,$warnings);
    }
}