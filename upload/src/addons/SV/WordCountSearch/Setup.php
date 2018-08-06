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

        $sm->createTable(
            'xf_post_words',
            function (Create $table) {
                $table->addColumn('post_id','int')->primaryKey();
                $table->addColumn('word_count', 'int');
            }
        );

        $sm->alterTable(
            'xf_search_index',
            function (Alter $table) {
                $table->addColumn('word_count', 'int')->nullable(true);
            }
        );
    }

    public function upgrade2010000Step1()
    {
        $sm = $this->schemaManager();

        $sm->alterTable('xf_thread', function (Alter $table)
        {
            $table->addColumn('word_count', 'int')->nullable(true)->setDefault(null);
        });
    }

    public function uninstallStep1()
    {
        $sm = $this->schemaManager();

        $sm->dropTable('xf_post_words');

        $sm->alterTable(
            'xf_search_index',
            function (Alter $table) {
                $table->dropColumns(['word_count']);
            }
        );
    }

    public function uninstallStep2()
    {
        $sm = $this->schemaManager();

        $sm->alterTable('xf_thread', function (Alter $table)
        {
            $table->dropColumns('word_count');
        });
    }
}