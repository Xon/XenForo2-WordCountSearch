<?php

namespace SV\WordCountSearch;

use XF\Template\Templater;

abstract class Listener
{

    /** @noinspection PhpUnusedParameterInspection */
    public static function orderMacroPreRender(Templater $templater, string &$type, string &$template, string &$name, array &$arguments, array &$globalVars): void
    {
        $arguments['options']['word_count'] = \XF::phrase('word_count');
    }
}
