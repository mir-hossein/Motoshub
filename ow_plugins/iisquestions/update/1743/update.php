<?php

$optionDao = IISQUESTIONS_BOL_OptionDao::getInstance();
$questionDao = IISQUESTIONS_BOL_QuestionDao::getInstance();
$query = "DELETE `o` FROM `{$optionDao->getTableName()}` as `o`  
            LEFT JOIN `{$questionDao->getTableName()}` as `q` ON 
            ( `o`.`questionId` = `q`.`id` ) WHERE
            `q`.`entityId` IS NULL AND `q`.`entityType` IS NULL";

OW::getDbo()->query($query);
$query = "DELETE FROM `{$questionDao->getTableName()}` WHERE `entityId` IS NULL AND `entityType` IS NULL";
OW::getDbo()->query($query);

