<?php
/**
 * Created by PhpStorm.
 * User: emdro
 * Date: 03/08/2016
 * Time: 08:55
 */

namespace EdgarEz\ToolsBundle\Command\Content;


class Validators
{
    public static function validateLocationID($locationID)
    {
        if (preg_match('/[^0-9]/', $locationID))
            return false;

        return $locationID;
    }

    public static function validateIdentifier($identifier)
    {
        if (preg_match('/[^a-z_]/', $identifier))
            return false;

        return $identifier;
    }
}