<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

include_once(_PS_MODULE_DIR_ . 'k_cookies/classes/CookieClass.php');

class k_Cookies extends Module
{
    private $prefix = 'K_COOKIE_';
    protected $_html = '';

    /**
     * k_Cookies constructor.
     */
    public function __construct()
    {
        $this->name = 'k_cookies';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'Kadolis';
        $this->need_instance = 0;
        $this->secure_key = Tools::hash($this->name);
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Comply to the European cookie law');
        $this->description = $this->l('Comply to the european cookie law with the french tarte au citron.');
        $this->ps_versions_compliancy = array('min' => '1.7.0.0', 'max' => _PS_VERSION_);
    }

    /**
     * install()
     *
     * @return bool
     */
    public function install()
    {
        return parent::install() &&
            $this->installDB() &&
            $this->installData() &&
            $this->installConfiguration() &&
            $this->registerHook('displayCustomerAccount') &&
            $this->registerHook('displayHeader');
    }

    /**
     * install DB
     *
     * @return bool
     */
    public function installDB()
    {
        return (bool) Db::getInstance()->execute('
            CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'k_cookies` (
              `id_cookie` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
              `name` varchar(50) NOT NULL,
              `config` text,
              `active` tinyint(1) UNSIGNED NOT NULL DEFAULT \'0\',
              `date_add` datetime NOT NULL,
              `date_upd` datetime NOT NULL,
              PRIMARY KEY (`id_cookie`,`name`)
            ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=UTF8;
        ');
    }

    /**
     * Insert default data into DB
     *
     * @return bool
     */
    public function installData()
    {
        $ret = true;
        $services = $this->listServices();
        foreach ($services as $key => $service){
            $json = array();
            $fields = $this->{"get{$key}Fields"}();
            foreach ($fields as $inputs){
                if(is_array($inputs)){
                    foreach ($inputs as $field){
                        $pattern = '/config\[(\w+)\]/i';
                        $replacement = '${1}';
                        $name = preg_replace($pattern, $replacement, $field['name']);
                        switch ($field['type']){
                            case 'text':
                                $json[$name] = '';
                                break;
                            case 'switch':
                                $json[$name] = 0;
                                break;
                        }
                    }
                }
            }

            $ret &= Db::getInstance()->Execute('INSERT INTO `'._DB_PREFIX_.'k_cookies` SET 
            `name` = "'. pSQL($key).'",
            `config` = \''. json_encode (!empty($json) ? $json : new stdClass).'\',
            `date_add` = NOW(),
            `date_upd` = NOW()
            ');
        }

        return $ret;
    }

    /**
     * install Configuration
     *
     * @return bool
     */
    public function installConfiguration(){
        return (bool) Configuration::updateValue($this->prefix.'CMS',0) &&
            Configuration::updateValue($this->prefix.'hashtag','tarteaucitron') &&
            Configuration::updateValue($this->prefix.'highPrivacy',0) &&
            Configuration::updateValue($this->prefix.'orientation','top') &&
            Configuration::updateValue($this->prefix.'adblocker',0) &&
            Configuration::updateValue($this->prefix.'showAlertSmall',0) &&
            Configuration::updateValue($this->prefix.'cookieslist',1) &&
            Configuration::updateValue($this->prefix.'removeCredit',0) &&
            Configuration::updateValue($this->prefix.'btnDisabledColor','#808080') &&
            Configuration::updateValue($this->prefix.'btnAllowColor','#1B870B') &&
            Configuration::updateValue($this->prefix.'btnDenyColor','#9C1A1A') &&
            Configuration::updateValue($this->prefix.'btnAllDisabledColor','#808080') &&
            Configuration::updateValue($this->prefix.'btnAllAllowedColor','#1B870B') &&
            Configuration::updateValue($this->prefix.'btnAllDeniedColor','#9C1A1A');
    }

    /**
     * uninstall()
     *
     * @return bool
     */
    public function uninstall()
    {
        return parent::uninstall() &&
            $this->uninstallDB() &&
            Configuration::deleteByName($this->prefix.'CMS') &&
            Configuration::deleteByName($this->prefix.'hashtag') &&
            Configuration::deleteByName($this->prefix.'highPrivacy') &&
            Configuration::deleteByName($this->prefix.'orientation') &&
            Configuration::deleteByName($this->prefix.'adblocker') &&
            Configuration::deleteByName($this->prefix.'showAlertSmall') &&
            Configuration::deleteByName($this->prefix.'cookieslist') &&
            Configuration::deleteByName($this->prefix.'removeCredit') &&
            Configuration::deleteByName($this->prefix.'btnDisabledColor') &&
            Configuration::deleteByName($this->prefix.'btnAllowColor') &&
            Configuration::deleteByName($this->prefix.'btnDenyColor') &&
            Configuration::deleteByName($this->prefix.'btnAllDisabledColor') &&
            Configuration::deleteByName($this->prefix.'btnAllAllowedColor') &&
            Configuration::deleteByName($this->prefix.'btnAllDeniedColor');
    }

    /**
     * uninstall DB
     *
     * @return bool
     */
    public function uninstallDB(){
        return Db::getInstance()->execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'k_cookies`');
    }

    /**
     * List all availables services from tarteaucitron.services.js
     * @return array
     */
    public function listServices()
    {
        $servicesFile = file_get_contents(_PS_MODULE_DIR_.$this->name.'/views/js/tarteaucitron.services.js');
        $services = [];

        preg_match_all('/"((?<type>)key|name)":(\s?)"(?<value>.*)",/m', $servicesFile, $keys);
        for ($i = 0; $i < count($keys['value']) / 2; $i++) {
            $services[$keys['value'][$i * 2]] = $keys['value'][$i * 2 + 1];
        }
        ksort($services);
        return $services;
    }

    /**
     * back office module configuration page content
     * @return String
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function getContent()
    {
        if (Tools::isSubmit('submitConfiguration') ||
            Tools::isSubmit('submitService')){
            $this->_html .= $this->_postProcess();
        }

        $this->_html .= $this->renderAddForm();

        return  $this->_html;
    }

    /**
     * Load the configuration form.
     * @return string
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function renderAddForm()
    {
        $return = '';

        $ads = [
            'adsense',
            'adsensesearchform',
            'adsensesearchresult',
            'amazon',
            'clicmanager',
            'criteo',
            'datingaffiliation',
            'datingaffiliationpopup',
            'facebookpixel',
            'ferankpub',
            'googleadwordsconversion',
            'googleadwordsremarketing',
            'googlepartners',
            'prelinker',
            'pubdirecte',
            'shareasale',
            'twenga',
            'vshop',
        ];
        $analytics = [
            'alexa',
            'analytics',
            'clicky',
            'crazyegg',
            'etracker',
            'ferank',
            'gajs',
            'getplus',
            'gtag',
            'hotjar',
            'mautic',
            'microsoftcampaignanalytics',
            'statcounter',
            'visualrevenue',
            'webmecanik',
            'wysistat',
            'xiti',
            'xitismarttag',
        ];
        $apis = [
            'jsapi',
            'googlemaps',
            'googletagmanager',
            'timelinejs',
            'typekit',
        ];
        $comments = [
            'disqus',
            'facebookcomment',
        ];
        $socials = [
            'addthis',
            'addtoanyfeed',
            'addtoanyshare',
            'ekomi',
            'facebook',
            'facebooklikebox',
            'gplus',
            'gplusbadge',
            'linkedin',
            'pinterest',
            'shareaholic',
            'sharethis',
            'twitter',
            'twitterembed',
            'twittertimeline',
        ];
        $supports = [
            'purechat',
            'uservoice',
            'zopim',
        ];
        $videos = [
            'calameo',
            'dailymotion',
            'prezi',
            'slideshare',
            'vimeo',
            'youtube',
            'youtubeplaylist',
        ];
        $others = [
            'iframe',
        ];

        $return .= $this->fetch($this->local_path.'/views/templates/admin/configure.tpl');

        $return .= '<div class="row">';
        $return .= '<div class="tab-content col-lg-12 col-md-9">';

        $return .= '<div class="tab-pane active" id="configForm">';
        $return .= $this->renderConfiguration();
        $return .= '</div>';//.tab-pane

        $return .= '<div class="tab-pane" id="ads">';
        foreach ($ads as $ad) {
            $return .= $this->renderService($ad);
        }
        $return .= '</div>';//.tab-pane

        $return .= '<div class="tab-pane" id="analytics">';
        foreach ($analytics as $analytic) {
            $return .= $this->renderService($analytic);
        }
        $return .= '</div>';//.tab-pane

        $return .= '<div class="tab-pane" id="apis">';
        foreach ($apis as $api) {
            $return .= $this->renderService($api);
        }
        $return .= '</div>';//.tab-pane

        $return .= '<div class="tab-pane" id="comments">';
        foreach ($comments as $comment) {
            $return .= $this->renderService($comment);
        }
        $return .= '</div>';//.tab-pane

        $return .= '<div class="tab-pane" id="social">';
        foreach ($socials as $social) {
            $return .= $this->renderService($social);
        }
        $return .= '</div>';//.tab-pane

        $return .= '<div class="tab-pane" id="support">';
        foreach ($supports as $support) {
            $return .= $this->renderService($support);
        }
        $return .= '</div>';//.tab-pane

        $return .= '<div class="tab-pane" id="video">';
        foreach ($videos as $video) {
            $return .= $this->renderService($video);
        }
        $return .= '</div>';//.tab-pane

        $return .= '<div class="tab-pane" id="others">';
        foreach ($others as $other) {
            $return .= $this->renderService($other);
        }
        $return .= '</div>';//.tab-pane

        $return .= '</div>'; // /.tab-content
        $return .= '</div>'; // /.row

        return $return;
    }

    /**
     * Save configurations
     * @return string
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function _postProcess()
    {
        if (Tools::isSubmit('submitConfiguration')) {
            Configuration::updateValue($this->prefix.'CMS', Tools::getValue($this->prefix.'CMS'));
            Configuration::updateValue($this->prefix.'hashtag', Tools::getValue($this->prefix.'hashtag'));
            Configuration::updateValue($this->prefix.'highPrivacy', Tools::getValue($this->prefix.'highPrivacy'));
            Configuration::updateValue($this->prefix.'orientation', Tools::getValue($this->prefix.'orientation'));
            Configuration::updateValue($this->prefix.'adblocker', Tools::getValue($this->prefix.'adblocker'));
            Configuration::updateValue($this->prefix.'showAlertSmall', Tools::getValue($this->prefix.'showAlertSmall'));
            Configuration::updateValue($this->prefix.'cookieslist', Tools::getValue($this->prefix.'cookieslist'));
            Configuration::updateValue($this->prefix.'removeCredit', Tools::getValue($this->prefix.'removeCredit'));
            Configuration::updateValue($this->prefix.'btnDisabledColor', Tools::getValue($this->prefix.'btnDisabledColor','#808080'));
            Configuration::updateValue($this->prefix.'btnAllowColor', Tools::getValue($this->prefix.'btnAllowColor','#1B870B'));
            Configuration::updateValue($this->prefix.'btnDenyColor', Tools::getValue($this->prefix.'btnDenyColor','#9C1A1A'));
            Configuration::updateValue($this->prefix.'btnAllDisabledColor', Tools::getValue($this->prefix.'btnAllDisabledColor','#808080'));
            Configuration::updateValue($this->prefix.'btnAllAllowedColor', Tools::getValue($this->prefix.'btnAllAllowedColor','#1B870B'));
            Configuration::updateValue($this->prefix.'btnAllDeniedColor', Tools::getValue($this->prefix.'btnAllDeniedColor','#9C1A1A'));
        }

        if (Tools::isSubmit('submitService')) {
            $config = Tools::getValue('config');
            $service = Tools::getValue('service');
            $active = Tools::getValue($service.'_active');
            $cookie = CookieClass::getCookieByName($service);
            $cookie->active = $active;
            $cookie->config = $config ? json_encode($config) : json_encode(new stdClass());
            $cookie->save();
        }

        return (count($this->_errors) ? $this->displayError($this->_errors) : $this->displayConfirmation($this->trans('The settings have been updated.', array(), 'Admin.Notifications.Success')));
    }

    /**
     * Render plugin configuration form
     * @return string
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function renderConfiguration()
    {
        $services = $this->listServices();
        $services_input = [];
        foreach ($services as $key => $service) {
            array_push(
                $services_input,
                array(
                    'type' => 'text',
                    'label' => $service,
                    'name' => $this->prefix.$key,
                )
            );
        }
        // get CMS pages
        $CMS = CMS::getCMSPages( $this->context->language->id, null, false, $this->context->shop->id);

        $orientation_options = array(
            array(
                "orientation" => 'top',
                "name" => $this->l('Top'),
            ),
            array(
                "orientation" => 'bottom',
                "name" => $this->l('Bottom'),
            ),
        );

        $fields_form = array(
            'form' => array(
                'input' => array(
//                    $services_input,
                    array(
                        'type' => 'select',
                        'label' => $this->l('Cookies CMS page'),
                        'name' => $this->prefix.'CMS',
                        'required' => true,
                        'options' => array(
                            'query' => $CMS,
                            'id' => 'id_cms',
                            'name' => 'meta_title',
                        ),
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Hashtag'),
                        'name' => $this->prefix.'hashtag',
                        'hint' =>$this->l('Ouverture automatique du panel avec le hashtag'),
                        'required' => true,
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('High Privacy'),
                        'name' => $this->prefix.'highPrivacy',
                        'hint' => $this->l('désactiver le consentement implicite (en naviguant) ?'),
                        'required' => true,
                        'is_bool' => true,
                        'values' => array(
                            array(
                                'id' => 'highprivacy_on',
                                'value' => 1,
                                'label' => $this->trans('Yes', array(), 'Admin.Global'),
                            ),
                            array(
                                'id' => 'highprivacy_off',
                                'value' => 0,
                                'label' => $this->trans('No', array(), 'Admin.Global'),
                            ),
                        ),
                    ),
                    array(
                        'type' => 'select',
                        'label' => $this->l('Orientation'),
                        'name' => $this->prefix.'orientation',
                        'hint' => $this->l('le bandeau doit être en haut (top) ou en bas (bottom) ?'),
                        'required' => true,
                        'options' => array(
                            'query' => $orientation_options,
                            'id' => 'orientation',
                            'name' => 'name',
                        ),
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Adblocker'),
                        'name' => $this->prefix.'adblocker',
                        'hint' => $this->l('Afficher un message si un adblocker est détecté'),
                        'required' => true,
                        'is_bool' => true,
                        'values' => array(
                            array(
                                'id' => 'adblocker_on',
                                'value' => 1,
                                'label' => $this->trans('Yes', array(), 'Admin.Global'),
                            ),
                            array(
                                'id' => 'adblocker_off',
                                'value' => 0,
                                'label' => $this->trans('No', array(), 'Admin.Global'),
                            ),
                        ),
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Show alert small'),
                        'name' => $this->prefix.'showAlertSmall',
                        'hint' => $this->l('afficher le petit bandeau en bas à droite ?'),
                        'required' => true,
                        'is_bool' => true,
                        'values' => array(
                            array(
                                'id' => 'showalertamall_on',
                                'value' => 1,
                                'label' => $this->trans('Yes', array(), 'Admin.Global'),
                            ),
                            array(
                                'id' => 'ashowalertamall_off',
                                'value' => 0,
                                'label' => $this->trans('No', array(), 'Admin.Global'),
                            ),
                        ),
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Cookies list'),
                        'name' => $this->prefix.'cookieslist',
                        'hint' => $this->l('Afficher la liste des cookies installés ?'),
                        'required' => true,
                        'is_bool' => true,
                        'values' => array(
                            array(
                                'id' => 'cookieslist_on',
                                'value' => 1,
                                'label' => $this->trans('Yes', array(), 'Admin.Global'),
                            ),
                            array(
                                'id' => 'cookieslist_off',
                                'value' => 0,
                                'label' => $this->trans('No', array(), 'Admin.Global'),
                            ),
                        ),
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Remove credit'),
                        'name' => $this->prefix.'removeCredit',
                        'hint' => $this->l('supprimer le lien vers la source ?'),
                        'required' => true,
                        'is_bool' => true,
                        'values' => array(
                            array(
                                'id' => 'removecredit_on',
                                'value' => 1,
                                'label' => $this->trans('Yes', array(), 'Admin.Global'),
                            ),
                            array(
                                'id' => 'removecredit_off',
                                'value' => 0,
                                'label' => $this->trans('No', array(), 'Admin.Global'),
                            ),
                        ),
                    ),
                    array(
                        'type' => 'color',
                        'label' => $this->l('Button disabled color'),
                        'name' => $this->prefix.'btnDisabledColor',
                    ),
                    array(
                        'type' => 'color',
                        'label' => $this->l('Button allow color'),
                        'name' => $this->prefix.'btnAllowColor',
                    ),
                    array(
                        'type' => 'color',
                        'label' => $this->l('Button disallow color'),
                        'name' => $this->prefix.'btnDenyColor',
                    ),
                    array(
                        'type' => 'color',
                        'label' => $this->l('Button all disabled color'),
                        'name' => $this->prefix.'btnAllDisabledColor',
                    ),
                    array(
                        'type' => 'color',
                        'label' => $this->l('Button all allow color'),
                        'name' => $this->prefix.'btnAllAllowedColor',
                    ),
                    array(
                        'type' => 'color',
                        'label' => $this->l('Button all disallow color'),
                        'name' => $this->prefix.'btnAllDeniedColor',
                    ),
                ),
                'submit' => array(
                    'title' => $this->getTranslator()->trans('Save', array(), 'Admin.Actions'),
                ),
            ),
        );

        $fields_value = array(
            $this->prefix.'CMS' => Configuration::get($this->prefix.'CMS'),
            $this->prefix.'hashtag' => Configuration::get($this->prefix.'hashtag'),
            $this->prefix.'highPrivacy' => Configuration::get($this->prefix.'highPrivacy'),
            $this->prefix.'orientation' => Configuration::get($this->prefix.'orientation'),
            $this->prefix.'adblocker' => Configuration::get($this->prefix.'adblocker'),
            $this->prefix.'showAlertSmall' => Configuration::get($this->prefix.'showAlertSmall'),
            $this->prefix.'cookieslist' => Configuration::get($this->prefix.'cookieslist'),
            $this->prefix.'removeCredit' => Configuration::get($this->prefix.'removeCredit'),
            $this->prefix.'btnDisabledColor' => Configuration::get($this->prefix.'btnDisabledColor'),
            $this->prefix.'btnAllowColor' => Configuration::get($this->prefix.'btnAllowColor'),
            $this->prefix.'btnDenyColor' => Configuration::get($this->prefix.'btnDenyColor'),
            $this->prefix.'btnAllDisabledColor' => Configuration::get($this->prefix.'btnAllDisabledColor'),
            $this->prefix.'btnAllAllowedColor' => Configuration::get($this->prefix.'btnAllAllowedColor'),
            $this->prefix.'btnAllDeniedColor' => Configuration::get($this->prefix.'btnAllDeniedColor'),
        );

        $lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->default_form_language = $lang->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get(
            'PS_BO_ALLOW_EMPLOYEE_FORM_LANG'
        ) : 0;
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitConfiguration';
        $helper->currentIndex = $this->context->link->getAdminLink(
                'AdminModules',
                false
            ).'&configure='.$this->name.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = array(
            'fields_value' => $fields_value,
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($fields_form));
    }

    /**
     * Render specific service form
     * @param array $service
     * @return string
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function renderService($service)
    {
        $consts = array(
            array(
                'type' => 'hidden',
                'name' => 'service',
            ),
            array(
                'type' => 'switch',
                'label' => $this->getTranslator()->trans('Enabled', array(), 'Admin.Global'),
                'name' => $service.'_active',
                'is_bool' => true,
                'values' => array(
                    array(
                        'id' => $service.'_on',
                        'value' => 1,
                        'label' => $this->getTranslator()->trans('Yes', array(), 'Admin.Global'),
                    ),
                    array(
                        'id' => $service.'_off',
                        'value' => 0,
                        'label' => $this->getTranslator()->trans('No', array(), 'Admin.Global'),
                    ),
                ),
            ),
        );

        $fields = $this->{"get{$service}Fields"}();
        $legend = $fields['legend'];
        $inputs = $fields['inputs'];

        $fields_form = array(
            'form' => array(
                'legend' => array(
                    'title' => $legend,
                ),
                'input' => array_merge($inputs,$consts),
                'submit' => array(
                    'title' => $this->getTranslator()->trans('Save', array(), 'Admin.Actions'),
                ),
            ),
        );

        $fields_value = array();

        $cookie = CookieClass::getCookieByName($service);
        $configs = json_decode($cookie->config);
        foreach ($configs as $key => $config){
            $fields_value['config['.$key.']'] = $config;
        }

        $fields_value[$service.'_active'] = $cookie->active;
        $fields_value['service'] = $service;

        $lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->default_form_language = $lang->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get(
            'PS_BO_ALLOW_EMPLOYEE_FORM_LANG'
        ) : 0;
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitService';
        $helper->currentIndex = $this->context->link->getAdminLink(
                'AdminModules',
                false
            ).'&configure='.$this->name.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = array(
            'fields_value' => $fields_value,
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($fields_form));
    }

    /**
     * Get AddThis Fields
     * @return array
     */
    protected function getAddThisFields()
    {
        $legend = $this->l('AddThis');

        $inputs = array(
            array(
                'type' => 'text',
                'label' => $this->l('PUB ID'),
                'name' => 'config[addthisPubId]',
            ),
        );

        return ['legend' => $legend, 'inputs' => $inputs];
    }

    /**
     * Get AddToAny (feed) Fields
     * @return array
     */
    protected function getAddToAnyFeedFields()
    {
        $legend = $this->l('AddToAny (feed)');

        $inputs = array(
            array(
                'type' => 'text',
                'label' => $this->l('feed uri'),
                'name' => 'config[addtoanyfeedUri]',
            ),
        );

        return ['legend' => $legend, 'inputs' => $inputs];
    }

    /**
     * Get AddToAny (share) Fields
     * @return array
     */
    protected function getAddToAnyShareFields()
    {
        $legend = $this->l('AddToAny (share)');
        return ['legend' => $legend, 'inputs' => []];
    }

    /**
     * Get Google Adsense Fields
     * @return array
     */
    protected function getAdSenseFields()
    {
        $legend = $this->l('Google Adsense');
        return ['legend' => $legend, 'inputs' => []];
    }

    /**
     * Get Google Adsense Search (form) Fields
     * @return array
     */
    protected function getAdSenseSearchFormFields()
    {
        $legend = $this->l('Google Adsense Search (form)');
        return ['legend' => $legend, 'inputs' => []];
    }

    /**
     * Get Google Adsense Search (result) Fields
     * @return array
     */
    protected function getAdSenseSearchResultFields()
    {
        $legend = $this->l('Google Adsense Search (result)');

        $inputs = array(
            array(
                'type' => 'text',
                'label' => $this->l('partner pub ID'),
                'name' => 'config[adsensesearchresultCx]',
                'desc' => 'partner-pub-XXXXXXXXXXXXX:XXXXXXX',
            ),
        );

        return ['legend' => $legend, 'inputs' => $inputs];
    }

    /**
     * Get Alexa Fields
     * @return array
     */
    protected function getAlexaFields()
    {
        $legend = $this->l('Alexa');

        $inputs = array(
            array(
                'type' => 'text',
                'label' => $this->l('Alexa account ID'),
                'name' => 'config[alexaAccountID]',
            ),
        );

        return ['legend' => $legend, 'inputs' => $inputs];
    }

    /**
     * Get Amazon Fields
     * @return array
     */
    protected function getAmazonFields()
    {
        $legend = $this->l('Amazon');
        return ['legend' => $legend, 'inputs' => []];
    }

    /**
     * Get Google Analytics (Universal) Fields
     * @return array
     */
    protected function getAnalyticsFields()
    {
        $legend = $this->l('Google Analytics (Universal)');

        $inputs = array(
            array(
                'type' => 'text',
                'label' => $this->l('Google Analytics Tracking ID'),
                'name' => 'config[analyticsUa]',
                'size' => 20,
                'hint' => $this->l('This information is available in your Google Analytics account'),
            ),
            array(
                'type' => 'switch',
                'label' => $this->l('Enable User ID tracking'),
                'name' => 'config[analyticsUserID]',
                'values' => array(
                    array(
                        'id' => 'analyticsUserID_enabled',
                        'value' => 1,
                        'label' => $this->l('Enabled'),
                    ),
                    array(
                        'id' => 'analyticsUserID_disabled',
                        'value' => 0,
                        'label' => $this->l('Disabled'),
                    ),
                ),
            ),
            array(
                'type' => 'switch',
                'label' => $this->l('Anonymize IP'),
                'name' => 'config[analyticsAnonymize]',
                'hint' => $this->l(
                    'Use this option to anonymize the visitor’s IP to comply with data privacy laws in some countries'
                ),
                'values' => array(
                    array(
                        'id' => 'analyticsAnonymize_enabled',
                        'value' => 1,
                        'label' => $this->l('Enabled'),
                    ),
                    array(
                        'id' => 'analyticsAnonymize_disabled',
                        'value' => 0,
                        'label' => $this->l('Disabled'),
                    ),
                ),
            ),
        );

        // Check if multistore is active
        $inputs = array_merge($inputs, array(
            array(
                'type' => 'switch',
                'label' => $this->l('Enable Cross-Domain tracking'),
                'name' => 'config[analyticsCrossDomain]',
                'hint' => $this->l('Multistore need to be active'),
                'values' => array(
                    array(
                        'id' => 'analyticsCrossDomain_enabled',
                        'value' => 1,
                        'label' => $this->l('Enabled')
                    ),
                    array(
                        'id' => 'analyticsCrossDomain_disabled',
                        'value' => 0,
                        'label' => $this->l('Disabled')
                    )
                )
            ),
        ));

        return ['legend' => $legend, 'inputs' => $inputs];
    }

    /**
     * Get Calameo Fields
     * @return array
     */
    protected function getCalameoFields()
    {
        $legend = $this->l('Calameo');
        return ['legend' => $legend, 'inputs' => []];
    }

    /**
     * Get Clicky Fields
     * @return array
     */
    protected function getClickyFields()
    {
        $legend = $this->l('Clicky');

        $inputs = array(
            array(
                'type' => 'text',
                'label' => $this->l('Clicky ID'),
                'name' => 'config[clickyId]',
            ),
        );

        return ['legend' => $legend, 'inputs' => $inputs];
    }

    /**
     * Get Clicmanager Fields
     * @return array
     */
    protected function getClicManagerFields()
    {
        $legend = $this->l('Clicmanager');
        return ['legend' => $legend, 'inputs' => []];
    }

    /**
     * Get Crazy Egg Fields
     * @return array
     */
    protected function getCrazyEggFields()
    {
        $legend = $this->l('Crazy Egg');

        $inputs = array(
            array(
                'type' => 'text',
                'label' => $this->l('Crazy Egg ID'),
                'name' => 'config[crazyeggId]',
            ),
        );

        return ['legend' => $legend, 'inputs' => $inputs];
    }

    /**
     * Get Criteo Fields
     * @return array
     */
    protected function getCriteoFields()
    {
        $legend = $this->l('Criteo');
        return ['legend' => $legend, 'inputs' => []];
    }

    /**
     * Get Dailymotion Fields
     * @return array
     */
    protected function getDailymotionFields()
    {
        $legend = $this->l('Dailymotion');
        return ['legend' => $legend, 'inputs' => []];
    }

    /**
     * Get Dating Affiliation Fields
     * @return array
     */
    protected function getDatingAffiliationFields()
    {
        $legend = $this->l('Dating Affiliation');
        return ['legend' => $legend, 'inputs' => []];
    }

    /**
     * Get Dating Affiliation (Pop Up) Fields
     * @return array
     */
    protected function getDatingAffiliationPopUpFields()
    {
        $legend = $this->l('Dating Affiliation (Pop Up)');
        return ['legend' => $legend, 'inputs' => []];
    }

    /**
     * Get Disqus Fields
     * @return array
     */
    protected function getDisqusFields()
    {
        $legend = $this->l('Disqus');

        $inputs = array(
            array(
                'type' => 'text',
                'label' => $this->l('Disqus Shortname'),
                'name' => 'config[disqusShortname]',
            ),
        );

        return ['legend' => $legend, 'inputs' => $inputs];
    }

    /**
     * Get eKomi Fields
     * @return array
     */
    protected function getEkomiFields()
    {
        $legend = $this->l('eKomi');

        $inputs = array(
            array(
                'type' => 'text',
                'label' => $this->l('CERT-ID'),
                'name' => 'config[ekomiCertId]',
            ),
        );

        return ['legend' => $legend, 'inputs' => $inputs];
    }

    /**
     * Get eTracker Fields
     * @return array
     */
    protected function getEtrackerFields()
    {
        $legend = $this->l('eTracker');

        $inputs = array(
            array(
                'type' => 'text',
                'label' => $this->l('Data Secure Code'),
                'name' => 'config[etracker]',
                'hint' => 'data-secure-code'
            ),
        );

        return ['legend' => $legend, 'inputs' => $inputs];
    }

    /**
     * Get Facebook Fields
     * @return array
     */
    protected function getFacebookFields()
    {
        $legend = $this->l('Facebook');
        return ['legend' => $legend, 'inputs' => []];
    }

    /**
     * Get Facebook (commentaire) Fields
     * @return array
     */
    protected function getFacebookCommentFields()
    {
        $legend = $this->l('Facebook (commentaire)');
        return ['legend' => $legend, 'inputs' => []];
    }

    /**
     * Get Facebook (like box) Fields
     * @return array
     */
    protected function getFacebookLikeBoxFields()
    {
        $legend = $this->l('Facebook (like box)');
        return ['legend' => $legend, 'inputs' => []];
    }

    /**
     * Get Facebook Pixel Fields
     * @return array
     */
    protected function getFacebookPixelFields()
    {
        $legend = $this->l('Facebook Pixel');

        $inputs = array(
            array(
                'type' => 'text',
                'label' => $this->l('Facebook Pixel ID'),
                'name' => 'config[facebookpixelId]',
            ),
        );

        return ['legend' => $legend, 'inputs' => $inputs];

    }

    /**
     * Get FERank Fields
     * @return array
     */
    protected function getFERankFields()
    {
        $legend = $this->l('FERank');
        return ['legend' => $legend, 'inputs' => []];
    }

    /**
     * Get FERank (pub) Fields
     * @return array
     */
    protected function getFERankPubFields()
    {
        $legend = $this->l('FERank (pub)');
        return ['legend' => $legend, 'inputs' => []];
    }

    /**
     * Get Google Analytics (ga.js) Fields
     * @return array
     */
    protected function getGaJSFields()
    {
        $legend = $this->l('Google Analytics (ga.js)');

        $inputs = array(
            array(
                'type' => 'text',
                'label' => $this->l('Google Analytics ID'),
                'name' => 'config[gajsUa]',
                'hint' => 'UA-XXXXXXXX-X'
            ),
        );

        return ['legend' => $legend, 'inputs' => $inputs];
    }

    /**
     * Get Get+ Fields
     * @return array
     */
    protected function getGetPlusFields()
    {
        $legend = $this->l('Get+');

        $inputs = array(
            array(
                'type' => 'text',
                'label' => $this->l('Get + account ID'),
                'name' => 'config[getplusId]',
            ),
        );

        return ['legend' => $legend, 'inputs' => $inputs];
    }

    /**
     * Get Google Adwords (conversion) Fields
     * @return array
     */
    protected function getGoogleAdwordsConversionFields()
    {
        $legend = $this->l('Google Adwords (conversion)');
        $inputs = array(
            array(
                'type' => 'text',
                'label' => $this->l('Adwords Conversion ID'),
                'name' => 'config[adwordsconversionId]',
            ),
            // @TODO set missing parameters
//            array(
//                'type' => 'text',
//                'label' => $this->l('Adwords Conversion Label'),
//                'name' => 'config[adwordsconversionLabel]',
//            ),
            // @TODO set prestashop language
//            array(
//                'type' => 'text',
//                'label' => $this->l('Adwords Conversion Language'),
//                'name' => 'config[adwordsconversionLanguage]',
//            ),
//            array(
//                'type' => 'text',
//                'label' => $this->l('Adwords Conversion Format'),
//                'name' => 'config[adwordsconversionFormat]',
//            ),
//            array(
//                'type' => 'text',
//                'label' => $this->l('Adwords Conversion Color'),
//                'name' => 'config[adwordsconversionColor]',
//            ),
//            array(
//                'type' => 'text',
//                'label' => $this->l('Adwords Conversion Value'),
//                'name' => 'config[adwordsconversionValue]',
//            ),
            // @TODO set prestashop currency
//            array(
//                'type' => 'text',
//                'label' => $this->l('Adwords Conversion Currency'),
//                'name' => 'config[adwordsconversionCurrency]',
//            ),
        );

        return ['legend' => $legend, 'inputs' => $inputs];
    }

    /**
     * Get Google Adwords (remarketing) Fields
     * @return array
     */
    protected function getGoogleAdwordsRemarketingFields()
    {
        $legend = $this->l('Google Adwords (remarketing)');
        $inputs = array(
            array(
                'type' => 'text',
                'label' => $this->l('Google adwords remarketing ID'),
                'name' => 'config[adwordsremarketingId]',
            ),
        );
        return ['legend' => $legend, 'inputs' => $inputs];
    }

    /**
     * Get Google Maps Fields
     * @return array
     */
    protected function getGoogleMapsFields()
    {
        $legend = $this->l('Google Maps');

        $inputs = array(
            array(
                'type' => 'text',
                'label' => $this->l('Google Maps API Key'),
                'name' => 'config[googlemapsKey]',
            ),
        );

        return ['legend' => $legend, 'inputs' => $inputs];
    }

    /**
     * Get Google Partners Badge Fields
     * @return array
     */
    protected function getGooglePartnersFields()
    {
        $legend = $this->l('Google Partners Badge');
        return ['legend' => $legend, 'inputs' => []];
    }

    /**
     * Get Google Tag Manager Fields
     * @return array
     */
    protected function getGoogleTagManagerFields()
    {
        $legend = $this->l('Google Tag Manager');

        $inputs = array(
            array(
                'type' => 'text',
                'label' => $this->l('Google Tag Manager ID'),
                'name' => 'config[googletagmanagerId]',
                'desc' => 'GTM-XXXX',
            ),
        );

        return ['legend' => $legend, 'inputs' => $inputs];
    }

    /**
     * Get Google+ Fields
     * @return array
     */
    protected function getGPlusFields()
    {
        $legend = $this->l('Google+');
        return ['legend' => $legend, 'inputs' => []];
    }

    /**
     * Get Google+ (badge) Fields
     * @return array
     */
    protected function getGPlusBadgeFields()
    {
        $legend = $this->l('Google+ (badge)');
        return ['legend' => $legend, 'inputs' => []];
    }

    /**
     * Get Google Analytics (gtag.js) Fields
     * @return array
     */
    protected function getGtagFields()
    {
        $legend = $this->l('Google Analytics (gtag.js)');

        $inputs = array(
            array(
                'type' => 'text',
                'label' => $this->l('Google Analytics UA'),
                'name' => 'config[gtagUa]',
                'desc' => 'UA-XXXXXXXX-X',
            ),
        );

        return ['legend' => $legend, 'inputs' => $inputs];
    }

    /**
     * Get Hotjar Fields
     * @return array
     */
    protected function getHotjarFields()
    {
        $legend = $this->l('Hotjar');

        $inputs = array(
            array(
                'type' => 'text',
                'label' => $this->l('Hotjar ID'),
                'name' => 'config[hotjarID]',
            ),
            array(
                'type' => 'text',
                'label' => $this->l('Hotjar Snippet Version '),
                'name' => 'config[hotjarSV]',
            ),
        );

        return ['legend' => $legend, 'inputs' => $inputs];
    }

    /**
     * Get Web content (Iframe) Fields
     * @return array
     */
    protected function getIframeFields()
    {
        $legend = $this->l('Web content (Iframe)');

        $inputs = array(
            array(
                'type' => 'text',
                'label' => $this->l('Iframe name'),
                'name' => 'config[name]',
            ),
            array(
                'type' => 'text',
                'label' => $this->l('Iframe privacy url'),
                'name' => 'config[uri]',
            ),
            array(
                'type' => 'text',
                'label' => $this->l('Iframe privacy url'),
                'name' => 'config[cookies]',
                'desc' => 'Need to be an array [\'cookie 1\', \'cookie 2\']',
            ),
        );

        return ['legend' => $legend, 'inputs' => $inputs];
    }

    /**
     * Get Google jsapi Fields
     * @return array
     */
    protected function getJSAPIFields()
    {
        $legend = $this->l('Google jsapi');
        return ['legend' => $legend, 'inputs' => []];
    }

    /**
     * Get Linkedin Fields
     * @return array
     */
    protected function getLinkedInFields()
    {
        $legend = $this->l('Linkedin');
        return ['legend' => $legend, 'inputs' => []];
    }

    /**
     * Get Mautic Fields
     * @return array
     */
    protected function getMauticFields()
    {
        $legend = $this->l('Mautic');

        $inputs = array(
            array(
                'type' => 'text',
                'label' => $this->l('mautic URL'),
                'name' => 'config[mauticurl]',
            ),
        );

        return ['legend' => $legend, 'inputs' => $inputs];
    }

    /**
     * Get Microsoft Campaign Analytics Fields
     * @return array
     */
    protected function getMicrosoftCampaignAnalyticsFields()
    {
        $legend = $this->l('Microsoft Campaign Analytics');
        return ['legend' => $legend, 'inputs' => []];
    }

    /**
     * Get Pinterest Fields
     * @return array
     */
    protected function getPinterestFields()
    {
        $legend = $this->l('Pinterest');
        return ['legend' => $legend, 'inputs' => []];
    }

    /**
     * Get Prelinker Fields
     * @return array
     */
    protected function getPrelinkerFields()
    {
        $legend = $this->l('Prelinker');
        return ['legend' => $legend, 'inputs' => []];
    }

    /**
     * Get Prezi Fields
     * @return array
     */
    protected function getPreziFields()
    {
        $legend = $this->l('Prezi');
        return ['legend' => $legend, 'inputs' => []];
    }

    /**
     * Get Pubdirecte Fields
     * @return array
     */
    protected function getPubdirecteFields()
    {
        $legend = $this->l('Pubdirecte');
        return ['legend' => $legend, 'inputs' => []];
    }

    /**
     * Get PureChat Fields
     * @return array
     */
    protected function getPureChatFields()
    {
        $legend = $this->l('PureChat');

        $inputs = array(
            array(
                'type' => 'text',
                'label' => $this->l('PureChat ID'),
                'name' => 'config[purechatId]',
            ),
        );

        return ['legend' => $legend, 'inputs' => $inputs];
    }

    /**
     * Get Shareaholic Fields
     * @return array
     */
    protected function getShareaholicFields()
    {
        $legend = $this->l('Shareaholic');

        $inputs = array(
            array(
                'type' => 'text',
                'label' => $this->l('Shareaholic site ID'),
                'name' => 'config[shareaholicSiteId]',
            ),
        );

        return ['legend' => $legend, 'inputs' => $inputs];
    }

    /**
     * Get ShareASale Fields
     * @return array
     */
    protected function getShareASaleFields()
    {
        $legend = $this->l('ShareASale');
        return ['legend' => $legend, 'inputs' => []];
    }

    /**
     * Get ShareThis Fields
     * @return array
     */
    protected function getShareThisFields()
    {
        $legend = $this->l('ShareThis');

        $inputs = array(
            array(
                'type' => 'text',
                'label' => $this->l('ShareThis publisher'),
                'name' => 'config[sharethisPublisher]',
            ),
        );

        return ['legend' => $legend, 'inputs' => $inputs];
    }

    /**
     * Get SlideShare Fields
     * @return array
     */
    protected function getSlideShareFields()
    {
        $legend = $this->l('SlideShare');
        return ['legend' => $legend, 'inputs' => []];
    }

    /**
     * Get StatCounter Fields
     * @return array
     */
    protected function getStatCounterFields()
    {
        $legend = $this->l('StatCounter');
        return ['legend' => $legend, 'inputs' => []];
    }

    /**
     * Get Timeline JS Fields
     * @return array
     */
    protected function getTimelineJSFields()
    {
        $legend = $this->l('Timeline JS');
        return ['legend' => $legend, 'inputs' => []];
    }

    /**
     * Get Twenga Fields
     * @return array
     */
    protected function getTwengaFields()
    {
        $legend = $this->l('Twenga');
        return ['legend' => $legend, 'inputs' => []];
    }

    /**
     * Get Twitter Fields
     * @return array
     */
    protected function getTwitterFields()
    {
        $legend = $this->l('Twitter');
        return ['legend' => $legend, 'inputs' => []];
    }

    /**
     * Get Twitter (cards) Fields
     * @return array
     */
    protected function getTwitterEmbedFields()
    {
        $legend = $this->l('Twitter (cards)');
        return ['legend' => $legend, 'inputs' => []];
    }

    /**
     * Get Twitter (timelines) Fields
     * @return array
     */
    protected function getTwitterTimelineFields()
    {
        $legend = $this->l('Twitter (timelines)');
        return ['legend' => $legend, 'inputs' => []];
    }

    /**
     * Get Typekit (adobe) Fields
     * @return array
     */
    protected function getTypekitFields()
    {
        $legend = $this->l('Typekit (adobe)');

        $inputs = array(
            array(
                'type' => 'text',
                'label' => $this->l('typekit ID'),
                'name' => 'config[typekitId]',
            ),
        );

        return ['legend' => $legend, 'inputs' => $inputs];
    }

    /**
     * Get UserVoice Fields
     * @return array
     */
    protected function getUserVoiceFields()
    {
        $legend = $this->l('UserVoice');

        $inputs = array(
            array(
                'type' => 'text',
                'label' => $this->l('UserVoice API KEY'),
                'name' => 'config[userVoiceApi]',
            ),
        );

        return ['legend' => $legend, 'inputs' => $inputs];
    }

    /**
     * Get Vimeo Fields
     * @return array
     */
    protected function getVimeoFields()
    {
        $legend = $this->l('Vimeo');
        return ['legend' => $legend, 'inputs' => []];
    }

    /**
     * Get VisualRevenue Fields
     * @return array
     */
    protected function getVisualRevenueFields()
    {
        $legend = $this->l('VisualRevenue');

        $inputs = array(
            array(
                'type' => 'text',
                'label' => $this->l('VisualRevenue ID'),
                'name' => 'config[visualrevenueId]',
            ),
        );

        return ['legend' => $legend, 'inputs' => $inputs];
    }

    /**
     * Get vShop Fields
     * @return array
     */
    protected function getVshopFields()
    {
        $legend = $this->l('vShop');
        return ['legend' => $legend, 'inputs' => []];
    }

    /**
     * Get Webmecanik Fields
     * @return array
     */
    protected function getWebmecanikFields()
    {
        $legend = $this->l('Webmecanik');
        return ['legend' => $legend, 'inputs' => []];
    }

    /**
     * Get Wysistat Fields
     * @return array
     */
    protected function getWysistatFields()
    {
        $legend = $this->l('Wysistat');
        return ['legend' => $legend, 'inputs' => []];
    }

    /**
     * Get Xiti Fields
     * @return array
     */
    protected function getXitiFields()
    {
        $legend = $this->l('Xiti');

        $inputs = array(
            array(
                'type' => 'text',
                'label' => $this->l('Xiti ID'),
                'name' => 'config[xitiId]',
            ),
        );

        return ['legend' => $legend, 'inputs' => $inputs];
    }

    /**
     * Get Xiti (SmartTag) Fields
     * @return array
     */
    protected function getXitiSmartTagFields()
    {
        $legend = $this->l('Xiti (SmartTag)');

        $inputs = array(
            array(
                'type' => 'text',
                'label' => $this->l('Xiti SmartTag Site ID'),
                'name' => 'config[SmarttagSiteID]',
            ),
        );

        return ['legend' => $legend, 'inputs' => $inputs];
    }

    /**
     * Get YouTube Fields
     * @return array
     */
    protected function getYouTubeFields()
    {
        $legend = $this->l('YouTube');
        return ['legend' => $legend, 'inputs' => []];
    }

    /**
     * Get YouTube (playlist) Fields
     * @return array
     */
    protected function getYouTubePlaylistFields()
    {
        $legend = $this->l('YouTube (playlist)');
        return ['legend' => $legend, 'inputs' => []];
    }

    /**
     * Get Zopim Fields
     * @return array
     */
    protected function getZopimFields()
    {
        $legend = $this->l('Zopim');

        $inputs = array(
            array(
                'type' => 'text',
                'label' => $this->l('Zopim ID'),
                'name' => 'config[zopimID]',
            ),
        );

        return ['legend' => $legend, 'inputs' => $inputs];
    }

    /**
     * Adds the Tracking code
     * @param $params
     * @return string
     * @throws PrestaShopDatabaseException
     */
    public function hookdisplayHeader($params)
    {
        $this->context->controller->registerJavascript($this->name, 'modules/'.$this->name.'/views/js/tarteaucitron.js', ['position' => 'head', 'priority' => 150]);
        $this->context->controller->registerStylesheet($this->name, 'modules/'.$this->name.'/views/css/tarteaucitron.css', ['media' => 'all', 'priority' => 150]);

        $shops = Shop::getShops();
        $is_multistore_active = Shop::isFeatureActive();

        $current_shop_id = (int)Context::getContext()->shop->id;

        $this->smarty->assign(array(
            'hashtag' => Configuration::get($this->prefix.'hashtag'),
            'highPrivacy' => Configuration::get($this->prefix.'highPrivacy'),
            'orientation' => Configuration::get($this->prefix.'orientation'),
            'adblocker' => Configuration::get($this->prefix.'adblocker'),
            'showAlertSmall' => Configuration::get($this->prefix.'showAlertSmall'),
            'cookieslist' => Configuration::get($this->prefix.'cookieslist'),
            'removeCredit' => Configuration::get($this->prefix.'removeCredit'),
            'btnDisabledColor' => Configuration::get($this->prefix.'btnDisabledColor'),
            'btnAllowColor' => Configuration::get($this->prefix.'btnAllowColor'),
            'btnDenyColor' => Configuration::get($this->prefix.'btnDenyColor'),
            'btnAllDisabledColor' => Configuration::get($this->prefix.'btnAllDisabledColor'),
            'btnAllAllowedColor' => Configuration::get($this->prefix.'btnAllAllowedColor'),
            'btnAllDeniedColor' => Configuration::get($this->prefix.'btnAllDeniedColor'),
            'useSecureMode' => Configuration::get('PS_SSL_ENABLED'),
            'cookieCMSLink' => $this->context->link->getCMSLink(Configuration::get($this->prefix.'CMS')),
            'shops' => $shops,
            'is_multistore_active' => $is_multistore_active,
            'currentShopId' => $current_shop_id,
        ));

        $services = CookieClass::getAllCookies();

        $return = $this->display(dirname(__FILE__), 'k_cookies.script.tpl');
        $script = '<script>';
        foreach ($services as $service){
            $script .= $this->displayService($service);
        }
        $script .= '</script>';
        return $return.$script;
    }

    /**
     * Display service .tpl file
     * @param $service
     * @return string
     */
    public function displayService($service)
    {
        $configs = json_decode($service['config']);
        foreach($configs as $key => $config){
            $this->smarty->assign(array($key => $config));
        }

        return $this->display(dirname(__FILE__), 'services/'.$service['name'].'.tpl');
    }

    /**
     * Add Cookie tab into customer account
     * @return string
     */
    public function hookDisplayCustomerAccount()
    {
        return $this->display(dirname(__FILE__), '/views/templates/front/customerAccount.tpl');
    }

}
