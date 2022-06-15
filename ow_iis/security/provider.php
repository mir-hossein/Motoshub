<?php

/**
 * User: Hamed Tahmooresi
 * Date: 1/3/2016
 * Time: 3:12 PM
 */
class IISSecurityProvider
{
    private static $classInstance;
    public static $prefixBackuplabel = 'iisbckp_';
    public static $prefixRemovedlabel = 'removed_';
    public static $removeTriggerNameBackupTable = 'riistrig_';
    public static $updateTriggerNameBackupTable = 'uiistrig_';
    public static $statusMessage;
    public static $aparatResourceName = 'aparat.com';
    public static $checkingLoadMorePeriod = 1;
    public static $updateActivityUserTimeThreshold = 120;
    public $COOKIE_SAVE_DAY = 365;

    /**
     * Returns an instance of class (singleton pattern implementation).
     *
     * @return OW_EventManager
     */
    public static function getInstance()
    {
        if (IISSecurityProvider::$classInstance === null) {
            IISSecurityProvider::$classInstance = new IISSecurityProvider();
        }

        return IISSecurityProvider::$classInstance;
    }

    public function set_php_ini_params()
    {
        if (session_id() != '') {
            return;
        }
        //disable transparent sid support
        ini_set('session.use_trans_sid', '0');
        ini_set('session.use_cookies', '1');
        ini_set('session.use_only_cookcheckies', '1');
        ini_set('session.cookie_httponly', '1');

        if (OW::getRequest()->isSsl()) {
            ini_set('session.cookie_secure', '1');
        }

        if (version_compare(PHP_VERSION, '5.3.0') >= 0) {
            ini_set('session.hash_function', 'sha512');
        } else {
            ini_set('session.hash_function', 1);
        }

        ini_set('session.hash_bits_per_character', 6);
        ini_set('session.entropy_length', 256);
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // This is a server using Windows!

        } else {
            // This is a server not using Windows!
            // As of PHP 5.4.0 session.entropy_file defaults to /dev/urandom or /dev/arandom if it is available. In PHP 5.3.0 this directive is left empty by default.
            ini_set('session.entropy_file', '/dev/urandom');
        }
    }


    public static function parse_size($size)
    {
        $unit = preg_replace('/[^bkmgtpezy]/i', '', $size); // Remove the non-unit characters from the size.
        $size = preg_replace('/[^0-9\.]/', '', $size); // Remove the non-numeric characters from the size.
        if ($unit) {
            // Find the position of the unit in the ordered string which is the power of magnitude to multiply a kilobyte by.
            return round($size * pow(1024, stripos('bkmgtpezy', $unit[0])));
        } else {
            return round($size);
        }
    }

    public static function beforeCheckUriRequest(OW_Event $event){
        if(isset($_SERVER['REQUEST_URI'])){
            $requestedUri = trim($_SERVER['REQUEST_URI'], '/');
            $requestedUri = trim($requestedUri);
            if($requestedUri == "base/ping/index" || strpos($requestedUri, "secure/files/") !== false || strpos($requestedUri, "iisajaxloader/myfeed/newly") !== false) {
                $event->setData(array('ignore' => true));
            }
        }
    }

    public static function onBeforeErrorRender(OW_Event $event)
    {
        $params = $event->getParams();
        if (isset($params['errorData'])) {
            $errorData = $params['errorData'];
            if (isset($errorData['message']) && strpos(strtoupper($errorData['message']), 'GENERAL ERROR') !== false) {
                if (isset($_SERVER['CONTENT_LENGTH'])) {
                    $CONTENT_LENGTH = $_SERVER['CONTENT_LENGTH'];
                    $post_max_size = IISSecurityProvider::parse_size(ini_get('post_max_size'));
                    if ($CONTENT_LENGTH >= $post_max_size) {
                        $errorData['message'] = OW::getLanguage()->text('base', 'upload_file_max_upload_filesize_error');
                    } else {
                        $errorData['message'] = OW::getLanguage()->text('base', 'upload_file_fail');
                    }
                    $event->setData(array('errorData' => $errorData));
                }
            }
        }
    }

    public static function setStatusMessage($statusMessage)
    {
        self::$statusMessage = $statusMessage;
    }

    public static function getStatusMessage()
    {
        return self::$statusMessage;
    }

    public function onBeforeAlterQueryExecuted(OW_Event $event)
    {
        $params = $event->getParams();
        if (isset($params['query'])) {
            $query = $params['query'];
            $queryParams = $params['params'];
            $query = trim(preg_replace('/\s\s+/', ' ', str_replace("\n", " ", $query)));
            if (strpos(strtoupper($query), 'ALTER TABLE') !== false) {
                $details = explode(' ', $query);
                $details = array_map('strtolower', $details);
                $tableName = $details[2];
                $tableName = str_replace('`', '', $tableName);
                if (strpos(strtoupper($query), 'DROP INDEX') !== false || strpos(strtoupper($query), 'ADD UNIQUE') !== false) {
                    return;
                }
                if(in_array(strtolower($details['4']), array("constraint", "unique", "key")) && in_array('add', array_map('strtolower', $details))){
                    return;
                }
                $table = OW::getDbo()->queryForRow('show tables like :tableName', array('tableName' => $tableName));
                if (!empty($table)) {
                    $backupTableName = self::getTableBackupName($tableName);
                    $backupTable = OW::getDbo()->queryForRow('show tables like :tableName', array('tableName' => $backupTableName));
                    if (!empty($backupTable)) {
                        $details[2] = '`' . $backupTableName . '`';
                        $query = implode(' ', $details);
                        if(strtoupper($details['3'])=="ADD" && !in_array(strtolower($details['4']), array("index", "key", "constraint", "unique", "spatial", "foreign key", "fulltext"))){
                            $columns = OW::getDbo()->queryForList("describe ".$tableName);
                            $lastColumn = $columns[sizeof($columns)-1]['Field'];
                            //check semicolon
                            $hasSemiColon = false;
                            if(substr($query, -1) == ";"){
                                $hasSemiColon = true;
                                $query = substr($query, 0, sizeof($query)-2);
                            }
                            $query .= " After `".$lastColumn."`";
                            if($hasSemiColon){
                                $query .= ";";
                            }
                        }
                        OW::getDbo()->query($query, $queryParams);
                    }
                }
            }
        }
    }

    public function onAfterQueryExecuted(OW_Event $event)
    {
        $installComplete = false;
        try
        {
            $installComplete = (bool) OW::getConfig()->getValue('base', 'install_complete');
        }
        catch ( Exception $e )
        {
            $installComplete = false;
        }

        $params = $event->getParams();
        if (isset($params['query'])) {
            $query = $params['query'];
            $query = trim(preg_replace('/\s\s+/', ' ', str_replace("\n", " ", $query)));
            if (strpos(strtoupper($query), 'INSERT') !== false ||
                strpos(strtoupper($query), 'DELETE') !== false ||
                strpos(strtoupper($query), 'REPLACE') !== false ||
                strpos(strtoupper($query), 'UPDATE') !== false) {
                $details = explode(' ', $query);
                $tableName = str_replace('`', '', $details[1]);
                $tableName = str_replace(OW_DB_PREFIX, '', $tableName);
                $isCliScript = php_sapi_name() === 'cli';
                 if ( $installComplete && !$isCliScript && OW::getUser() != null && OW::getUser()->isAuthenticated() && OW::getUser()->getUserObject() != null){
                    $lastActivityUpdate = OW::getUser()->getUserObject()->activityStamp;
                    $ignoreTables = $tableName == 'base_user_online' || ($tableName == 'base_user' && strpos($query, 'activityStamp') !== false);
                    if(!$ignoreTables && $lastActivityUpdate < time() - self::$updateActivityUserTimeThreshold){
                     try{
                         BOL_UserService::getInstance()->updateActivityStamp(OW::getUser()->getId(), OW::getApplication()->getContext());
                     } catch (Exception $ex) {

                     }
                    }
                }
            }
        }
    }

    /*
     * Doing backup from all tables to save all removed or updated data.
     * All table schema copied in anohter table with name of *_iisbackup (* = Name of table needed backup) and create trigger for each tables for saving all removed data.
     */
    public static function createBackupTables(OW_Event $event)
    {
        if(defined('BACKUP_TABLES_USING_TRIGGER') && BACKUP_TABLES_USING_TRIGGER == false){
            return;
        }
        set_time_limit(600);

        //Filter on tables we do not want to backup.
        $tablesDontNeedBackup = array(OW_DB_PREFIX . 'base_user_online',
            'phinxlog',
            OW_DB_PREFIX . 'base_language_value',
            OW_DB_PREFIX . 'base_language_prefix',
            OW_DB_PREFIX . 'base_language_key',
            OW_DB_PREFIX . 'base_language',
            OW_DB_PREFIX . 'mailbox_user_last_data',
            OW_DB_PREFIX . 'base_cron_job',
            OW_DB_PREFIX . 'base_config',
            OW_DB_PREFIX . 'base_site_statistic',
            OW_DB_PREFIX . 'base_theme',
            OW_DB_PREFIX . 'base_geolocationdata_ipv4',
            OW_DB_PREFIX . 'notifications_send_queue',
            OW_DB_PREFIX . 'newsfeed_action_set',
            OW_DB_PREFIX . 'iisblockingip_block_ip',
            OW_DB_PREFIX . 'notifications_notification',
            OW_DB_PREFIX . 'base_mail',
            OW_DB_PREFIX . 'base_theme_control',
            OW_DB_PREFIX . 'base_theme_content',
            OW_DB_PREFIX . 'base_login_cookie',
            OW_DB_PREFIX . 'base_component_place_cache',
            OW_DB_PREFIX . 'iisgraph_graph',
            OW_DB_PREFIX . 'iisgraph_node',
            OW_DB_PREFIX . 'iisgraph_group',
            OW_DB_PREFIX . 'iismobilesupport_notifications',
            OW_DB_PREFIX . 'iissecurityessentials_request_manager',
            OW_DB_PREFIX . 'iishashtag_tag',
            OW_DB_PREFIX . 'base_db_cache',
        );
        $tablesWithCustomTriggerForUpdate = array(OW_DB_PREFIX . 'base_user');
        //Get all tables
        $queryGetAllTables = 'select * from information_schema.tables WHERE TABLE_SCHEMA = \'' . OW_DB_NAME . '\'';
        $allTables = OW::getDbo()->queryForList($queryGetAllTables);

        foreach ($allTables as $table) {

            //Postfix of backup table name
            $prefixBackuplabel = self::$prefixBackuplabel;

            $prefixRemovedTable = self::$prefixRemovedlabel;

            //Main table name
            $tableName = $table['TABLE_NAME'];

            //Table name for backup updated or removed data
            $backupTableName = self::getTableBackupName($tableName);

            //Trigger name for removed data
            $removeTriggerName = self::$removeTriggerNameBackupTable . $tableName;

            //Trigger name for updated data
            $updateTriggerName = self::$updateTriggerNameBackupTable . $tableName;

            //Check filtering list. Also we do not create a backup table from table that is backup.
            if (!(strpos($tableName, $prefixBackuplabel) === 0) && !(strpos($tableName, $prefixRemovedTable) === 0) && !in_array($tableName, $tablesDontNeedBackup)) {

                //Check backup table exists or not
                $backupTable = OW::getDbo()->queryForRow('show tables like :tableName', array('tableName' => $backupTableName));
                if (empty($backupTable)) {

                    //Create backup table like targer table
                    $queryCopyTableForCreatingBackupTable = 'CREATE TABLE ' . $backupTableName . ' LIKE ' . $tableName;
                    OW::getDbo()->query($queryCopyTableForCreatingBackupTable);

                    //Modify id column for dropping
                    $queryModifyPrimaryKeyForCreatingBackupTable = 'ALTER TABLE ' . $backupTableName . ' MODIFY id INT NOT NULL';
                    OW::getDbo()->query($queryModifyPrimaryKeyForCreatingBackupTable);

                    //If table has primary key, drop it.
                    $hasTablePrimaryKey = OW::getDbo()->queryForRow('SHOW INDEXES FROM ' . $backupTableName . ' WHERE Key_name = \'PRIMARY\'');
                    if (!empty($hasTablePrimaryKey)) {
                        //Drop primary key
                        $queryDropContraintOnPrimaryKeyForCreatingBackupTable = 'ALTER TABLE ' . $backupTableName . ' DROP PRIMARY KEY';
                        OW::getDbo()->query($queryDropContraintOnPrimaryKeyForCreatingBackupTable);
                    }

                    //Add backup_timestamp column
                    $queryAddTimestampColumnForCreatingBackupTable = 'ALTER TABLE ' . $backupTableName . ' ADD backup_timestamp INT(11)';
                    OW::getDbo()->query($queryAddTimestampColumnForCreatingBackupTable);

                    //Add backup_action column
                    $queryAddActionColumnForCreatingBackupTable = 'ALTER TABLE ' . $backupTableName . ' ADD backup_action varchar(2)';
                    OW::getDbo()->query($queryAddActionColumnForCreatingBackupTable);

                    //Add backup_id column
                    $queryAddIdColumnForCreatingBackupTable = 'ALTER TABLE ' . $backupTableName . ' ADD backup_pk_id INT PRIMARY KEY AUTO_INCREMENT';
                    OW::getDbo()->query($queryAddIdColumnForCreatingBackupTable);

                    //If table has unique key, drop it.
                    $listOfRemovedKey = array();
                    $hasTableUniqueKey = OW::getDbo()->queryForList('show indexes from ' . $backupTableName . ' WHERE Key_name != \'PRIMARY\'');
                    if (!empty($hasTableUniqueKey)) {
                        foreach ($hasTableUniqueKey as $row_index) {
                            $keyName = $row_index['Key_name'];
                            if (!in_array($keyName, $listOfRemovedKey)) {
                                $listOfRemovedKey[] = $keyName;
                                $queryDropContraintOnUniqueKeyForCreatingBackupTable = 'DROP index `' . $keyName . '` on ' . $backupTableName;
                                OW::getDbo()->query($queryDropContraintOnUniqueKeyForCreatingBackupTable);
                            }
                        }
                    }

                    //Add trigger for removed data
                    $triggerQueryForDoinBackupBeforeRemove = 'DROP TRIGGER IF EXISTS `' . $removeTriggerName . '`; CREATE TRIGGER `' . $removeTriggerName . '` Before DELETE ON `' . $tableName . '` FOR EACH ROW  BEGIN INSERT INTO `' . $backupTableName . '` (select tbl.*, UNIX_TIMESTAMP(NOW()) as backup_timestamp, \'r\' as backup_action, NULL as backup_pk_id from `' . $tableName . '` tbl where tbl.id = OLD.id); END;';
                    OW::getDbo()->query($triggerQueryForDoinBackupBeforeRemove);

                    if (!in_array($tableName, $tablesWithCustomTriggerForUpdate)) {
                        //Add trigger for updated data
                        $triggerQueryForDoinBackupBeforUpdate = 'DROP TRIGGER IF EXISTS `' . $updateTriggerName . '`; CREATE TRIGGER `' . $updateTriggerName . '` Before UPDATE ON `' . $tableName . '` FOR EACH ROW  BEGIN INSERT INTO `' . $backupTableName . '` (select tbl.*, UNIX_TIMESTAMP(NOW()) as backup_timestamp, \'u\' as backup_action, NULL as backup_pk_id from `' . $tableName . '` tbl where tbl.id = OLD.id); END;';
                        OW::getDbo()->query($triggerQueryForDoinBackupBeforUpdate);
                    }
                }
            }
        }

        foreach ($tablesDontNeedBackup as $tableDontNeedBackup) {
            $dropTableDontNeedBackupQuery = 'DROP TABLE IF EXISTS ' . self::getTableBackupName($tableDontNeedBackup);
            @OW::getDbo()->query($dropTableDontNeedBackupQuery);

            $dropRemoveTriggerOfTableDontNeedBackupQuery = 'DROP TRIGGER IF EXISTS `' . self::$removeTriggerNameBackupTable . $tableDontNeedBackup.'`';
            @OW::getDbo()->query($dropRemoveTriggerOfTableDontNeedBackupQuery);

            $dropUpdateTriggerOfTableDontNeedBackupQuery = 'DROP TRIGGER IF EXISTS `' . self::$updateTriggerNameBackupTable . $tableDontNeedBackup.'`';
            @OW::getDbo()->query($dropUpdateTriggerOfTableDontNeedBackupQuery);
        }

        //Custom trigger for base_user
        //check if trigger dosesnt exist
        //create (update and remove -> new data arrive + any colmun changed except activity_timestamp)

        $baseUserTrigger = OW::getDbo()->queryForRow('show tables like :tableName', array('tableName' => OW_DB_PREFIX.'base_user_uiistrig'));
        if (empty($baseUserTrigger)) {
            $updateTriggerName = OW_DB_PREFIX.'base_user_uiistrig';
            $tableName = OW_DB_PREFIX.'base_user';
            $backupTableName = 'iisbckp_'.OW_DB_PREFIX.'base_user';
            //Add trigger for updated data
            $triggerQueryForDoinBackupBeforeUpdate = 'DROP TRIGGER IF EXISTS `' . $updateTriggerName . '`; CREATE TRIGGER `' . $updateTriggerName . '` Before UPDATE ON `' . $tableName . '` FOR EACH ROW  BEGIN  IF NEW.email <> OLD.email  OR NEW.password <> OLD.password OR NEW.accountType <> OLD.accountType OR NEW.username <> OLD.username THEN  INSERT INTO `' . $backupTableName . '` (select tbl.*, UNIX_TIMESTAMP(NOW()) as backup_timestamp, \'u\' as backup_action, NULL as backup_pk_id from `' . $tableName . '` tbl where tbl.id = OLD.id); END IF; END;';
            OW::getDbo()->query($triggerQueryForDoinBackupBeforeUpdate);
        }
    }


    /*
     * Remove data backup using timestamp.
     */
    public static function deleteBackupData(OW_Event $event)
    {
        $params = $event->getParams();
        if (isset($params['timestamp'])) {
            $timestampDeadline = $params['timestamp'];
            $timestamp = time() - $timestampDeadline;

            //Get all tables
            $queryGetAllTables = 'select * from information_schema.tables WHERE TABLE_SCHEMA = \'' . OW_DB_NAME . '\'';
            $allTables = OW::getDbo()->queryForList($queryGetAllTables);

            //Postfix of backup table name
            $prefixBackuplabel = self::$prefixBackuplabel;

            foreach ($allTables as $table) {
                //Main table name
                $tableName = $table['TABLE_NAME'];

                //Table name for backup updated or removed data
                $backupTableName = self::getTableBackupName($tableName);

                //Check backup table exists or not
                $backupTable = OW::getDbo()->queryForRow('show tables like :tableName', array('tableName' => $backupTableName));
                if (!empty($backupTable)) {
                    if($tableName == BOL_AttachmentDao::getInstance()->getTableName()){
                        $queryForGetOldFiles = 'select * from ' . $backupTableName . ' where backup_action=\'r\' and backup_timestamp <' . $timestamp;
                        $attachList = OW::getDbo()->queryForObjectList($queryForGetOldFiles, BOL_AttachmentDao::getInstance()->getDtoClassName());
                        /* @var $item BOL_Attachment */
                        foreach ( $attachList as $item )
                        {
                            $filePath = BOL_AttachmentService::getInstance()->getAttachmentsDir() . $item->getFileName();
                            if ( OW::getStorage()->fileExists($filePath) )
                            {
                                OW::getStorage()->removeFile($filePath);
                            }
                        }
                    }
                    $forumPlugin = BOL_PluginDao::getInstance()->findPluginByKey('forum');
                    if(isset($forumPlugin) && $forumPlugin->isActive()) {
                        if ($tableName == FORUM_BOL_PostAttachmentDao::getInstance()->getTableName()) {
                            $queryForGetOldFiles = 'select * from ' . $backupTableName . ' where backup_action=\'r\' and backup_timestamp <' . $timestamp;
                            $attachList = OW::getDbo()->queryForObjectList($queryForGetOldFiles, FORUM_BOL_PostAttachmentDao::getInstance()->getDtoClassName());
                            /* @var $item FORUM_BOL_PostAttachment */
                            foreach ($attachList as $item) {
                                $ext = UTIL_File::getExtension($item->fileName);
                                $path = FORUM_BOL_PostAttachmentService::getInstance()->getAttachmentFilePath($item->getId(), $item->hash, $ext, $item->fileNameClean);
                                if (OW::getStorage()->fileExists($path)) {
                                    OW::getStorage()->removeFile($path);
                                }
                            }
                        }
                    }
                    $mailboxPlugin = BOL_PluginDao::getInstance()->findPluginByKey('mailbox');
                    if(isset($mailboxPlugin) && $mailboxPlugin->isActive()) {
                        if ($tableName == MAILBOX_BOL_AttachmentDao::getInstance()->getTableName()) {
                            $queryForGetOldFiles = 'select * from ' . $backupTableName . ' where backup_action=\'r\' and backup_timestamp <' . $timestamp;
                            $attachList = OW::getDbo()->queryForObjectList($queryForGetOldFiles, MAILBOX_BOL_AttachmentDao::getInstance()->getDtoClassName());
                            /* @var $item MAILBOX_BOL_Attachment */
                            foreach ($attachList as $item) {
                                $ext = UTIL_File::getExtension($item->fileName);
                                $attachmentPath = MAILBOX_BOL_ConversationService::getInstance()->getAttachmentFilePath($item->id, $item->hash, $ext, $item->fileName);
                                if (OW::getStorage()->fileExists($attachmentPath)) {
                                    OW::getStorage()->removeFile($attachmentPath);
                                }
                            }
                        }
                    }
                    $queryForRemovingOldData = 'delete from ' . $backupTableName . ' where backup_timestamp <' . $timestamp;
                    OW::getDbo()->query($queryForRemovingOldData);
                }
            }
        }
    }

    public static function setInitialData()
    {
        //set aparat resources for video embedding
        self::setAparatToResource();

        //add new admin menus, like logger settings
        self::addNewAdminMenus();

        //remove some questions from registration process
        self::removeUserProfileQuestions();

        //Delete social network static page
        self::deleteExternalStaticPage(4);

        //Delete static mobile page
        self::deleteStaticPages(477);

        //Delete static finance page
        self::deleteStaticPages(340);

        //Delete terms of services static page
        self::deleteStaticPages(411);

        //Delete privacy static page
        self::deleteStaticPages(468);

        //Remove default widget in mobile
        self::deleteWidgetUsingComponentPlaceUniqueName("admin-5295f2e03ec8a");

        //Remove default widget in mobile
        self::deleteWidgetUsingComponentPlaceUniqueName("admin-5295f2e40db5c");

        //Delete mobile page
//        self::deleteStaticPages(479);

        //Delete all mobile configuration
//        self::deleteMobileConfiguration();

        //set default age range to join
        self::setBirthDateInitialRange();

        //set default age range to join
        self::sanitizeValidExtList();
    }

    /***
     * Delete widget by key
     * @param $widgetKey
     */
    public static function deleteWidget($widgetKey)
    {
        BOL_ComponentAdminService::getInstance()->deleteWidget($widgetKey);
    }


    /***
     * Delete widget using componentPlaceUniqueName
     * @param $componentPlaceUniqueName
     */
    public static function deleteWidgetUsingComponentPlaceUniqueName($componentPlaceUniqueName)
    {
        BOL_ComponentAdminService::getInstance()->deletePlaceComponent($componentPlaceUniqueName);
    }

    public static function setBirthDateInitialRange()
    {
        $qBdate = BOL_QuestionService::getInstance()->findQuestionByName('birthdate');
        if ($qBdate != null) {
            $minYear = (int)date("Y") - 7;
            $maxYear = (int)date("Y") - 75;
            $qBdate->custom = '{"year_range":{"from":' . $maxYear . ',"to":' . $minYear . '}}';
            BOL_QuestionService::getInstance()->saveOrUpdateQuestion($qBdate);
        }
    }

//    public static function deleteMobileConfiguration(){
//        OW::getConfig()->saveConfig('base', 'disable_mobile_context', 1);
//
//        OW::getNavigation()->deleteMenuItem('mobile', 'mobile_admin_navigation');
//        OW::getNavigation()->deleteMenuItem('mobile', 'mobile_admin_pages_index');
//        OW::getNavigation()->deleteMenuItem('mobile', 'mobile_admin_pages_dashboard');
//        OW::getNavigation()->deleteMenuItem('mobile', 'mobile_admin_settings');
//        OW::getNavigation()->deleteMenuItem('mobile', 'mobile_pages_dashboard');
//    }

    public static function deleteExternalStaticPage($id)
    {
        if (empty($id)) {
            return;
        }
        $menu = BOL_NavigationService::getInstance()->findMenuItemById($id);
        if ($menu != null) {
            $service = BOL_NavigationService::getInstance();

            $languageService = BOL_LanguageService::getInstance();

            $langKey = $languageService->findKey($menu->getPrefix(), $menu->getKey());

            if (!empty($langKey)) {
                $list = $languageService->findAll();

                foreach ($list as $dto) {
                    $langValue = $languageService->findValue($dto->getId(), $langKey->getId());

                    if (empty($langValue)) {
                        continue;
                    }

                    $languageService->deleteValue($langValue);
                }

                $languageService->deleteKey($langKey->getId());
            }

            $service->deleteMenuItem($menu);
        }
    }

    public static function deleteStaticPages($id)
    {
        if (empty($id)) {
            return;
        }
        $menu = BOL_NavigationService::getInstance()->findMenuItemById($id);

        if ($menu != null) {

            $navigationService = BOL_NavigationService::getInstance();
            $navigationService->deleteMenuItem($menu);

            if (!empty($menu->getDocumentKey())) {
                $document = $navigationService->findDocumentByKey($menu->getDocumentKey());

                $navigationService->deleteDocument($document);

                $languageService = BOL_LanguageService::getInstance();


                $langKey = $languageService->findKey($menu->getPrefix(), $menu->getKey());
                $languageService->deleteKey($langKey->getId());

                $langKey = $languageService->findKey('base', 'local_page_meta_tags_' . $document->getKey());
                if ($langKey !== null) {
                    $languageService->deleteKey($langKey->getId());
                }

                $langKey = $languageService->findKey('base', 'local_page_title_' . $document->getKey());
                if ($langKey !== null) {
                    $languageService->deleteKey($langKey->getId());
                }

                $langKey = $languageService->findKey('base', 'local_page_content_' . $document->getKey());
                if ($langKey !== null) {
                    $languageService->deleteKey($langKey->getId());
                }
            }
        }
    }

    public static function removeUserProfileQuestions()
    {
        $questionsIdList = array(111, 112);
        BOL_QuestionService::getInstance()->deleteQuestion($questionsIdList);
    }

    public static function setAparatToResource()
    {
        $resources = BOL_TextFormatService::getInstance()->getMediaResourceList();
        $findAparatResource = false;
        foreach ($resources as $resource) {
            if (strpos($resource, self::$aparatResourceName) === 0 || strpos($resource, self::$aparatResourceName) > 0) {
                $findAparatResource = true;
            }
        }
        if (!$findAparatResource) {
            $resources[] = self::$aparatResourceName;
            OW::getConfig()->saveConfig('base', BOL_TextFormatService::CONF_MEDIA_RESOURCE_LIST, json_encode($resources));
        }
    }

    public static function addNewAdminMenus()
    {
        try {
            $dto = new BOL_MenuItem();
            $dto->setType(BOL_NavigationService::MENU_TYPE_SETTINGS)
                ->setPrefix('admin')
                ->setKey('sidebar_menu_item_log')
                ->setRoutePath('admin_settings_log')
                ->setOrder(5)
                ->setVisibleFor(3);
            BOL_NavigationService::getInstance()->saveMenuItem($dto);
        }catch (Exception $ex){}
    }

    public function installComplete()
    {
        //Update default theme
        self::updateDefaultTheme();

        //check persian language exist
        self::checkPersianLanguageExist();

        //remove Russian language
        self::removeRussianLanguage();

        //set initial data
        self::setInitialData();

        //Update all plugins languages
        self::updateLanguages(true, true, true);

        //alter some table columns (add index key)
        self::alterToIndexedColumns();

        //alter default tables for customizing
        self::updateDefaultTables();
    }

    public static function updateDefaultTables(){
        /*
         * we don't need backup of base_login_cookie table
         */
        $dropTableDontNeedBackupQuery = 'DROP TABLE IF EXISTS ' . self::getTableBackupName(OW_DB_PREFIX . 'base_login_cookie');
        OW::getDbo()->query($dropTableDontNeedBackupQuery);

        $dropRemoveTriggerOfTableDontNeedBackupQuery = 'DROP TRIGGER IF EXISTS `' . self::$removeTriggerNameBackupTable . OW_DB_PREFIX . 'base_login_cookie`';
        OW::getDbo()->query($dropRemoveTriggerOfTableDontNeedBackupQuery);

        $dropUpdateTriggerOfTableDontNeedBackupQuery = 'DROP TRIGGER IF EXISTS `' . self::$updateTriggerNameBackupTable . OW_DB_PREFIX . 'base_login_cookie`';
        OW::getDbo()->query($dropUpdateTriggerOfTableDontNeedBackupQuery);

        $checkColumnExist = "SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = '".OW_DB_NAME."' AND TABLE_NAME = '".OW_DB_PREFIX."base_login_cookie' AND COLUMN_NAME = 'timestamp'";
        $checkColumnExistResult = OW::getDbo()->queryForRow($checkColumnExist);
        if(is_array($checkColumnExistResult) && !empty($checkColumnExistResult)){
            return;
        }

        $query = "ALTER TABLE `".OW_DB_PREFIX."base_login_cookie` ADD `timestamp` int(11) NOT NULL default '".time()."';";
        OW::getDbo()->query($query);
    }


    /*
 * ALTER
 * ow_base_user_suspend.userId
 * ow_newsfeed_action_feed.activityId
 * ow_newsfeed_action_feed.feedId
 * ow_newsfeed_action_feed.feedType
 * ow_newsfeed_follow.feedId
 * ow_newsfeed_follow.feedTyp
 * ow_newsfeed_activity.privacy
 * ow_newsfeed_activity.visibility
 * ow_newsfeed_activity.status
 * COLUMNS TO INDEXED COLUMNS
 */
    public static function alterToIndexedColumns()
    {
        $hasTableKey = OW::getDbo()->queryForRow('SHOW INDEXES FROM '.OW_DB_PREFIX.'base_user_suspend WHERE Key_name = \'userId2\'');
        if (empty($hasTableKey)) {
            $query = 'ALTER TABLE `'.OW_DB_PREFIX.'base_user_suspend` ADD INDEX userId2 (userId)';
            OW::getDbo()->query($query);
        }
        $hasTableKey = OW::getDbo()->queryForRow('SHOW INDEXES FROM '.OW_DB_PREFIX.'newsfeed_action_feed WHERE Key_name = \'feedId\'');
        if (empty($hasTableKey)) {
            $query = 'ALTER TABLE `'.OW_DB_PREFIX.'newsfeed_action_feed` ADD INDEX feedId (feedId)';
            OW::getDbo()->query($query);
        }
        $hasTableKey = OW::getDbo()->queryForRow('SHOW INDEXES FROM '.OW_DB_PREFIX.'newsfeed_action_feed WHERE Key_name = \'feedType\'');
        if (empty($hasTableKey)) {
            $query = 'ALTER TABLE `'.OW_DB_PREFIX.'newsfeed_action_feed` ADD INDEX `feedType` (`feedType`)';
            OW::getDbo()->query($query);
        }
        $hasTableKey = OW::getDbo()->queryForRow('SHOW INDEXES FROM '.OW_DB_PREFIX.'newsfeed_follow WHERE Key_name = \'feedId\'');
        if (empty($hasTableKey)) {
            $query = 'ALTER TABLE `'.OW_DB_PREFIX.'newsfeed_follow` ADD INDEX feedId (feedId)';
            OW::getDbo()->query($query);
        }
        $hasTableKey = OW::getDbo()->queryForRow('SHOW INDEXES FROM '.OW_DB_PREFIX.'newsfeed_follow WHERE Key_name = \'feedType\'');
        if (empty($hasTableKey)) {
            $query = 'ALTER TABLE `'.OW_DB_PREFIX.'newsfeed_follow` ADD INDEX `feedType` (`feedType`)';
            OW::getDbo()->query($query);
        }
        $hasTableKey = OW::getDbo()->queryForRow('SHOW INDEXES FROM '.OW_DB_PREFIX.'newsfeed_activity WHERE Key_name = \'privacy\'');
        if (empty($hasTableKey)) {
            $query = 'ALTER TABLE `'.OW_DB_PREFIX.'newsfeed_activity` ADD INDEX `privacy` (`privacy`)';
            OW::getDbo()->query($query);
        }
        $hasTableKey = OW::getDbo()->queryForRow('SHOW INDEXES FROM '.OW_DB_PREFIX.'newsfeed_activity WHERE Key_name = \'visibility\'');
        if (empty($hasTableKey)) {
            $query = 'ALTER TABLE `'.OW_DB_PREFIX.'newsfeed_activity` ADD INDEX visibility (visibility)';
            OW::getDbo()->query($query);
        }
        $hasTableKey = OW::getDbo()->queryForRow('SHOW INDEXES FROM '.OW_DB_PREFIX.'newsfeed_activity WHERE Key_name = \'status\'');
        if (empty($hasTableKey)) {
            $query = 'ALTER TABLE `'.OW_DB_PREFIX.'newsfeed_activity` ADD INDEX `status` (`status`)';
            OW::getDbo()->query($query);
        }
    }

    public static function updateLanguages($updateValues=false, $updatePlugins=true, $updateBase=true)
    {
        if ($updatePlugins){
            $plugins = BOL_PluginService::getInstance()->findActivePlugins();
            foreach ($plugins as $plugin) {
                $path = OW::getPluginManager()->getPlugin($plugin->getKey())->getRootDir() . 'langs';
                if (OW::getStorage()->fileExists($path)) {
                    BOL_LanguageService::getInstance()->importPrefixFromDir($path, false, false, $updateValues);
                }
                else {
                    $path = OW::getPluginManager()->getPlugin($plugin->getKey())->getRootDir() . 'langs.zip';
                    if (OW::getStorage()->fileExists($path)) {
                        BOL_LanguageService::getInstance()->importPrefixFromZip($path, $plugin->getKey(), false, false, $updateValues);
                    }
                }
            }
        }

        if($updateBase){
            $prefix_base_languages = array('base', 'admin', 'nav', 'mobile');
            foreach ($prefix_base_languages as $prefixLanguage) {
                $path = OW_DIR_ROOT . 'ow_iis' . DS . 'translation' . DS . 'langs' . DS . $prefixLanguage;
                if (OW::getStorage()->fileExists($path)) {
                    BOL_LanguageService::getInstance()->importPrefixFromDir($path, false, false, $updateValues);
                }
            }
        }

        BOL_LanguageService::getInstance()->generateCacheForAllActiveLanguages();
    }

    public static function checkPersianLanguageExist()
    {
        $languagePersianDto = BOL_LanguageService::getInstance()->findByTag('fa-IR');
        if (!empty($languagePersianDto) && $languagePersianDto->status == "active") {
            self::setDefaultLanguageToPersian($languagePersianDto);
        } else if (empty($languagePersianDto) || $languagePersianDto->status != "active") {
            //Update english order
            $languageEnDto = BOL_LanguageService::getInstance()->findByTag('en');
            $languageEnOrder = $languageEnDto->getOrder();
            $languageEnDto->setOrder(BOL_LanguageService::getInstance()->findMaxOrder() + 1);
            BOL_LanguageService::getInstance()->save($languageEnDto);

            $persian_label = 'فارسی';
            $persian_tag = 'fa-IR';
            $persian_status = 'active';
            $persian_order = $languageEnOrder;
            $persian_rtl = true;

            if (empty($languagePersianDto)) {
                //Insert persian languages
                $languagePersianDto = new BOL_Language();
            }

            $languagePersianDto->setLabel($persian_label)
                ->setTag($persian_tag)
                ->setStatus($persian_status)
                ->setOrder($persian_order)
                ->setRtl($persian_rtl);

            BOL_LanguageService::getInstance()->save($languagePersianDto);
            self::setDefaultLanguageToPersian($languagePersianDto);
        }
    }

    public static function removeRussianLanguage()
    {
        $languageService = BOL_LanguageService::getInstance();
        $language = $languageService->findByTag('ru-Ru');
        if (!empty($language)) {
            $languageService->delete($language);
        }
    }

    public static function setDefaultLanguageToPersian($languagePersianDto)
    {
        BOL_LanguageService::getInstance()->setCurrentLanguage($languagePersianDto, false);
        OW::getSession()->set('base.language_id', $languagePersianDto->getId());
        setcookie('base_language_id', (string)$languagePersianDto->getId(), time() + 60 * 60 * 24 * 30, "/");
    }

    public static function updateDefaultTheme()
    {
        try {
            if (defined('OW_DB_HOST') && !OW::getThemeManager()->getThemeService()->themeExists(BOL_ThemeService::DEFAULT_THEME)) {
                OW::getThemeManager()->getThemeService()->updateThemeList();
                OW::getConfig()->saveConfig('base', 'selectedTheme', BOL_ThemeService::DEFAULT_THEME);
            }
        } catch (Exception $e) {
            //Do nothing
        }
    }

    public static function onBeforeEmailSend(OW_Event $event)
    {
        $styleDivConstant = 'font-family:tahoma!important; padding: 10px;background-color: #f2f5f7;';

        $styleDiv = '';
        $styleIMG = '';
        $data = $event->getData();
        if (!isset($data['htmlContent']) || $data['htmlContent'] == null) {
            return;
        }
        $float = 'right';
        $direction = 'rtl';
        if (BOL_LanguageService::getInstance()->getCurrent()->getRtl()) {
            $styleDiv = 'style="direction: rtl;' . $styleDivConstant . '"';
            $styleIMG = 'style="float:right;max-width:26px;padding: 13px;"';
        } else {
            $styleDiv = 'style="direction:rtl;border-radius:10px;padding:10px;background-color: #f2f5f7;' . $styleDivConstant . '"';
            $styleIMG = 'style="float:right;max-width: 26px;padding: 13px;"';
            $float = 'left';
            $direction = 'ltr';
        }

        BOL_MailService::getInstance()->getMailer()->addEmbeddedImage(OW::getThemeManager()->getCurrentTheme()->getImagesDir() . 'logo.png', 'embed_logo');
        $signcontents= "<br><br>" . OW::getLanguage()->text('base', 'ow_mail_information');
        $sign = '<div style="text-align:center;font-family:tahoma!important;color:#868686;font-size: 10px;">'.$signcontents.'</div>';
        $oldData = $data['htmlContent'];
        $dto =  BOL_ThemeService::getInstance()->findThemeByKey(OW::getConfig()->getValue('base', 'selectedTheme'));
        $controls = BOL_ThemeService::getInstance()->findThemeControls($dto->getId());
        foreach ( $controls as $control ){
            if( isset($control['key']) && $control['key'] == 'emailHeaderColor'){
                if ( isset($control['value']) ){
                    $headerColor = $control['value'];
                }else{
                    $headerColor = $control['defaultValue'];
                }
            }
        }
        if($headerColor == '#fff' || $headerColor == '#ffffff' || $headerColor == null || $headerColor == 'transparent'){
            $headerColor = '#6969ff';
        }
        $data['htmlContent'] = '<div style="padding: 0px 5px 0px 5px;margin-bottom:10px;width:100%;background-color: '. $headerColor .';float:' . $float . ';"><img ' . $styleIMG . '  src="cid:embed_logo" />';
        $data['htmlContent'] .= '<span style="font-size: 19px;line-height: 52px;color: #fff;display: inline-block;text-align: center;border-radius: 7px 7px 0px 0px;font-family: tahoma;float: ' . $float . ';direction: ' . $direction . '">' . OW::getConfig()->getValue('base', 'site_name') . '</span></div>';
        $data['htmlContent'] .= '<div ' . $styleDiv . '>' . $oldData . $sign . '</div>';
        $event->setData($data);
    }

    public static function checkRecipientsSuspended(OW_Event $event)
    {
        $params= $event->getParams();
        $data= $event->getData();
        if(isset($params['recipientEmailList']) && !empty($params['recipientEmailList']) && isset($params['mailState']))
        {
            $recipientEmailList=$params['recipientEmailList'];
            $mailState=$params['mailState'];
            foreach ( $recipientEmailList as $key=>$recipientEmail )
            {
                if($mailState['priority']!=BASE_CLASS_Mail::PRIORITY_VERY_HIGH )
                {
                    $userService= BOL_UserService::getInstance();
                    $user=$userService->findByEmail($recipientEmail);
                    if(isset($user) && $userService->isSuspended($user->getId()))
                    {
                        unset($recipientEmailList[$key]);
                    }
                }
            }
            $recipientEmailList = array_values($recipientEmailList);
            $data['recipientEmailList']=$recipientEmailList;
            $event->setData($data);
        }
    }

    public static function getTableBackupName($table_name)
    {
        return self::$prefixBackuplabel . $table_name;
    }

    public static function installPlugins($plugins)
    {
        if($plugins == null || !is_array($plugins)){
            return;
        }

        foreach ($plugins as $pluginKey){
            if($pluginKey == null || $pluginKey == ''){
                continue;
            }

            $plugin = BOL_PluginDao::getInstance()->findPluginByKey($pluginKey);
            if(!isset($plugin)) {
                BOL_PluginService::getInstance()->install($pluginKey);
            }else if(!$plugin->isActive()){
                BOL_PluginService::getInstance()->activate($pluginKey);
            }
        }
    }

    public static function updateStaticFiles($forceUpdate=false)
    {
        $xmlPlugins = BOL_PluginService::getInstance()->getPluginsXmlInfo();
        $plugins = BOL_PluginService::getInstance()->findAllPlugins();
        foreach ($plugins as $plugin) {
            self::updatePluginStaticFiles($plugin->getModule(), $plugin->getKey(), $xmlPlugins, $forceUpdate);
        }

        try {
            BOL_ThemeService::getInstance()->processAllUpdatedThemes();
        } catch (Exception $e) {
            //Do nothing
        }
        //set cachedEntitiesPostfix config
        self::updateCachedEntities();
    }

    public static function updateCachedEntities()
    {
        if ( OW::getConfig()->configExists("base", "cachedEntitiesPostfix") )
        {
            OW::getConfig()->saveConfig("base", "cachedEntitiesPostfix", UTIL_String::getRandomString());
        }else{
            OW::getConfig()->addConfig("base", "cachedEntitiesPostfix", UTIL_String::getRandomString());
        }
    }
    private static function updateStaticCurrentTheme(){
        $themeKey = OW::getConfig()->getValue('base', 'selectedTheme');
        $theme_service = BOL_ThemeService::getInstance();
        $theme = $theme_service->findThemeByKey($themeKey);
        if($theme != null){
            self::updateStaticThemeWithId($theme->getId());
        }
    }

    /***
     * @param $id
     */
    public static function updateStaticThemeWithId($id){
        $theme_service = BOL_ThemeService::getInstance();
        $theme_service->processTheme($id);
        $theme_service->updateCustomCssFile($id);
    }

    /***
     * @param $key
     * @param $module
     * @param $xmlPlugins
     * @param $forceUpdate
     */
    public static function updatePluginStaticFiles($module, $key, $xmlPlugins = null, $forceUpdate=false){
        $pluginStaticDir = OW_DIR_PLUGIN . $module . DS . 'static' . DS;

        $pluginDto = BOL_PluginService::getInstance()->findPluginByKey($key);
        if($pluginDto != null) {
            $hasPluginVersionUpdate = self::hasPluginVersionUpdate($key, $pluginDto->getBuild(), $xmlPlugins);
            $plugin = new OW_Plugin($pluginDto);
            $pluginHasStaticFiles = OW::getStorage()->fileExists($pluginStaticDir);
            $hasExistStaticFolder = OW::getStorage()->fileExists(OW_DIR_STATIC_PLUGIN . $plugin->getModuleName() . DS);
            if ($plugin != null && (!$hasExistStaticFolder || $hasPluginVersionUpdate || $forceUpdate) && !defined('OW_PLUGIN_XP') && $pluginHasStaticFiles) {
                $staticDir = OW_DIR_STATIC_PLUGIN . $module . DS;

                if (!$hasExistStaticFolder) {
                    OW::getStorage()->mkdir($staticDir);
                }

                UTIL_File::copyDir($pluginStaticDir, $staticDir);
                self::updateCachedEntities();
            }
        }
    }

    /***
     * @param $key
     * @param $dbVersion
     * @param $xmlPlugins
     * @return bool
     */
    private static function hasPluginVersionUpdate($key, $dbVersion, $xmlPlugins = null){
        if($xmlPlugins == null){
            return true;
        }
        foreach ($xmlPlugins as $plugin) {
            if (strcmp($plugin['key'], $key) == 0) {
                if($plugin['build'] > $dbVersion){
                    return true;
                }
            }
        }
        return false;
    }

    /***
     * @param $key
     */
    public static function updatePluginStaticFilesWithPluginKey($key){
        $plugin = BOL_PluginService::getInstance()->findPluginByKey($key);
        if($plugin != null){
            self::updatePluginStaticFiles($plugin->getModule(), $key);
        }
    }

    public static function checkDiff(OW_Event $event)
    {
        $params = $event->getParams();
        $phpExtenstions = get_loaded_extensions();
        if (isset($params['diff'])) {
            $diff = $params['diff'];
            $keyMySql = array_search('mysql', $diff);
            if ($keyMySql !== false && array_search('mysqli', $phpExtenstions) !== false) {
                unset($diff[$keyMySql]);
            }

            $event->setData(array('diff' => $diff));
        }
    }


    public static function checkMasterPageBlankHtml(OW_Event $event)
    {
        $params = $event->getParams();
        if (isset($params['assignedVars']) && isset($params['viewRenderer']) && isset($params['assignedVars']['pageSimple'])) {
            $params['viewRenderer']->assignVar('pageSimple', 1);
        }
    }

    public static function getSavedLocaledLanguages()
    {
        $query = 'SELECT * FROM `'.OW_DB_PREFIX.'base_language_key` l_key,`'.OW_DB_PREFIX.'base_language_value` l_value,`'.OW_DB_PREFIX.'base_language_prefix` l_prefix  WHERE l_prefix.`id` = l_key.`prefixId` and l_value.`keyId` =  l_key.`id` and l_key.`key` LIKE \'%page_%\'';
        $result = OW::getDbo()->queryForList($query);
        return $result;
    }

    public static function setSavedLocaledLanguages($result)
    {

        foreach ($result as $row) {
            $langKey = BOL_LanguageService::getInstance()->findKey($row['prefix'], $row['key']);
            if (!empty($langKey)) {
                $langValue = BOL_LanguageService::getInstance()->findValue($row['languageId'], $row['keyId']);

                if ($langValue === null) {
                    $langValue = new BOL_LanguageValue();
                    $langValue->setKeyId($row['keyId']);
                    $langValue->setLanguageId($row['languageId']);
                    BOL_LanguageService::getInstance()->saveValue($langValue->setValue($row['value']), false);
                }
            }
        }
    }

    public static function createUser($username, $email, $password, $date, $sex, $accountType = null, $securityCode = null)
    {
        $event = new OW_Event('base.on_before_user_create');
        OW::getEventManager()->trigger($event);

        $user = BOL_UserService::getInstance()->findByUsername($username);
        if ($user != null) {
            self::deleteUser($user->username);
        }

        $user = BOL_UserService::getInstance()->createUser($username, $password, $email, $accountType, true);
        $questionService = BOL_QuestionService::getInstance();
        $data = array();
        $data['username'] = $username;
        $data['email'] = $email;
        $data['realname'] = $username;
        $data['sex'] = $sex;
        $data['birthdate'] = $date;
        $questionService->saveQuestionsData($data, $user->getId());

        if(isset($securityCode)){
            BOL_QuestionService::getInstance()->saveQuestionsData(array('form_name'=>'requiredQuestionsForm', 'securityCode' => $securityCode), $user->getId());
        }

    }

    public static function deleteUser($username)
    {
        $user = BOL_UserService::getInstance()->findByUsername($username);
        if ($user != null) {
            BOL_QuestionService::getInstance()->deleteQuestionDataByUserId($user->getId());
            BOL_UserService::getInstance()->deleteUser($user->getId());
        }
    }

    public static function installAllAvailablePlugins()
    {
        $availablePlugins = BOL_PluginService::getInstance()->getAvailablePluginsList();
        //echo 'plugins being installed.';
        foreach ($availablePlugins as $availablePlugin) {
            if (in_array($availablePlugin['key'], array('iispluginmanager', 'iissaasclient', 'iismobileaccount', 'iissaas', 'iispreloader', 'iispiwik', 'iisdemo',
                'iisreveal', 'iispreloader', 'iisupdateserver', 'iissms', 'iisguidedtour', 'iisgmailconnect', 'iistechunit', 'iistechnology',
                'iisnationalcode','iisreport','iismultilingualsupport','iissso','iischallenge','questions','iisclamav'))) {
                //echo ' '.$availablePlugin['key'].' skipped,';
                continue;
            }
            try {
                $plugin = BOL_PluginService::getInstance()->install($availablePlugin['key']);
                OW::getPluginManager()->initPlugin(OW::getPluginManager()->getPlugin($plugin->getKey()));
            }
            catch ( LogicException $e )
            {
                OW::getLogger()->writeLog(OW_Log::ERROR, 'plugin_not_installed', ['actionType'=>OW_Log::CREATE, 'enType'=>'plugin', 'enId'=>$availablePlugin['key'], 'error'=>$e]);
            }
        }
    }

    public static function setAlbumCoverDefault(OW_Event $event)
    {
        $params = $event->getParams();
        if (isset($params['albumId'])) {
            $albumId = $params['albumId'];

            $coverDao = PHOTO_BOL_PhotoAlbumCoverDao::getInstance();

            if (($coverDto = $coverDao->findByAlbumId($albumId)) === null) {
                if (($photo = PHOTO_BOL_PhotoAlbumService::getInstance()->getLastPhotoByAlbumId($albumId)) === null) {
                    $coverUrl = $coverDao->getAlbumCoverDefaultUrl();
                } else {
                    $coverUrl = PHOTO_BOL_PhotoService::getInstance()->getPhotoUrlByType($photo->id, PHOTO_BOL_PhotoService::TYPE_MAIN, $photo->hash, !empty($photo->dimension) ? $photo->dimension : false);
                }

                $event->setData(array('coverUrl' => $coverUrl));
            }
        }
    }

    public static function existPluginKeyInActivePlugins($activePlugins, $key)
    {
        foreach ($activePlugins as $activePlugin) {
            if ($activePlugin->key == $key) {
                return true;
            }
        }

        return false;
    }

    public function onBeforeActionsListReturn(OW_Event $event)
    {
        $params = $event->getParams();
        if (isset($params['limit']) && isset($params['driver'])) {
            $limit = $params['limit'];
            $driver = $params['driver'];
            $idList = $params['idList'];
            $result = array();
            $count = (floor($limit[1] / ($limit[1] - self::$checkingLoadMorePeriod))) * $limit[0] + sizeof($idList);
            if (isset($limit[2]) && $limit[2] && sizeof($idList) >= $limit[1] && $driver != null) {
                $result['count'] = $count;
                array_pop($idList);
                $result['idList'] = $idList;
                $event->setData($result);
            } else if ($driver != null) {
                $result['count'] = $count;
                $result['idList'] = $idList;
                $event->setData($result);
            }
        }
    }

    public function onAfterPluginUnistall(OW_Event $event)
    {
        $params = $event->getParams();
        if (isset($params['tables']) && is_array($params['tables'])) {
            $tables = $params['tables'];
            foreach ($tables as $table) {
                $removeTriggerName = self::$removeTriggerNameBackupTable . $table;
                $updateTriggerName = self::$updateTriggerNameBackupTable . $table;
                $triggerQueryForDoinBackupBeforeRemove = 'DROP TRIGGER IF EXISTS `' . $removeTriggerName . '`;';
                OW::getDbo()->query($triggerQueryForDoinBackupBeforeRemove);

                $triggerQueryForDoinBackupBeforeRemove = 'DROP TRIGGER IF EXISTS `' . $updateTriggerName . '`;';
                OW::getDbo()->query($triggerQueryForDoinBackupBeforeRemove);

                $backupTableName = self::getTableBackupName($table);
                $newBackupTableName = str_replace(self::$prefixBackuplabel, self::$prefixRemovedlabel, $backupTableName) . '_' . UTIL_String::getRandomString(4, UTIL_String::RND_STR_ALPHA_WITH_CAPS_NUMERIC);
                $backupTable = OW::getDbo()->queryForRow('show tables like :tableName', array('tableName' => $backupTableName));
                if (!empty($backupTable)) {
                    $queryForReplaceTableName = 'RENAME TABLE ' . $backupTableName . ' TO ' . $newBackupTableName . ';';
                    try {
                        OW::getDbo()->query($queryForReplaceTableName);
                    } catch (Exception $e) {
                        //Do nothing
                    }
                }
            }
        }
    }

    public function decideToShowCurrencySetting(OW_Event $event)
    {
        $event->setData(array('hide' => true));
    }

    public static function multipleLanguageSentenceAlignmentCorrection(OW_Event $event)
    {
        $params = $event->getParams();
        $correctedSentence = "";
        if (BOL_LanguageService::getInstance()->getCurrent()->getTag()==='en') {
            return;
        }
        if (isset($params['sentence'])) {
            $correctedSentence = "&#x202B " . $params['sentence'];
        }
        $event->setData(array('correctedSentence' => $correctedSentence));
    }

    public function validateHtmlContent(OW_Event $event)
    {
        $params = $event->getParams();
        if (isset($params['settingList']) && $this->isHTMLValidatorExtensionExist()) {
            $settingList = $params['settingList'];
            if (isset($settingList['content'])) {
                $tidy = new tidy;
                $content = '<!DOCTYPE html><html><head><title></title></head><body>' . $settingList['content'] . '</body></html>';
                $tidy->parseString($content, array(), 'utf8');
                if ($tidy->errorBuffer != null) {
                    $errorsText = str_replace("\r\n", '<br>', UTIL_HtmlTag::escapeHtml(preg_replace('/line[\s\S]+?-/', '', $tidy->errorBuffer)));
                    $exceptionText = OW::getLanguage()->text('base', 'html_error');
                    $exceptionText .= '<span class="ow_button"><span><input class="accordion ow_ic_info" type="button" onclick="initAccordionButtonsProcessing(this);" value="' . OW::getLanguage()->text('base', 'html_error_details') . '"></span></span>';
                    $exceptionText .= '<div class="html_error_content_panel" style="direction: ltr !important; text-align: left;">' . $errorsText . '</div>';
                    throw new WidgetSettingValidateException($exceptionText, 'content');
                }
            }
        }
    }

    public static function createFileFromRawData($dir, $data) {
        $decodedData = base64_decode($data);
        $fp = fopen($dir, 'w');
        fwrite($fp, $decodedData);
        fclose($fp);
        return true;
    }

    public function beforeAllowCustomizationChanged(OW_Event $event)
    {
        $params = $event->getParams();
        if (!$this->isHTMLValidatorExtensionExist() && isset($params['placeName']) && in_array($params['placeName'], array('profile', 'dashboard', 'group'))) {
            $event->setData(array('error' => true));
        }
    }

    public function beforeCustomizationPageRenderer(OW_Event $event)
    {
        $params = $event->getParams();
        if (isset($params['this']) && !$this->isHTMLValidatorExtensionExist() && isset($params['customizeAllowed']) && isset($params['placeName'])) {
            if (in_array($params['placeName'], array('profile', 'dashboard', 'group'))) {
                if ($params['customizeAllowed']) {
                    BOL_ComponentAdminService::getInstance()->saveAllowCustomize($params['placeName'], false);
                }
                $params['this']->assign('allowCustomizationLocked', true);
                $event->setData(array('customizeAllowed' => false));
            }
        }
    }

    public function isHTMLValidatorExtensionExist()
    {
        return extension_loaded('tidy');
    }

    public function partialHalfSpaceCodeCorrection(OW_Event $event)
    {
        $params = $event->getParams();
        if (isset($params['sentence'])) {
            $sentence = $params['sentence'];
        }
        $sentence = strip_tags($sentence);
        if (isset($params['trimLength'])) {
            $trimLength = $params['trimLength'];
            $sentence = UTIL_String::truncate($sentence, $trimLength);
        }
        $counter = 0;
        while($this->shouldRemoveCharacterFromFirst($sentence) && $counter<20){
            $sentence = substr($sentence,1);
            $counter++;
        }
        $specificCharacters = substr($sentence, -6);
        if (strcmp($specificCharacters, "&zwnj;") == 0) {
            $correctedSentence = substr($sentence, 0, strlen($sentence) - 6);
        } else {
            $specificCharacters = substr($sentence, -5);
            if (strcmp($specificCharacters, "&zwnj") == 0) {
                $correctedSentence = substr($sentence, 0, strlen($sentence) - 5);
            } else {
                $specificCharacters = substr($sentence, -4);
                if (strcmp($specificCharacters, "&zwn") == 0) {
                    $correctedSentence = substr($sentence, 0, strlen($sentence) - 4);
                } else {
                    $specificCharacters = substr($sentence, -3);
                    if (strcmp($specificCharacters, "&zw") == 0) {
                        $correctedSentence = substr($sentence, 0, strlen($sentence) - 3);
                    } else {
                        $specificCharacters = substr($sentence, -2);
                        if (strcmp($specificCharacters, "&z") == 0) {
                            $correctedSentence = substr($sentence, 0, strlen($sentence) - 2);
                        } else {
                            $specificCharacters = substr($sentence, -1);
                            if (strcmp($specificCharacters, "&") == 0) {
                                $correctedSentence = substr($sentence, 0, strlen($sentence) - 1);
                            }
                        }
                    }

                }
            }
        }
        if (isset($correctedSentence)) {
            $event->setData(array('correctedSentence' => $correctedSentence));
        }else {
            $event->setData(array('correctedSentence' => $sentence));
        }
    }

    public function shouldRemoveCharacterFromFirst($string){
        $firstCharacter = substr($string, 0, 1);
        if($firstCharacter == "\r" || $firstCharacter == "\n"){
            return true;
        }
    }

    public function partialSpaceCodeCorrection(OW_Event $event)
    {
        $params = $event->getParams();
        if (isset($params['sentence'])) {
            $sentence = $params['sentence'];
        }
        $sentence = strip_tags($sentence);
        if (isset($params['trimLength'])) {
            $trimLength = $params['trimLength'];
            $sentence = UTIL_String::truncate($sentence, $trimLength);
        }
        $counter = 0;
        while($this->shouldRemoveCharacterFromFirst($sentence) && $counter<20){
            $sentence = substr($sentence,1);
            $counter++;
        }
        $specificCharacters = substr($sentence, -6);
        if (strcmp($specificCharacters, "&nbsp;") == 0) {
            $correctedSentence = substr($sentence, 0, strlen($sentence) - 6);
        } else {
            $specificCharacters = substr($sentence, -5);
            if (strcmp($specificCharacters, "&nbsp") == 0) {
                $correctedSentence = substr($sentence, 0, strlen($sentence) - 5);
            } else {
                $specificCharacters = substr($sentence, -4);
                if (strcmp($specificCharacters, "&nbs") == 0) {
                    $correctedSentence = substr($sentence, 0, strlen($sentence) - 4);
                } else {
                    $specificCharacters = substr($sentence, -3);
                    if (strcmp($specificCharacters, "&nb") == 0) {
                        $correctedSentence = substr($sentence, 0, strlen($sentence) - 3);
                    } else {
                        $specificCharacters = substr($sentence, -2);
                        if (strcmp($specificCharacters, "&n") == 0) {
                            $correctedSentence = substr($sentence, 0, strlen($sentence) - 2);
                        } else {
                            $specificCharacters = substr($sentence, -1);
                            if (strcmp($specificCharacters, "&") == 0) {
                                $correctedSentence = substr($sentence, 0, strlen($sentence) - 1);
                            }
                        }
                    }

                }
            }
        }
        if (isset($correctedSentence)) {
            $event->setData(array('correctedSentence' => $correctedSentence));
        } else {
            $event->setData(array('correctedSentence' => $sentence));
        }
    }

    public function htmlEntityCorrection(OW_Event $event)
    {
        $params = $event->getParams();
        if (isset($params['sentence'])) {
            $sentence = $params['sentence'];
        }
        $sentence = strip_tags($sentence);
        if (isset($params['trimLength'])) {
            $trimLength = $params['trimLength'];
            $sentence = UTIL_String::truncate($sentence, $trimLength);
        }
        $counter = 0;
        while($this->shouldRemoveCharacterFromFirst($sentence) && $counter<20){
            $sentence = substr($sentence,1);
            $counter++;
        }
        $lastSpace = strrpos($sentence, ' ');
        if ( $lastSpace > 0 && (strlen($sentence) - $lastSpace < 20) ) {
            $lastWord = substr($sentence, $lastSpace, strlen($sentence));
            if(strpos($lastWord,'&')> 0 ){
                $correctedSentence = substr($sentence, 0, $lastSpace);
            }
        }

        if (isset($correctedSentence)) {
            $event->setData(array('correctedSentence' => $correctedSentence));
        } else {
            $event->setData(array('correctedSentence' => $sentence));
        }
    }

    public function setDistinguishForRequiredField(OW_Event $event)
    {
        $params = $event->getParams();
        if (isset($params['element'])) {
            if ($params['element']->isRequired()) {
                $label = $params['element']->getLabel();
                if (strpos($label, 'ow_required_star') === false) {
                    $label .= '<span class="ow_required_star">*</span>';
                    $event->setData(array('distinguishedRequiredLabels' => $label));
                }
            }
        }
    }

    public function onBeforeNewsFeedStatusStringWrite(OW_Event $event)
    {
        $params = $event->getParams();
        if (isset($params['string'])) {
            $event->setData(array('string' => $this->setHomeUrlVariable($params['string'])));
        }
    }

    public function onAfterNewsFeedStatusStringRead(OW_Event $eventCheck)
    {
        $params = $eventCheck->getParams();
        if (isset($params['string'])) {
            $eventCheck->setData(array('string' => $this->correctHomeUrlVariable($params['string'])));
        }
        if(isset($params['toDecode'])){
            $decodedString = html_entity_decode($params['toDecode'],ENT_QUOTES);
            $decodedString = html_entity_decode($decodedString,ENT_COMPAT);
            $decodedString = UTIL_HtmlTag::stripTagsAndJs($decodedString);
            $eventCheck->setData(array('decodedString' => $decodedString));
        }
    }

    public function onAfterNotificationDataRead(OW_Event $event)
    {
        $params = $event->getParams();
        if (isset($params['data'])) {
            $data = $params['data'];
            if (!empty($data)) {
                $data = json_decode($data, true);
                if (isset($data['string']['vars']['receiver'])) {
                    $data['string']['vars']['receiver'] = $this->correctHomeUrlVariable($data['string']['vars']['receiver']);
                }
                if (isset($data['string']['vars']['actorUrl'])) {
                    $data['string']['vars']['actorUrl'] = $this->correctHomeUrlVariable($data['string']['vars']['actorUrl']);
                }
                if (isset($data['string']['vars']['url'])) {
                    $data['string']['vars']['url'] = $this->correctHomeUrlVariable($data['string']['vars']['url']);
                }
                if (isset($data['string']['vars']['userUrl'])) {
                    $data['string']['vars']['userUrl'] = $this->correctHomeUrlVariable($data['string']['vars']['userUrl']);
                }
                if (isset($data['string']['vars']['videoUrl'])) {
                    $data['string']['vars']['videoUrl'] = $this->correctHomeUrlVariable($data['string']['vars']['videoUrl']);
                }
                if (isset($data['string']['vars']['photoUrl'])) {
                    $data['string']['vars']['photoUrl'] = $this->correctHomeUrlVariable($data['string']['vars']['photoUrl']);
                }
                if (isset($data['string']['vars']['postUrl'])) {
                    $data['string']['vars']['postUrl'] = $this->correctHomeUrlVariable($data['string']['vars']['postUrl']);
                }
                if (isset($data['string']['vars']['topicUrl'])) {
                    $data['string']['vars']['topicUrl'] = $this->correctHomeUrlVariable($data['string']['vars']['topicUrl']);
                }
                if (isset($data['string']['vars']['competitionUrl'])) {
                    $data['string']['vars']['competitionUrl'] = $this->correctHomeUrlVariable($data['string']['vars']['competitionUrl']);
                }
                if (isset($data['string']['vars']['textLink'])) {
                    $data['string']['vars']['textLink'] = $this->correctHomeUrlVariable($data['string']['vars']['textLink']);
                }
                if (isset($data['string']['vars']['groupUrl'])) {
                    $data['string']['vars']['groupUrl'] = $this->correctHomeUrlVariable($data['string']['vars']['groupUrl']);
                }
                if (isset($data['string']['vars']['ownerUrl'])) {
                    $data['string']['vars']['ownerUrl'] = $this->correctHomeUrlVariable($data['string']['vars']['ownerUrl']);
                }
                if (isset($data['string']['vars']['contextUrl'])) {
                    $data['string']['vars']['contextUrl'] = $this->correctHomeUrlVariable($data['string']['vars']['contextUrl']);
                }
                if (isset($data['avatar']['src'])) {
                    $data['avatar']['src'] = $this->correctHomeUrlVariable($data['avatar']['src']);
                }
                if (isset($data['avatar']['url'])) {
                    $data['avatar']['url'] = $this->correctHomeUrlVariable($data['avatar']['url']);
                }
                if (isset($data['contentImage']['src'])) {
                    $data['contentImage']['src'] = $this->correctHomeUrlVariable($data['contentImage']['src']);
                } else if (isset($data['contentImage'])) {
                    $data['contentImage'] = $this->correctHomeUrlVariable($data['contentImage']);
                }
                if (isset($data['url'])) {
                    $data['url'] = $this->correctHomeUrlVariable($data['url']);
                }

                $event->setData(array('data' => $data));
            }
        }
    }

    public function onBeforeNotificationDataWrite(OW_Event $event)
    {
        $params = $event->getParams();
        if (isset($params['data'])) {
            $data = $params['data'];

            if (isset($data['string']['vars']['receiver'])) {
                $data['string']['vars']['receiver'] = $this->setHomeUrlVariable($data['string']['vars']['receiver']);
            }
            if (isset($data['string']['vars']['actorUrl'])) {
                $data['string']['vars']['actorUrl'] = $this->setHomeUrlVariable($data['string']['vars']['actorUrl']);
            }
            if (isset($data['string']['vars']['url'])) {
                $data['string']['vars']['url'] = $this->setHomeUrlVariable($data['string']['vars']['url']);
            }
            if (isset($data['string']['vars']['userUrl'])) {
                $data['string']['vars']['userUrl'] = $this->setHomeUrlVariable($data['string']['vars']['userUrl']);
            }
            if (isset($data['string']['vars']['videoUrl'])) {
                $data['string']['vars']['videoUrl'] = $this->setHomeUrlVariable($data['string']['vars']['videoUrl']);
            }
            if (isset($data['string']['vars']['photoUrl'])) {
                $data['string']['vars']['photoUrl'] = $this->setHomeUrlVariable($data['string']['vars']['photoUrl']);
            }
            if (isset($data['string']['vars']['postUrl'])) {
                $data['string']['vars']['postUrl'] = $this->setHomeUrlVariable($data['string']['vars']['postUrl']);
            }
            if (isset($data['string']['vars']['topicUrl'])) {
                $data['string']['vars']['topicUrl'] = $this->setHomeUrlVariable($data['string']['vars']['topicUrl']);
            }
            if (isset($data['string']['vars']['competitionUrl'])) {
                $data['string']['vars']['competitionUrl'] = $this->setHomeUrlVariable($data['string']['vars']['competitionUrl']);
            }
            if (isset($data['avatar']['src'])) {
                $data['avatar']['src'] = $this->setHomeUrlVariable($data['avatar']['src']);
            }
            if (isset($data['avatar']['url'])) {
                $data['avatar']['url'] = $this->setHomeUrlVariable($data['avatar']['url']);
            }
            if (isset($data['contentImage']['src'])) {
                $data['contentImage']['src'] = $this->setHomeUrlVariable($data['contentImage']['src']);
            } else if (isset($data['contentImage'])) {
                $data['contentImage'] = $this->setHomeUrlVariable($data['contentImage']);
            }
            if (isset($data['url'])) {
                $data['url'] = $this->setHomeUrlVariable($data['url']);
            }
            $event->setData(array('data' => $data));
        }
    }


    public function setHomeUrlVariable($string)
    {
        return str_replace(OW_URL_HOME, '$$BASE_URL$$', $string);
    }

    public function correctHomeUrlVariable($string)
    {
        return preg_replace('/\$\$BASE_URL\$\$/', OW_URL_HOME, $string);
    }

    public function onAfterGetTplData(OW_Event $event)
    {
        $params = $event->getParams();
        $hasMobileVersion = true;
        if (isset($params['item'])) {
            $item = $params['item'];
            if (!empty($item["disabled"]) && $item["disabled"]) {
                $hasMobileVersion = false;
            }
            $event->setData(array('hasMobileVersion' => $hasMobileVersion));
        }
    }

    public function isMobileVersion(OW_Event $event)
    {
        $params = $event->getParams();
        if (isset($params['check']) && $params['check'] == true) {
            if (OW::getApplication()->getContext() == OW::CONTEXT_MOBILE) {
                $event->setData(array('isMobileVersion' => true));
            } else {
                $event->setData(array('isMobileVersion' => false));
            }
        }
    }

    /***
     * Checking privacy of forum sections
     * @param OW_Event $event
     */
    public function onBeforeForumSectionsReturn(OW_Event $event)
    {
        if (OW::getUser()->isAuthenticated() && OW::getUser()->isAuthorized('forum')) {
            return;
        }
        $params = $event->getParams();
        if (isset($params['sectionGroupList'])) {
            //Fetch all section group list
            $sectionGroupList = $params['sectionGroupList'];

            //Make corrected section group list by privacy (group role)
            $correctSectionGroupList = array();

            foreach ($sectionGroupList as $section) {
                $correctedSection = $section;

                //unset groups for set correct groups
                unset($correctedSection['groups']);

                //Fetch all groups in section
                $groupsSection = $section['groups'];
                foreach ($groupsSection as $group) {

                    $isPrivate = $group['isPrivate'];

                    $rolesId = null;
                    if(isset($group['rolesId'])) {
                        //Fetch group roles id
                        $rolesId = $group['rolesId'];
                    }
                    $forUserId = null;
                    if (OW::getUser()->isAuthenticated()) {
                        $forUserId = OW::getUser()->getId();
                    }
                    $groupAvailableForUser = false;
                    if ($isPrivate) {
                        //Continue if user is not entered and group is private
                        if ($rolesId == null) {
                            continue;
                        }
                        $authService = BOL_AuthorizationService::getInstance();

                        $hasGuestRoleInGroupRolesId = in_array($authService->getGuestRoleId(), $rolesId);
                        if (!$hasGuestRoleInGroupRolesId && $forUserId == null) {
                            continue;
                        } else if ($forUserId != null) {
                            //Compare user roles and group roles
                            $userRoles = $authService->findUserRoleList($forUserId);
                            $userRoleIdList = array();
                            foreach ($userRoles as $role) {
                                $userRoleIdList[] = $role->id;
                            }
                            $groupAvailableForUser = FORUM_BOL_ForumService::getInstance()->isPrivateGroupAvailable($forUserId, $rolesId, $userRoleIdList);
                        } else {
                            //Group is visible for guest
                            $groupAvailableForUser = true;
                        }
                    } else {
                        //Group is public
                        $groupAvailableForUser = true;
                    }

                    if ($groupAvailableForUser) {
                        $correctedSection['groups'][] = $group;
                    }
                }

                //Checking section has group
                if (isset($correctedSection['groups']) && sizeof($correctedSection['groups']) > 0) {
                    $correctSectionGroupList[$correctedSection['sectionId']] = $correctedSection;
                }
            }
            $event->setData(array('sectionGroupList' => $correctSectionGroupList));
        }
    }

    /***
     * @param OW_Event $event
     */
    public function checkPhotoExtension(OW_Event $event)
    {
        $params = $event->getParams();
        $pngExt = 'png';
        if (isset($params['photoId']) && isset($params['size'])) {
            $id = $params['photoId'];
            $size = $params['size'];
            $userFilesDir = OW::getPluginManager()->getPlugin('photo')->getUserFilesDir();
            $filePath = null;
            switch ($size) {
                case 1:
                    $filePath = $userFilesDir . PHOTO_BOL_PhotoTemporaryDao::TMP_PHOTO_PREVIEW_PREFIX . $id . '.' . $pngExt;
                    break;
                case 2:
                    $filePath = $userFilesDir . PHOTO_BOL_PhotoTemporaryDao::TMP_PHOTO_PREFIX . $id . '.' . $pngExt;
                    break;
                case 3:
                    $filePath = $userFilesDir . PHOTO_BOL_PhotoTemporaryDao::TMP_PHOTO_ORIGINAL_PREFIX . $id . '.' . $pngExt;
                    break;
                case 4:
                    $filePath = $userFilesDir . PHOTO_BOL_PhotoTemporaryDao::TMP_PHOTO_SMALL . $id . '.' . $pngExt;
                    break;
                case 5:
                    $filePath = $userFilesDir . PHOTO_BOL_PhotoTemporaryDao::TMP_PHOTO_FULLSCREEN . $id . '.' . $pngExt;
                    break;
            }

            if ($filePath != null) {
                if (OW::getStorage()->fileExists($filePath)) {
                    $event->setData(array('ext' => '.' . $pngExt));
                }
            }
        } else if (isset($params['source']) && isset($params['destination'])) {
            $source = $params['source'];
            $destination = $params['destination'];
            $extInfo = pathinfo($source);
            $ext = null;
            if(isset($extInfo['extension'])) {
                $ext = $extInfo['extension'];
            }
            if (strtolower($ext) != $pngExt && isset($_FILES['file']['name'])) {
                $ext = pathinfo($_FILES['file']['name'])['extension'];
            }
            if (strtolower($ext) != $pngExt && isset($_FILES['image']['name'])) {
                $ext = pathinfo($_FILES['image']['name'])['extension'];
            }
            if (strtolower($ext) == $pngExt) {
                $newDestination = pathinfo($destination)['dirname'] . DS . pathinfo($destination)['filename'] . '.' . $pngExt;
                $event->setData(array('destination' => $newDestination));
            }

        } else if (isset($params['cover']) && isset($params['subPath'])) {
            $cover = $params['cover'];
            $subPath = $params['subPath'];
            $filePath = OW::getPluginManager()->getPlugin('photo')->getUserFilesDir() . $subPath . $cover->id . '_' . $cover->hash . '.' . $pngExt;
            if (OW::getStorage()->fileExists($filePath)) {
                $event->setData(array('ext' => '.' . $pngExt));
            }
        } else if (isset($params['fullPath'])) {
            $fullPath = $params['fullPath'];
            $filePath = $fullPath . '.' . $pngExt;
            if (OW::getStorage()->fileExists($filePath)) {
                $event->setData(array('ext' => '.' . $pngExt));
            }
        } else if (isset($params['photoId']) && isset($params['hash']) && isset($params['type'])) {
            $photoId = $params['photoId'];
            $hash = $params['hash'];
            $hashSlug = !empty($hash) ? '_' . $hash : '';
            $type = $params['type'];
            $filePath = null;

            switch ($type) {
                case PHOTO_BOL_PhotoService::TYPE_MAIN:
                    $filePath = PHOTO_BOL_PhotoDao::getInstance()->getPhotoUploadDir() . PHOTO_BOL_PhotoDao::PHOTO_PREFIX . $photoId . $hashSlug . '.' . $pngExt;
                    break;
                case PHOTO_BOL_PhotoService::TYPE_PREVIEW:
                    $filePath = PHOTO_BOL_PhotoDao::getInstance()->getPhotoUploadDir() . PHOTO_BOL_PhotoDao::PHOTO_PREVIEW_PREFIX . $photoId . $hashSlug . '.' . $pngExt;
                    break;
                case PHOTO_BOL_PhotoService::TYPE_ORIGINAL:
                    $filePath = PHOTO_BOL_PhotoDao::getInstance()->getPhotoUploadDir() . PHOTO_BOL_PhotoDao::PHOTO_ORIGINAL_PREFIX . $photoId . $hashSlug . '.' . $pngExt;
                    break;
                case PHOTO_BOL_PhotoService::TYPE_SMALL:
                    $filePath = PHOTO_BOL_PhotoDao::getInstance()->getPhotoUploadDir() . PHOTO_BOL_PhotoDao::PHOTO_SMALL_PREFIX . $photoId . $hashSlug . '.' . $pngExt;
                    break;
                case PHOTO_BOL_PhotoService::TYPE_FULLSCREEN:
                    $filePath = PHOTO_BOL_PhotoDao::getInstance()->getPhotoUploadDir() . PHOTO_BOL_PhotoDao::PHOTO_FULLSCREEN_PREFIX . $photoId . $hashSlug . '.' . $pngExt;
                    break;
                default:
                    $filePath = PHOTO_BOL_PhotoDao::getInstance()->getPhotoUploadDir() . PHOTO_BOL_PhotoDao::PHOTO_PREFIX . $photoId . $hashSlug . '.' . $pngExt;
                    break;
            }

            if ($filePath != null && OW::getStorage()->fileExists($filePath)) {
                $event->setData(array('ext' => '.' . $pngExt));
            }
        } else if (isset($params['photoId']) && isset($params['type']) && isset($params['dir'])) {
            $photoId = $params['photoId'];
            $dir = $params['dir'];
            $type = $params['type'];
            $filePath = null;
            switch ($type) {
                case PHOTO_BOL_PhotoService::TYPE_MAIN:
                    $filePath = $dir . PHOTO_BOL_PhotoDao::PHOTO_PREFIX . $photoId . '.' . $pngExt;
                    break;
                case PHOTO_BOL_PhotoService::TYPE_PREVIEW:
                    $filePath = $dir . PHOTO_BOL_PhotoDao::PHOTO_PREVIEW_PREFIX . $photoId . '.' . $pngExt;
                    break;
                case PHOTO_BOL_PhotoService::TYPE_ORIGINAL:
                    $filePath = $dir . PHOTO_BOL_PhotoDao::PHOTO_ORIGINAL_PREFIX . $photoId . '.' . $pngExt;
                    break;
                case PHOTO_BOL_PhotoService::TYPE_SMALL:
                    $filePath = $dir . PHOTO_BOL_PhotoDao::PHOTO_SMALL_PREFIX . $photoId . '.' . $pngExt;
                    break;
                case PHOTO_BOL_PhotoService::TYPE_FULLSCREEN:
                    $filePath = $dir . PHOTO_BOL_PhotoDao::PHOTO_FULLSCREEN_PREFIX . $photoId . '.' . $pngExt;
                    break;
                default:
                    $filePath = $dir . PHOTO_BOL_PhotoDao::PHOTO_PREFIX . $photoId . '.' . $pngExt;
                    break;
            }

            if ($filePath != null && OW::getStorage()->fileExists($filePath)) {
                $event->setData(array('ext' => '.' . $pngExt));
            }
        } else if (isset($params['checkExtenstionPath'])) {
            $path = $params['checkExtenstionPath'];
            $extInfo = pathinfo($path);
            $ext = null;
            if(isset($extInfo['extension'])) {
                $ext = $extInfo['extension'];
            }
            if (strtolower($ext) == $pngExt) {
                $event->setData(array('ext' => '.' . $pngExt));
            }
        }
    }

    /***
     * Search in private sections of forum
     * @param OW_Event $event
     */
    public function onBeforeForumAdvanceSearchQueryExecute(OW_Event $event)
    {
        $params = $event->getParams();
        if (isset($params['tags'])) {
            $tags = $params['tags'];
            $private_tag = array();
            $isPost = false;
            foreach ($tags as $tag) {
                if (strpos($tag, 'post') !== false) {
                    $isPost = true;
                }
                $tag = str_replace('_public', '', $tag);
                $private_tag[] = $tag;
            }

            if (sizeof($private_tag) > 0) {
                $userRoleIdList = array();
                if (!OW::getUser()->isAuthenticated()) {
                    $userRoleIdList[] = BOL_AuthorizationService::getInstance()->getGuestRoleId();
                } else {
                    $userRoles = BOL_AuthorizationService::getInstance()->findUserRoleList(OW::getUser()->getId());
                    foreach ($userRoles as $role) {
                        $userRoleIdList[] = $role->id;
                    }
                }

                $numberOfUserRoleIdList = sizeof($userRoleIdList);
                $extendedUserRoleQueryCondition = ' and ( ';
                foreach ($userRoleIdList as $userRoleId) {
                    $extendedUserRoleQueryCondition .= ' g.roles like \'%"' . $userRoleId . '"%\'';
                    if ($numberOfUserRoleIdList > 1) {
                        $extendedUserRoleQueryCondition .= ' or ';
                        $numberOfUserRoleIdList--;
                    }
                }
                $extendedUserRoleQueryCondition .= ' ) ';

                $subQueryExtendedWhereCondition = ' or (a.' . BOL_SearchEntityTagDao::ENTITY_TAG . ' IN (' . OW::getDbo()->mergeInClause($private_tag) . ') ';
                if ($isPost) {
                    $subQueryExtendedWhereCondition .= ' and (select count(*) from '.OW_DB_PREFIX.'forum_post p, '.OW_DB_PREFIX.'forum_topic t, '.OW_DB_PREFIX.'forum_group g where p.topicId = t.id and t.groupId = g.id and p.id = b.' . BOL_SearchEntityDao::ENTITY_ID . ' ' . $extendedUserRoleQueryCondition . ')>0 ) ';
                } else {
                    $subQueryExtendedWhereCondition .= ' and (select count(*) from '.OW_DB_PREFIX.'forum_topic t, '.OW_DB_PREFIX.'forum_group g where t.groupId = g.id and t.id = b.' . BOL_SearchEntityDao::ENTITY_ID . ' ' . $extendedUserRoleQueryCondition . ')>0 ) ';
                }
                $event->setData(array('subQueryExtendedWhereCondition' => $subQueryExtendedWhereCondition));
            }
        }
    }

    public function validateUploadedFileName(OW_Event $event)
    {
        $params = $event->getParams();
        $originalFileName = $params['fileName'];
        //temporary fix for android upload audio problems
        $ext = '';
        if ( strpos($originalFileName, '.') > 0 )
        {
            $ext = UTIL_File::getExtension($originalFileName);
        }
        if($ext==''){
            if(isset($_COOKIE['UsingMobileApp']) && $_COOKIE['UsingMobileApp']=='android')
            {
                $ext = 'mp3';
                $originalFileName .= '.'.$ext;
            }
        }
        if (isset($originalFileName) && sizeof(explode('.', $originalFileName)) > 1) {
            $fileName = IISSecurityProvider::generateUniqueId() . '.' . $ext;
            $fixedOriginalFileName= str_replace('%','_',$originalFileName);
            if(mb_strlen($fixedOriginalFileName)>75)
            {
                $fixedOriginalFileName= mb_substr($fixedOriginalFileName, 0, 75) ."... .".$ext;
            }
            $event->setData(array('fileName' => $fileName,'fixedOriginalFileName'=>$fixedOriginalFileName));
        }
    }

    public function setDefaultTimeZoneForUser(OW_Event $event)
    {
        $params = $event->getParams();
        if (isset($params['userId']) && !isset($params['forEditProfile'])) {
            $preferenceDataDao = BOL_PreferenceDataDao::getInstance();
            $oldValue = $preferenceDataDao->findByPreferenceListForUserList(array('timeZoneSelect'), array($params['userId']));
            if($oldValue == null || !isset($oldValue[$params['userId']]['timeZoneSelect'])){
                $preferenceData = new BOL_PreferenceData();
                $preferenceData->key = "timeZoneSelect";
                $preferenceData->userId = $params['userId'];
                $preferenceData->value = json_encode(OW::getConfig()->getValue('base', 'site_timezone'));
                $preferenceDataDao->save($preferenceData);
            }
        }
    }


    public function checkImageExtenstionForAddAsImagesOfUrl(OW_Event $event){
        $params = $event->getParams();
        if (isset($params['img'])) {
            $img = $params['img'];
            $validType = array('png', 'jpg', 'jpeg');
            $extInfo = pathinfo($img);
            $ext = null;
            if(isset($extInfo['extension'])) {
                $ext = $extInfo['extension'];
            }
            if (!in_array(strtolower($ext), $validType)) {
                $event->setData(array('wrong' => true));
            }
        }
    }

    public function enableDesktopOfflineChat(OW_Event $event){
        $params = $event->getParams();
        if (isset($params['enOfflineChat'])) {
            $event->setData(array('setOfflineChat' => true));
        }
    }

    public function userListFriendshipStatus(OW_Event $mainEvent){
        $params = $mainEvent->getParams();
        $questions = BOL_QuestionService::getInstance()->findSearchQuestionsForAccountType('all');
        $questionNameList = array();
        $questionNameValues = array();
        $questionValues = array();
        foreach ( $questions as $key => $question )
        {
            $questionNameList[] = $question['name'];
            $questionNameValues[$question['name']] = Ow::getLanguage()->text('base','questions_question_'.$question['name'].'_label');
        }
        $friendList = array();
        $userIds = array();
        if (isset($params['list'])) {
            $list = $params['list'];
        }
        $questionData=array();
        $userData = array();
        $language = OW::getLanguage();
        if(class_exists('FRIENDS_BOL_Service')) {
            if (isset($list) && isset($params['desktopVersion'])) {
                foreach ($list as $item) {
                    $userIds[] = $item->id;
                    $service = FRIENDS_BOL_Service::getInstance();
                    $isFriends = $service->findFriendship(Ow::getUser()->getId(), $item->id);
                    if (isset($isFriends) && $isFriends->status == 'active') {
                        $friendList[$item->id] = $isFriends;
                    }
                }
            } else if (isset($list) && isset($params['mobileVersion'])) {
                foreach ($list as $item) {
                    $userIds[] = $item;
                    $service = FRIENDS_BOL_Service::getInstance();
                    $isFriends = $service->findFriendship(Ow::getUser()->getId(), $item);
                    if (isset($isFriends) && $isFriends->status == 'active') {
                        $friendList[$item] = $isFriends;
                    }
                }
            }
            $questionData = BOL_QuestionService::getInstance()->getQuestionData($userIds, $questionNameList);
            foreach ($userIds as $userId){
                foreach ($questions as $question )
                {
                    $event = new OW_Event('base.questions_field_get_label', array(
                        'presentation' => $question['presentation'],
                        'fieldName' => $question['name'],
                        'configs' => $question['custom'],
                        'type' => 'view'
                    ));

                    OW::getEventManager()->trigger($event);

                    $label = $event->getData();

                    $questionLabelList[$question['name']] = !empty($label) ? $label : BOL_QuestionService::getInstance()->getQuestionLang($question['name']);

                    $event = new OW_Event('base.questions_field_get_value', array(
                        'presentation' => $question['presentation'],
                        'fieldName' => $question['name'],
                        'value' => empty($questionData[$userId][$question['name']]) ? null : $questionData[$userId][$question['name']],
                        'questionInfo' => $question,
                        'userId' => $userId
                    ));

                    OW::getEventManager()->trigger($event);

                    $eventValue = $event->getData();

                    if ( !empty($eventValue) )
                    {
                        $questionData[$userId][$question['name']] = $eventValue;

                        continue;
                    }


                    if ( !empty($questionData[$userId][$question['name']]) )
                    {
                        switch ( $question['presentation'] )
                        {
                            case BOL_QuestionService::QUESTION_PRESENTATION_CHECKBOX:

                                if ( (int) $questionData[$userId][$question['name']] === 1 )
                                {
                                    $questionData[$userId][$question['name']] = OW::getLanguage()->text('base', 'yes');
                                }
                                break;

                            case BOL_QuestionService::QUESTION_PRESENTATION_DATE:

                                $format = OW::getConfig()->getValue('base', 'date_field_format');

                                $value = 0;

                                switch ( $question['type'] )
                                {
                                    case BOL_QuestionService::QUESTION_VALUE_TYPE_DATETIME:

                                        $date = UTIL_DateTime::parseDate($questionData[$userId][$question['name']], UTIL_DateTime::MYSQL_DATETIME_DATE_FORMAT);

                                        if ( isset($date) )
                                        {
                                            $format = OW::getConfig()->getValue('base', 'date_field_format');
                                            $value = mktime(0, 0, 0, $date['month'], $date['day'], $date['year']);
                                        }

                                        break;

                                    case BOL_QuestionService::QUESTION_VALUE_TYPE_SELECT:

                                        $value = (int) $questionData[$userId][$question['name']];

                                        break;
                                }
                                $simpleDateFormat =  OW::getEventManager()->trigger(new OW_Event(IISEventManager::ON_RENDER_FORMAT_DATE_FIELD, array('timeStamp' => $value, 'isPresentationDate' => true)));
                                if($simpleDateFormat->getData() && isset($simpleDateFormat->getData()['jalaliSimpleFormat'])){
                                    $questionData[$userId][$question['name']] =  $simpleDateFormat->getData()['jalaliSimpleFormat'];
                                }
                                else {

                                    if ($format === 'dmy') {
                                        $questionData[$userId][$question['name']] = date("d/m/Y", $value);
                                    } else {
                                        $questionData[$userId][$question['name']] = date("m/d/Y", $value);
                                    }
                                }

                                break;

                            case BOL_QuestionService::QUESTION_PRESENTATION_BIRTHDATE:

                                $date = UTIL_DateTime::parseDate($questionData[$userId][$question['name']], UTIL_DateTime::MYSQL_DATETIME_DATE_FORMAT);
                                $questionData[$userId][$question['name']] = UTIL_DateTime::formatBirthdate($date['year'], $date['month'], $date['day']);

                                break;

                            case BOL_QuestionService::QUESTION_PRESENTATION_AGE:

                                $date = UTIL_DateTime::parseDate($questionData[$userId][$question['name']], UTIL_DateTime::MYSQL_DATETIME_DATE_FORMAT);
                                $questionData[$userId][$question['name']] = UTIL_DateTime::getAge($date['year'], $date['month'], $date['day']) . " " . $language->text('base', 'questions_age_year_old');

                                break;

                            case BOL_QuestionService::QUESTION_PRESENTATION_RANGE:

                                $range = explode('-', $questionData[$userId][$question['name']]);
                                $questionData[$userId][$question['name']] = $language->text('base', 'form_element_from') . " " . $range[0] . " " . $language->text('base', 'form_element_to') . " " . $range[1];

                                break;
                            case BOL_QuestionService::QUESTION_PRESENTATION_FSELECT:
                                $questionValue = (int) $questionData[$userId][$question['name']];
                                $parentName = $question['name'];
                                if ( !empty($question['parent']) )
                                {
                                    $parent = BOL_QuestionService::getInstance()->findQuestionByName($question['parent']);

                                    if ( !empty($parent) )
                                    {
                                        $parentName = $parent->name;
                                    }
                                }

                                $questionValues = BOL_QuestionService::getInstance()->findQuestionValues($parentName);
                                $value = array();

                                foreach ( $questionValues as $val )
                                {
                                    /* @var $val BOL_QuestionValue */
                                    if ( ( (int) $val->value ) == $questionValue  )
                                    {
                                        $value[$val->value] = BOL_QuestionService::getInstance()->getQuestionValueLang($val->questionName, $val->value);
                                    }
                                }

                                if ( !empty($value) )
                                {
                                    $questionData[$userId][$question['name']] = $value;
                                }

                                break;

                            case BOL_QuestionService::QUESTION_PRESENTATION_SELECT:
                            case BOL_QuestionService::QUESTION_PRESENTATION_RADIO:
                                $value = "";
                                $multicheckboxValue = (int) $questionData[$userId][$question['name']];

                                $parentName = $question['name'];

                                if ( !empty($question['parent']) )
                                {
                                    $parent = BOL_QuestionService::getInstance()->findQuestionByName($question['parent']);

                                    if ( !empty($parent) )
                                    {
                                        $parentName = $parent->name;
                                    }
                                }

                                $questionValues = BOL_QuestionService::getInstance()->findQuestionValues($parentName);
                                $value = array();

                                foreach ( $questionValues as $val )
                                {
                                    /* @var $val BOL_QuestionValue */
                                    if ( ( (int) $val->value ) == $multicheckboxValue )
                                    {
                                        /* if ( strlen($value) > 0 )
                                          {
                                          $value .= ', ';
                                          }

                                          $value .= $language->text('base', 'questions_question_' . $parentName . '_value_' . ($val->value)); */

                                        $value = BOL_QuestionService::getInstance()->getQuestionValueLang($val->questionName, $val->value);
                                    }
                                }

                                if ( !empty($value) )
                                {
                                    $questionData[$userId][$question['name']] = $value;
                                }
                                break;

                            case BOL_QuestionService::QUESTION_PRESENTATION_MULTICHECKBOX:
                                $value = "";
                                $multicheckboxValue = json_decode($questionData[$userId][$question['name']],true);
                                $parentName = $question['name'];

                                if ( !empty($question['parent']) )
                                {
                                    $parent = BOL_QuestionService::getInstance()->findQuestionByName($question['parent']);

                                    if ( !empty($parent) )
                                    {
                                        $parentName = $parent->name;
                                    }
                                }
                                $questionValues = BOL_QuestionService::getInstance()->findQuestionValues($parentName);

                                foreach ( $questionValues as $val )
                                {
                                    /* @var $val BOL_QuestionValue */
                                    if ( in_array((int) $val->value,$multicheckboxValue) )
                                    {
                                        $value  = $value .BOL_QuestionService::getInstance()->getQuestionValueLang($val->questionName, $val->value). ', ';
                                    }
                                }

                                if ( !empty($value) )
                                {
                                    $value = rtrim($value,', ');
                                    $questionData[$userId][$question['name']] = $value;
                                }

                                break;
                            case BOL_QuestionService::QUESTION_PRESENTATION_URL:
                            case BOL_QuestionService::QUESTION_PRESENTATION_TEXT:
                            case BOL_QuestionService::QUESTION_PRESENTATION_TEXTAREA:
                                if ( !is_string($questionData[$userId][$question['name']]) )
                                {
                                    break;
                                }

                                $value = trim($questionData[$userId][$question['name']]);

                                if ( strlen($value) > 0 )
                                {
                                    $questionData[$userId][$question['name']] = UTIL_HtmlTag::autoLink(nl2br($value));
                                }

                                break;
                        }

                        $pluginIisSecurity = BOL_PluginDao::getInstance()->findPluginByKey('iissecurityessentials');
                        if(isset($pluginIisSecurity) && $pluginIisSecurity->isActive()) {
                            $questionPrivacy = IISSECURITYESSENTIALS_BOL_Service::getInstance()->getQuestionPrivacy($userId, $question['id']);
                            if (IISSECURITYESSENTIALS_BOL_Service::$PRIVACY_FRIENDS_ONLY == $questionPrivacy && !isset($friendList[$userId])) {
                                unset($questionData[$userId][$question['name']]);
                            } else if (IISSECURITYESSENTIALS_BOL_Service::$PRIVACY_ONLY_FOR_ME == $questionPrivacy && $userId != OW::getUser()->getId()) {
                                unset($questionData[$userId][$question['name']]);
                            }
                        }
                    }else{
                        unset($questionData[$userId][$question['name']]);
                    }
                }
            }
            $mainEvent->setData(array('friendList' => $friendList, 'answerValues' => $questionData, 'questionNameList' => $questionNameList,
                'questionNameValues' =>$questionNameValues));
        }
    }


    /***
     *
     */
    public static function sanitizeValidExtList(){
        $unvalidList= ['swf'];
        if(OW::getConfig()->configExists('base', 'attch_ext_list')) {
            $validFileExtensions = json_decode(OW::getConfig()->getValue('base', 'attch_ext_list'), true);
            $newValidList = array();
            foreach ($validFileExtensions as $ext) {
                if (array_search($ext, $unvalidList) === false)
                    $newValidList[] = $ext;
            }
            OW::getConfig()->saveConfig('base', 'attch_ext_list', json_encode($newValidList));
        }
        else{
            $ext = array(
                'txt', 'doc', 'docx', 'sql', 'csv', 'xls', 'ppt', 'pdf',
                'jpg', 'jpeg', 'png', 'gif', 'bmp', 'psd', 'ai',
                'avi', 'wmv', 'mp3', '3gp', 'flv', 'mkv', 'mpeg', 'mpg',
                'zip', 'gz', 'tgz', 'gzip', '7z', 'bzip2', 'rar'
            );
            OW::getConfig()->addConfig('base', 'attch_ext_list', json_encode($ext));
        }
    }

    public static function checkCoreUpdate($db){
        $currentDbBuild = (int) $db->queryForColumn("SELECT `value` FROM `" . OW_DB_PREFIX . "base_config` WHERE `key` = 'base' AND `name` = 'soft_build'");
        $currentXmlInfo = (array) simplexml_load_file(OW_DIR_ROOT . 'ow_version.xml');
        if ( (int) $currentXmlInfo['build'] > $currentDbBuild )
        {
            return array("currentDbBuild" => $currentDbBuild , "currentXmlInfo" => $currentXmlInfo);
        }
        return null;
    }

    public static function updateCoreWithDefaultDb(){
        self::updateCore(OW::getDbo());
    }

    /***
     * @param $db
     * @return null|string
     */
    public static function updateCore($db){
        $checkCoreUpdate = self::checkCoreUpdate($db);
        $currentBuild = null;
        $currentXmlInfo = null;
        if ( $checkCoreUpdate != null )
        {
            $currentBuild = $checkCoreUpdate['currentDbBuild'];
            $currentXmlInfo = $checkCoreUpdate['currentXmlInfo'];
            $db->query("UPDATE `" . OW_DB_PREFIX . "base_config` SET `value` = 1 WHERE `key` = 'base' AND `name` = 'maintenance'");

            if(!defined('UPDATE_DIR_ROOT')){
                //define UPDATE_DIR_ROOT
                require_once OW_DIR_UTIL . 'file.php';
                define('UPDATE_DIR_ROOT', OW_DIR_ROOT . 'ow_updates' . DS);
                require_once UPDATE_DIR_ROOT . 'classes' . DS . 'autoload.php';
                require_once UPDATE_DIR_ROOT . 'classes' . DS . 'error_manager.php';
                require_once UPDATE_DIR_ROOT . 'classes' . DS . 'updater.php';
                spl_autoload_register(array('UPDATE_Autoload', 'autoload'));
                UPDATE_ErrorManager::getInstance(true);
                $autoloader = UPDATE_Autoload::getInstance();
                $autoloader->addPackagePointer('BASE_CLASS', OW_DIR_SYSTEM_PLUGIN . 'base' . DS . 'classes' . DS);
                $autoloader->addPackagePointer('UPDATE', UPDATE_DIR_ROOT . 'classes' . DS);
            }
            $owpUpdateDir = UPDATE_DIR_ROOT . 'updates' . DS;

            $updateDirList = array();

            $handle = opendir($owpUpdateDir);

            while ( ($item = readdir($handle)) !== false )
            {
                if ( $item === '.' || $item === '..' )
                {
                    continue;
                }

                $dirPath = $owpUpdateDir . ((int) $item);

                if ( OW::getStorage()->fileExists($dirPath) && OW::getStorage()->isDir($dirPath) )
                {
                    $updateDirList[] = (int) $item;
                }
            }

            sort($updateDirList);

            try {
                foreach ($updateDirList as $item) {
                    if ($item > $currentBuild) {
                        include($owpUpdateDir . $item . DS . 'update.php');
                        // $updateXmlInfo = (array) simplexml_load_file($owpUpdateDir . $item . DS . 'update.xml');
                        $db->query("UPDATE `" . OW_DB_PREFIX . "base_config` SET `value` = :build WHERE `key` = 'base' AND `name` = 'soft_build'", array('build' => $item));
                    }
                }

                $db->query("UPDATE `" . OW_DB_PREFIX . "base_config` SET `value` = :build WHERE `key` = 'base' AND `name` = 'soft_build'", array('build' => $currentXmlInfo['build']));
                $db->query("UPDATE `" . OW_DB_PREFIX . "base_config` SET `value` = :version WHERE `key` = 'base' AND `name` = 'soft_version'", array('version' => $currentXmlInfo['version']));
            } catch (Exception $e) {
                OW::getLogger()->writeLog(OW_Log::ERROR, 'update_failed', ['actionType'=>OW_Log::UPDATE, 'enType'=>'core', 'enId'=>$currentXmlInfo['version'], 'exception'=>$e]);
            }

            //automatically, add new translation keys on update
            IISSecurityProvider::updateLanguages(true, false, true);

            $db->query("UPDATE `" . OW_DB_PREFIX . "base_config` SET `value` = 0 WHERE `key` = 'base' AND `name` = 'update_soft'");
            $db->query("UPDATE `" . OW_DB_PREFIX . "base_config` SET `value` = 0 WHERE `key` = 'base' AND `name` = 'maintenance'");
            $db->query("UPDATE `" . OW_DB_PREFIX . "base_config` SET `value` = 1 WHERE `key` = 'base' AND `name` = 'dev_mode'");
        }

        if($currentXmlInfo != null && isset($currentXmlInfo['version'])){
            return $currentXmlInfo['version'];
        }

        return null;
    }

    /***
     * @param $db
     * @param bool $showResult
     */
    public static function updateAllPlugins($db, $showResult = true){
        $plugins = BOL_PluginService::getInstance()->findActivePlugins();
        $pluginKey = null;

        /* @var $plugin BOL_Plugin */
        foreach ( $plugins as $plugin )
        {
            if(!$plugin->isSystem()) {
                self::updatePlugin($db, $plugin->getKey(), false);
            }
        }

        if($showResult) {
            OW::getFeedback()->info(OW_Language::getInstance()->text('admin', 'manage_plugins_batch_update_success_message'));
        }
    }

    /***
     * @param $db
     * @param $pluginKey
     * @param bool $showResult
     */
    public static function updatePlugin($db, $pluginKey, $showResult = true){
        if ( !empty($pluginKey) )
        {
            $query = "SELECT * FROM `" . OW_DB_PREFIX . "base_plugin` WHERE `key` = :key";
            $result = $db->queryForRow($query, array('key' => trim($pluginKey)));

            // plugin not found
            if ( empty($result) )
            {
                if($showResult) {
                    OW::getFeedback()->warning(OW::getLanguage()->text('admin', 'manage_plugins_update_process_error'));
                }
            }
            else
            {
                $xmlInfoArray = (array) simplexml_load_file(OW_DIR_ROOT . 'ow_plugins' . DS . $result['module'] . DS . 'plugin.xml');

                if ( (int) $xmlInfoArray['build'] > (int) $result['build'] )
                {
                    $db->query("UPDATE `" . OW_DB_PREFIX . "base_config` SET `value` = 1 WHERE `key` = 'base' AND `name` = 'maintenance'");

                    $owpUpdateDir = OW_DIR_ROOT . 'ow_plugins' . DS . $result['module'] . DS . 'update' . DS;

                    $updateDirList = array();

                    try {
                        if ( OW::getStorage()->fileExists($owpUpdateDir) )
                        {
                            $handle = opendir($owpUpdateDir);

                            while ( ($item = readdir($handle)) !== false )
                            {
                                if ( $item === '.' || $item === '..' )
                                {
                                    continue;
                                }

                                if ( OW::getStorage()->fileExists($owpUpdateDir . ((int) $item)) && OW::getStorage()->isDir($owpUpdateDir . ((int) $item)) )
                                {
                                    $updateDirList[] = (int) $item;
                                }
                            }

                            sort($updateDirList);

                            foreach ( $updateDirList as $item )
                            {
                                if ( (int) $item > (int) $result['build'] )
                                {
                                    include($owpUpdateDir . $item . DS . 'update.php');
                                    $query = "UPDATE `" . OW_DB_PREFIX . "base_plugin` SET `build` = :build, `update` = 0 WHERE `key` = :key";
                                    $db->query($query, array('build' => (int) $item, 'key' => $result['key']));
                                }
                            }
                        }

                        $query = "UPDATE `" . OW_DB_PREFIX . "base_plugin` SET `build` = :build, `update` = 0, `title` = :title, `description` = :desc WHERE `key` = :key";
                        $db->query($query, array('build' => (int) $xmlInfoArray['build'], 'key' => $result['key'], 'title' => $xmlInfoArray['name'], 'desc' => $xmlInfoArray['description']));

                        if($showResult) {
                            OW::getFeedback()->info(OW::getLanguage()->text('admin', 'manage_plugins_update_success_message'));
                        }
                    } catch (Exception $e) {
                        OW::getLogger()->writeLog(OW_Log::ERROR, 'update_failed', ['actionType'=>OW_Log::UPDATE, 'enType'=>'plugin', 'enId'=>$pluginKey, 'exception'=>$e]);
                        if($showResult) {
                            OW::getFeedback()->error(OW::getLanguage()->text('admin', 'manage_plugins_update_process_error'));
                        }
                    }

                    //automatically, add new translation keys on update
                    Updater::getLanguageService()->updatePrefixForPlugin(trim($pluginKey), true);

                    $db->query("UPDATE `" . OW_DB_PREFIX . "base_config` SET `value` = 0 WHERE `key` = 'base' AND `name` = 'maintenance'");
                    $db->query("UPDATE `" . OW_DB_PREFIX . "base_config` SET `value` = 59 WHERE `key` = 'base' AND `name` = 'dev_mode'");
                }
                else
                {
                    $db->query("UPDATE `" . OW_DB_PREFIX . "base_plugin` SET `update` = 0 WHERE `key` = :key", array('key' => $result['key']));
                    if($showResult) {
                        OW::getFeedback()->warning(OW::getLanguage()->text('admin', 'manage_plugins_up_to_date_message'));
                    }
                }
            }
        }
    }

    /***
     * @param $db
     * @param $themeKey
     * @param bool $showResult
     */
    public static function updateTheme($db, $themeKey, $showResult = true){
        if ( !empty($themeKey) )
        {
            $query = "SELECT * FROM `" . OW_DB_PREFIX . "base_theme` WHERE `key` = :key";
            $result = $db->queryForRow($query, array('key' => trim($themeKey)));

            // theme not found
            if ( empty($result) )
            {
                $result_lang_key = 'manage_themes_update_process_error';
            }
            else
            {
                $xmlInfoArray = (array) simplexml_load_file(OW_DIR_ROOT . 'ow_themes' . DS . $result['key'] . DS . 'theme.xml');

                if ( (int) $xmlInfoArray['build'] > (int) $result['build'] )
                {
                    $db->query("UPDATE `" . OW_DB_PREFIX . "base_config` SET `value` = 1 WHERE `key` = 'base' AND `name` = 'maintenance'");

                    $query = "UPDATE `" . OW_DB_PREFIX . "base_theme` SET `update` = 0 WHERE `key` = :key";
                    $db->query($query, array('key' => $result['key']));

                    BOL_ThemeService::getInstance()->updateThemeInfo($result['key'], true);

                    $result_lang_key = 'manage_themes_update_success_message';

                    $db->query("UPDATE `" . OW_DB_PREFIX . "base_config` SET `value` = 0 WHERE `key` = 'base' AND `name` = 'maintenance'");
                    $db->query("UPDATE `" . OW_DB_PREFIX . "base_config` SET `value` = 1 WHERE `key` = 'base' AND `name` = 'dev_mode'");
                }
                else
                {
                    $db->query("UPDATE `" . OW_DB_PREFIX . "base_theme` SET `update` = 0 WHERE `key` = :key", array('key' => $result['key']));
                    $result_lang_key = 'manage_themes_up_to_date_message';
                }
            }

            if($showResult) {
                // update result actions
                OW::getFeedback()->info(OW::getLanguage()->text('admin', $result_lang_key));
            }
        }
    }

    public static function showCoreUpdateResult($version){
        if ( !empty($version) )
        {
            echo '
                  <!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
                  <html>
                  <head>
                  <title></title>
                  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
                  </head>
                  <body style="direction: rtl;text-align: right;">
                  <div style="width:400px;margin: 15vw auto 0;font: 14px tahoma;text-align: center;border: 1px #addaff solid;padding-bottom: 21px;border-radius: 6px;background-color: #f7fdff;line-height: 25px;">
                  <h3 style="color: #555;font:bold 20px Yekan;background-color: #addaff;margin: 0;padding-top: 10px;padding-bottom: 10px;margin-bottom: 10px;">به‌روزرسانی با موفقیت انجام شد.</h3>
                  نسخه شما با موفقیت به‌روزرسانی شد به نسخه <b>' . $version . '</b>!<br />
                  رفتن به <a style="color:#3366CC;" href="' . OW_URL_HOME . '">صفحه اصلی</a>&nbsp; یا &nbsp;<a style="color:#3366CC;" href="' . OW_URL_HOME . 'admin">پنل مدیریت</a>
                  </div>
                  </body>
                  </html>
             ';
        }
        else
        {
            echo '
                  <!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
                  <html>
                  <head>
                  <title></title>
                  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
                  </head>
                  <body style="direction: rtl;text-align: right;font:18px Yekan;">
                  <div style="width:400px;margin: 15vw auto 0;font: 14px tahoma;text-align: center;border: 1px #addaff solid;padding-bottom: 21px;border-radius: 6px;background-color: #f7fdff;line-height: 25px;">
                  <h3 style="color: #555;font:bold 20px Yekan;background-color: #addaff;margin: 0;padding-top: 10px;padding-bottom: 10px;margin-bottom: 10px;">درخواست به‌روزرسانی</h3>
                  نسخه شما به‌روز است. <br />
                  رفتن به <a style="color:#3366CC;" href="' . OW_URL_HOME . '">صفحه اصلی</a>&nbsp; یا &nbsp;<a style="color:#3366CC;" href="' . OW_URL_HOME . 'admin">پنل مدیریت</a>
                  </div>
                  </body>
                  </html>
                  ';
        }
    }

    public static function build_url_query_string( $url, array $paramsToUpdate = array(), $anchor = null )
    {
        $requestUrlArray = parse_url($url);

        $currentParams = array();

        if ( isset($requestUrlArray['query']) )
        {
            parse_str($requestUrlArray['query'], $currentParams);
        }

        $currentParams = array_merge($currentParams, $paramsToUpdate);

        return $requestUrlArray['scheme'] . '://' . $requestUrlArray['host'] . $requestUrlArray['path'] . '?' . http_build_query($currentParams) . ( $anchor === null ? '' : '#' . trim($anchor) );
    }

    /***
     * @param $destPluginFilesDir
     * @param $destUsersFilesDir
     */
    public static function copyInitialUsersAndPluginsFiles($destPluginFilesDir, $destUsersFilesDir){
        self::copyPluginFilesDir($destPluginFilesDir);
        self::copyUserFilesDir($destUsersFilesDir);
    }

    /***
     * @param $destPluginFilesDir
     */
    public static function copyPluginFilesDir($destPluginFilesDir){
        $sourcePluginFilesTmpDir = OW_DIR_ROOT . 'ow_iis' . DS . 'ow_pluginfiles_initial' . DS;
        OW::getStorage()->copyDir($sourcePluginFilesTmpDir, $destPluginFilesDir);
    }

    /***
     * @param $destUsersFilesDir
     */
    public static function copyUserFilesDir($destUsersFilesDir){
        $sourceUserFilesTmpDir = OW_DIR_ROOT . 'ow_iis' . DS . 'ow_userfiles_initial' . DS;
        OW::getStorage()->copyDir($sourceUserFilesTmpDir, $destUsersFilesDir);
    }

    public static function checkPluginActive($key, $return = false){
        $plugin = BOL_PluginDao::getInstance()->findPluginByKey($key);
        if(!isset($plugin) || !$plugin->isActive()) {
            if($return){
                return false;
            }else{
                throw new Redirect404Exception();
            }
        }
        return true;
    }

    public static function forwardPost($actionId,$sourceId,$selectedIds,$privacy=null,$visibility=null,$feedType,$forwardType,$isWebSevice=false)
    {
        $newsfeedService = NEWSFEED_BOL_Service::getInstance();
        $actionNewsfeed = $newsfeedService->findActionById($actionId);
        $actionData = json_decode($actionNewsfeed->data);
        $groupService = GROUPS_BOL_Service::getInstance();
        if (!isset($actionData) || !isset($actionData->content) || !isset($actionData->content->vars)|| !isset($feedType)) {
            if(!$isWebSevice) {
                throw new Redirect404Exception();
            }else{
                return array('valid' => false, 'message' => 'Error_set_data');
            }
        }
        if(isset($actionData->sourceUser))
        {
            $sourceUser = $actionData->sourceUser;
        }
        else if(isset($actionData->data->userId) && OW::getUser()->getId()!=$actionData->data->userId) {
            $userName = BOL_UserService::getInstance()->getDisplayName($actionData->data->userId);
            $userUrl = BOL_UserService::getInstance()->getUserUrl($actionData->data->userId);
            $sourceUser = OW::getLanguage()->text('iisnewsfeedplus','source_user_information',array('userUrl'=>$userUrl,'userName'=>$userName));

        }
        $status = strip_tags(nl2br($actionData->status));
        $status = str_replace('&#8235;', '', $status);
        if (!OW::getUser()->isAuthenticated()) {
            if(!$isWebSevice) {
                throw new Redirect404Exception();
            }else{
                return array('valid' => false, 'message' => 'authorization_error');
            }
        }

        $statusPrivacy = 'everybody';
        $statusVisibility='15';
        if(isset($privacy) && in_array($privacy, array('everybody', 'friends_only','only_for_me')))
        {
            $statusPrivacy=$privacy;
        }
        if(isset($visibility))
        {
            $statusVisibility=$visibility;
        }
        $userId = OW::getUser()->getId();

        /*
         * create new album and new photos for destination group(s)
         */
        foreach($selectedIds as $selectedId)
        {
            $attachId = null;
            if($forwardType=='groups') {
                $group = $groupService->findGroupById($selectedId);
                if (!isset($group)) {
                    continue;
                }
                $private = $group->whoCanView == GROUPS_BOL_Service::WCV_INVITE;
                $statusVisibility = $private
                    ? 14 // VISIBILITY_FOLLOW + VISIBILITY_AUTHOR + VISIBILITY_FEED
                    : 15; // Visible for all (15)
            }
            else if($forwardType=='user') {
                $iisSecurityEssentialPlugin = BOL_PluginDao::getInstance()->findPluginByKey('iissecurityessentials');
                /*
                 * disable selecting user who doesn't allow anyone to write on his/her wall
                 */
                if(isset($iisSecurityEssentialPlugin) && $iisSecurityEssentialPlugin->isActive()) {
                    $whoCanPostPrivacy = IISSECURITYESSENTIALS_BOL_Service::getInstance()->getActionValueOfPrivacy('who_post_on_newsfeed', $selectedId);
                    $statusPrivacy = $whoCanPostPrivacy;
                }
            }
            $content = '';
            /*
             * create new album and photo(s)
             */
            $entityType = 'user';
            $entityId = $userId;
            $feedId = $selectedId;
            $_POST['feedType'] = $forwardType;
            $_POST['feedId'] = $feedId;
            $_POST['visibility'] = $statusVisibility;
            $_POST['status'] = $status;
            $bundle = IISSecurityProvider::generateUniqueId('nfa-' . "feed1");

            /*
             * create new attachment(s) file
             */
            $newAttachment_feed_data = '';
            $previewIdList=array();
            if (isset($actionData->attachmentIdList)) {
                $newAttachmentIds = array();
                BOL_FileTemporaryService::getInstance()->deleteUserTemporaryFiles($userId);
                foreach ($actionData->attachmentIdList as $attachmentId) {
                    $attachment = BOL_AttachmentDao::getInstance()->findById((int)$attachmentId);
                    $attachmentPath = BOL_AttachmentService::getInstance()->getAttachmentsDir(). $attachment->fileName;
                    $fileExt = UTIL_File::getExtension($attachment->fileName);
                    $newAttachmentFileName =urldecode($attachment->origFileName);
                    $item = array();
                    $item['name'] = $newAttachmentFileName;
                    $item['type'] = 'image/'.$fileExt;
                    $item['error'] = 0;
                    $item['size'] = UTIL_File::getFileSize($attachmentPath,false);
                    $pluginKey = 'iisnewsfeedplus';
                    $tempFileId = BOL_FileTemporaryService::getInstance()->addTemporaryFile($attachmentPath,$newAttachmentFileName,$userId);
                    $item['tmp_name']=BOL_FileTemporaryService::getInstance()->getTemporaryFilePath($tempFileId);
                    $dtoArr =BOL_AttachmentService::getInstance()->processUploadedFile($pluginKey, $item, $bundle);
                    $newAttachmentIds[] = $dtoArr['dto']->id;
                    if(isset($actionData->previewIdList) && in_array($attachmentId,$actionData->previewIdList))
                    {
                        $previewIdList[]=$dtoArr['dto']->id;
                    }
                }

                $attachmentCount = 1;
                foreach ($newAttachmentIds as $newAttachmentId)
                {
                    if ($attachmentCount > 1) {
                        $newAttachment_feed_data = $newAttachment_feed_data . '-' . $attachmentCount . ':' . $newAttachmentId;
                    } else {
                        $newAttachment_feed_data = $attachmentCount . ':' . $newAttachmentId;
                    }
                    $attachmentCount++;
                }
                $_POST['attachment_feed_data']=$newAttachment_feed_data;


                /*
                 * get preview images from original post
                 */
                $previewCount = 1;
                $newPreviewIdList='';
                if(sizeof($previewIdList)>0) {
                    foreach ($previewIdList as $previewId) {
                        if ($previewCount > 1) {
                            $newPreviewIdList = $newPreviewIdList . '-' . $previewId;
                        } else {
                            $newPreviewIdList = $previewId;
                        }
                        $previewCount++;
                    }
                    $_POST['attachment_preview_data'] = $newPreviewIdList;
                }
            }
            $event = new OW_Event("feed.before_content_add", array(
                "feedType" => $_POST['feedType'],
                "feedId" => $_POST['feedId'],
                "visibility" => $_POST['visibility'],
                "userId" => $userId,
                "status" => $status,
                "type" => empty($content["type"]) ? "text" : $content["type"],
                "data" => $content
            ));

            OW::getEventManager()->trigger($event);

            $data = $event->getData();
            if (!empty($data)) {
                if (!empty($attachId)) {
                    BOL_AttachmentService::getInstance()->deleteAttachmentByBundle("newsfeed", $attachId);
                }
                $item = empty($data["entityType"]) || empty($data["entityId"])
                    ? null
                    : array(
                        "entityType" => $data["entityType"],
                        "entityId" => $data["entityId"]
                    );

                $eventIisGroupsPlusManager = new OW_Event('iisgroupsplus.on.update.group.status', array('feedId' => $_POST['feedId'],
                    'feedType' => $_POST['feedType'], 'status' => $_POST['status'], 'statusId' => $item['entityId']));
                OW::getEventManager()->trigger($eventIisGroupsPlusManager);

                if(!$isWebSevice) {
                    echo json_encode(array(
                        "item" => $item,
                        "message" => empty($data["message"]) ? null : $data["message"],
                        "error" => empty($data["error"]) ? null : $data["error"]
                    ));
                    exit;
                }else{
                    return array('valid' => empty($data["error"]) ? null : $data["error"], 'message' => empty($data["message"]) ? null : $data["message"]);
                }
            }
            $status = UTIL_HtmlTag::autoLink($status);

            $eventForward = new OW_Event('base.on.before.forward.status.create', array('actionData' => $actionData));
            OW::getEventManager()->trigger($eventForward);

            $actionAdditionalData = array(
                "content" => $content,
                "attachmentId" => $attachId,
                "sourceUser"=>isset($sourceUser)? $sourceUser : null
            );

            if($forwardType=='user')
                $actionAdditionalData['ReceiverId'] = $feedId;

            if(isset($eventForward->getData()['data'])){
                $actionAdditionalData = array_merge($eventForward->getData()['data']);
            }

            $out = NEWSFEED_BOL_Service::getInstance()
                ->addStatus(OW::getUser()->getId(), $_POST['feedType'], $_POST['feedId'], $_POST['visibility'], $status, $actionAdditionalData);

        }
        if(!$isWebSevice) {
            exit(json_encode(array('result' => true)));
        }else{
            return array('valid' => true, 'message' => 'Data post Successfully');
        }
    }


     public function onAfterRouteCheckRequest(OW_Event $event)
     {
         $mobileSupportEvent= OW::getEventManager()->trigger(new OW_Event('check.url.webservice',array()));
         if(isset($mobileSupportEvent->getData()['isWebService']) && $mobileSupportEvent->getData()['isWebService'])
         {
             return true;
         }
         $mobileEvent = OW::getEventManager()->trigger(new OW_Event(IISEventManager::IS_MOBILE_VERSION,array('check' => true)));
         $attrs = OW::getRequestHandler()->getHandlerAttributes();
         $cntrlArray=explode('_',$attrs[OW_RequestHandler::ATTRS_KEY_CTRL]);
         if(sizeof($cntrlArray)==3) {
             if ( $cntrlArray[1]=="MCTRL" && !OW::getRequest()->isAjax() && !$mobileEvent->getData()['isMobileVersion']) {
                 OW::getApplication()->redirect(OW::getRouter()->urlForRoute('base_page_404'));
             } else if ($cntrlArray[1]=="CTRL" && $mobileEvent->getData()['isMobileVersion']) {
                 OW::getApplication()->redirect(OW::getRouter()->urlForRoute('base_page_404'));
             }
         }
     }

    public function checkOwnerOfActionId(OW_Event $event)
    {
        $newsfeedPlugin = BOL_PluginDao::getInstance()->findPluginByKey('newsfeed');
        if(!isset($newsfeedPlugin) || !$newsfeedPlugin->isActive()) {
            return;
        }
        $params=$event->getParams();
        if(!isset($params['entityId']) || !isset($params['pluginKey']))
        {
            return;
        }
        switch($params['pluginKey'])
        {
            case 'photo':
                $photoPlugin = BOL_PluginDao::getInstance()->findPluginByKey('photo');
                if(!isset($photoPlugin) || !$photoPlugin->isActive()) {
                    return;
                }
                $photoService = PHOTO_BOL_PhotoService::getInstance();
                $photo = $photoService->findPhotoById($params['entityId']);
                $action = NEWSFEED_BOL_ActionDao::getInstance()->findAction('multiple_photo_upload',$photo->uploadKey);
                if(isset($action))
                {
                    $actionData=json_decode($action->data,true);
                    if(isset($actionData['photoIdList']) && in_array($params['entityId'],$actionData['photoIdList'])) {
                        $album = PHOTO_BOL_PhotoAlbumService::getInstance()->findAlbumById($photo->albumId);
                    }
                }
                if(isset($album)) {
                    $event->setData(array('ownerId' => $album->userId));
                }
                break;
            default:
                return;
        }

    }

    public static function getStaticPepper()
    {
        if(defined('OW_PASSWORD_PEPPER')) {
            return OW_PASSWORD_PEPPER;
        }else{
            return OW_PASSWORD_SALT;
        }
    }

    public function autoLoginCookieUpdate(OW_Event $event){
        $params = $event->getParams();
        if(isset($params['day'])){
            $day = $params['day'];
            $mobileEvent = OW::getEventManager()->trigger(new OW_Event(IISEventManager::IS_MOBILE_VERSION,array('check' => true)));
            if(isset($mobileEvent->getData()['isMobileVersion'])&& $mobileEvent->getData()['isMobileVersion']==true) {
                $day = $this->COOKIE_SAVE_DAY;
            }
            $event->setData(array('day' => $day));
        }
    }

    public static function addMediaElementPlayerAfterRender(){
        $attr = OW::getRequestHandler()->getHandlerAttributes();
        if( $attr[OW_RequestHandler::ATTRS_KEY_CTRL]=='VIDEO_CTRL_Add' || $attr[OW_RequestHandler::ATTRS_KEY_CTRL]=='VIDEO_MCTRL_Add') {
            return;
        }
        if(($attr[OW_RequestHandler::ATTRS_KEY_CTRL]=='VIDEO_CTRL_Video' || $attr[OW_RequestHandler::ATTRS_KEY_CTRL]=='VIDEO_MCTRL_Video')  && $attr[OW_RequestHandler::ATTRS_KEY_ACTION]=='edit')
        {
            return;
        }
        OW::getDocument()->addStyleSheet(OW::getPluginManager()->getPlugin('base')->getStaticCssUrl() . 'mediaelementplayer.css');
        OW::getDocument()->addScript(OW::getPluginManager()->getPlugin('base')->getStaticJsUrl() . 'mediaelement-and-player.js');
        OW::getDocument()->addOnloadScript('$(\'video\').mediaelementplayer();');
        OW::getDocument()->addOnloadScript('$(\'audio\').mediaelementplayer();');
    }

    public static function generateUniqid($prefix = "", $more_entropy = false) {
        return self::generateUniqueId($prefix, $more_entropy);
    }

    public static function generateUniqueId($prefix = "", $more_entropy = false){
        if (PHP_OS === "WINNT") {
            $prefix = $prefix . rand(0, 1000);
        }
        return uniqid($prefix, $more_entropy);
    }

    public function editConsoleItemContent(OW_Event $event){
        $params = $event->getParams();
        if(!isset($params['item'])) {
            return;
        }
        $item = $params['item'];
        $regex = '#<\s*?a\b[^>]*>(.*?)</a\b[^>]*>#s';
        preg_match($regex, $item['html'], $matches);
        if(count($matches)>1 && $matches[1]!=OW::getLanguage()->text("base","forgot_password_label") && $matches[1]!=OW::getLanguage()->text("base","console_item_sign_up_label")) {
            $item['title'] = $matches[1];
            $event->setData(array('item' => $item));
        }
    }

    public static function getDomTextContent($text){
        if(strpos($text, '<') !== false) {
            //DomDocument
            $text = '<div>'.$text.'</div>';
            $doc = new DOMDocument();
            @$doc->loadHTML(mb_convert_encoding($text, 'HTML-ENTITIES', 'UTF-8'));
            //$domDoc1 = preg_replace('~<(?:!DOCTYPE|/?(?:html|head|body))[^>]*>\s*~i', '', $doc->saveHTML());

            # remove <!DOCTYPE
            $doc->removeChild($doc->doctype);
            # remove <html><body></body></html>
            /*** get the links from the HTML ***/

            $links = $doc->getElementsByTagName('a');
            $linksReplaces = IISSecurityProvider::getLinkReplaces($links);

            $doc = IISSecurityProvider::getCircleBulletsReplaces($doc);
            $doc = IISSecurityProvider::getNumberBulletsReplaces($doc);

            $element = $doc->firstChild->firstChild->firstChild;
            $text = $element->textContent;

            foreach ($linksReplaces as $key => $value) {
                $text = str_replace($key, $value, $text);
            }
        }
        return $text;
    }

    public static function getLinkReplaces($links){
        $replaces = array();

        /*** loop over the links ***/
        foreach ($links as $tag)
        {
            if(isset($tag->childNodes) && isset($tag->childNodes->item(0)->nodeValue)) {
                $innerText = $tag->childNodes->item(0)->nodeValue;
                if (strpos($innerText, '...')) {
                    $arr = parse_url($innerText);
                    if (isset($arr['scheme'])) {
                        $replaces[$tag->childNodes->item(0)->nodeValue] = $tag->getAttribute('href');
                    }
                }
            }
        }
        return $replaces;
    }

    /***
     * @param DOMDocument $doc
     * @return DOMDocument
     */
    public static function getNumberBulletsReplaces($doc){
        foreach ($doc->getElementsByTagName('ol') as $bulletsTag)
        {
            if(isset($bulletsTag->childNodes) && isset($bulletsTag->childNodes)) {
                $index = 1;
                foreach ($bulletsTag->childNodes as $bullet) {
                    $checkString = IISSecurityProvider::removeNewLine($bullet->nodeValue);
                    if ($checkString != ''){
                        $bullet->nodeValue = $index.'. ' . $bullet->nodeValue ;
                        $index++;
                    }
                }
            }
        }
        return $doc;
    }

    /***
     * @param DOMDocument $doc
     * @return DOMDocument
     */
    public static function getCircleBulletsReplaces($doc){
        foreach ($doc->getElementsByTagName('ul') as $bulletsTag)
        {
            if(isset($bulletsTag->childNodes) && isset($bulletsTag->childNodes)) {
                foreach ($bulletsTag->childNodes as $bullet) {
                    $checkString = IISSecurityProvider::removeNewLine($bullet->nodeValue);
                    if ($checkString != ''){
                        $bullet->nodeValue = '&#x2022; ' . $bullet->nodeValue ;
                    }
                }
            }
        }
        return $doc;
    }

    public static function removeNewLine($checkString){
        $checkString = preg_replace("'\r'","", $checkString);
        $checkString = preg_replace("'\n '","", $checkString);
        $checkString = preg_replace("'\n '","", $checkString);
        $checkString = preg_replace("' '","", $checkString);
        $checkString = trim($checkString);
        $checkString = trim($checkString, '\n');
        return $checkString;
    }

    public static function themeCoreDetector(){
        $themeManager = OW::getThemeManager();
        $currentThemeKey =$themeManager->getSelectedTheme()->getDto()->key;
        $currentThemeXML = BOL_ThemeService::getInstance()->getThemeXmlInfoForKey($currentThemeKey);
        if (!(isset($currentThemeXML['coreGeneration']))){
            return false;
        }else{
            return true;
        }
    }

}