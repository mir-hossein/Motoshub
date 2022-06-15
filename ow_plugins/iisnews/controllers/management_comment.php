<?php

/**
 * @author Yaser Alimardany <yaser.alimardany@gmail.com>
 * @package ow_plugins.iisnews.controllers
 * @since 1.0
 */
class IISNEWS_CTRL_ManagementComment extends OW_ActionController
{

    public function index()
    {

        if ( !OW::getUser()->isAuthenticated() )
        {
            throw new AuthenticateException();
        }

        $this->setPageHeading(OW::getLanguage()->text('iisnews', 'management_page_heading'));
        $this->setPageHeadingIconClass('ow_ic_write');

        $this->addComponent('menu', new IISNEWS_CMP_ManagementMenu());

        $service = EntryService::getInstance();

        $userId = OW::getUser()->getId();

        $page = empty($_GET['page']) ? 1 : $_GET['page'];

        $this->assign('thisUrl', OW_URL_HOME . OW::getRequest()->getRequestUri());

        $rpp = (int) OW::getConfig()->getValue('iisnews', 'results_per_page');

        $first = ($page - 1) * $rpp;
        $count = $rpp;

        $list = $service->findUserEntryCommentList($userId, $first, $count);
        $authorIdList = array();
        $entryList = array();
        foreach ( $list as $id => $item )
        {
            if ( !empty($info[$item['userId']]) )
            {
                continue;
            }

            $message=$item['message'];
            $decodedMessage=urldecode($message);
            $stringRenderer = OW::getEventManager()->trigger(new OW_Event('hashtag.before_render_string', array('string' => $decodedMessage)));
            if (isset($stringRenderer->getData()['string'])) {
                $decodedMessage = ($stringRenderer->getData()['string']);
            }
            $list[$id]['message']=$decodedMessage;

            $list[$id]['url'] = urldecode(OW::getRouter()->urlForRoute('user-entry', array('id'=>$item['entityId'])));
            $entryList[$item['entityId']] = $service->findById($item['entityId']);
            $authorIdList[] = $item['userId'];
        }

        $usernameList = array();
        $displayNameList = array();
        $avatarUrlList = array();

        if ( !empty($authorIdList) )
        {
            $userService = BOL_UserService::getInstance();

            $usernameList = $userService->getUserNamesForList($authorIdList);
            $displayNameList = $userService->getDisplayNamesForList($authorIdList);
            $avatarUrlList = BOL_AvatarService::getInstance()->getAvatarsUrlList($authorIdList);
        }

        $this->assign('entryList', $entryList);
        $this->assign('usernameList', $usernameList);
        $this->assign('displaynameList', $displayNameList);
        $this->assign('avatarUrlList', $avatarUrlList);

        $this->assign('list', $list);

        $itemCount = $service->countUserEntryComment($userId);

        $pageCount = ceil($itemCount / $rpp);

        $this->addComponent('paging', new BASE_CMP_Paging($page, $pageCount, 5));
    }

    public function deleteComment( $params )
    {

        if ( empty($params['id']) || intval($params['id']) <= 0 )
        {
            throw new InvalidArgumentException();
        }

        $id = (int) $params['id'];

        $isAuthorized = true; // TODO: Authorization needed

        if ( !$isAuthorized )
        {
            exit;
        }

        BOL_CommentService::getInstance()->deleteComment($id);

        OW::getFeedback()->info(OW::getLanguage()->text('iisnews', 'manage_page_comment_deleted_msg'));

        if ( !empty($_GET['back-to']) )
        {
            if(strpos( $_GET['back-to'], ":") === false ) {
                $this->redirect($_GET['back-to']);
            }
        }
        $this->redirect(OW::getRouter()->urlForRoute('iisnews-manage-comments'));
    }
}