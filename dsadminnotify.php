<?php
/*
 * ----------------------------------------------------------------------------
 * "THE BEER-WARE LICENSE" (Revision 42):
 * <DARK SIDE TEAM> wrote this file. As long as you retain this notice you
 * can do whatever you want with this stuff. If we meet some day, and you think
 * this stuff is worth it, you can buy me a beer in return Poul-Henning Kamp
 * ----------------------------------------------------------------------------
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class DsAdminnotify extends Module
{
    protected $config_form = false;

    public function __construct()
    {
        $this->name = 'dsadminnotify';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'Dark-Side.pro';
        $this->need_instance = 1;
        $this->module_key = 'b32e2ac80ed4f9a5de4f86984ab7202b';

        /*
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('DS: Admin Customer Control');
        $this->description = $this->l('Notify admin about news from store');

        $this->confirmUninstall = $this->l('');

        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update.
     */
    private function createTab()
    {
        $response = true;
        $parentTabID = Tab::getIdFromClassName('AdminDarkSideMenu');
        if ($parentTabID) {
            $parentTab = new Tab($parentTabID);
        } else {
            $parentTab = new Tab();
            $parentTab->active = 1;
            $parentTab->name = array();
            $parentTab->class_name = 'AdminDarkSideMenu';
            foreach (Language::getLanguages() as $lang) {
                $parentTab->name[$lang['id_lang']] = 'Dark-Side.pro';
            }
            $parentTab->id_parent = 0;
            $parentTab->module = '';
            $response &= $parentTab->add();
        }
        $parentTab_2ID = Tab::getIdFromClassName('AdminDarkSideMenuSecond');
        if ($parentTab_2ID) {
            $parentTab_2 = new Tab($parentTab_2ID);
        } else {
            $parentTab_2 = new Tab();
            $parentTab_2->active = 1;
            $parentTab_2->name = array();
            $parentTab_2->class_name = 'AdminDarkSideMenuSecond';
            foreach (Language::getLanguages() as $lang) {
                $parentTab_2->name[$lang['id_lang']] = 'Dark-Side Config';
            }
            $parentTab_2->id_parent = $parentTab->id;
            $parentTab_2->module = '';
            $response &= $parentTab_2->add();
        }
        $tab = new Tab();
        $tab->active = 1;
        $tab->class_name = 'AdministratorDsAdminNotify';
        $tab->name = array();
        foreach (Language::getLanguages() as $lang) {
            $tab->name[$lang['id_lang']] = 'Admin Customer Control';
        }
        $tab->id_parent = $parentTab_2->id;
        $tab->module = $this->name;
        $response &= $tab->add();

        return $response;
    }

    private function tabRem()
    {
        $id_tab = Tab::getIdFromClassName('AdministratorDsAdminNotify');
        if ($id_tab) {
            $tab = new Tab($id_tab);
            $tab->delete();
        }
        $parentTab_2ID = Tab::getIdFromClassName('AdminDarkSideMenuSecond');
        if ($parentTab_2ID) {
            $tabCount_2 = Tab::getNbTabs($parentTab_2ID);
            if ($tabCount_2 == 0) {
                $parentTab_2 = new Tab($parentTab_2ID);
                $parentTab_2->delete();
            }
        }
        $parentTabID = Tab::getIdFromClassName('AdminDarkSideMenu');
        if ($parentTabID) {
            $tabCount = Tab::getNbTabs($parentTabID);
            if ($tabCount == 0) {
                $parentTab = new Tab($parentTabID);
                $parentTab->delete();
            }
        }

        return true;
    }

    public function install()
    {
        $this->createTab();

        Configuration::updateValue('DSADMINNOTIFY_ACCOUNT_EMAIL', null);
        Configuration::updateValue('DSADMINNOTIFY_UPDATE_ACCOUNT', true);
        Configuration::updateValue('DSADMINNOTIFY_ADD_ACCOUNT', true);
        Configuration::updateValue('DSADMINNOTIFY_LOGOUT_ACCOUNT', true);
        Configuration::updateValue('DSADMINNOTIFY_LOGIN_ACCOUNT', true);
        Configuration::updateValue('DSADMINNOTIFY_ORDER_RETURN', true);
        Configuration::updateValue('DSADMINNOTIFY_PAYMENT_SUCCESS', true);
        Configuration::updateValue('DSADMINNOTIFY_ORDER_UPDATE', true);

        return parent::install() &&
            $this->registerHook('actionCustomerAccountAdd') &&
            $this->registerHook('actionCustomerAccountUpdate') &&
            $this->registerHook('actionCustomerLogoutAfter') &&
            $this->registerHook('actionPaymentConfirmation') &&
            $this->registerHook('actionOrderStatusUpdate') &&
            $this->registerHook('actionOrderReturn') &&
            $this->registerHook('actionCustomerLoginAfter') &&
            $this->registerHook('actionCustomerLogoutBefore');
    }

    public function uninstall()
    {
        $this->tabRem();
        Configuration::deleteByName('DSADMINNOTIFY_ORDER_UPDATE');
        Configuration::deleteByName('DSADMINNOTIFY_PAYMENT_SUCCESS');
        Configuration::deleteByName('DSADMINNOTIFY_ORDER_RETURN');
        Configuration::deleteByName('DSADMINNOTIFY_LOGIN_ACCOUNT');
        Configuration::deleteByName('DSADMINNOTIFY_LOGOUT_ACCOUNT');
        Configuration::deleteByName('DSADMINNOTIFY_ADD_ACCOUNT');
        Configuration::deleteByName('DSADMINNOTIFY_UPDATE_ACCOUNT');
        Configuration::deleteByName('DSADMINNOTIFY_ACCOUNT_EMAIL');

        return parent::uninstall();
    }

    /**
     * Load the configuration form.
     */
    public function getContent()
    {
        /*
         * If values have been submitted in the form, process.
         */
        if (((bool) Tools::isSubmit('submitAdminnotifyModule')) == true) {
            $msg = $this->postProcess();
            $form = $this->renderForm();

            return $msg.$form;
        }

        return $this->renderForm();
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitAdminnotifyModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($this->getConfigForm()));
    }

    /**
     * Create the structure of your form.
     */
    protected function getConfigForm()
    {
        return array(
            'form' => array(
                'legend' => array(
                'title' => $this->l('Settings'),
                'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'prefix' => '<i class="icon icon-envelope"></i>',
                        'desc' => $this->l('Enter a valid email address'),
                        'name' => 'DSADMINNOTIFY_ACCOUNT_EMAIL',
                        'label' => $this->l('Email'),
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Active'),
                        'name' => 'DSADMINNOTIFY_UPDATE_ACCOUNT',
                        'is_bool' => true,
                        'desc' => $this->l('Enable if you want have notification when customer update account.'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled'),
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled'),
                            ),
                        ),
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Active'),
                        'name' => 'DSADMINNOTIFY_ADD_ACCOUNT',
                        'is_bool' => true,
                        'desc' => $this->l('Enable if you want have notification when customer create account.'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled'),
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled'),
                            ),
                        ),
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Active'),
                        'name' => 'DSADMINNOTIFY_LOGOUT_ACCOUNT',
                        'is_bool' => true,
                        'desc' => $this->l('Enable if you want have notification when customer logout from store.'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled'),
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled'),
                            ),
                        ),
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Active'),
                        'name' => 'DSADMINNOTIFY_ORDER_UPDATE',
                        'is_bool' => true,
                        'desc' => $this->l('Enable if you want have notification when customer logout from store.'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled'),
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled'),
                            ),
                        ),
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Active'),
                        'name' => 'DSADMINNOTIFY_PAYMENT_SUCCESS',
                        'is_bool' => true,
                        'desc' => $this->l('Enable if you want have notification when customer logout from store.'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled'),
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled'),
                            ),
                        ),
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Active'),
                        'name' => 'DSADMINNOTIFY_ORDER_RETURN',
                        'is_bool' => true,
                        'desc' => $this->l('Enable if you want have notification when customer logout from store.'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled'),
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled'),
                            ),
                        ),
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValues()
    {
        return array(
            'DSADMINNOTIFY_ACCOUNT_EMAIL' => Configuration::get('DSADMINNOTIFY_ACCOUNT_EMAIL', null),
            'DSADMINNOTIFY_LOGOUT_ACCOUNT' => Configuration::get('DSADMINNOTIFY_LOGOUT_ACCOUNT', true),
            'DSADMINNOTIFY_ADD_ACCOUNT' => Configuration::get('DSADMINNOTIFY_ADD_ACCOUNT', true),
            'DSADMINNOTIFY_UPDATE_ACCOUNT' => Configuration::get('DSADMINNOTIFY_UPDATE_ACCOUNT', true),
            'DSADMINNOTIFY_LOGIN_ACCOUNT' => Configuration::get('DSADMINNOTIFY_LOGIN_ACCOUNT', true),
            'DSADMINNOTIFY_ORDER_RETURN' => Configuration::get('DSADMINNOTIFY_ORDER_RETURN', true),
            'DSADMINNOTIFY_PAYMENT_SUCCESS' => Configuration::get('DSADMINNOTIFY_PAYMENT_SUCCESS', true),
            'DSADMINNOTIFY_ORDER_UPDATE' => Configuration::get('DSADMINNOTIFY_ORDER_UPDATE', true),
        );
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {
        $form_values = $this->getConfigFormValues();
        $email = Tools::getValue('DSADMINNOTIFY_ACCOUNT_EMAIL');
        $updateAccount = Tools::getValue('DSADMINNOTIFY_UPDATE_ACCOUNT');
        $logoutAccount = Tools::getValue('DSADMINNOTIFY_LOGOUT_ACCOUNT');
        $addAccount = Tools::getValue('DSADMINNOTIFY_ADD_ACCOUNT');
        $loginAccount = Tools::getValue('DSADMINNOTIFY_LOGIN_ACCOUNT');
        $orderReturn = Tools::getValue('DSADMINNOTIFY_ORDER_RETURN');
        $paymentSuccess = Tools::getValue('DSADMINNOTIFY_PAYMENT_SUCCESS');
        $orderUpdate = Tools::getValue('DSADMINNOTIFY_ORDER_UPDATE');

        if (Validate::isInt($updateAccount) != true) {
            return $this->displayError($this->trans('Update account field must be number', array(), 'Modules.Adminnotify.Admin'));
        }

        if (Validate::isInt($loginAccount) != true) {
            return $this->displayError($this->trans('Login account field must be number', array(), 'Modules.Adminnotify.Admin'));
        }

        if (Validate::isInt($logoutAccount) != true) {
            return $this->displayError($this->trans('Logout account field must be number', array(), 'Modules.Adminnotify.Admin'));
        }

        if (Validate::isInt($orderReturn) != true) {
            return $this->displayError($this->trans('Order return field must be number', array(), 'Modules.Adminnotify.Admin'));
        }

        if (Validate::isInt($paymentSuccess) != true) {
            return $this->displayError($this->trans('Payment success field must be number', array(), 'Modules.Adminnotify.Admin'));
        }

        if (Validate::isInt($orderUpdate) != true) {
            return $this->displayError($this->trans('Order update field must be number', array(), 'Modules.Adminnotify.Admin'));
        }

        if (Validate::isEmail($email) != true) {
            return $this->displayError($this->trans('You must correct fill email field', array(), 'Modules.Adminnotify.Admin'));
        }

        
            Configuration::updateValue('DSADMINNOTIFY_ACCOUNT_EMAIL', pSQL($email));
            Configuration::updateValue('DSADMINNOTIFY_UPDATE_ACCOUNT', (int)($updateAccount));
            Configuration::updateValue('DSADMINNOTIFY_LOGOUT_ACCOUNT', (int)($logoutAccount));
            Configuration::updateValue('DSADMINNOTIFY_ADD_ACCOUNT', (int)($addAccount));
            Configuration::updateValue('DSADMINNOTIFY_LOGIN_ACCOUNT', (int)($loginAccount));
            Configuration::updateValue('DSADMINNOTIFY_ORDER_RETURN', (int)($orderReturn));
            Configuration::updateValue('DSADMINNOTIFY_PAYMENT_SUCCESS', (int)($paymentSuccess));
            Configuration::updateValue('DSADMINNOTIFY_ORDER_UPDATE', (int)($orderUpdate));

            return $this->displayConfirmation($this->trans('Settings updated.', array(), 'Admin.Adminnotify.Success'));
    }

    public function hookActionCustomerAccountAdd($params)
    {
        $subject = $this->displayName.$this->l('New customer');
        $email = $params['newCustomer']->email;
        $firstname = $params['newCustomer']->firstname;
        $lastname = $params['newCustomer']->lastname;
        $array = array(
            '{email}' => Configuration::get('PS_SHOP_EMAIL'),
            '{message}' => $this->l('You have new customer'),
            '{firstname}' => $firstname,
            '{lastname}' => $lastname,
            '{email}' => $email,
        );

        $isToSend = Configuration::get('DSADMINNOTIFY_ADD_ACCOUNT');

        if ($isToSend == true) {
            $send = $this->sendMail($subject, $array);
        }
    }

    public function hookActionCustomerAccountUpdate($params)
    {
        $data = get_object_vars($params['customer']);
        $email = $data['email'];
        $firstname = $data['firstname'];
        $lastname = $data['lastname'];
        $subject = $this->displayName.$this->l('Customer update account');
        $array = array(
            '{email}' => Configuration::get('PS_SHOP_EMAIL'),
            '{message}' => $this->l('This customer update account:'),
            '{firstname}' => $firstname,
            '{lastname}' => $lastname,
            '{email}' => $email,
        );
        $isToSend = Configuration::get('DSADMINNOTIFY_UPDATE_ACCOUNT');

        if ($isToSend == true) {
            $send = $this->sendMail($subject, $array);
        }
    }

    public function hookActionCustomerLogoutAfter($params)
    {
        $data = get_object_vars($params['customer']);
        $email = $data['email'];
        $firstname = $data['firstname'];
        $lastname = $data['lastname'];

        $subject = $this->displayName.$this->l('Customer logout from store');
        $array = array(
            '{email}' => Configuration::get('PS_SHOP_EMAIL'),
            '{message}' => $this->l('This customer is logout from store:'),
            '{firstname}' => $firstname,
            '{lastname}' => $lastname,
            '{email}' => $email,
        );
        $isToSend = Configuration::get('DSADMINNOTIFY_LOGOUT_ACCOUNT');
        if ($isToSend == true) {
            $send = $this->sendMail($subject, $array);
        }
    }

    public function hookActionPaymentConfirmation($params)
    {
        $orderID = $params['id_order'];
        $customerData = $this->getCustomerData($orderID);
        $subject = $this->displayName.$this->l('The customer made the payment successfully');
        $array = array(
            '{email}' => Configuration::get('PS_SHOP_EMAIL'),
            '{message}' => $this->l('This customer made the payment successfully:'),
            '{firstname}' => $customerData['firstname'],
            '{lastname}' => $customerData['lastname'],
            '{email}' => $customerData['email'],
        );
        $isToSend = Configuration::get('DSADMINNOTIFY_PAYMENT_SUCCESS');

        if ($isToSend == true) {
            $send = $this->sendMail($subject, $array);
        }
    }

    public function hookActionOrderStatusUpdate($params)
    {
        $orderID = $params['id_order'];
        $customerData = $this->getCustomerData($orderID);
        $subject = $this->displayName.$this->l('Order status for the customer has been updated');
        $array = array(
            '{email}' => Configuration::get('PS_SHOP_EMAIL'),
            '{message}' => $this->l('Order status for the customer has been updated:'),
            '{firstname}' => $customerData['firstname'],
            '{lastname}' => $customerData['lastname'],
            '{email}' => $customerData['email'],
        );
        $isToSend = Configuration::get('DSADMINNOTIFY_ORDER_UPDATE');

        if ($isToSend == true) {
            $send = $this->sendMail($subject, $array);
        }
    }

    public function hookActionOrderReturn($params)
    {
        $orderID = new Order((int) $params['orderReturn']->id_order);
        $customerData = $this->getCustomerData($orderID);
        $subject = $this->displayName.$this->l('The customer started the refund process');
        $array = array(
            '{email}' => Configuration::get('PS_SHOP_EMAIL'),
            '{message}' => $this->l('The customer started the refund process:'),
            '{firstname}' => $customerData['firstname'],
            '{lastname}' => $customerData['lastname'],
            '{email}' => $customerData['email'],
        );
        $isToSend = Configuration::get('DSADMINNOTIFY_ORDER_RETURN');

        if ($isToSend == true) {
            $send = $this->sendMail($subject, $array);
        }
    }

    public function hookActionCustomerLoginAfter($params)
    {
        $subject = $this->displayName.$this->l('The customer was login into your store');
        $data = get_object_vars($params['customer']);
        $email = $data['email'];
        $firstname = $data['firstname'];
        $lastname = $data['lastname'];
        $array = array(
            '{email}' => Configuration::get('PS_SHOP_EMAIL'),
            '{message}' => $this->l('The customer is login into your store:'),
            '{firstname}' => $firstname,
            '{lastname}' => $lastname,
            '{email}' => $email,
        );
        $isToSend = Configuration::get('DSADMINNOTIFY_LOGIN_ACCOUNT');

        if ($isToSend == true) {
            $send = $this->sendMail($subject, $array);
        }
    }

    protected function getCustomerData($orderID)
    {
        $sql = 'SELECT '._DB_PREFIX_.'customer.firstname, '._DB_PREFIX_.'customer.lastname, '._DB_PREFIX_.'customer.email FROM '._DB_PREFIX_.'orders LEFT JOIN '._DB_PREFIX_.'customer ON '._DB_PREFIX_.'customer.id_customer = '._DB_PREFIX_.'orders.id_customer WHERE '._DB_PREFIX_.'orders.id_order = '.(int)$orderID;
        $result = Db::getInstance()->ExecuteS($sql);

        return $result;
    }

    protected function sendMail($subject, $array)
    {
        $langID = (int) Configuration::get('PS_LANG_DEFAULT');
        $template_path = $this->local_path.'mails/';
        $to = Configuration::get('DSADMINNOTIFY_ACCOUNT_EMAIL');
        $template = 'template';

        Mail::send($langID, $template, pSQL($subject), $array, pSQL($to), null, null, null, null, $mode_smtp = 1, $template_path, $die = false, $idShop = null, $bcc = null, $replyTo = null, $replyToName = null);
    }
}
