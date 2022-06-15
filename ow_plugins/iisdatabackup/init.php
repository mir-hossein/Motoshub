<?php

/**
 * Copyright (c) 2016, Yaser Alimardany
 * All rights reserved.
 */

OW::getRouter()->addRoute(new OW_Route('iisdatabackup.admin', 'iisdatabackup/admin', 'IISDATABACKUP_CTRL_Admin', 'index'));
OW::getRouter()->addRoute(new OW_Route('iisdatabackup.admin.data', 'iisdatabackup/admin/data', 'IISDATABACKUP_CTRL_Admin', 'data'));