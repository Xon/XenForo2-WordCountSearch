<?php

namespace SV\WordCountSearch;

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

    /**
     * @param Create|Alter $table
     * @param string       $name
     * @param string|null  $type
     * @param string|null  $length
     * @return \XF\Db\Schema\Column
     */
    protected function addOrChangeColumn($table, $name, $type = null, $length = null)
    {
        if ($table instanceof Create)
        {
            $table->checkExists(true);

            return $table->addColumn($name, $type, $length);
        }
        else if ($table instanceof Alter)
        {
            if ($table->getColumnDefinition($name))
            {
                return $table->changeColumn($name, $type, $length);
            }

            return $table->addColumn($name, $type, $length);
        }
        else
        {
            throw new \LogicException("Unknown schema DDL type ". get_class($table));

        }
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

    /**
     * @param array $errors
     * @param array $warnings
     */
    public function checkRequirements(&$errors = [], &$warnings = [])
    {
        /** @var \XF\Repository\AddOn $addOnRepo */
        $addOnRepo = \XF::app()->repository('XF:AddOn');
        /** @var array $addOns */
        $addOns = $addOnRepo->getEnabledAddOns();
        if (isset($addOns['SV/Threadmarks']) && $addOns['SV/Threadmarks'] < 2000100)
        {
            $warnings[] = 'Recommend Threadmarks v2.0.1+';
        }
    }
}