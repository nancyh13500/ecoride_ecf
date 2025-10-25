document.addEventListener('DOMContentLoaded', function () {
    // Récupération des données depuis PHP (passées via window)
    const covoituragesData = window.covoituragesData || [];

    // Préparer les données pour Chart.js
    const labels = [];
    const data = [];

    // Créer un tableau des 30 derniers jours
    const today = new Date();
    const last30Days = [];

    for (let i = 29; i >= 0; i--) {
        const date = new Date(today);
        date.setDate(date.getDate() - i);
        const dateStr = date.toISOString().split('T')[0];
        last30Days.push(dateStr);
    }

    // Remplir les données (0 pour les jours sans covoiturage)
    last30Days.forEach(date => {
        labels.push(new Date(date).toLocaleDateString('fr-FR', {
            day: '2-digit',
            month: '2-digit'
        }));

        const found = covoituragesData.find(item => item.jour === date);
        data.push(found ? parseInt(found.nombre_covoiturages) : 0);
    });

    // Configuration du graphique
    const ctx = document.getElementById('covoituragesChart').getContext('2d');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Nombre de covoiturages',
                data: data,
                borderColor: 'rgb(75, 192, 192)',
                backgroundColor: 'rgba(75, 192, 192, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: 'rgb(75, 192, 192)',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 5,
                pointHoverRadius: 7
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    borderColor: 'rgb(75, 192, 192)',
                    borderWidth: 1
                }
            },
            scales: {
                x: {
                    display: true,
                    title: {
                        display: true,
                        text: 'Date',
                        color: '#666'
                    },
                    grid: {
                        color: 'rgba(0, 0, 0, 0.1)'
                    }
                },
                y: {
                    display: true,
                    title: {
                        display: true,
                        text: 'Nombre de covoiturages',
                        color: '#666'
                    },
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    },
                    grid: {
                        color: 'rgba(0, 0, 0, 0.1)'
                    }
                }
            },
            interaction: {
                mode: 'nearest',
                axis: 'x',
                intersect: false
            },
            animation: {
                duration: 2000,
                easing: 'easeInOutQuart'
            }
        }
    });
});