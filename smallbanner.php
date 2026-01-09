<?php
/**
 * Small Banner Module
 *
 * @author    Antigravity
 * @copyright 2026 Antigravity
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class SmallBanner extends Module
{
    protected $config_name = 'SMALLBANNER_CONFIG';

    public function __construct()
    {
        $this->name = 'smallbanner';
        $this->tab = 'front_office_features';
        $this->version = '1.1.0';
        $this->author = 'Mykhailo Chernykh';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Small Banner Pro');
        $this->description = $this->l('Цей модуль дозволяє виводити кастомний банер у різних частинах сайту з підтримкою мобільної адаптації.');

        $this->ps_versions_compliancy = array('min' => '1.7', 'max' => _PS_VERSION_);
    }

    public function install()
    {
        return parent::install() &&
            $this->registerHook('displayHeader') &&
            $this->registerHook('displayTop');
    }

    public function uninstall()
    {
        Configuration::deleteByName('SMALLBANNER_IMAGE');
        Configuration::deleteByName('SMALLBANNER_HOOKS');
        return parent::uninstall();
    }

    public function getContent()
    {
        $output = '';

        if (Tools::isSubmit('submit' . $this->name)) {
            $all_hooks = array('displayTop', 'displayFooter', 'displayHome', 'displayLeftColumn', 'displayRightColumn');
            $selected_hooks = array();
            foreach ($all_hooks as $hook) {
                if (Tools::getValue('SMALLBANNER_HOOKS_' . $hook)) {
                    $selected_hooks[] = $hook;
                }
            }
            Configuration::updateValue('SMALLBANNER_HOOKS', json_encode($selected_hooks));
            Configuration::updateValue('SMALLBANNER_WIDTH', (int)Tools::getValue('SMALLBANNER_WIDTH'));
            Configuration::updateValue('SMALLBANNER_TOP', (int)Tools::getValue('SMALLBANNER_TOP'));
            Configuration::updateValue('SMALLBANNER_RIGHT', (int)Tools::getValue('SMALLBANNER_RIGHT'));
            Configuration::updateValue('SMALLBANNER_LINK', Tools::getValue('SMALLBANNER_LINK'));

            if (isset($_FILES['SMALLBANNER_IMAGE']) && !empty($_FILES['SMALLBANNER_IMAGE']['tmp_name'])) {
                if ($error = ImageManager::validateUpload($_FILES['SMALLBANNER_IMAGE'])) {
                    $output .= $this->displayError($error);
                } else {
                    $ext = pathinfo($_FILES['SMALLBANNER_IMAGE']['name'], PATHINFO_EXTENSION);
                    $file_name = md5($_FILES['SMALLBANNER_IMAGE']['name'] . time()) . '.' . $ext;
                    if (!move_uploaded_file($_FILES['SMALLBANNER_IMAGE']['tmp_name'], _PS_MODULE_DIR_ . $this->name . '/views/img/' . $file_name)) {
                        $output .= $this->displayError($this->l('An error occurred while attempting to upload the file.'));
                    } else {
                        Configuration::updateValue('SMALLBANNER_IMAGE', $file_name);
                        $output .= $this->displayConfirmation($this->l('Image uploaded successfully.'));
                    }
                }
            }

            $this->_updateHooks($selected_hooks);
            $output .= $this->displayConfirmation($this->l('Settings updated.'));
        }

        return $output . $this->renderForm();
    }

    protected function _updateHooks($selected_hooks)
    {
        $all_hooks = array('displayTop', 'displayFooter', 'displayHome', 'displayLeftColumn', 'displayRightColumn');
        foreach ($all_hooks as $hook) {
            if (is_array($selected_hooks) && in_array($hook, $selected_hooks)) {
                $this->registerHook($hook);
            } else {
                $this->unregisterHook($hook);
            }
        }
    }

    public function renderForm()
    {
        $fields_form = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Settings'),
                    'icon' => 'icon-cogs'
                ),
                'input' => array(
                    array(
                        'type' => 'file',
                        'label' => $this->l('Banner image'),
                        'name' => 'SMALLBANNER_IMAGE',
                        'display_image' => true,
                        'desc' => $this->l('Upload a small banner image.'),
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Banner max-width (px)'),
                        'name' => 'SMALLBANNER_WIDTH',
                        'class' => 'fixed-width-lg',
                        'desc' => $this->l('Default is 120.'),
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Top offset (px)'),
                        'name' => 'SMALLBANNER_TOP',
                        'class' => 'fixed-width-lg',
                        'desc' => $this->l('Vertical offset from top. Default is 5.'),
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Right offset (px)'),
                        'name' => 'SMALLBANNER_RIGHT',
                        'class' => 'fixed-width-lg',
                        'desc' => $this->l('Horizontal offset from right. Default is 70.'),
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Banner link'),
                        'name' => 'SMALLBANNER_LINK',
                        'desc' => $this->l('URL where the user is redirected when clicking the banner (optional).'),
                    ),
                    array(
                        'type' => 'checkbox',
                        'label' => $this->l('Display in hooks'),
                        'name' => 'SMALLBANNER_HOOKS',
                        'values' => array(
                            'query' => array(
                                array('id' => 'displayTop', 'name' => 'displayTop (Header Right)'),
                                array('id' => 'displayFooter', 'name' => 'displayFooter'),
                                array('id' => 'displayHome', 'name' => 'displayHome'),
                                array('id' => 'displayLeftColumn', 'name' => 'displayLeftColumn'),
                                array('id' => 'displayRightColumn', 'name' => 'displayRightColumn'),
                            ),
                            'id' => 'id',
                            'name' => 'name'
                        )
                    )
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                )
            ),
        );

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
        $helper->submit_action = 'submit' . $this->name;
        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFieldsValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id
        );

        return $helper->generateForm(array($fields_form));
    }

    public function getConfigFieldsValues()
    {
        $config_hooks = Configuration::get('SMALLBANNER_HOOKS');
        $hooks = $config_hooks ? json_decode($config_hooks, true) : array();
        
        $image = Configuration::get('SMALLBANNER_IMAGE');
        $image_url = '';
        if ($image) {
            $image_url = $this->context->link->getMediaLink(_MODULE_DIR_ . $this->name . '/views/img/' . $image);
        }

        $values = array(
            'SMALLBANNER_IMAGE' => $image,
            'SMALLBANNER_WIDTH' => Configuration::get('SMALLBANNER_WIDTH') ?: 120,
            'SMALLBANNER_TOP' => Configuration::get('SMALLBANNER_TOP') !== false ? Configuration::get('SMALLBANNER_TOP') : 5,
            'SMALLBANNER_RIGHT' => Configuration::get('SMALLBANNER_RIGHT') !== false ? Configuration::get('SMALLBANNER_RIGHT') : 70,
            'SMALLBANNER_LINK' => Configuration::get('SMALLBANNER_LINK'),
        );

        // Pre-fill file field with image thumbnail if it exists
        if ($image) {
            $values['SMALLBANNER_IMAGE_thumb'] = $image_url;
            $image_path = _PS_MODULE_DIR_ . $this->name . '/views/img/' . $image;
            $values['SMALLBANNER_IMAGE_size'] = file_exists($image_path) ? filesize($image_path) / 1024 : 0;
        }

        $all_hooks = array('displayTop', 'displayFooter', 'displayHome', 'displayLeftColumn', 'displayRightColumn');
        foreach ($all_hooks as $hook) {
            $values['SMALLBANNER_HOOKS_' . $hook] = is_array($hooks) && in_array($hook, $hooks);
        }

        return $values;
    }

    public function hookDisplayHeader()
    {
        $this->context->controller->addCSS($this->_path . 'views/css/smallbanner.css', 'all');
    }

    public function hookDisplayTop($params) { return $this->renderBanner('displayTop'); }
    public function hookDisplayFooter($params) { return $this->renderBanner('displayFooter'); }
    public function hookDisplayHome($params) { return $this->renderBanner('displayHome'); }
    public function hookDisplayLeftColumn($params) { return $this->renderBanner('displayLeftColumn'); }
    public function hookDisplayRightColumn($params) { return $this->renderBanner('displayRightColumn'); }

    protected function renderBanner($hookName)
    {
        $image = Configuration::get('SMALLBANNER_IMAGE');
        if (!$image) {
            $image = 'default.png';
        }

        $width = (int)Configuration::get('SMALLBANNER_WIDTH') ?: 120;
        $top = Configuration::get('SMALLBANNER_TOP') !== false ? Configuration::get('SMALLBANNER_TOP') : 5;
        $right = Configuration::get('SMALLBANNER_RIGHT') !== false ? Configuration::get('SMALLBANNER_RIGHT') : 70;
        $link = Configuration::get('SMALLBANNER_LINK');

        $this->context->smarty->assign(array(
            'smallbanner_img' => $this->context->link->getMediaLink(_MODULE_DIR_ . $this->name . '/views/img/' . $image),
            'smallbanner_width' => $width,
            'smallbanner_top' => $top,
            'smallbanner_right' => $right,
            'smallbanner_link' => $link,
            'hook_name' => strtolower($hookName)
        ));

        return $this->display(__FILE__, 'smallbanner.tpl');
    }
}
