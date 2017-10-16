<?php

namespace MinecraftAvatars\XF\Pub\Controller;

use MinecraftAvatars\MinecraftAvatarsHelper;
use MinecraftAvatars\MojangAPI;
use XF\Entity\User;

class Account extends XFCP_Account
{
    /**
     * Handling Minecraft username and checkbox "Use skin head as avatar"
     *
     * @param User $visitor
     * @return \XF\Mvc\FormAction
     */
    protected function accountDetailsSaveProcess(User $visitor)
    {
        $form = parent::accountDetailsSaveProcess($visitor);

        $input = $this->filter([
            'minecraftavatars' => [
                'username' => 'str',
                'use_head_as_avatar' => 'bool'
            ]
        ]);

        // ---> Cases that don't require to connect to Mojang API services --->

        /* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  */
        /* Detect changes */
        /* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  */
        if(
            $visitor->minecraftavatars_username === $input['minecraftavatars']['username']
            &&
            $visitor->minecraftavatars_use_skin === $input['minecraftavatars']['use_head_as_avatar']
        )
        {
            // No changes were made. Don't do anything!
            return $form;
        }

        /* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  */
        /* If checkbox is selected but username is not given */
        /* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  */
        if(
            !$input['minecraftavatars']['username']
            &&
            $input['minecraftavatars']['use_head_as_avatar']
        )
        {
            // This can't be. Someone is trying to hack the system!
            $form->logError('Trying to use Minecraft skin head as avatar without providing username!');
            return $form;
        }

        /* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  */
        /* If username is empty */
        /* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  */
        if(!$input['minecraftavatars']['username'])
        {
            // User wants to reset all his Minecraft-related fields to defaults and remove all avatars
            self::resetAllMinecraftRelated($visitor);
            return $form;
        }

        /* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  */
        /* If checkbox is unselected (was selected previously) and username is the same and not null */
        /* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  */
        if(
            $visitor->minecraftavatars_username
            &&
            ($visitor->minecraftavatars_username === $input['minecraftavatars']['username'])
            &&
            $visitor->minecraftavatars_use_skin
            &&
            !$input['minecraftavatars']['use_head_as_avatar']
        )
        {
            // Save new checkbox state
            $visitor->minecraftavatars_use_skin = $input['minecraftavatars']['use_head_as_avatar'];
            $visitor->save();

            // Remove all skins except of small avatar-thumb
            MinecraftAvatarsHelper::removeMinecraftAvatars($visitor, false);

            return $form;
        }

        // ---> Next goes cases where we do need to connect to Mojang API services --->

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
            $form->logError(\XF::phrase('minecraftavatars_api_status_red_error'));
            return $form;
        }

        /* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  */
        /* Checking if username is incorrect */
        /* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  */
        if(!($user_uuid = MojangAPI::getUuid($input['minecraftavatars']['username'])))
        {
            // Username is incorrect
            $form->logError(\XF::phrase('minecraftavatars_incorrect_username'));
            return $form;
        }

        /* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  */
        /* Checking if skin is available */
        /* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  */
        if(($user_skin = MojangAPI::getSkin($user_uuid)) === false)
        {
            // Skin is not available for now
            $form->logError(\XF::phrase('minecraftavatars_cant_access_skin'));
            return $form;
        }

        // ---> All checks are done and everything is OK --->

        self::resetAllMinecraftRelated($visitor);

        /* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  */
        /* Setting table fields */
        /* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  */
        $visitor->minecraftavatars_username =       $input['minecraftavatars']['username'];
        $visitor->minecraftavatars_uuid =           $user_uuid;
        $visitor->minecraftavatars_use_skin =       $input['minecraftavatars']['use_head_as_avatar'];
        $visitor->minecraftavatars_avatar_date =    time();
        $visitor->save();

        // ---> Fields are set. Working with avatars now --->

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

        return $form;
    }

    /**
     * Replacing all old avatars with new ones
     *
     * @return \XF\Mvc\Reply\Error|\XF\Mvc\Reply\Redirect
     */
    public function actionReloadSkinHead()
    {
        $visitor = \XF::visitor();

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
            return $this->error(\XF::phrase('minecraftavatars_api_status_red_error'));
        }

        /* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  */
        /* Checking if skin is available */
        /* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  */
        if(($user_skin = MojangAPI::getSkin($visitor->minecraftavatars_uuid)) === false)
        {
            // Skin is not available for now
            return $this->error(\XF::phrase('minecraftavatars_cant_access_skin'));
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

        return $this->redirect($this->buildLink('account/account-details'), \XF::phrase('minecraftavatars_account_reload_skin_success'));
    }

    /**
     * Reset values in columns to their defaults and removing all created avatars for that user
     *
     * @param User $user
     */
    public static function resetAllMinecraftRelated(User $user)
    {
        $user->minecraftavatars_username = '';
        $user->minecraftavatars_uuid =     '';
        $user->minecraftavatars_use_skin = false;
        $user->minecraftavatars_avatar_date = 0;
        $user->save();

        MinecraftAvatarsHelper::removeMinecraftAvatars($user);
    }
}