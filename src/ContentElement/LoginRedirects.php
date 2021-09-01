<?php

/*
 * Copyright (c) 2021 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\LoginRedirectsBundle\ContentElement;

use Contao\BackendTemplate;
use Contao\ContentElement;
use Contao\FrontendUser;
use Contao\MemberGroupModel;
use Contao\MemberModel;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\System;

class LoginRedirects extends ContentElement
{
    /**
     * Template var.
     *
     * @var string
     */
    protected $strTemplate = 'ce_loginRedirects';

    /**
     * Backend.
     *
     * @return string
     */
    public function generate()
    {
        // If backendmode shows wildcard.
        if (TL_MODE === 'BE') {
            $arrRedirect = StringUtil::deserialize($this->lr_choose_redirect, true);

            $arrWildcard = [];
            $i = 0;

            $arrWildcard[] = '### LOGIN REDIRECTS ###';
            $arrWildcard[] = '<br /><br />';
            $arrWildcard[] = '<table>';
            $arrWildcard[] = '<colgroup>';
            $arrWildcard[] = '<col width="175" />';
            $arrWildcard[] = '<col width="400" />';
            $arrWildcard[] = '</colgroup>';

            if (\count($arrRedirect) > 0) {
                foreach ($arrRedirect as $key => $value) {
                    $arrWildcard[] = '<tr>';

                    $arrWildcard[] = '<td>';
                    $arrWildcard[] = ++$i.'. '.$this->lookUpName($value['lr_id']);
                    $arrWildcard[] = '</td>';

                    $arrPage = $this->lookUpPage($value['lr_redirecturl']);

                    $arrWildcard[] = '<td>';

                    if ('' != $arrPage['link']) {
                        $arrWildcard[] = '<a '.LINK_NEW_WINDOW.' href="'.$arrPage['link'].'">';
                        $arrWildcard[] = $arrPage['title'];
                        $arrWildcard[] = '</a>';
                    } else {
                        $arrWildcard[] = $arrPage['title'];
                    }
                    $arrWildcard[] = '</td>';

                    $arrWildcard[] = '</tr>';
                }
            } else {
                $arrWildcard[] = '<tr><td>'.$GLOBALS['TL_LANG']['tl_content']['lr_noentries'].'</td></tr>';
            }

            $arrWildcard[] = '</table>';

            $objTemplate = new BackendTemplate('be_wildcard');

            $objTemplate->wildcard = implode("\n", $arrWildcard);
            $objTemplate->title = $this->headline;
            $objTemplate->id = $this->id;
            $objTemplate->link = $this->name;

            return $objTemplate->parse();
        }

        return parent::generate();
    }

    /**
     * Frontend.
     */
    protected function compile()
    {
        $framework = System::getContainer()->get('contao.framework');
        $user = $framework->createInstance(FrontendUser::class);

        // Get settings
        $arrRedirect = StringUtil::deserialize($this->lr_choose_redirect, true);

        //return if the array is empty
        if (0 == \count($arrRedirect)) {
            return;
        }

        // Get usergroups
        $currentGroups = (\is_array($user->groups)) ? $user->groups : [];

        // Build group and members array
        foreach ($arrRedirect as $key => $value) {
            $redirect = false;
            $arrId = explode('::', $value['lr_id']);

            switch ($arrId[0]) {
                case 'G':
                    //redirect if the user is in the correct group
                    if (\in_array($arrId[1], $currentGroups)) {
                        $redirect = true;
                    }

                    break;

                case 'M':
                    //redirect if the FE-User id is found
                    if ($user->id == $arrId[1]) {
                        $redirect = true;
                    }

                    break;

                case 'allmembers':
                    //redirect if we have a valid FE-User
                    if ('' != $user->id) {
                        $redirect = true;
                    }

                    break;

                case 'guestsonly':
                    //skip loop if we have a user-id
                    if ('' == $user->id) {
                        $redirect = true;
                    }

                    break;

                case 'all':
                    //no test, just redirect:)
                    $redirect = true;

                    break;
            }

            if ($redirect) {
                $pageRedirect = $this->replaceInsertTags($value['lr_redirecturl']);

                if (null === $pageRedirect) {
                    System::getContainer()->get('monolog.logger.contao')->log('Try to redirect, but the necessary page cannot be found in the database.', __FUNCTION__.' | '.__CLASS__, TL_ERROR);

                    return;
                }
                /* @var PageModel $objPage */
                global $objPage;

                // Check if redirect target and current page are equal.
                if ($objPage->getFrontendUrl() !== $pageRedirect) {
                    $this->redirect($pageRedirect);
                }
            }
        }
    }

    /** ------------------------------------------------------------------------
     * Helper.
     */

    /**
     * Look up a member name or group name.
     *
     * @param string $id
     *
     * @return string
     */
    private function lookUpName($id)
    {
        $framework = System::getContainer()->get('contao.framework');

        switch ($id) {
            case 'all':
            case 'allmembers':
            case 'guestsonly':
                return $GLOBALS['TL_LANG']['tl_content']['lr_'.$id];

                break;

            default:
                $id = explode('::', $id);

                if ('M' == $id[0]) {
                    $id = $id[1];

                    $user = $framework->getAdapter(MemberModel::class)->findById($id, ['limit' => 1]);

                    if (null === $user) {
                        return $GLOBALS['TL_LANG']['ERR']['lr_unknownMember'];
                    }

                    if (0 != \strlen($user->firstname) && 0 != \strlen($user->lastname)) {
                        return $user->firstname.' '.$user->lastname;
                    }

                    return $user->username;
                } elseif ('G' == $id[0]) {
                    $id = $id = $id[1];

                    $group = $framework->getAdapter(MemberGroupModel::class)->findById($id, ['limit' => 1]);

                    if (null === $group) {
                        return $GLOBALS['TL_LANG']['ERR']['lr_unknownGroup'];
                    }

                    return $group->name;
                }

                break;
        }

        return $GLOBALS['TL_LANG']['ERR']['lr_unknownType'];
    }

    /**
     * Look up a page title.
     *
     * @param string $insertTag
     *
     * @return array
     */
    private function lookUpPage($insertTag)
    {
        $id = str_replace(['{{link_url::', '}}'], ['', ''], $insertTag);
        /** @var PageModel $page */
        $page = System::getContainer()->get('contao.framework')->getAdapter(PageModel::class)->findById($id);

        if (null === $page) {
            return [
                'title' => $GLOBALS['TL_LANG']['ERR']['lr_unknownPage'],
                'link' => '',
            ];
        }

        return [
            'title' => $page->title.((0 != \strlen($page->pageTitle)) ? ' - '.$page->pageTitle : ''),
            'link' => $page->getFrontendUrl(),
        ];
    }
}
