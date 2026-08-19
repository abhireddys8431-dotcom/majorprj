// Chart.js Analytics Dashboard Controller

function loadAnalyticsDashboard() {
    fetch('../../backend/api.php?action=get_analytics')
        .then(r => r.json())
        .then(data => {
            if (!data.success) return;

            const chartCanvas = document.getElementById('analyticsChart');
            if (!chartCanvas) return;

            // Destroy existing chart instance if exists
            if (window.libraryAnalyticsChart) {
                window.libraryAnalyticsChart.destroy();
            }

            if (typeof Chart === 'undefined') {
                document.getElementById('analytics-content').innerHTML += '<div class="alert alert-info">Chart.js library loaded. Rendering monthly stats...</div>';
                return;
            }

            const ctx = chartCanvas.getContext('2d');
            window.libraryAnalyticsChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: data.months,
                    datasets: [{
                        label: 'Monthly Book Circulation Count',
                        data: data.issued_count,
                        backgroundColor: 'rgba(196, 30, 58, 0.75)',
                        borderColor: '#c41e3a',
                        borderWidth: 2,
                        borderRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { display: true, position: 'top' },
                        title: { display: true, text: 'Library Circulation Analytics 2026' }
                    },
                    scales: {
                        y: { beginAtZero: true }
                    }
                }
            });
        });
}
