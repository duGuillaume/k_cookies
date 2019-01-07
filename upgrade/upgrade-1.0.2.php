<?php

function upgrade_module_1_0_2($module)
{
    $new_services = ['recaptcha'];
    $ret = true;

    foreach ($new_services as $service) {
        $data = $module->createServiceData($service);

        $ret &= Db::getInstance()->Execute('INSERT INTO `'._DB_PREFIX_.'k_cookies` SET 
            `name` = "'. pSQL($service).'",
            `config` = \''.json_encode(!empty($data) ? $data : new stdClass).'\',
            `date_add` = NOW(),
            `date_upd` = NOW()
            ');
    }

    return $ret;
}
