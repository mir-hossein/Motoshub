<?php

/**
 * @author Yaser Alimardany <yaser.alimardany@gmail.com>
 * @package ow_plugins.iisnews.controllers
 * @since 1.0
 */
class IISNEWS_MCTRL_Save extends OW_MobileActionController
{

    public function index( $params = array() )
    {
        if (OW::getRequest()->isAjax())
        {
            exit();
        }

        if ( !OW::getUser()->isAuthenticated() )
        {
            throw new AuthenticateException();
        }
        $plugin = OW::getPluginManager()->getPlugin('iisnews');
        OW::getNavigation()->activateMenuItem(OW_Navigation::MAIN, 'iisnews', 'main_menu_item');

        $this->setPageHeadingIconClass('ow_ic_write');
        $this->assign('backUrl', (OW::getRouter()->urlForRoute('iisnews')));
        OW::getDocument()->addStyleSheet(OW::getPluginManager()->getPlugin('iisnews')->getStaticCssUrl().'news.css');

        if (!OW::getUser()->isAuthorized('iisnews') && !OW::getUser()->isAuthorized('iisnews', 'add') && !OW::getUser()->isAdmin() )
        {
            $status = BOL_AuthorizationService::getInstance()->getActionStatus('iisnews', 'add_news');
            throw new AuthorizationException($status['msg']);
        }

        $this->assign('authMsg', null);

        $id = empty($params['id']) ? 0 : $params['id'];

        $service = EntryService::getInstance(); /* @var $service EntryService */

        $tagService = BOL_TagService::getInstance();

        if ( intval($id) > 0 )
        {
            $entry = $service->findById($id);

            if(!isset($entry)){
                throw new Redirect404Exception();
            }

            if ($entry->authorId != OW::getUser()->getId() && !OW::getUser()->isAuthorized('iisnews'))
            {
                throw new Redirect404Exception();
            }

            $eventParams = array(
                'action' => EntryService::PRIVACY_ACTION_VIEW_NEWS_POSTS,
                'ownerId' => $entry->authorId
            );

            $privacy = OW::getEventManager()->getInstance()->call('plugin.privacy.get_privacy', $eventParams);
            if (!empty($privacy))
            {
                $entry->setPrivacy($privacy);
            }
            $this->assign('enPublishDate', true);
        }
        else
        {
            $entry = new Entry();

            $eventParams = array(
                'action' => EntryService::PRIVACY_ACTION_VIEW_NEWS_POSTS,
                'ownerId' => OW::getUser()->getId()
            );

            $privacy = OW::getEventManager()->getInstance()->call('plugin.privacy.get_privacy', $eventParams);
            if (!empty($privacy))
            {
                $entry->setPrivacy($privacy);
            }

            $entry->setAuthorId(OW::getUser()->getId());
        }

        $form = new SaveForm($entry);
        if ($entry->getImage() )
        {
            $this->assign('imgsrc', $service->generateImageUrl($entry->getImage(), true));
        }
        if ( OW::getRequest()->isPost() && (!empty($_POST['command']) && in_array($_POST['command'], array('draft', 'publish')) ) && $form->isValid($_POST) )
        {
            $form->process($this);
            OW::getApplication()->redirect(OW::getRouter()->urlFor('IISNEWS_MCTRL_Save', 'index', array('id' => $entry->getId())));
        }

        $this->addForm($form);
        $this->assign('info', array('dto' => $entry));

        if (intval($id) > 0) {
            $this->setPageHeading(OW::getLanguage()->text('iisnews', 'edit_page_heading'));
            OW::getDocument()->setTitle(OW::getLanguage()->text('iisnews', 'meta_title_edit_news_entry'));
            OW::getDocument()->setDescription(OW::getLanguage()->text('iisnews', 'meta_description_edit_news_entry'));
        }
        else{
            $this->setPageHeading(OW::getLanguage()->text('iisnews', 'save_page_heading'));
            OW::getDocument()->setTitle(OW::getLanguage()->text('iisnews', 'meta_title_new_news_entry'));
            OW::getDocument()->setDescription(OW::getLanguage()->text('iisnews', 'meta_description_new_news_entry'));
        }

        OW::getEventManager()->trigger(new OW_Event(IISEventManager::ON_BEFORE_NEWS_VIEW_RENDER,
            array('newsId' => $entry->getId(), 'pageType' => 'edit')));

        $this->assign("urlForBack",OW::getRouter()->urlForRoute("iisnews"));
    }

    public function delete( $params )
    {
        if (OW::getRequest()->isAjax() || !OW::getUser()->isAuthenticated())
        {
            exit();
        }
        $pluginIisSecurity = BOL_PluginDao::getInstance()->findPluginByKey('iissecurityessentials');
        if(isset($pluginIisSecurity) && $pluginIisSecurity->isActive()) {
            $code = $params['code'];
            if(!isset($code)){
                throw new Redirect404Exception();
            }
            OW::getEventManager()->trigger(new OW_Event('iissecurityessentials.on.check.request.manager',
                array('senderId' => OW::getUser()->getId(), 'code'=>$code,'activityType'=>'delete_news')));
        }
        /*
          @var $service EntryService
         */
        $service = EntryService::getInstance();

        $id = $params['id'];

        $dto = $service->findById($id);

        if ( !empty($dto) )
        {
            if ($dto->authorId == OW::getUser()->getId() || OW::getUser()->isAuthorized('iisnews'))
            {
                OW::getEventManager()->trigger(new OW_Event(EntryService::EVENT_BEFORE_DELETE, array(
                    'entryId' => $id
                )));
                $service->delete($dto);
                OW::getEventManager()->trigger(new OW_Event(EntryService::EVENT_AFTER_DELETE, array(
                    'entryId' => $id
                )));
                OW::getEventManager()->trigger(new OW_Event('feed.delete_item', array('entityType' => 'news-entry', 'entityId' => $id)));
            }
        }

        OW::getEventManager()->call('notifications.remove', array(
            'entityType' => 'news-add_news',
            'entityId' => $id
        ));

        OW::getEventManager()->trigger(new OW_Event("feed.delete_item", array(
            'entityType' => 'news-entry',
            'entityId' => $id
        )));

        if ( !empty($_GET['back-to']) )
        {
            if(strpos( $_GET['back-to'], ":") === false ) {
                $this->redirect($_GET['back-to']);
            }
        }
        $author = BOL_UserService::getInstance()->findUserById($dto->authorId);
        $this->redirect(OW::getRouter()->urlForRoute('iisnews-default', array('user' => $author->getUsername())));
    }
}

class SaveForm extends Form
{
    /**
     *
     * @var Entry
     */
    private $entry;
    /**
     *
     * @var type EntryService
     */
    private $service;


    public function __construct( Entry $entry, $tags = array() )
    {
        parent::__construct('save');
        if( $entry->getTimestamp()!=null) {
            $currentYear = date('Y', time());
            if(OW::getConfig()->getValue('iisjalali', 'dateLocale')==1){
                $currentYear=$currentYear-1;
            }
            $publishDate = new DateField('publish_date');
            $publishDate->setMinYear($currentYear - 10);
            $publishDate->setMaxYear($currentYear + 10);
            $publishDate->setRequired();
            $publishDate->setLabel(OW::getLanguage()->text('iisnews', 'save_form_lbl_date'));
            $this->addElement($publishDate);
            $publishDate = date('Y', $entry->getTimestamp()) . '/' . date('n', $entry->getTimestamp()) . '/' . date('j', $entry->getTimestamp());
            $this->getElement('publish_date')->setValue($publishDate);

            $enPublishDate = new CheckboxField('enPublishDate');
            $enPublishDate->setLabel(OW::getLanguage()->text('iisnews', 'save_form_lbl_date_enable'));
            $enPublishDate->addAttribute("onclick", "initPublishDateField('.published_date');");
            $this->addElement($enPublishDate);
        }
        OW::getDocument()->addScript(OW::getPluginManager()->getPlugin('iisnews')->getStaticJsUrl().'iisnews.js');
        $language = OW::getLanguage();
        $enRoleList = new CheckboxField('enSentNotification');
        $enRoleList->setLabel($language->text('iisnews', 'notification_form_lbl_published'));
        $this->addElement($enRoleList);
        $this->service = EntryService::getInstance();

        $this->entry = $entry;

        $this->setMethod('post');

        $titleTextField = new TextField('title');

        $this->addElement($titleTextField->setLabel(OW::getLanguage()->text('iisnews', 'save_form_lbl_title'))->setValue($entry->getTitle())->setRequired(true));
        
        $entryTextArea = new MobileWysiwygTextarea('entry','iisnews');
        $entryTextArea->setLabel(OW::getLanguage()->text('iisnews', 'save_form_lbl_entry'));
        $stringRenderer = OW::getEventManager()->trigger(new OW_Event(IISEventManager::ON_AFTER_NEWSFEED_STATUS_STRING_READ,array('string' => $entry->getEntry())));
        if(isset($stringRenderer->getData()['string'])){
            $entry->setEntry($stringRenderer->getData()['string']);
        }
        $entryTextArea->setValue($entry->getEntry());
        $entryTextArea->setRequired(true);
        $this->addElement($entryTextArea);
        $imageField = new FileField('image');
        $imageField->setLabel($language->text('iisnews', 'add_form_image_label'));
        $this->addElement($imageField);

        $deleteImageField = new HiddenField('deleteImage');
        $deleteImageField->setId('deleteImage');
        $deleteImageField->setValue('false');
        $this->addElement($deleteImageField);


        $draftSubmit = new Submit('draft');
        $draftSubmit->addAttribute('onclick', "$('#save_entry_command').attr('value', 'draft');");

        if ( $entry->getId() != null && !$entry->isDraft() )
        {
            $text = OW::getLanguage()->text('iisnews', 'change_status_draft');
        }
        else
        {
            $text = OW::getLanguage()->text('iisnews', 'save_draft');
        }

        $this->addElement($draftSubmit->setValue($text));

        if ( $entry->getId() != null && !$entry->isDraft() )
        {
            $text = OW::getLanguage()->text('iisnews', 'update');
        }
        else
        {
            $text = OW::getLanguage()->text('iisnews', 'save_publish');
        }

        $publishSubmit = new Submit('publish');
        $publishSubmit->addAttribute('onclick', "$('#save_entry_command').attr('value', 'publish');");

        $this->addElement($publishSubmit->setValue($text));

        $tagService = BOL_TagService::getInstance();

        $tags = array();

        if ( intval($this->entry->getId()) > 0 )
        {
            $arr = $tagService->findEntityTags($this->entry->getId(), 'news-entry');

            foreach ( (!empty($arr) ? $arr : array() ) as $dto )
            {
                $tags[] = $dto->getLabel();
            }
        }

        $tf = new TagsInputField('tf');
        $tf->setLabel(OW::getLanguage()->text('iisnews', 'tags_field_label'));
        $tf->setValue($tags);

        $this->addElement($tf);
        $this->setEnctype(Form::ENCTYPE_MULTYPART_FORMDATA);
    }

    public function process( $ctrl )
    {
        OW::getCacheManager()->clean( array( EntryDao::CACHE_TAG_POST_COUNT ));

        $service = EntryService::getInstance(); /* @var $entryDao EntryService */

        $data = $this->getValues();




        $data['title'] = UTIL_HtmlTag::escapeHtml(UTIL_HtmlTag::stripTagsAndJs($data['title']));

        $entryIsNotPublished = $this->entry->getStatus() == 2;

        $text = UTIL_HtmlTag::sanitize($data['entry']);
        $stringRenderer = OW::getEventManager()->trigger(new OW_Event(IISEventManager::ON_BEFORE_NEWSFEED_STATUS_STRING_WRITE,array('string' => $text)));
        if(isset($stringRenderer->getData()['string'])){
            $text = $stringRenderer->getData()['string'];
        }
        /* @var $entry Entry */
        $this->entry->setTitle($data['title']);
        $this->entry->setEntry($text);
        $this->entry->setIsDraft($_POST['command'] == 'draft');
        if($_POST['deleteImage']==1)
        {
            if( !empty($this->entry->image) )
            {
                $storage = OW::getStorage();
                $storage->removeFile(EntryService::getInstance()->generateImagePath($entry->image));
                $storage->removeFile(EntryService::getInstance()->generateImagePath($entry->image, false));
                $this->entry->image=null;
            }
        }
        if ( !empty($_FILES['image']['name']) )
        {
            if ( (int) $_FILES['image']['error'] !== 0 || !is_uploaded_file($_FILES['image']['tmp_name']) || !UTIL_File::validateImage($_FILES['image']['name']) )
            {
                OW::getFeedback()->error(OW::getLanguage()->text('base', 'not_valid_image'));
                //$this->redirect();
                OW::getApplication()->redirect();
            }
            else
            {
                $this->entry->setImage(IISSecurityProvider::generateUniqueId());
                $this->service->saveNewsImage($_FILES['image']['tmp_name'],  $this->entry->getImage());

            }
        }
        $isCreate = empty($this->entry->id);
        if ( $isCreate )
        {
            $this->entry->setTimestamp(time());
            //Required to make #698 and #822 work together
            if ($_POST['command'] == 'draft')
            {
                $this->entry->setIsDraft(2);
            }

            BOL_AuthorizationService::getInstance()->trackAction('iisnews', 'add_news');
        }
        else
        {
            //If entry is not new and saved as draft, remove their item from newsfeed
            if ($_POST['command'] == 'draft')
            {
                OW::getEventManager()->trigger(new OW_Event('feed.delete_item', array('entityType' => 'news-entry', 'entityId' => $this->entry->id)));
            }
            if($data['enPublishDate']!=null && $data['enPublishDate']==true) {
                $dateArray = explode('/', $data['publish_date']);

                $timeStamp = mktime(date('h'), date('i'), date('s'), $dateArray[1], $dateArray[2], $dateArray[0]);

                $this->entry->setTimestamp($timeStamp);
            }
            else if($entryIsNotPublished)
            {
                // Update timestamp if entry was published for the first time
                $this->entry->setTimestamp(time());
            }

        }

        $service->save($this->entry);

        $tags = array();
        if ( intval($this->entry->getId()) > 0 )
        {
            $tags = $data['tf'];
            foreach ( $tags as $id => $tag )
            {
                $tags[$id] = UTIL_HtmlTag::stripTags($tag);
            }
        }
        $tagService = BOL_TagService::getInstance();
        $tagService->updateEntityTags($this->entry->getId(), 'news-entry', $tags );


        $service->save($this->entry);
        $eventForEnglishFieldSupport = new OW_Event('iismultilingualsupport.store.multilingual.data', array('entityId' => $this->entry->getId(),'entityType'=>'news'));
        OW::getEventManager()->trigger($eventForEnglishFieldSupport);
        if ($_POST['command'] != 'draft' && $data['enSentNotification']!=null && $data['enSentNotification']==true)
        {
            $userRoles = "";
            if(isset($data['userRoles'])){
                $userRoles = $data['userRoles'];
            }
            // trigger event comment add
            $event = new OW_Event('base_add_news', array(
                'entityType' => 'news-entry',
                'entityId' =>  $this->entry->getId(),
                'userId' => OW::getUser()->getId(),
                'roles' => $userRoles,
                'pluginKey' => 'iisnews'
            ));

            OW::getEventManager()->trigger($event);
        }


        $isCreate = empty($this->entry->id);
        if ($this->entry->isDraft())
        {
            $tagService->setEntityStatus('news-entry', $this->entry->getId(), false);

            if ($isCreate)
            {
                OW::getFeedback()->info(OW::getLanguage()->text('iisnews', 'create_draft_success_msg'));
            }
            else
            {
                OW::getFeedback()->info(OW::getLanguage()->text('iisnews', 'edit_draft_success_msg'));
            }
            $ctrl->redirect(OW::getRouter()->urlForRoute('news-manage-drafts'));
        }
        else
        {
            $tagService->setEntityStatus('news-entry', $this->entry->getId(), true);

            //Newsfeed
            $event = new OW_Event('feed.action', array(
                'pluginKey' => 'iisnews',
                'entityType' => 'news-entry',
                'entityId' => $this->entry->getId(),
                'userId' => $this->entry->getAuthorId(),
            ));
            OW::getEventManager()->trigger($event);

            if ($isCreate)
            {
                OW::getFeedback()->info(OW::getLanguage()->text('iisnews', 'create_success_msg'));

                OW::getEventManager()->trigger(new OW_Event(EntryService::EVENT_AFTER_ADD, array(
                    'entryId' => $this->entry->getId()
                )));
            }
            else
            {
                OW::getFeedback()->info(OW::getLanguage()->text('iisnews', 'edit_success_msg'));
                OW::getEventManager()->trigger(new OW_Event(EntryService::EVENT_AFTER_EDIT, array(
                    'entryId' => $this->entry->getId()
                )));
            }
            $news_entry = EntryService::getInstance()->findById($this->entry->id);

            if(!isset($news_entry)){
                throw new Redirect404Exception();
            }

            if( $news_entry->isDraft == EntryService::POST_STATUS_PUBLISHED )
            {
                BOL_AuthorizationService::getInstance()->trackActionForUser($news_entry->authorId, 'iisnews', 'add_news');
            }
            $ctrl->redirect(OW::getRouter()->urlForRoute('entry', array('id' => $this->entry->getId())));
        }
    }
}
