<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

class Exercicedashboard extends Module
{
    public function __construct()
    {
        $this->name = 'exercicedashboard';
        $this->tab = 'dashboard';
        $this->version = '1.0.0';
        $this->author = 'Nael';
        $this->need_instance = 0;

        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->l('Exercice Dashboard');
        $this->description = $this->l('Affiche des données météo dans le dashboard PrestaShop.');

        $this->confirmUninstall = $this->l('Êtes-vous sûr de vouloir désinstaller ce module ?');
    }

    public function install()
    {
        return parent::install()
            && $this->registerHook('dashboardZoneOne')
            && $this->registerHook('displayBackOfficeHeader')
            && $this->createTable()
            && Configuration::updateValue('EXD_MODULE_ACTIVE', 1);
    }

    public function uninstall()
    {
        return parent::uninstall()
            && $this->unregisterHook('dashboardZoneOne')
            && $this->unregisterHook('displayBackOfficeHeader')
            && $this->deleteTable()
            && Configuration::deleteByName('EXD_API_KEY')
            && Configuration::deleteByName('EXD_UPDATE_FREQ')
            && Configuration::deleteByName('EXD_MODULE_ACTIVE');
    }

    protected function createTable()
    {
        $sql = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'exd_data` (
            `id_exd_data` INT AUTO_INCREMENT PRIMARY KEY,
            `temperature` FLOAT,
            `city` VARCHAR(100),
            `updated_at` DATETIME
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

        return Db::getInstance()->execute($sql);
    }

    protected function deleteTable()
    {
        $sql = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'exd_data`';
        return Db::getInstance()->execute($sql);
    }

    public function getContent()
    {
        if (Tools::isSubmit('submit_exd_config')) {
            Configuration::updateValue('EXD_API_KEY', Tools::getValue('EXD_API_KEY'));
            Configuration::updateValue('EXD_UPDATE_FREQ', Tools::getValue('EXD_UPDATE_FREQ'));
            Configuration::updateValue('EXD_MODULE_ACTIVE', (int)Tools::getValue('EXD_MODULE_ACTIVE'));
            $this->context->controller->confirmations[] = $this->l('Configuration enregistrée');
        }

        return $this->renderForm();
    }

    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
        $helper->title = $this->displayName;
        $helper->show_cancel_button = false;
        $helper->submit_action = 'submit_exd_config';

        $fields_form[0]['form'] = [
            'legend' => ['title' => $this->l('Configuration')],
            'input' => [
                [
                    'type' => 'text',
                    'label' => $this->l('Clé API'),
                    'name' => 'EXD_API_KEY',
                    'required' => true,
                ],
                [
                    'type' => 'select',
                    'label' => $this->l('Fréquence de mise à jour'),
                    'name' => 'EXD_UPDATE_FREQ',
                    'options' => [
                        'query' => [
                            ['id' => 'manual', 'name' => $this->l('Manuelle')],
                            ['id' => '24h', 'name' => $this->l('Toutes les 24h')],
                        ],
                        'id' => 'id',
                        'name' => 'name',
                    ],
                ],
                [
                    'type' => 'switch',
                    'label' => $this->l('Activer le module'),
                    'name' => 'EXD_MODULE_ACTIVE',
                    'is_bool' => true,
                    'values' => [
                        ['id' => 'active_on', 'value' => 1, 'label' => $this->l('Oui')],
                        ['id' => 'active_off', 'value' => 0, 'label' => $this->l('Non')],
                    ],
                ],
            ],
            'submit' => ['title' => $this->l('Enregistrer')],
        ];

        $helper->fields_value = [
            'EXD_API_KEY' => Configuration::get('EXD_API_KEY'),
            'EXD_UPDATE_FREQ' => Configuration::get('EXD_UPDATE_FREQ'),
            'EXD_MODULE_ACTIVE' => Configuration::get('EXD_MODULE_ACTIVE'),
        ];

        return $helper->generateForm($fields_form);
    }

    public function hookDisplayBackOfficeHeader()
    {
        if (Tools::getValue('controller') === 'AdminDashboard') {
            Media::addJsDef([
                'ajax_exercise_url' => $this->context->link->getAdminLink('AdminAjaxExercicedashboard')
            ]);
            $this->context->controller->addJS($this->_path . 'views/dashboard.js');
        }
    }

    public function hookDashboardZoneOne()
    {
        if (!Configuration::get('EXD_MODULE_ACTIVE')) {
            return '';
        }

        $row = Db::getInstance()->getRow('SELECT * FROM ' . _DB_PREFIX_ . 'exd_data ORDER BY updated_at DESC');

        $temperature = isset($row['temperature']) ? $row['temperature'] . ' °C' : $this->l('Données indisponibles');
        $lastUpdate = isset($row['updated_at']) ? $row['updated_at'] : 'N/A';

        return '
        <div class="panel">
            <h3><i class="icon-sun"></i> ' . $this->l('Météo actuelle') . '</h3>
            <p><strong>' . $this->l('Température :') . '</strong> ' . $temperature . '</p>
            <p><strong>' . $this->l('Dernière mise à jour :') . '</strong> ' . $lastUpdate . '</p>
            <button id="exercise-refresh-weather" class="btn btn-default">
                <i class="icon-refresh"></i> ' . $this->l('Mettre à jour maintenant') . '
            </button>
        </div>';
    }

    public function updateWeatherData()
    {
        $apiKey = Configuration::get('EXD_API_KEY');
        if (!$apiKey) {
            return false;
        }

        $city = 'Paris';
        $url = 'https://api.openweathermap.org/data/2.5/weather?q=' . urlencode($city) . '&units=metric&appid=' . $apiKey;

        $response = @Tools::file_get_contents($url);
        if (!$response) {
            return false;
        }

        $data = json_decode($response, true);

        if (isset($data['main']['temp'])) {
            return Db::getInstance()->insert('exd_data', [
                'temperature' => (float)$data['main']['temp'],
                'city' => pSQL($city),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }

        return false;
    }
}
