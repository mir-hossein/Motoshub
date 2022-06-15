<?php
/**
 * Smarty function
 *
 * @param mixed var
 * @package OW_Smarty $smarty
 */

function smarty_function_user_link( $params, $smarty )
{
    $userService = BOL_UserService::getInstance();

    // default values for deleted / not found user
    $userUrl = $userService->getUserUrlForUsername('deleted-user');
    $userName = OW::getLanguage()->text('base', 'deleted_user');
    $realName = null;
    if(isset($params['showNames'])) {
        if (isset($params['id'])) {
            $user = $userService->findUserById($params['id']);

            if ($user) {
                $userName = $user->getUsername();
                $userUrl = $userService->getUserUrlForUsername($userName);
                $realName = BOL_QuestionService::getInstance()->getQuestionData(array($params['id']), array("realname"))['1'];
            }
        }
        else
        {
            if (isset($params['username'])) {
                $userName = trim($params['username']);
                $userUrl = $userService->getUserUrlForUsername(trim($params['username']));
                $userId = BOL_UserService::getInstance()->findByUsername($userName)->getId();
                $data = BOL_QuestionService::getInstance()->getQuestionData(array($userId), array("realname"));
                if(isset($data[$userId]['realname'])){
                    $realName = $data[$userId]['realname'];
                }
            }
        }
        if ($realName == null)
            $markup = "<a href=\"{$userUrl}\">{$userName}</a>";
        else {
            $string = OW::getLanguage()->text('base', 'questions_question_realname_label');
            $markup = "<a href=\"{$userUrl}\">{$userName}</a></br>" . $string . ": " . $realName;
        }
    }
    else
    {
        if ( isset($params['id']) )
        {
            $user = $userService->findUserById($params['id']);

            if ( $user )
            {
                $userUrl = $userService->getUserUrlForUsername($user->getUsername());
                $displayName = $userService->getDisplayName($user->getId());
            }
        }
        else
        {
            if ( isset($params['username']) )
            {
                $userUrl = $userService->getUserUrlForUsername(trim($params['username']));
            }

            $displayName = isset($params['name']) ? trim($params['name']) : (isset($params['username']) ? trim($params['username']) : '');
        }

        $markup = "<a href=\"{$userUrl}\">{$displayName}</a>";

    }
    return $markup;

    }