<?php

namespace Smile\EzToolsBundle\Command\Content;

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
