<?php

/**
 * EXHIBIT A. Common Public Attribution License Version 1.0
 * The contents of this file are subject to the Common Public Attribution License Version 1.0 (the “License”);
 * you may not use this file except in compliance with the License. You may obtain a copy of the License at
 * http://www.oxwall.org/license. The License is based on the Mozilla Public License Version 1.1
 * but Sections 14 and 15 have been added to cover use of software over a computer network and provide for
 * limited attribution for the Original Developer. In addition, Exhibit A has been modified to be consistent
 * with Exhibit B. Software distributed under the License is distributed on an “AS IS” basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License for the specific language
 * governing rights and limitations under the License. The Original Code is Oxwall software.
 * The Initial Developer of the Original Code is Oxwall Foundation (http://www.oxwall.org/foundation).
 * All portions of the code written by Oxwall Foundation are Copyright (c) 2011. All Rights Reserved.

 * EXHIBIT B. Attribution Information
 * Attribution Copyright Notice: Copyright 2011 Oxwall Foundation. All rights reserved.
 * Attribution Phrase (not exceeding 10 words): Powered by Oxwall community software
 * Attribution URL: http://www.oxwall.org/
 * Graphic Image as provided in the Covered Code.
 * Display of Attribution Information is required in Larger Works which are defined in the CPAL as a work
 * which combines Covered Code or portions thereof with code not governed by the terms of the CPAL.
 */

/**
 * @author Sardar Madumarov <madumarov@gmail.com>
 * @package ow.ow_system_plugins.base.components
 * @since 1.0
 */
class BASE_MCMP_CommentsList extends BASE_CMP_CommentsList
{
    /**
     * Constructor.
     *
     * @param string $entityType
     * @param integer $entityId
     * @param integer $page
     * @param string $displayType
     */
    public function __construct( BASE_CommentsParams $params, $id )
    {
        parent::__construct($params, $id);
        $this->setTemplate(OW::getPluginManager()->getPlugin('base')->getMobileCmpViewDir() . 'comments_list.html');
    }

    protected function init()
    {
        $commentList = $this->commentService->findCommentList($this->params->getEntityType(), $this->params->getEntityId(), null, $this->params->getCommentCountOnPage());
        $commentList = array_reverse($commentList);
        OW::getEventManager()->bind('base.comment_item_process', array($this, 'itemHandler'));
        $commentList = $this->processList($commentList);
        $this->assign('comments', $commentList);
        $countToLoad = $this->commentCount - $this->params->getCommentCountOnPage();
        $this->assign('countToLoad', $countToLoad);


        static $dataInit = false;

        if ( !$dataInit )
        {
            $staticDataArray = array(
                'respondUrl' => OW::getRouter()->urlFor('BASE_CTRL_Comments', 'getMobileCommentList'),
                'delUrl' => OW::getRouter()->urlFor('BASE_CTRL_Comments', 'deleteComment'),
                'delAtchUrl' => OW::getRouter()->urlFor('BASE_CTRL_Comments', 'deleteCommentAtatchment'),
                'delConfirmMsg' => OW::getLanguage()->text('base', 'comment_delete_confirm_message'),
            );
            OW::getDocument()->addOnloadScript("window.owCommentListCmps.staticData=" . json_encode($staticDataArray) . ";");
            $dataInit = true;
        }
        
        $jsParams = json_encode(
                array(
                    'totalCount' => $this->commentCount,
                    'contextId' => $this->cmpContextId,
                    'displayType' => $this->params->getDisplayType(),
                    'entityType' => $this->params->getEntityType(),
                    'entityId' => $this->params->getEntityId(),
                    'commentIds' => $this->commentIdList,
                    'pluginKey' => $this->params->getPluginKey(),
                    'ownerId' => $this->params->getOwnerId(),
                    'commentCountOnPage' => $this->params->getCommentCountOnPage(),
                    'actionArray' => $this->actionArr,
                    'cid' => $this->id,
                    'loadCount' => $this->commentService->getConfigValue(BOL_CommentService::CONFIG_MB_COMMENTS_COUNT_TO_LOAD)
                )
        );

        OW::getDocument()->addOnloadScript(
            "window.owCommentListCmps.items['$this->id'] = new OwMobileCommentsList($jsParams);
            window.owCommentListCmps.items['$this->id'].init();"
        );
    }

    public function itemHandler( BASE_CLASS_EventProcessCommentItem $e )
    {
        $deleteButton = false;
        $cAction = null;
        $value = $e->getItem();

        if ( $this->isOwnerAuthorized || $this->isModerator || (int) OW::getUser()->getId() === (int) $value->getUserId() )
        {
            $deleteButton = true;
        }

        $flagButton = $value->getUserId() != OW::getUser()->getId();

        if ( $this->isBaseModerator || $deleteButton || $flagButton )
        {
            $items = array();
            if ( $deleteButton )
            {
                $delId = 'del-' . $value->getId();
                array_unshift($items,  array(
                    'key' => 'udel',
                    'label' => OW::getLanguage()->text('base', 'contex_action_comment_delete_label'),
                    'order' => 1,
                    'class' => null,
                    'id' => $delId,
                    'attributes' => array(
                    ),
                ));
                $this->actionArr['comments'][$delId] = $value->getId();
            }

            if ( $flagButton )
            {
                array_unshift($items, array(
                    'key' => 'cflag',
                    'label' => OW::getLanguage()->text('base', 'flag'),
                    'order' => 2,
                    'class' => null,
                    'id' => $value->getId(),
                    'attributes' => array(
                        'onclick' => 'var d = $(this).data(); OW.flagContent(d.etype, d.eid);',
                        'data-etype' => 'comment',
                        'data-eid' =>$value->id

                    )
                ));
            }
        }
        $cAction = new BASE_MCMP_ContextAction($items);
        if ( $this->params->getCommentPreviewMaxCharCount() > 0 && mb_strlen($value->getMessage()) > $this->params->getCommentPreviewMaxCharCount() )
        {
            $e->setDataProp('previewMaxChar', $this->params->getCommentPreviewMaxCharCount());
        }

        $e->setDataProp('cnxAction', empty($cAction) ? '' : $cAction->render());
    }
}
