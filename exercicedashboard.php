<?php

if (!defined('_PS_VERSION_')) {
    exit; 
}

class Exercicedashboard extends Module
{
    public function __construct()
    {
        // On définit les infos de base du module
        $this->name = 'exercicedashboard';
        $this->tab = 'dashboard'; 
        $this->version = '1.0.0';
        $this->author = 'Nael';
        $this->need_instance = 0; 

        $this->bootstrap = true; 
        parent::__construct();

        // Titre et description affichés dans BO
        $this->displayName = $this->l('Exercice Dashboard');
        $this->description = $this->l('Affiche des données météo dans le dashboard PrestaShop.');

        $this->confirmUninstall = $this->l('Êtes-vous sûr de vouloir désinstaller ce module ?');
    }

    // Lors de l'installation, on fait les actions nécessaires : hooks + création table + activation module
    public function install()
    {
        return parent::install()
            && $this->registerHook('dashboardZoneOne') // hook pour afficher sur le dashboard
            && $this->registerHook('displayBackOfficeHeader') // pour injecter JS dans le back office
            && $this->createTable() // créer la table en base pour stocker les données
            && Configuration::updateValue('EXD_MODULE_ACTIVE', 1); // on active le module par défaut
    }

    // Lors de la désinstallation, on nettoie tout proprement (hooks, table, conf)
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

    // Création de la table SQL pour stocker les données météo
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

    // Suppression de la table à la désinstallation
    protected function deleteTable()
    {
        $sql = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'exd_data`';
        return Db::getInstance()->execute($sql);
    }

    // Gestion du formulaire de config dans le BO
    public function getContent()
    {
        // Si on a soumis le formulaire, on sauvegarde les valeurs en config PrestaShop
        if (Tools::isSubmit('submit_exd_config')) {
            Configuration::updateValue('EXD_API_KEY', Tools::getValue('EXD_API_KEY'));
            Configuration::updateValue('EXD_UPDATE_FREQ', Tools::getValue('EXD_UPDATE_FREQ'));
            Configuration::updateValue('EXD_MODULE_ACTIVE', (int)Tools::getValue('EXD_MODULE_ACTIVE'));
            $this->context->controller->confirmations[] = $this->l('Configuration enregistrée');
        }

        // On retourne le formulaire à afficher
        return $this->renderForm();
    }

    // Construction du formulaire de config avec HelperForm
    protected function renderForm()
    {
        $helper = new HelperForm();

        // Paramètres basiques du helper
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
        $helper->title = $this->displayName;
        $helper->show_cancel_button = false;
        $helper->submit_action = 'submit_exd_config';

        // On définit les champs du formulaire : clé API, fréquence maj, activation
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

        // Valeurs par défaut des champs (récupérées depuis la config)
        $helper->fields_value = [
            'EXD_API_KEY' => Configuration::get('EXD_API_KEY'),
            'EXD_UPDATE_FREQ' => Configuration::get('EXD_UPDATE_FREQ'),
            'EXD_MODULE_ACTIVE' => Configuration::get('EXD_MODULE_ACTIVE'),
        ];

        // Génère et retourne le HTML du formulaire
        return $helper->generateForm($fields_form);
    }

    // Hook pour ajouter le JS nécessaire sur la page du dashboard back-office
    public function hookDisplayBackOfficeHeader()
    {
        // On ajoute notre script seulement sur la page dashboard du BO
        if (Tools::getValue('controller') === 'AdminDashboard') {
            // Passe l’URL ajax vers le JS pour faire la requête
            Media::addJsDef([
                'ajax_exercise_url' => $this->context->link->getAdminLink('AdminAjaxExercicedashboard')
            ]);
            // Ajoute le fichier JS
            $this->context->controller->addJS($this->_path . 'views/dashboard.js');
        }
    }

    // Hook qui affiche le bloc météo dans la zone 1 du dashboard
    public function hookDashboardZoneOne()
    {
        // Si le module est désactivé, on affiche rien
        if (!Configuration::get('EXD_MODULE_ACTIVE')) {
            return '';
        }

        // Récupère la dernière donnée météo stockée en base
        $row = Db::getInstance()->getRow('SELECT * FROM ' . _DB_PREFIX_ . 'exd_data ORDER BY updated_at DESC');

        // Prépare les infos à afficher, avec fallback si pas de données
        $temperature = isset($row['temperature']) ? $row['temperature'] . ' °C' : $this->l('Données indisponibles');
        $lastUpdate = isset($row['updated_at']) ? $row['updated_at'] : 'N/A';
        $city = isset($row['city']) ? $row['city'] : $this->l('Ville inconnue');
        // Retourne le HTML du bloc affiché dans le dashboard
        return '
        <div class="panel">
            <h3><i class="icon-sun"></i> ' . $this->l('Météo actuelle') . '</h3>
            <p><strong>' . $this->l('Ville :') . '</strong> ' . htmlspecialchars($city) . '</p>
            <p><strong>' . $this->l('Température :') . '</strong> ' . $temperature . '</p>
            <p><strong>' . $this->l('Dernière mise à jour :') . '</strong> ' . $lastUpdate . '</p>
            <button id="exercise-refresh-weather" class="btn btn-default">
                <i class="icon-refresh"></i> ' . $this->l('Mettre à jour maintenant') . '
            </button>
        </div>';
    }

    // Fonction qui fait la requête à l’API météo et stocke la donnée en base
    public function updateWeatherData()
    {
        $apiKey = Configuration::get('EXD_API_KEY');
        if (!$apiKey) {
            return false; // Pas de clé API => on arrête
        }

        $city = 'Paris'; // ville fixée pour l’instant
        $url = 'https://api.openweathermap.org/data/2.5/weather?q=' . urlencode($city) . '&units=metric&appid=' . $apiKey;

        // Récupère le JSON depuis l’API météo
        $response = @Tools::file_get_contents($url);
        if (!$response) {
            return false; // échec requête
        }

        $data = json_decode($response, true);

        // Si on a la température dans la réponse, on insère en base
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
