<?php

namespace MinecraftAvatars;

use XF\Entity\User;
use XF\Util\File;

/**
 * Useful functions for working with Minecraft avatars. Providing connection between xF and MojangAPI library
 *
 * @package MinecraftAvatars
 */
class MinecraftAvatarsHelper
{
    public static $AVATARS_PATH = "data/minecraft_avatars/";
    public static $AVATARS_PATH_REL = "minecraft_avatars/";

    /**
     * Creating avatar file of specified size in "data/minecraft_avatars" folder
     *
     * @param User $user
     * @param $skin
     * @param $sizeCode
     */
    public static function createAvatarFromSkin(User $user, $skin, $sizeCode)
    {
        $sizeMap = \XF::app()->container('avatarSizeMap');
        $group = floor($user->user_id / 1000);

        $avatar = MojangAPI::getPlayerHeadFromSkin($skin, $sizeMap[$sizeCode]);
        File::writeFile(self::$AVATARS_PATH . "{$sizeCode}/{$group}/{$user->user_id}.png", $avatar, false);
        unset($avatar);
    }

    /**
     * Creating avatars for all sizes in size map in "data/minecraft_avatars" folder
     *
     * @param User $user
     * @param $skin
     */
    public static function createAvatarsAllSizes(User $user, $skin)
    {
        $sizeMap = \XF::app()->container('avatarSizeMap');

        foreach($sizeMap as $sizeCode => $size)
        {
            self::createAvatarFromSkin($user, $skin, $sizeCode);
        }
    }

    /**
     * Removing all created Minecraft avatars for user. If "remove_all" is false then removing all avatars except
     * "s" avatar
     *
     * @param User $user
     * @param bool $remove_all
     */
    public static function removeMinecraftAvatars(User $user, $remove_all = true)
    {
        $sizeMap = \XF::app()->container('avatarSizeMap');

        if($remove_all !== true)
        {
            unset($sizeMap['s']);
        }

        $group = floor($user->user_id / 1000);

        foreach($sizeMap as $sizeCode => $size) {
            try {
                unlink("data/minecraft_avatars/{$sizeCode}/{$group}/{$user->user_id}.png");
            } catch (\ErrorException $e) {
                continue;
            }
        }
    }

    /**
     * Get Minecraft avatar url with specified size. If there is not such an avatar then it returns default avatar URL
     *
     * @param User $user
     * @param $sizeCode
     * @return string
     */
    public static function getAvatarURL(User $user, $sizeCode)
    {
        $group = floor($user->user_id / 1000);
        $path_to_avatar = self::$AVATARS_PATH_REL . "{$sizeCode}/{$group}/{$user->user_id}.png";

        if(file_exists('data/' . $path_to_avatar)) {
            return \XF::app()->applyExternalDataUrl($path_to_avatar . '?' . $user->minecraftavatars_avatar_date);
        } else {
            return self::getDefaultAvatarURL($sizeCode);
        }
    }

    /**
     * Get default (Steve) avatar url. Returning base64 encoded PNG image data
     *
     * @param $sizeCode
     * @return string
     */
    public static function getDefaultAvatarURL($sizeCode)
    {
        $sizeMap = \XF::app()->container('avatarSizeMap');
        return 'data:image/png;base64,' . base64_encode(MojangAPI::getSteveHead($sizeMap[$sizeCode]));
    }
}