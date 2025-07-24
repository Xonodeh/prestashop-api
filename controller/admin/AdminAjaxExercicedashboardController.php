<?php
class AdminAjaxExercicedashboardController extends ModuleAdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->bootstrap = true;
        $this->module = Module::getInstanceByName('exercicedashboard');
    }

    public function displayAjaxUpdateWeather()
    {
        if (!$this->module) {
            die(json_encode(['success' => false, 'message' => 'Module introuvable']));
        }

        if (!Configuration::get('EXD_MODULE_ACTIVE')) {
            die(json_encode(['success' => false, 'message' => 'Module désactivé']));
        }

        $apiKey = Configuration::get('EXD_API_KEY');
        if (!$apiKey) {
            die(json_encode(['success' => false, 'message' => 'Clé API manquante']));
        }

        // Appelle la fonction updateWeatherData du module pour faire la mise à jour
        $success = $this->module->updateWeatherData();

        if ($success) {
            die(json_encode(['success' => true, 'message' => 'Météo mise à jour avec succès']));
        } else {
            die(json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour']));
        }
    }
}
