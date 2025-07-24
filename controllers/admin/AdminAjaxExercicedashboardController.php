<?php
class AdminAjaxExercicedashboardController extends ModuleAdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->bootstrap = true; // Bootstrap activé pour le style back-office
        $this->module = Module::getInstanceByName('exercicedashboard'); // On récupère l’instance du module
    }

    // Cette fonction est appelée quand on fait la requête Ajax updateWeather
    public function displayAjaxUpdateWeather()
    {
        // Vérifie que le module est bien chargé
        if (!$this->module) {
            die(json_encode(['success' => false, 'message' => 'Module introuvable']));
        }

        // Vérifie que le module est activé dans la conf
        if (!Configuration::get('EXD_MODULE_ACTIVE')) {
            die(json_encode(['success' => false, 'message' => 'Module désactivé']));
        }

        // Récupère la clé API, sinon on arrête tout
        $apiKey = Configuration::get('EXD_API_KEY');
        if (!$apiKey) {
            die(json_encode(['success' => false, 'message' => 'Clé API manquante']));
        }

        // On appelle la fonction updateWeatherData qui récupère et stocke les données météo
        $success = $this->module->updateWeatherData();

        // Selon le résultat, on renvoie un JSON avec succès ou erreur
        if ($success) {
            die(json_encode(['success' => true, 'message' => 'Météo mise à jour avec succès']));
        } else {
            die(json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour']));
        }
    }
}
