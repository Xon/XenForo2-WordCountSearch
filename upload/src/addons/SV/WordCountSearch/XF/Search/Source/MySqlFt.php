<?php
namespace SV\WordCountSearch\XF\Search\Source;

use XF\Search\IndexRecord;

/**
 * @extends \XF\Search\Source\MySqlFt
 *
 * @package SV\WordCountSearch\XF\Search\Source
 */
class MySqlFt extends XFCP_MySqlFt
{
    /** @var int|null */
    protected $_last_word_count = null;

    public function index(IndexRecord $record)
    {
        $this->_last_word_count = empty($record->metadata['word_count']) ? 0 : $record->metadata['word_count'];
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

    protected function _mangleLastBulkInsert(): void
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

    /** @noinspection PhpMissingParentCallCommonInspection */
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
}
