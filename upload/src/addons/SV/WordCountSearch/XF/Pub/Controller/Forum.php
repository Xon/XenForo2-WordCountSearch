<?php
/**
 * @noinspection PhpMissingReturnTypeInspection
 */

namespace SV\WordCountSearch\XF\Pub\Controller;

use SV\WordCountSearch\Repository\WordCount as WordCountRepo;
use XF\Finder\Thread as ThreadFinder;

/**
 * Class Forum
 *
 * @package SV\WordCountSearch\XF\Pub\Controller
 */
class Forum extends XFCP_Forum
{
    protected function getAvailableForumSorts(\XF\Entity\Forum $forum)
    {
        $sorts = parent::getAvailableForumSorts($forum);

        $sorts['word_count'] = 'word_count';

        return $sorts;
    }

    /**
     * @param \XF\Entity\Forum $forum
     * @return array
     */
    protected function getForumFilterInput(\XF\Entity\Forum $forum)
    {
        $filters = parent::getForumFilterInput($forum);

        if (WordCountRepo::get()->isThreadWordCountSupported($forum))
        {
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
        }

        return $filters;
    }

    /**
     * @param \XF\Entity\Forum $forum
     * @param ThreadFinder     $threadFinder
     * @param array            $filters
     */
    protected function applyForumFilters(\XF\Entity\Forum $forum, ThreadFinder $threadFinder, array $filters)
    {
        parent::applyForumFilters($forum, $threadFinder, $filters);

        if (WordCountRepo::get()->isThreadWordCountSupported($forum))
        {
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
}