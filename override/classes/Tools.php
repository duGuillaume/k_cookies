<?php

class Tools extends ToolsCore
{
    public static function getCookie($key)
    {
        if (!isset($key) || empty($key) || !is_string($key)) {
            return $_COOKIE;
        }

        $value = isset($_COOKIE[$key]) ? $_COOKIE[$key] : null;

        if (is_string($value)) {
            return stripslashes(urldecode(preg_replace('/((\%5C0+)|(\%00+))/i', '', urlencode($value))));
        }

        return $value;
    }
}
