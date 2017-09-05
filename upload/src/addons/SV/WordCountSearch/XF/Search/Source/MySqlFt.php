<?php
namespace SV\WordCountSearch\XF\Search\Source;

use SV\WordCountSearch\XF\Search\Query\RangeMetadataConstraint;
use XF\Search\Query\Query;
use XF\Search\IndexRecord;

class MySqlFt extends XFCP_MySqlFt
{
    protected $_last_word_count = null;

    public function index(IndexRecord $record)
    {
        $this->_last_word_count = empty($record->metadata['word_count']) ? null : $record->metadata['word_count'];
        unset($record->metadata['word_count']);

        $bulkIndexingOld = $this->bulkIndexing;
        $this->bulkIndexing = true;
        try
        {
            parent::index($record);
            // index may call flushBulkIndexing, so we need to touch up the last record
            $this->_mangleLastBulkInsert();
            // only flush IFF we have something to flush and are not in bulk-insert mode already
            if (!$bulkIndexingOld && $this->bulkIndexRecords)
            {
                $this->flushBulkIndexing();
            }
        }
        finally
        {
            $this->bulkIndexing = $bulkIndexingOld;
            $this->_last_word_count = null;
        }
    }

    protected function _mangleLastBulkInsert()
    {
        if ($this->_last_word_count !== null)
        {
            if ($this->bulkIndexRecords)
            {
                end($this->bulkIndexRecords);
                $index = key($this->bulkIndexRecords);
                $this->bulkIndexRecords[$index]['word_count'] = $this->_last_word_count;
                reset($this->bulkIndexRecords);
            }
            $this->_last_word_count = null;
        }
    }

    protected function flushBulkIndexing()
    {
        $this->_mangleLastBulkInsert();

        if ($this->bulkIndexRecords)
        {
            $this->db()->insertBulk('xf_search_index', $this->bulkIndexRecords, false,
                                    'title = VALUES(title), message = VALUES(message), metadata = VALUES(metadata), '
                                    . 'item_date = VALUES(item_date), user_id = VALUES(user_id), discussion_id = VALUES(discussion_id), word_count = VALUES(word_count)'
            );
        }

        $this->bulkIndexRecords = [];
    }

    public function search(Query $query, $maxResults)
    {
        /** @var \SV\WordCountSearch\XF\Search\Query\Query $query */
        $query = clone $query; // do not allow others to see our manipulation for the query object
        // rewrite metadata range queries into search_index queries
        $constraints = $query->getMetadataConstraints();
        foreach($constraints as $key => $constraint)
        {
            if ($constraint instanceof RangeMetadataConstraint)
            {
                $sqlConstraint = $constraint->asSqlConstraint();
                if ($sqlConstraint)
                {
                    unset($constraints[$key]);
                    $query->withSql($sqlConstraint);
                }
            }
        }
        // XF\Search\Search & XF\Search\Query\Query aren't extendable
        \SV\WordCountSearch\XF\Search\Query\Query::setMetadataConstraintsHack($query, $constraints);
        //$query->setMetadataConstraints($constraints);

        return parent::search($query, $maxResults);
    }
}