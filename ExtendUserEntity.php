<?php

namespace MinecraftAvatars;

use XF\Mvc\Entity\Entity;

class ExtendUserEntity
{
    public static function userEntityStructure(\XF\Mvc\Entity\Manager $em, \XF\Mvc\Entity\Structure &$structure)
    {
        $structure->columns['minecraftavatars_username'] =      ['type' => Entity::STR, 'maxLength' => 16,  'censor' => true, 'defalut' => ''];
        $structure->columns['minecraftavatars_uuid'] =          ['type' => Entity::STR, 'maxLength' => 200, 'censor' => true, 'defalut' => ''];
        $structure->columns['minecraftavatars_use_skin'] =      ['type' => Entity::BOOL, 'default' => false];
        $structure->columns['minecraftavatars_avatar_date'] =   ['type' => Entity::UINT, 'default' => 0];
    }
}