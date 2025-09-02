document.addEventListener('DOMContentLoaded', async function () {
  try {
    const resp = await fetch('../modulos/charts/char_1.php');
    const chartData = await resp.json();

    const canvas = document.getElementById('aduanasChart');
    if (canvas) {
      const ctx = canvas.getContext('2d');
      new Chart(ctx, {
        type: 'bar',
        data: {
          labels: chartData.labels,
          datasets: [{
            label: 'Operaciones',
            data: chartData.data,
            backgroundColor: 'rgba(8, 69, 211, 0.64)',
            borderColor: 'rgb(107, 122, 156)',
            borderWidth: 1
          }]
        },
        options: {
          responsive: true,
          scales: {
            y: { beginAtZero: true }
          }
        }
      });
    }
  } catch (err) {
    console.error('Error cargando datos del gráfico:', err);
  }
});