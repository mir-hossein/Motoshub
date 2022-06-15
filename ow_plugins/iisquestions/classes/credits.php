<?php


/**
 * @author Milad Heshmati <milad.heshmati@gmail.com>
 * @package questions.classes
 */
class IISQUESTIONS_CLASS_Credits
{
    const ACTION_ANSWER = 'answer';
    const ACTION_ADD_ANSWER = 'add_answer';

    public $allActions = array();

    private $actions;
    private $authActions;

    public function __construct()
    {
        $this->actions[] = array('pluginKey' => 'iisquestions', 'action' => 'answer', 'amount' => 1);
        $this->actions[] = array('pluginKey' => 'iisquestions', 'action' => 'add_answer', 'amount' => 1);

        $this->allActions = array(
            self::ACTION_ANSWER,
            self::ACTION_ADD_ANSWER
        );
        
        $this->authActions[self::ACTION_ANSWER] = 'answer';
        $this->authActions[self::ACTION_ADD_ANSWER] = 'add_answer';
    }

    public function bindCreditActionsCollect( BASE_CLASS_EventCollector $e )
    {
        foreach ( $this->actions as $action )
        {
            $e->add($action);
        }
    }

    public function triggerCreditActionsAdd()
    {
        $e = new BASE_CLASS_EventCollector('usercredits.action_add');

        foreach ( $this->actions as $action )
        {
            $e->add($action);
        }

        OW::getEventManager()->trigger($e);
    }

    public function isAvaliable( $action )
    {
        if ( OW::getUser()->isAuthorized('iisquestions', $action) )
        {
            return true;
        }
        
        return $this->isPromoted($action);
    }

    public function isPromoted( $action )
    {
        $status = BOL_AuthorizationService::getInstance()->getActionStatus('iisquestions', $action);
        
        return $status['status'] == BOL_AuthorizationService::STATUS_PROMOTED;
    }

    public function getErrorMessage( $action )
    {
        $status = BOL_AuthorizationService::getInstance()->getActionStatus('iisquestions', $action);
        
        if ( $status['status'] == BOL_AuthorizationService::STATUS_PROMOTED )
        {
            return $status['msg'];
        }
        
        return null;
    }

    public function trackUse( $action )
    {
        BOL_AuthorizationService::getInstance()->trackAction('iisquestions', $action);
    }
    
    public function getActionKey( OW_Event $e )
    {
        $params = $e->getParams();
        $authAction = $params['actionName'];

        if ( $params['groupName'] != 'iisquestions' )
        {
            return;
        }

        if ( !empty($this->authActions[$authAction]) )
        {
            $e->setData($this->authActions[$authAction]);
        }
    }
}