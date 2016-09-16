<?php

namespace Smile\EzToolsBundle\Command\ContentTypeGroup;

class Validators
{
    public static function validateContentTypeGroupName($contentTypeGroupName)
    {
        if (preg_match('/[^ a-z0-9]/i', $contentTypeGroupName))
            return false;

        return $contentTypeGroupName;
    }
}
