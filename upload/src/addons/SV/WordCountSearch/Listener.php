<?php

namespace SV\WordCountSearch;

use XF\Template\Templater;

/**
 * Class Listener
 *
 * @package SV\WordCountSearch
 */
class Listener
{
    /**
     * @param Templater $templater
     * @param           $type
     * @param           $template
     * @param           $name
     * @param array     $arguments
     * @param array     $globalVars
     */
    public static function orderMacroPreRender(/** @noinspection PhpUnusedParameterInspection */ Templater $templater, &$type, &$template, &$name, array &$arguments, array &$globalVars)
    {
        $arguments['options']['word_count'] = \XF::phrase('word_count');
    }
}
