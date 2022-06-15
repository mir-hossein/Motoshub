<?php
Updater::getSeoService()->addSitemapEntity('iisnews', 'iisnews_sitemap', 'iisnews', array(
    'news'
));
Updater::getLanguageService()->importPrefixFromZip(dirname(__FILE__) . DS . 'langs.zip', 'iisnews');
