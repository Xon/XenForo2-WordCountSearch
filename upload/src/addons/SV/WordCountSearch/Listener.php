<?php
namespace SV\WordCountSearch;


use XF\Template\Templater;

class Listener
{
    public static function orderMacroPreRender(Templater $templater, &$type, &$template, &$name, array &$arguments, array &$globalVars)
    {
        $arguments['options']['word_count'] = \XF::phrase('word_count');
    }
}
