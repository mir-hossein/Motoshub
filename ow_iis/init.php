<?php
/**
 * User: Hamed Tahmooresi
 * Date: 1/5/2016
 * Time: 3:31 PM
 */
require_once 'event' . DS . 'manager.php';

$iisSecurityProvider = IISSecurityProvider::getInstance();


//OW::getEventManager()->bind(IISEventManager::ON_AFTER_SESSION_START, array($iisSecurityProvider, 'secureOxwallAgainstCSRF'));
OW::getEventManager()->bind(OW_EventManager::ON_AFTER_PLUGIN_INSTALL, array($iisSecurityProvider, 'createBackupTables'));
OW::getEventManager()->bind(IISEventManager::ON_DATA_BACKUP_DELETE, array($iisSecurityProvider, 'deleteBackupData'));
OW::getEventManager()->bind(IISEventManager::ON_AFTER_SQL_IMPORT_IN_INSTALLING, array($iisSecurityProvider, 'createBackupTables'));
OW::getEventManager()->bind(IISEventManager::ON_AFTER_INSTALLATION_COMPLETED, array($iisSecurityProvider, 'installComplete'));
OW::getEventManager()->bind(IISEventManager::ON_BEFORE_INSTALL_EXTENSIONS_CHECK, array($iisSecurityProvider, 'checkDiff'));
OW::getEventManager()->bind('base.mail_service.send.check_mail_state', array($iisSecurityProvider, 'onBeforeEmailSend'));
OW::getEventManager()->bind(IISEventManager::ON_ALBUM_DEFAULT_COVER_SET, array($iisSecurityProvider, 'setAlbumCoverDefault'));
OW::getEventManager()->bind(IISEventManager::ON_BEFORE_ERROR_RENDER, array($iisSecurityProvider, 'onBeforeErrorRender'));
OW::getEventManager()->bind(IISEventManager::BEFORE_ALTER_QUERY_EXECUTED, array($iisSecurityProvider, 'onBeforeAlterQueryExecuted'));
OW::getEventManager()->bind(IISEventManager::AFTER_QUERY_EXECUTED, array($iisSecurityProvider, 'onAfterQueryExecuted'));
OW::getEventManager()->bind(IISEventManager::ON_BEFORE_ACTIONS_LIST_RETURN, array($iisSecurityProvider, 'onBeforeActionsListReturn'));
OW::getEventManager()->bind(OW_EventManager::ON_AFTER_PLUGIN_UNINSTALL, array($iisSecurityProvider, 'onAfterPluginUnistall'));
OW::getEventManager()->bind(IISEventManager::CHECK_MASTER_PAGE_BLANK_HTML_FOR_UPLOAD_IMAGE_FORM, array($iisSecurityProvider, 'checkMasterPageBlankHtml'));
OW::getEventManager()->bind(IISEventManager::ON_BEFORE_CURRENCY_FIELD_APPEAR, array($iisSecurityProvider, 'decideToShowCurrencySetting'));
OW::getEventManager()->bind(IISEventManager::CORRECT_MULTIPLE_LANGUAGE_SENTENCE_ALIGNMENT, array($iisSecurityProvider, 'multipleLanguageSentenceAlignmentCorrection'));
OW::getEventManager()->bind(IISEventManager::ON_VALIDATE_HTML_CONTENT, array($iisSecurityProvider, 'validateHtmlContent'));
OW::getEventManager()->bind(IISEventManager::BEFORE_ALLOW_CUSTOMIZATION_CHANGED, array($iisSecurityProvider, 'beforeAllowCustomizationChanged'));
OW::getEventManager()->bind(IISEventManager::BEFORE_CUSTOMIZATION_PAGE_RENDERER, array($iisSecurityProvider, 'beforeCustomizationPageRenderer'));
OW::getEventManager()->bind(IISEventManager::PARTIAL_HALF_SPACE_CODE_DISPLAY_CORRECTION, array($iisSecurityProvider, 'partialHalfSpaceCodeCorrection'));
OW::getEventManager()->bind(IISEventManager::PARTIAL_SPACE_CODE_DISPLAY_CORRECTION, array($iisSecurityProvider, 'partialSpaceCodeCorrection'));
OW::getEventManager()->bind(IISEventManager::HTML_ENTITY_CORRECTION, array($iisSecurityProvider, 'htmlEntityCorrection'));
OW::getEventManager()->bind(IISEventManager::DISTINGUISH_REQUIRED_FIELD, array($iisSecurityProvider, 'setDistinguishForRequiredField'));
OW::getEventManager()->bind(IISEventManager::ON_AFTER_NEWSFEED_STATUS_STRING_READ, array($iisSecurityProvider, 'onAfterNewsFeedStatusStringRead'));
OW::getEventManager()->bind(IISEventManager::ON_BEFORE_NEWSFEED_STATUS_STRING_WRITE, array($iisSecurityProvider, 'onBeforeNewsFeedStatusStringWrite'));
OW::getEventManager()->bind(IISEventManager::ON_AFTER_NOTIFICATION_STRING_READ, array($iisSecurityProvider, 'onAfterNotificationDataRead'));
OW::getEventManager()->bind(IISEventManager::ON_BEFORE_NOTIFICATION_STRING_WRITE, array($iisSecurityProvider, 'onBeforeNotificationDataWrite'));
OW::getEventManager()->bind(IISEventManager::ON_AFTER_GET_TPL_DATA , array($iisSecurityProvider, 'onAfterGetTplData'));
OW::getEventManager()->bind(IISEventManager::ON_BEFORE_FORUM_SECTIONS_RETURN , array($iisSecurityProvider, 'onBeforeForumSectionsReturn'));
OW::getEventManager()->bind(IISEventManager::ON_BEFORE_FORUM_ADVANCE_SEARCH_QUERY_EXECUTE , array($iisSecurityProvider, 'onBeforeForumAdvanceSearchQueryExecute'));
OW::getEventManager()->bind(IISEventManager::IS_MOBILE_VERSION , array($iisSecurityProvider, 'isMobileVersion'));
OW::getEventManager()->bind(IISEventManager::ON_BEFORE_PHOTO_TEMPORARY_PATH_RETURN , array($iisSecurityProvider, 'checkPhotoExtension'));
OW::getEventManager()->bind(IISEventManager::VALIDATE_UPLOADED_FILE_NAME, array($iisSecurityProvider, 'validateUploadedFileName'));
OW::getEventManager()->bind(OW_EventManager::ON_USER_REGISTER, array($iisSecurityProvider, 'setDefaultTimeZoneForUser'));
OW::getEventManager()->bind(IISEventManager::ON_BEFORE_URL_IMAGE_ADD_ON_CHECK_LINK, array($iisSecurityProvider, 'checkImageExtenstionForAddAsImagesOfUrl'));
OW::getEventManager()->bind(IISEventManager::ENABLE_DESKTOP_OFFLINE_CHAT, array($iisSecurityProvider, 'enableDesktopOfflineChat'));
OW::getEventManager()->bind(IISEventManager::USER_LIST_FRIENDSHIP_STATUS, array($iisSecurityProvider, 'userListFriendshipStatus'));
OW::getEventManager()->bind(IISEventManager::BEFORE_CHECK_URI_REQUEST, array($iisSecurityProvider, 'beforeCheckUriRequest'));
OW::getEventManager()->bind(IISEventManager::ON_BEFORE_AUTOLOGIN_COOKIE_UPDATE, array($iisSecurityProvider, 'autoLoginCookieUpdate'));
OW::getEventManager()->bind(OW_EventManager::ON_AFTER_REQUEST_HANDLE, array($iisSecurityProvider, 'onAfterRouteCheckRequest'));
OW::getEventManager()->bind(IISEventManager::CHECK_OWNER_OF_ACTION_ID, array($iisSecurityProvider, 'checkOwnerOfActionId'));
OW::getEventManager()->bind(OW_EventManager::ON_BEFORE_DOCUMENT_RENDER, array($iisSecurityProvider, 'addMediaElementPlayerAfterRender'));
OW::getEventManager()->bind(IISEventManager::ON_BEFORE_CONSOLE_ITEM_RENDER, array($iisSecurityProvider, 'editConsoleItemContent'));
OW::getEventManager()->bind('base.mail_service.send.check_mail_state', array($iisSecurityProvider, 'checkRecipientsSuspended'));


IISSecurityProvider::updateDefaultTheme();

//require_once OW_DIR_ROOT.'ow_system_plugins'.DS.'base'.DS.'classes'.DS.'cache_backend_mysql.php';
//OW::getCacheManager()->setCacheEnabled(true);
//OW::getCacheManager()->setCacheBackend(new BASE_CLASS_CacheBackendMysql(OW::getDbo()));
