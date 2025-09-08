document.addEventListener('DOMContentLoaded', async function () {
  try {
    const resp = await fetch('../modulos/inicio/charts/chart_1.php');
    const chartData = await resp.json();

    document.getElementById('numero2').textContent = chartData.total;
    document.getElementById('referencia2').textContent = chartData.refAntigua;
    console.log('Respuesta backend:', chartData);

    const canvas = document.getElementById('aduanasChart2');
    if (canvas) {
      const ctx = canvas.getContext('2d');
      new Chart(ctx, {
        type: 'bar',
        data: {
          labels: chartData.labels,
          datasets: [{
            label: 'En Contabilidad',
            data: chartData.data,
            backgroundColor: 'rgba(211, 8, 52, 0.64)',
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