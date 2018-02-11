<?php

namespace SV\WordCountSearch\XF\Search\Query;

use XF\Search\Query\MetadataConstraint;

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
