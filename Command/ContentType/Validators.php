<?php

namespace Smile\EzToolsBundle\Command\ContentType;

class Validators
{
    public static function validateContentTypeName($contentTypeName)
    {
        if (preg_match('/[^ a-z0-9]/i', $contentTypeName))
            return false;

        return $contentTypeName;
    }

    public static function validateContentTypeIdentifier($contentTypeIdentifier)
    {
        if (preg_match('/[^a-z_]/', $contentTypeIdentifier))
            return false;

        return $contentTypeIdentifier;
    }

    public static function validateFieldIdentifier($fieldIdentifier)
    {
        if (preg_match('/[^a-z_]/', $fieldIdentifier))
            return false;

        return $fieldIdentifier;
    }

    public static function validateFieldName($fieldName)
    {
        if (preg_match('/[^ a-z0-9]/i', $fieldName))
            return false;

        return $fieldName;
    }
}
