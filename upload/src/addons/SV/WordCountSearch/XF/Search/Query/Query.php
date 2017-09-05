<?php

namespace SV\WordCountSearch\XF\Search\Query;

use XF\Search\Query\MetadataConstraint;

/**
 * * XF doesn't allow this to be extended
 *
 */
class Query extends \XF\Search\Query\Query // extends XFCP_Query
{
    /**
     * @param \XF\Search\Query\Query $query
     * @param MetadataConstraint[] $metadataConstraints
     */
    public static function setMetadataConstraintsHack($query, $metadataConstraints)
    {
        $query->metadataConstraints = $metadataConstraints;
    }

    /**
     * @param MetadataConstraint[] $metadataConstraints
     */
    public function setMetadataConstraints($metadataConstraints)
    {
        $this->metadataConstraints = $metadataConstraints;
    }
}