<?php

namespace MinecraftAvatars\XF\Pub\Controller;

use MinecraftAvatars\MinecraftAvatarsHelper;
use MinecraftAvatars\MojangAPI;

class Login extends XFCP_Login
{
    /**
     * Reloading skin when logging in
     *
     * @return \XF\Mvc\Reply\Error|\XF\Mvc\Reply\Redirect|\XF\Mvc\Reply\View
     */
    public function actionLogin()
    {
        $parent = parent::actionLogin();

        $visitor = \XF::visitor();
        if($visitor->minecraftavatars_username)
        {
            /* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  */
            /* Checking API state */
            /* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  */
            $status = MojangAPI::getStatus();
            if(
                $status['api.mojang.com'] === 'red'
                ||
                $status['sessionserver.mojang.com'] === 'red'
            )
            {
                // Having problems with Mojang API services
                return $parent;
            }

            /* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  */
            /* Checking if skin is available */
            /* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  */
            if(($user_skin = MojangAPI::getSkin($visitor->minecraftavatars_uuid)) === false)
            {
                // Skin is not available for now
                return $parent;
            }

            MinecraftAvatarsHelper::removeMinecraftAvatars($visitor);

            /* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  */
            /* Checking if user has custom skin */
            /* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  */
            if($user_skin !== null)
            {
                // User do have an unique skin
                if($visitor->minecraftavatars_use_skin) {
                    // User wants to use skin head as avatar so creating avatars for all sizes
                    MinecraftAvatarsHelper::createAvatarsAllSizes($visitor, $user_skin);
                } else {
                    // Creating only small avatar that will appear in "About" section in user profile
                    MinecraftAvatarsHelper::createAvatarFromSkin($visitor, $user_skin, 's');
                }
            }

            $visitor->minecraftavatars_avatar_date = time();
            $visitor->save();
        }

        return $parent;
    }
}