<?php
OW::getRouter()->addRoute(new OW_Route('iismobilesupport-index', 'mobile/service/:key', "IISMOBILESUPPORT_MCTRL_Service", 'index'));
OW::getRouter()->addRoute(new OW_Route('iismobilesupport-use-mobile', 'mobile/use_mobile_only', "IISMOBILESUPPORT_MCTRL_Service", 'useMobile'));
OW::getRouter()->addRoute(new OW_Route('iismobilesupport-notifications', 'notifications', 'IISMOBILESUPPORT_MCTRL_Service', 'notifications'));
OW::getRouter()->addRoute(new OW_Route('iismobilesupport-web-service-get-information', 'mobile/services/information/:type', "IISMOBILESUPPORT_MCTRL_Service", 'getInformation'));
OW::getRouter()->addRoute(new OW_Route('iismobilesupport-web-service-get-information-without-type', 'mobile/services/information', "IISMOBILESUPPORT_MCTRL_Service", 'getInformation'));
OW::getRouter()->addRoute(new OW_Route('iismobilesupport-web-service-action', 'mobile/services/action/:type', "IISMOBILESUPPORT_MCTRL_Service", 'action'));
OW::getRouter()->addRoute(new OW_Route('iismobilesupport-web-service-action-without-type', 'mobile/services/action', "IISMOBILESUPPORT_MCTRL_Service", 'action'));
OW::getRouter()->addRoute(new OW_Route('iismobilesupport-latest-version', 'mobile-app/latest/:type', "IISMOBILESUPPORT_CTRL_Service", 'downloadLatestVersion'));
IISMOBILESUPPORT_MCLASS_EventHandler::getInstance()->init();