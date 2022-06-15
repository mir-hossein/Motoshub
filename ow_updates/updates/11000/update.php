<?php


$query1=" UPDATE  `" . OW_DB_PREFIX . "base_question` SET `type`='fselect' where `type`='select' AND `presentation`='select' ";

Updater::getDbo()->query($query1);


$query2=" UPDATE  `" . OW_DB_PREFIX . "base_question` SET `presentation`='fselect' where `type`='fselect' AND `presentation`='select' ";

Updater::getDbo()->query($query2);
