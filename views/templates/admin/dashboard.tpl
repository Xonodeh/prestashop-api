<div class="panel">
  <h3><i class="icon-sun"></i> {$l s='Météo actuelle' mod='exercicedashboard'}</h3>
  <p><strong>{$l s='Ville :' mod='exercicedashboard'}</strong> {$city}</p>
  <p><strong>{$l s='Température :' mod='exercicedashboard'}</strong> <span id="weather-temp">{$temperature}</span></p>
  <p><strong>{$l s='Dernière mise à jour :' mod='exercicedashboard'}</strong> <span id="weather-update">{$lastUpdate}</span></p>
  <button id="exercise-refresh-weather" class="btn btn-default">
    <i class="icon-refresh"></i> {$l s='Mettre à jour maintenant' mod='exercicedashboard'}
  </button>
</div>
