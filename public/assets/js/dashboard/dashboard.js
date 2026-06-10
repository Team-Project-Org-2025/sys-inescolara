const PALETTE = [
  '#2e7d32', '#e5a835', '#0ea5e9', '#dc2626', '#8b5cf6',
  '#06b6d4', '#f59e0b', '#ec4899', '#14b8a6', '#f97316',
];

function renderPlantsChart(data) {
  const canvas = document.getElementById('chartPlantsBySpecies');
  if (!canvas || !data || !data.length) return;

  const labels = data.map(d => d.label || '');
  const values = data.map(d => parseInt(d.value, 10) || 0);
  const colors = PALETTE.slice(0, labels.length);

  new Chart(canvas.getContext('2d'), {
    type: 'bar',
    data: {
      labels,
      datasets: [{
        label: 'Cantidad',
        data: values,
        backgroundColor: colors.map(c => c + '33'),
        borderColor: colors,
        borderWidth: 1.5,
        borderRadius: 3,
        barPercentage: 0.6,
        categoryPercentage: 0.7,
      }],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      animation: { duration: 500 },
      plugins: {
        legend: { display: false },
        tooltip: {
          backgroundColor: 'rgba(0,0,0,0.8)',
          padding: 8,
          cornerRadius: 6,
          callbacks: {
            label: (ctx) => ` ${ctx.parsed.y} plantas`,
          },
        },
      },
      scales: {
        x: {
          grid: { display: false },
          ticks: { font: { size: 9 }, maxRotation: 35 },
        },
        y: {
          beginAtZero: true,
          grid: { color: 'rgba(0,0,0,0.04)', drawBorder: false },
          ticks: {
            font: { size: 9 },
            stepSize: 1,
          },
        },
      },
    },
  });
}

function renderInventoryChart(data) {
  const canvas = document.getElementById('chartInventorySummary');
  if (!canvas || !data || !data.length) return;

  const labels = data.map(d => d.label || '');
  const values = data.map(d => parseInt(d.value, 10) || 0);

  const colorMap = {
    'Alto': '#2e7d32',
    'Medio': '#e5a835',
    'Bajo': '#f59e0b',
    'Sin stock': '#dc2626',
  };
  const colors = labels.map(l => colorMap[l] || '#6b7280');
  const bgColors = colors.map(c => c + '80');

  new Chart(canvas.getContext('2d'), {
    type: 'doughnut',
    data: {
      labels,
      datasets: [{
        data: values,
        backgroundColor: bgColors,
        borderColor: colors,
        borderWidth: 2,
        hoverOffset: 6,
      }],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      cutout: '60%',
      animation: { animateRotate: true, duration: 600 },
      plugins: {
        legend: {
          position: 'bottom',
          labels: { font: { size: 9 }, padding: 8, usePointStyle: true },
        },
        tooltip: {
          backgroundColor: 'rgba(0,0,0,0.8)',
          padding: 8,
          cornerRadius: 6,
          callbacks: {
            label: (ctx) => {
              const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
              const pct = total > 0 ? ((ctx.parsed / total) * 100).toFixed(1) : 0;
              return ` ${ctx.label}: ${ctx.parsed} lotes (${pct}%)`;
            },
          },
        },
      },
    },
  });
}

function initDashboard() {
  if (typeof window.plantsBySpecies !== 'undefined') {
    renderPlantsChart(window.plantsBySpecies);
  }
  if (typeof window.inventorySummary !== 'undefined') {
    renderInventoryChart(window.inventorySummary);
  }
}

if (document.readyState === 'complete' || document.readyState === 'interactive') {
  initDashboard();
} else {
  document.addEventListener('DOMContentLoaded', initDashboard);
}
