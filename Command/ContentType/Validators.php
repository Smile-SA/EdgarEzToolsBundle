<?php
/**
 * Created by PhpStorm.
 * User: emdro
 * Date: 03/08/2016
 * Time: 08:55
 */

namespace EdgarEz\ToolsBundle\Command\ContentType;


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

    public static function validateAttributeIdentifier($attributeIdentifier)
    {
        if (preg_match('/[^a-z_]/', $attributeIdentifier))
            return false;

        return $attributeIdentifier;
    }

    public static function validateAttributeName($attributeName)
    {
        if (preg_match('/[^ a-z0-9]/i', $attributeName))
            return false;

        return $attributeName;
    }
}