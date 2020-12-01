<?php

namespace SV\WordCountSearch\XF\Repository;



/**
 * Extends \XF\Repository\Thread
 */
class Thread extends XFCP_Thread
{
    public function getDefaultThreadListSortOptions($forAdminConfig): array
    {
        $options = parent::getDefaultThreadListSortOptions($forAdminConfig);

        $options['word_count'] = 'word_count';

        return $options;
    }
}