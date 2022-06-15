<?php

/**
 * iisphotoplus
 */

IISPHOTOPLUS_CLASS_EventHandler::getInstance()->init();
OW::getRouter()->addRoute(new OW_Route('iisphotoplus.ajax_upload_submit', 'iisphotoplus/ajax-upload-submit', 'IISPHOTOPLUS_CTRL_AjaxUpload', 'ajaxSubmitPhotos'));