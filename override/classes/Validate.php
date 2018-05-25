<?php

class Validate extends ValidateCore
{

    /**
     * Check if $string is a valid JSON string
     *
     * @param string $string JSON string to validate
     * @return bool Validity is ok or not
     */
    public static function isJson($string)
    {
        json_decode($string);

        return (json_last_error() == JSON_ERROR_NONE);
    }
}
