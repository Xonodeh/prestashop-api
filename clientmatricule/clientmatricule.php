<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

use PrestaShop\PrestaShop\Core\Form\FormField;

class ClientMatricule extends Module
{
    public function __construct()
    {
        $this->name = 'clientmatricule';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'Nael';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Client Matricule Checker');
        $this->description = $this->l('Ajoute un champ matricule à l’inscription client et vérifie sa validité.');
    }

    public function install()
    {
        return parent::install()
            && $this->registerHook('additionalCustomerFormFields')
            && $this->registerHook('actionCustomerAccountAdd')
            && $this->installDB();
    }

    public function uninstall()
    {
        return parent::uninstall()
            && $this->uninstallDB();
    }

    protected function installDB()
    {
        $sql = "CREATE TABLE IF NOT EXISTS `"._DB_PREFIX_."customer_matricule` (
            `id_customer_matricule` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `id_customer` INT UNSIGNED NOT NULL,
            `id_shop` INT UNSIGNED NOT NULL,
            `matricule` VARCHAR(255) NOT NULL,
            `date_add` DATETIME NOT NULL,
            PRIMARY KEY (`id_customer_matricule`)
        ) ENGINE="._MYSQL_ENGINE_." DEFAULT CHARSET=utf8mb4;";

        return Db::getInstance()->execute($sql);
    }

    protected function uninstallDB()
    {
        return Db::getInstance()->execute("DROP TABLE IF EXISTS `"._DB_PREFIX_."customer_matricule`");
    }

    public function hookAdditionalCustomerFormFields($params)
    {
        return [
            (new FormField())
                ->setName('matricule')
                ->setType('text')
                ->setLabel($this->l('Matricule'))
                ->setRequired(true)
        ];
    }

    public function hookActionCustomerAccountAdd($params)
    {
        $customer = $params['newCustomer'];
        $matricule = Tools::getValue('matricule');
        $idShop = (int)Context::getContext()->shop->id;

        if ($matricule) {
            Db::getInstance()->insert('customer_matricule', [
                'id_customer' => (int)$customer->id,
                'id_shop' => $idShop,
                'matricule' => pSQL($matricule),
                'date_add' => date('Y-m-d H:i:s')
            ]);
        }
    }

    public function getContent()
    {
        $output = '';

        if (Tools::isSubmit('submitMatriculeFile')) {
            if (isset($_FILES['matricule_file']) && $_FILES['matricule_file']['error'] == 0) {
                $shopId = (int)Context::getContext()->shop->id;
                $filename = _PS_MODULE_DIR_ . 'clientmatricule/uploads/matricules_shop_' . $shopId . '.csv';

                if (!file_exists(dirname($filename))) {
                    mkdir(dirname($filename), 0755, true);
                }

                if (move_uploaded_file($_FILES['matricule_file']['tmp_name'], $filename)) {
                    $output .= $this->displayConfirmation('Fichier matricule importé avec succès.');
                } else {
                    $output .= $this->displayError('Erreur lors de l\'upload.');
                }
            } else {
                $output .= $this->displayError('Aucun fichier sélectionné.');
            }
        }

        return $output . $this->renderForm();
    }

    protected function renderForm()
    {
        $shopId = (int)Context::getContext()->shop->id;
        $csvPath = _PS_MODULE_DIR_ . 'clientmatricule/uploads/matricules_shop_' . $shopId . '.csv';

        $helper = new HelperForm();
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
        $helper->show_cancel_button = false;
        $helper->default_form_language = (int)Configuration::get('PS_LANG_DEFAULT');
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ?: 0;
        $helper->title = $this->displayName;
        $helper->submit_action = 'submitMatriculeFile';

        $fields_form = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Importer les matricules valides (.csv)'),
                ],
                'input' => [
                    [
                        'type' => 'file',
                        'label' => $this->l('Fichier CSV'),
                        'name' => 'matricule_file',
                        'desc' => $this->l('Une ligne par matricule. Exemple : ABC123'),
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Importer'),
                    'class' => 'btn btn-primary',
                ],
            ],
        ];

        return $helper->generateForm([$fields_form]) .
            $this->fileExistsNotice($csvPath);
    }

    protected function fileExistsNotice($path)
    {
        if (file_exists($path)) {
            return '<div class="alert alert-info">Un fichier est déjà présent pour cette boutique :<br><code>' . basename($path) . '</code></div>';
        }
        return '';
    }
}
