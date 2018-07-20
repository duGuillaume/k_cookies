<?php

function upgrade_module_1_1_1($module)
{

    $ret = true;

    $ret &= Configuration::updateValue('K_COOKIE_handleBrowserDNTRequest',0);

    $new_services = ['aduptechads', 'aduptechconversion', 'aduptechretargeting', 'googlemapssearch','matomo', 'multiplegtag','issuu'];

    foreach ($new_services as $service){

        $data = $module->createServiceData($service);

        $ret &= Db::getInstance()->Execute('INSERT INTO `'._DB_PREFIX_.'k_cookies` SET 
            `name` = "'. pSQL($service).'",
            `config` = \''. json_encode (!empty($data) ? $data : new stdClass).'\',
            `date_add` = NOW(),
            `date_upd` = NOW()
            ');
    }

    return $ret;

}
