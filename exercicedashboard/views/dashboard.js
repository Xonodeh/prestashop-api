// Quand la page est prête, on ajoute un événement clic au bouton de mise à jour météo
document.addEventListener('DOMContentLoaded', function () {
  const btn = document.getElementById('exercise-refresh-weather');
  if (btn) {
    btn.addEventListener('click', function () {
          // Désactive le bouton et change le texte pendant la requête
      btn.disabled = true;
      btn.innerText = 'Mise à jour...';

      // Envoie une requête POST pour récupérer la météo via Ajax
      fetch(ajax_exercise_url, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: 'ajax=1&action=updateWeather'
      })
        .then(response => response.json())
        .then(data => {
          alert(data.message);// Affiche le message retourné
          location.reload();

          // Met à jour dynamiquement l’affichage de la température et de la date si succès
          if(data.success) {
            const tempSpan = document.getElementById('weather-temp');
            const updateSpan = document.getElementById('weather-update');

            if (tempSpan && updateSpan) {
              // On récupère la température depuis le message
              const tempMatch = data.message.match(/(\d+(\.\d+)?)\s*°C/);
              if (tempMatch) {
                tempSpan.textContent = tempMatch[1] + ' °C';
                updateSpan.textContent = new Date().toLocaleString();
              }
            }
          }
        })
        .catch(() => {
          alert('Erreur lors de la mise à jour météo.');// Gère les erreurs
        })
        .finally(() => { // Réactive le bouton et remet le texte initial
          btn.disabled = false;
          btn.innerText = 'Mettre à jour maintenant';
        });
    });
  }
});
