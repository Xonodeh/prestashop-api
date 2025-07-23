document.addEventListener('DOMContentLoaded', function () {
    const btn = document.getElementById('exercise-refresh-weather');
    if (btn) {
      btn.addEventListener('click', function () {
        btn.disabled = true;
        btn.innerText = 'Mise à jour...';
  
        fetch(ajax_exercise_url, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
          },
          body: 'ajax=1&action=updateWeather'
        })
          .then(response => response.json())
          .then(data => {
            alert(data.message);
          })
          .catch(() => {
            alert('Erreur lors de la mise à jour météo.');
          })
          .finally(() => {
            btn.disabled = false;
            btn.innerText = 'Mettre à jour maintenant';
          });
      });
    }
  });
  