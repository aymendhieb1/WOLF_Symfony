const WEATHER_API_KEY = "1bca0ccc6c06a3ec4663f2baa688aa0d";
const WEATHER_API_URL = "http://api.openweathermap.org/data/2.5/forecast";

function getWeatherForDate(sessionDate) {
    const today = new Date();
    const targetDate = new Date(sessionDate);
    const diffTime = Math.abs(targetDate - today);
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

    // If date is more than 5 days away, return indeterminate
    if (diffDays > 5) {
        return Promise.resolve({
            status: 'indeterminate',
            message: 'Météo non disponible'
        });
    }

    return fetch(`${WEATHER_API_URL}?q=Ariana,TN&units=metric&appid=${WEATHER_API_KEY}`)
        .then(response => response.json())
        .then(data => {
            // Find the forecast closest to our target date
            const forecast = data.list.find(item => {
                const forecastDate = new Date(item.dt * 1000);
                return forecastDate.getDate() === targetDate.getDate();
            });

            if (forecast) {
                return {
                    status: 'success',
                    temp: Math.round(forecast.main.temp),
                    description: forecast.weather[0].description,
                    icon: forecast.weather[0].icon
                };
            } else {
                return {
                    status: 'not_found',
                    message: 'Météo non disponible'
                };
            }
        })
        .catch(error => {
            console.error('Error fetching weather:', error);
            return {
                status: 'error',
                message: 'Erreur de chargement'
            };
        });
}

function updateSessionCardWeather(modalElement, sessionDate) {
    getWeatherForDate(sessionDate).then(weatherData => {
        if (weatherData.status === 'success') {
            const weatherHtml = `
                <div class="weather-info">
                    <img src="http://openweathermap.org/img/w/${weatherData.icon}.png" alt="Weather icon" style="width: 50px; height: 50px;">
                    <div class="weather-details">
                        <span class="temperature">${weatherData.temp}°C</span>
                        <span class="description">${weatherData.description}</span>
                    </div>
                </div>
            `;
            // Insert weather info after the places disponibles
            const placesElement = modalElement.querySelector('[class*="places disponibles"]');
            if (placesElement) {
                const weatherDiv = document.createElement('div');
                weatherDiv.className = 'weather-container';
                weatherDiv.style.marginTop = '10px';
                weatherDiv.innerHTML = weatherHtml;
                placesElement.parentNode.insertBefore(weatherDiv, placesElement.nextSibling);
            }
        } else {
            console.log('Weather status:', weatherData.status, weatherData.message);
        }
    });
}

// Function to initialize weather for session cards in modal
function initializeWeather() {
    const sessionModal = document.querySelector('.modal-content');
    if (sessionModal) {
        const dateElements = sessionModal.querySelectorAll('[class*="date"]');
        dateElements.forEach(dateEl => {
            const dateText = dateEl.textContent.trim();
            if (dateText) {
                const date = dateText.split(' ')[0]; // Extract date part
                updateSessionCardWeather(dateEl.closest('.modal-content'), date);
            }
        });
    }
}

// Call initializeWeather when modal is shown
document.addEventListener('DOMContentLoaded', function() {
    // Initialize weather when modal is shown
    const modalTriggers = document.querySelectorAll('[data-toggle="modal"]');
    modalTriggers.forEach(trigger => {
        trigger.addEventListener('click', function() {
            // Wait for modal to be fully shown
            setTimeout(initializeWeather, 500);
        });
    });
}); 