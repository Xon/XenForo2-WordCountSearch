<?php
/**
 * @noinspection PhpUnusedParameterInspection
 */

namespace SV\WordCountSearch;

use XF\AddOn\AddOn;
use XF\Entity\AddOn as AddOnEntity;

abstract class InstallListener
{
    public static function addonPostRebuild(AddOn $addOn, AddOnEntity $installedAddOn, array $json): void
    {
        static::runInstallSteps($addOn);
    }

    public static function addonPostInstall(AddOn $addOn, AddOnEntity $installedAddOn, array $json, array &$stateChanges): void
    {
        static::runInstallSteps($addOn);
    }

    protected static function runInstallSteps(AddOn $addOn): void
    {
        if (empty(Setup::$supportedAddOns[$addOn->getAddOnId()]))
        {
            return;
        }

        // kick off the installer
        $setup = new Setup($addOn, \XF::app());
        $setup->applySchemaUpdates();
    }
}
