<?php

if (!OW::getConfig()->configExists('iisgroupsplus', 'groupFileAndJoinFeed')){
    OW::getConfig()->addConfig('iisgroupsplus', 'groupFileAndJoinFeed', '["fileFeed","joinFeed"]');
}

