<?php

/**
 * Smarty modifier to render hashtaga/mentions/emojies.
 *
 * @author Issa Annamoradnejad <i.moradnejad@gmail.com>
 * @package ow.ow_smarty.plugin
 * @since 1.0
 * @param $string
 * @return string
 */
function smarty_modifier_prettify( $string )
{
    $stringRenderer = OW::getEventManager()->trigger(new OW_Event('hashtag.before_render_string', array('string' => $string)));
    if (isset($stringRenderer->getData()['string'])) {
        $string = ($stringRenderer->getData()['string']);
    }
    return $string;
}
