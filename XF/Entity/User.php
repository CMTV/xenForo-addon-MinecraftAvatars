<?php

namespace MinecraftAvatars\XF\Entity;

use MinecraftAvatars\MinecraftAvatarsHelper;

class User extends XFCP_User
{
    /**
     * Returning correct avatar type when using Minecraft skin head as avatar
     *
     * @return string
     */
    public function getAvatarType()
    {
        if($this->minecraftavatars_avatar_date)
        {
            return 'custom';
        } else {
            return parent::getAvatarType();
        }
    }

    /**
     * Getting URL to minecraft skin head
     *
     * @param $sizeCode
     * @param null $forceType
     * @param bool $canonical
     * @return mixed|null|string
     */
    public function getAvatarUrl($sizeCode, $forceType = null, $canonical = false) {
        if($this->minecraftavatars_use_skin) {
            $sizeMap = \XF::app()->container('avatarSizeMap');
            if (!isset($sizeMap[$sizeCode]))
            {
                // Always fallback to 's' in the event of an unknown size (e.g. 'xs', 'xxs' etc.)
                $sizeCode = 's';
            }

            if ($this->minecraftavatars_avatar_date)
            {
                return MinecraftAvatarsHelper::getAvatarUrl($this, $sizeCode);
            }
            else
            {
                return null;
            }
        } else {
            return parent::getAvatarUrl($sizeCode, $forceType, $canonical);
        }
    }
}