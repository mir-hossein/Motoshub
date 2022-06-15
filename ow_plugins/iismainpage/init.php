<?php
/**
 * iismainpage
 */
/**
 * @author Yaser Alimardany <yaser.alimardany@gmail.com>
 * @package ow_plugins.iismainpage
 * @since 1.0
 */

OW::getRouter()->addRoute(new OW_Route('iismainpage.admin', 'iismainpage/admin', 'IISMAINPAGE_CTRL_Admin', 'index'));
OW::getRouter()->addRoute(new OW_Route('iismainpage.admin.ajax-save-order', 'iismainpage/admin/ajax-save-order', 'IISMAINPAGE_CTRL_Admin', 'ajaxSaveOrder'));