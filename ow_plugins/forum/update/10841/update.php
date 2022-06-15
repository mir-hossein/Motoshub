<?php

try
{
    $dbo = Updater::getDbo();
    $logger = Updater::getLogger();
    $query = 'drop index `post_text` on `'.OW_DB_PREFIX.'forum_post`';
    $dbo->query($query);

    $query = 'drop index `topic_title` on `'.OW_DB_PREFIX.'forum_topic`';
    $dbo->query($query);
}
catch (Exception $e)
{
    $logger->addEntry(json_encode($e));
}
