<?php

namespace SV\WordCountSearch\XF\Search\Query;

use XF\Search\Query\MetadataConstraint;

/**
 * Class Query
 *
 * @package SV\WordCountSearch\XF\Search\Query
 */
class Query extends XFCP_Query
{
    /**
     * @param MetadataConstraint[] $metadataConstraints
     */
    public function setMetadataConstraints($metadataConstraints)
    {
        $this->metadataConstraints = $metadataConstraints;
    }
}
