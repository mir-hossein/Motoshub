<?php

/**
 *
 * @author Yaser Alimardany <yaser.alimardany@gmail.com>
 * @package ow_plugins.newsfeed.classes
 * @since 1.0
 */
class IISQUESTIONS_CLASS_NewsfeedDriver extends NEWSFEED_CLASS_Driver
{
    private $listCount = 0;
    protected function findActionList($params)
    {
        $params['length'] = $params['displayCount'];
        $params['formats'] = null;
        $params['startTime'] = time();
        $params['displayType'] = 'action';
        $params['viewMore'] = 1;
        $params['checkMore'] = true;
        $this->params = $params;
        $actionList = array();
        switch ($params['feedType']) {
            case 'all':
                $actionList = $this->findAll(array($params['offset'] != 0 ? $params['offset'] + 1 : 0, $params['displayCount'], $params['checkMore']));
                break;
            case 'my':
                $actionList = $this->findMy(array($params['offset'] != 0 ? $params['offset'] + 1 : 0, $params['displayCount'], $params['checkMore']));
                break;
            case 'friend':
                $actionList = $this->findFriends(array($params['offset'] != 0 ? $params['offset'] + 1 : 0, $params['displayCount'], $params['checkMore']));
                break;
            case 'hottest':
                $actionList = $this->findHottest(array($params['offset'] != 0 ? $params['offset'] + 1 : 0, $params['displayCount'], $params['checkMore']));
                break;
        }
        return $actionList;
    }

    protected function findActionCount($params)
    {
        return $this->listCount;
    }

    protected function findActivityList($params, $actionIds)
    {
        return NEWSFEED_BOL_ActivityDao::getInstance()->findByActionIds($actionIds);
    }

    private function findAll($limit = null)
    {
        if (!empty($limit))
            list($list, $count) = IISQUESTIONS_BOL_Service::getInstance()->findAllQuestions(intval($limit[0]), intval($limit[1]));
        else
            list($list, $count) = IISQUESTIONS_BOL_Service::getInstance()->findAllQuestions();
        $idList = array();
        foreach ($list as $question) {
            $action = NEWSFEED_BOL_Service::getInstance()->findAction($question->entityType, $question->entityId);
            if (isset($action))
                $idList[] = $action->getId();
        }
        $this->listCount = $count;
        return $this->findOrderedListByIdList($idList);
    }

    private function findHottest($limit = null)
    {
        if (!empty($limit))
            list($list, $count) = IISQUESTIONS_BOL_Service::getInstance()->findHottestQuestions(intval($limit[0]), intval($limit[1]));
        else
            list($list, $count) = IISQUESTIONS_BOL_Service::getInstance()->findHottestQuestions();
        $idList = array();
        foreach ($list as $question) {
            $action = NEWSFEED_BOL_Service::getInstance()->findAction($question->entityType, $question->entityId);
            if (isset($action))
                $idList[] = $action->getId();
        }
        $this->listCount = $count;
        return $this->findOrderedListByIdList($idList);
    }

    private function findMy($limit = null)
    {
        if (!empty($limit))
            list($list, $count) = IISQUESTIONS_BOL_Service::getInstance()->findMyQuestions(intval($limit[0]), intval($limit[1]));
        else
            list($list, $count) = IISQUESTIONS_BOL_Service::getInstance()->findMyQuestions();
        $idList = array();
        foreach ($list as $question) {
            $action = NEWSFEED_BOL_Service::getInstance()->findAction($question->entityType, $question->entityId);
            if (isset($action))
                $idList[] = $action->getId();
        }
        $this->listCount = $count;
        return $this->findOrderedListByIdList($idList);
    }

    private function findFriends($limit = null)
    {
        if (!empty($limit))
            list($list, $count) = IISQUESTIONS_BOL_Service::getInstance()->findFriendQuestions(intval($limit[0]), intval($limit[1]));
        else
            list($list, $count) = IISQUESTIONS_BOL_Service::getInstance()->findFriendQuestions();
        $idList = array();
        foreach ($list as $question) {
            $action = NEWSFEED_BOL_Service::getInstance()->findAction($question->entityType, $question->entityId);
            if (isset($action))
                $idList[] = $action->getId();
        }
        $this->listCount = $count;
        return $this->findOrderedListByIdList($idList);
    }

    private function findOrderedListByIdList($idList)
    {
        if (empty($idList)) {
            return array();
        }

        $unsortedDtoList = NEWSFEED_BOL_ActionDao::getInstance()->findByIdList($idList);
        $unsortedList = array();
        foreach ($unsortedDtoList as $dto) {
            $unsortedList[$dto->id] = $dto;
        }

        $sortedList = array();
        foreach ($idList as $id) {
            if (!empty($unsortedList[$id])) {
                $sortedList[] = $unsortedList[$id];
            }
        }

        return $sortedList;
    }


}