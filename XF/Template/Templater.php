<?php

namespace MinecraftAvatars\XF\Template;

use MinecraftAvatars\MinecraftAvatarsHelper;

class Templater extends XFCP_Templater
{
    /**
     * <xf:avatar.../> now displays Minecraft skin head as user avatar
     * If "minecraft_avatar" attribute is specified then showing small Minecraft skin head thumbnail
     *
     * @param $templater
     * @param $escape
     * @param $user
     * @param $size
     * @param bool $canonical
     * @param array $attributes
     * @return mixed|string
     */
    public function fnAvatar($templater, &$escape, $user, $size, $canonical = false, $attributes = [])
    {
        $html = parent::fnAvatar($templater, $escape, $user, $size, $canonical, $attributes);

        /* Showing Minecraft skin head instead of default avatar */
        if($attributes['minecraft_avatar'])
        {
            $html = '<img src="' . MinecraftAvatarsHelper::getAvatarUrl($user, $size) . '" class="avatar minecraft-avatar minecraft-head-thumb">';
            return $html;
        }

        /* Adding pixelated image-rendering CSS property to user avatars */
        if($user->minecraftavatars_use_skin)
        {
            $html = str_replace('class="', 'class="minecraft-avatar ', $html);
        }

        return $html;
    }
}