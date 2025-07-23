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

        if (!Configuration::get('EXERCISE_ACTIVE')) {
            die(json_encode(['success' => false, 'message' => 'Module désactivé']));
        }

        $apiKey = Configuration::get('EXERCISE_API_KEY');
        if (!$apiKey) {
            die(json_encode(['success' => false, 'message' => 'Clé API manquante']));
        }

        $city = 'Paris'; // tu peux rendre configurable plus tard

        $url = 'https://api.openweathermap.org/data/2.5/weather?q=' . urlencode($city) . '&units=metric&appid=' . $apiKey;

        $response = @file_get_contents($url);
        if (!$response) {
            die(json_encode(['success' => false, 'message' => 'Impossible de récupérer les données météo']));
        }

        $data = json_decode($response, true);
        if (!isset($data['main']['temp'])) {
            die(json_encode(['success' => false, 'message' => 'Données météo invalides']));
        }

        $temperature = (float)$data['main']['temp'];
        $now = date('Y-m-d H:i:s');

        // Enregistrer en base
        Db::getInstance()->insert('exercicedashboard_data', [
            'temperature' => $temperature,
            'last_update' => $now,
        ]);

        die(json_encode(['success' => true, 'message' => 'Météo mise à jour : ' . $temperature . ' °C']));
    }
}
