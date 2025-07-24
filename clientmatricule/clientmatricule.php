<?php

if (!defined('_PS_VERSION_')) {
    exit; // Sécurité : empêche l'accès direct au fichier
}

use PrestaShop\PrestaShop\Core\Form\FormField;

class ClientMatricule extends Module
{
    public function __construct()
    {
        // Informations basiques du module
        $this->name = 'clientmatricule';
        $this->tab = 'administration'; // Onglet Back Office
        $this->version = '1.0.0';
        $this->author = 'Nael';
        $this->need_instance = 0; // Pas besoin d'instance dans la liste modules
        $this->bootstrap = true; // Active le style bootstrap pour le BO

        parent::__construct();

        // Nom et description visibles dans le BO
        $this->displayName = $this->l('Client Matricule Checker');
        $this->description = $this->l('Ajoute un champ matricule à l’inscription client et vérifie sa validité.');
    }

    // Méthode d'installation du module
    public function install()
    {
        return parent::install()
            // Enregistrement des hooks nécessaires
            && $this->registerHook('additionalCustomerFormFields') // Pour ajouter le champ matricule au FO
            && $this->registerHook('actionCustomerAccountAdd')     // Pour récupérer et stocker le matricule à l'inscription
            && $this->installDB();                                 // Création de la table personnalisée
    }

    // Méthode de désinstallation du module
    public function uninstall()
    {
        return parent::uninstall()
            && $this->uninstallDB();  // Suppression de la table personnalisée
    }

    // Création de la table SQL qui stockera les matricules liés aux clients et boutiques
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

    // Suppression de la table SQL lors de la désinstallation
    protected function uninstallDB()
    {
        return Db::getInstance()->execute("DROP TABLE IF EXISTS `"._DB_PREFIX_."customer_matricule`");
    }

    // Hook pour ajouter un champ supplémentaire dans le formulaire d'inscription front-office
    public function hookAdditionalCustomerFormFields($params)
    {
        return [
            (new FormField()) // Création d'un champ formulaire
                ->setName('matricule')     // Nom du champ HTML
                ->setType('text')          // Type champ texte
                ->setLabel($this->l('Matricule')) // Libellé affiché
                ->setRequired(true)        // Champ obligatoire
        ];
    }

    // Hook déclenché après la création d'un nouveau compte client
    // Ici on récupère le matricule saisi et on l'insère dans la table personnalisée
    public function hookActionCustomerAccountAdd($params)
    {
        $customer = $params['newCustomer']; // Objet customer nouvellement créé
        $matricule = Tools::getValue('matricule'); // Valeur saisie dans le formulaire
        $idShop = (int)Context::getContext()->shop->id; // Boutique courante

        if ($matricule) {
            // Insertion du matricule en base, lié au client et à la boutique
            Db::getInstance()->insert('customer_matricule', [
                'id_customer' => (int)$customer->id,
                'id_shop' => $idShop,
                'matricule' => pSQL($matricule),
                'date_add' => date('Y-m-d H:i:s')
            ]);
        }
    }

    // Méthode pour générer le contenu de la page configuration du module dans le BO
    public function getContent()
    {
        $output = '';

        // Gestion du formulaire d'upload CSV soumis
        if (Tools::isSubmit('submit_csv_upload') && isset($_FILES['csv_file'])) {
            $file = $_FILES['csv_file'];

            if ($file['error'] === UPLOAD_ERR_OK) {
                // Lecture du contenu CSV
                $csvData = file_get_contents($file['tmp_name']);
                $rows = array_map('str_getcsv', explode(PHP_EOL, $csvData));
                $header = array_shift($rows); // Suppression de la ligne d'en-tête

                // Affichage simple des données extraites pour vérification
                foreach ($rows as $row) {
                    if (count($row) === 3) {
                        list($nom, $prenom, $matricule) = $row;
                        $output .= "Nom : $nom, Prénom : $prenom, Matricule : $matricule<br>";
                    }
                }
            } else {
                $output .= '<div class="error">Erreur lors du téléchargement du fichier.</div>';
            }
        }

        // Assignation des variables Smarty pour le template config.tpl
        $this->context->smarty->assign([
            'current' => AdminController::$currentIndex,  // URL de la page courante dans BO
            'module_name' => $this->name,                 // Nom du module
        ]);

        // Affiche le résultat + formulaire (template)
        return $output . $this->display(__FILE__, 'views/templates/admin/config.tpl');
    }

    // Méthode pour générer le formulaire de configuration dans le BO
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

        // Définition des champs du formulaire (ici un input file pour importer un CSV)
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

        // Génération du formulaire + affichage d'une notice si un fichier existe déjà
        return $helper->generateForm([$fields_form]) .
            $this->fileExistsNotice($csvPath);
    }

    // Affiche un message si un fichier CSV existe déjà pour la boutique courante
    protected function fileExistsNotice($path)
    {
        if (file_exists($path)) {
            return '<div class="alert alert-info">Un fichier est déjà présent pour cette boutique :<br><code>' . basename($path) . '</code></div>';
        }
        return '';
    }
}
