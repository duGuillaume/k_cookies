<?php

class CookieClass extends ObjectModel
{

    /** @var string Config */
    public $config;

    /** @var string Name */
    public $name;

    /** @var bool Status for display */
    public $active = 0;

    /** @var string Object creation date */
    public $date_add;

    /** @var string Object last modification date */
    public $date_upd;

    public static $definition = array(
        'table' => 'k_cookies',
        'primary' => 'id_cookie',
        'fields' => array(
            'config' => array('type' => self::TYPE_HTML, 'validate' => 'isJson'),
            'name' => array('type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'required' => true),
            'active' => array('type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => true),
        )
    );

    /**
     * Create new CookieClass from service name
     * @param $name
     * @return CookieClass
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getCookieByName($name){
        $getID = Db::getInstance()->getValue('SELECT id_cookie FROM `' . _DB_PREFIX_ .self::$definition['table'].'` WHERE `name` = "'.pSQL($name).'"');
        return new CookieClass($getID);
    }

    /**
     * Return availables cookies
     *
     * @param bool $active
     * @return array|false|mysqli_result|null|PDOStatement|resource
     * @throws PrestaShopDatabaseException
     */
    public static function getAllCookies($active = true){
        $cookies = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
            SELECT c.`id_cookie`, c.`name`, c.`config`, c.`active`
            FROM ' . _DB_PREFIX_ . 'k_cookies c
            WHERE 1 = 1 '.
            ($active ? ' AND c.`active` = 1' : ' ')
        );

        return $cookies;
    }

}
