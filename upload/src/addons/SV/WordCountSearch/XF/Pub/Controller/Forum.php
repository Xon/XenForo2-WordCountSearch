<?php

namespace SV\WordCountSearch\XF\Pub\Controller;

/**
 * Class Forum
 *
 * @package SV\WordCountSearch\XF\Pub\Controller
 */
class Forum extends XFCP_Forum
{
    /**
     * @param \XF\Entity\Forum $forum
     *
     * @return array
     */
    protected function getForumFilterInput(\XF\Entity\Forum $forum)
    {
        $filters = parent::getForumFilterInput($forum);

        $input = $this->filter([
            'min_word_count' => 'int',
            'max_word_count' => 'int'
        ]);

        if ($input['min_word_count'])
        {
            $filters['min_word_count'] = $input['min_word_count'];
        }

        if ($input['max_word_count'])
        {
            $filters['max_word_count'] = $input['max_word_count'];
        }

        return $filters;
    }

    /**
     * @param \XF\Entity\Forum  $forum
     * @param \XF\Finder\Thread $threadFinder
     * @param array             $filters
     */
    protected function applyForumFilters(\XF\Entity\Forum $forum, \XF\Finder\Thread $threadFinder, array $filters)
    {
        parent::applyForumFilters($forum, $threadFinder, $filters);

        if (!empty($filters['min_word_count']))
        {
            $threadFinder->where('word_count', '>=', $filters['min_word_count']);
        }

        if (!empty($filters['max_word_count']))
        {
            $threadFinder->where('word_count', '<=', $filters['max_word_count']);
        }
    }
}