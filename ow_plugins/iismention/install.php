<?php

/**
 * iismention
 */
/**
 * @author Issa Annamoradnejad <i.moradnejad@gmail.com>
 * @package ow_plugins.iismention
 * @since 1.0
 */

if ( !OW::getConfig()->configExists('iismention', 'max_count') )
{
    OW::getConfig()->addConfig('iismention', 'max_count', 5, 'Mention Max Count');
}
