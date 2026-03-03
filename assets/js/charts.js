let messagesPerUserChartInstance = null;
let dailyMessagesChartInstance = null;

function loadCharts() {
    fetch('ajax/fetch_charts.php')
        .then(response => response.json())
        .then(data => {
            renderMessagesPerUserChart(data.messagesPerUser);
            renderDailyMessagesChart(data.dailyMessages);
        });
}

function renderMessagesPerUserChart(data) {
    const ctx = document.getElementById('messagesPerUserChart').getContext('2d');
    
    if (messagesPerUserChartInstance) {
        messagesPerUserChartInstance.destroy();
    }

    messagesPerUserChartInstance = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: data.labels || [],
            datasets: [{
                label: 'Total Messages Sent',
                data: data.data || [],
                backgroundColor: 'rgba(18, 140, 126, 0.6)',
                borderColor: 'rgba(18, 140, 126, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}

function renderDailyMessagesChart(data) {
    const ctx = document.getElementById('dailyMessagesChart').getContext('2d');
    
    if (dailyMessagesChartInstance) {
        dailyMessagesChartInstance.destroy();
    }

    dailyMessagesChartInstance = new Chart(ctx, {
        type: 'line',
        data: {
            labels: data.labels || [],
            datasets: [{
                label: 'Daily Messages',
                data: data.data || [],
                backgroundColor: 'rgba(37, 211, 102, 0.2)',
                borderColor: 'rgba(37, 211, 102, 1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}