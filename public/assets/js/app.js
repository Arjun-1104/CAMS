// Updates the server clock and attendance chart.

async function refreshServerClock() {
  const el = document.getElementById('server-clock');
  if (!el) return;
  try {
    const res = await fetch(el.dataset.url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
    const data = await res.json();
    el.textContent = data.date + ' · ' + data.time + ' (server)';
  } catch (e) {
    el.textContent = 'Server clock unavailable';
  }
}

document.addEventListener('DOMContentLoaded', () => {
  refreshServerClock();
  setInterval(refreshServerClock, 1000);

  const chartEl = document.getElementById('attChart');
  if (chartEl && window.Chart) {
    const labels = JSON.parse(chartEl.dataset.labels || '[]');
    const values = JSON.parse(chartEl.dataset.values || '[]');
    new Chart(chartEl, {
      type: 'doughnut',
      data: {
        labels,
        datasets: [{ data: values, backgroundColor: ['#1a2b48', '#b8892d', '#2b5f7a', '#1f6b4a', '#8f2d2d'] }]
      },
      options: { plugins: { legend: { position: 'bottom' } } }
    });
  }
});
