<?php

# remove useless notifications

$sql = "DELETE FROM  `".OW_DB_PREFIX."notifications_notification` WHERE `entityType`='user_invitation' AND `action`='groups-invitation' AND `viewed`=0;";
Updater::getDbo()->query($sql);