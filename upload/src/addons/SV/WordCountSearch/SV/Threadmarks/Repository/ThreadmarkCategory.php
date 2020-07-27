<?php

namespace SV\WordCountSearch\SV\Threadmarks\Repository;

use XF\Mvc\Entity\Entity;

/**
 * Class ThreadmarkCategory
 *
 * @package SV\WordCountSearch\SV\Threadmarks\Repository
 */
class ThreadmarkCategory extends XFCP_ThreadmarkCategory
{
    /**
     * @param Entity $container
     *
     * @return array
     */
    public function getThreadmarkCategoryData(Entity $container)
    {
        if (!$container instanceof \XF\Entity\Thread)
        {
            return parent::getThreadmarkCategoryData($container);
        }

        $db = $this->db();

        return $db->fetchAllKeyed('
            SELECT threadmark_category_id, MAX(threadmark.position) AS position, CAST(SUM(COALESCE(post_word.word_count, 0)) AS SIGNED) AS word_count
            FROM xf_sv_threadmark AS threadmark
            LEFT JOIN xf_post_words AS post_word ON (threadmark.content_id = post_word.post_id AND threadmark.content_type = ?)
            WHERE threadmark.container_id = ?
              AND threadmark.container_type = ?
              AND threadmark.message_state = \'visible\'
            GROUP BY threadmark.threadmark_category_id
            ORDER BY threadmark.threadmark_category_id
        ', 'threadmark_category_id', ['post', $container->getEntityId(), $container->getEntityContentType()]);
    }
}