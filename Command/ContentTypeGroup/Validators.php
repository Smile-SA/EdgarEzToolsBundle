<?php
/**
 * Created by PhpStorm.
 * User: emdro
 * Date: 03/08/2016
 * Time: 08:55
 */

namespace EdgarEz\ToolsBundle\Command\ContentTypeGroup;


class Validators
{
    public static function validateContentTypeGroupName($contentTypeGroupName)
    {
        if (preg_match('/[^ a-z0-9]/i', $contentTypeGroupName))
            return false;

        return $contentTypeGroupName;
    }
}