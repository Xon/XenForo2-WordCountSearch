<?php

namespace SV\ThreadStarterAlerts;

use XF\AddOn\AbstractSetup;
use XF\AddOn\StepRunnerInstallTrait;
use XF\AddOn\StepRunnerUninstallTrait;
use XF\AddOn\StepRunnerUpgradeTrait;
use XF\Db\Schema\Alter;
use XF\Db\Schema\Create;

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
    }

    public function uninstallStep1()
    {
        $sm = $this->schemaManager();

        $sm->dropTable('xf_post_words');
    }
}