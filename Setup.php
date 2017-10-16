<?php

namespace MinecraftAvatars;

use XF\AddOn\AbstractSetup;
use XF\AddOn\StepRunnerInstallTrait;
use XF\AddOn\StepRunnerUninstallTrait;
use XF\AddOn\StepRunnerUpgradeTrait;
use XF\Db\Schema\Alter;

class Setup extends AbstractSetup
{
	use StepRunnerInstallTrait;
	use StepRunnerUpgradeTrait;
	use StepRunnerUninstallTrait;

    /**
     * Adding columns to 'xf_user'
     */
	public function installStep1()
    {
        $this->schemaManager()->alterTable('xf_user', function (Alter $table)
        {
            $table->addColumn('minecraftavatars_username', 'varchar', 16)->setDefault('');
            $table->addColumn('minecraftavatars_uuid', 'varchar', 200)->setDefault('');
            $table->addColumn('minecraftavatars_use_skin', 'tinyInt')->setDefault(0);
            $table->addColumn('minecraftavatars_avatar_date', 'int')->setDefault(0);
        });
    }

    /**
     * Removing directory with Minecraft avatars
     */
    public function uninstallStep1()
    {
        \XF\Util\File::deleteDirectory("data/minecraft_avatars");
    }

    /**
     * Removing added columns
     */
    public function uninstallStep2()
    {
        $this->schemaManager()->alterTable('xf_user', function(Alter $table)
        {
            $table->dropColumns([
                'minecraftavatars_username',
                'minecraftavatars_uuid',
                'minecraftavatars_use_skin',
                'minecraftavatars_avatar_date'
            ]);
        });
    }
}