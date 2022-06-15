<?php

/**
 * Search component class.
 *
 * @author Yaser Alimardany <yaser.alimardany@gmail.com>
 * @package ow_plugins.iisadvancesearch.classes
 * @since 1.0
 */
class IISADVANCESEARCH_CMP_Search extends OW_Component
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->assign('searchActionUrl', OW::getRouter()->urlForRoute('iisadvancesearch.search'));
    }
}