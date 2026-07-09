/* ============================================================
   Dashboard Admin - JavaScript Principal
   ============================================================ */

document.addEventListener('DOMContentLoaded', () => {

  /* ----------------------------------------------------------
     Sidebar Toggle
     ---------------------------------------------------------- */
  const sidebar = document.getElementById('sidebar');
  const toggleBtn = document.getElementById('sidebarToggle');

  if (toggleBtn && sidebar) {
    toggleBtn.addEventListener('click', () => {
      sidebar.classList.toggle('collapsed');
      localStorage.setItem('sidebar_collapsed', sidebar.classList.contains('collapsed'));
    });

    if (localStorage.getItem('sidebar_collapsed') === 'true') {
      sidebar.classList.add('collapsed');
    }
  }

  /* ----------------------------------------------------------
     Nav Item Ativo
     ---------------------------------------------------------- */
  const currentPage = window.location.pathname.split('/').pop() || 'index.php';
  document.querySelectorAll('.nav-item[data-page]').forEach(item => {
    if (item.dataset.page === currentPage) {
      item.classList.add('active');
    }
  });

  /* ----------------------------------------------------------
     Gráfico de Receita Mensal (Chart.js)
     ---------------------------------------------------------- */
  const revenueCtx = document.getElementById('revenueChart');
  if (revenueCtx && typeof Chart !== 'undefined') {
    const labels  = revenueCtx.dataset.labels  ? JSON.parse(revenueCtx.dataset.labels)  : [];
    const revenue = revenueCtx.dataset.revenue ? JSON.parse(revenueCtx.dataset.revenue) : [];
    const orders  = revenueCtx.dataset.orders  ? JSON.parse(revenueCtx.dataset.orders)  : [];

    new Chart(revenueCtx, {
      type: 'bar',
      data: {
        labels,
        datasets: [
          {
            label: 'Receita (R$)',
            data: revenue,
            backgroundColor: 'rgba(99,102,241,.85)',
            borderRadius: 6,
            yAxisID: 'y',
          },
          {
            label: 'Pedidos',
            data: orders,
            type: 'line',
            borderColor: '#10b981',
            backgroundColor: 'rgba(16,185,129,.1)',
            tension: .4,
            pointRadius: 4,
            pointBackgroundColor: '#10b981',
            fill: true,
            yAxisID: 'y1',
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        plugins: {
          legend: {
            position: 'top',
            align: 'end',
            labels: { boxWidth: 12, font: { size: 12 } },
          },
          tooltip: {
            callbacks: {
              label(ctx) {
                if (ctx.dataset.yAxisID === 'y') {
                  return ' R$ ' + Number(ctx.raw).toLocaleString('pt-BR', { minimumFractionDigits: 2 });
                }
                return ' ' + ctx.raw + ' pedidos';
              },
            },
          },
        },
        scales: {
          x: { grid: { display: false } },
          y: {
            type: 'linear', position: 'left',
            grid: { color: 'rgba(0,0,0,.05)' },
            ticks: {
              callback: v => 'R$' + (v / 1000).toFixed(0) + 'k',
            },
          },
          y1: {
            type: 'linear', position: 'right',
            grid: { drawOnChartArea: false },
            ticks: { stepSize: 10 },
          },
        },
      },
    });
  }

  /* ----------------------------------------------------------
     Gráfico Donut - Fontes de Tráfego
     ---------------------------------------------------------- */
  const trafficCtx = document.getElementById('trafficChart');
  if (trafficCtx && typeof Chart !== 'undefined') {
    new Chart(trafficCtx, {
      type: 'doughnut',
      data: {
        labels: ['Orgânico', 'Direto', 'Social', 'E-mail', 'Referência'],
        datasets: [{
          data: [38, 27, 18, 10, 7],
          backgroundColor: ['#6366f1','#10b981','#f59e0b','#3b82f6','#ef4444'],
          borderWidth: 0,
          hoverOffset: 6,
        }],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '70%',
        plugins: {
          legend: { display: false },
          tooltip: {
            callbacks: {
              label: ctx => ` ${ctx.label}: ${ctx.raw}%`,
            },
          },
        },
      },
    });
  }

  /* ----------------------------------------------------------
     Gráfico de Linha - Analytics
     ---------------------------------------------------------- */
  const analyticsCtx = document.getElementById('analyticsChart');
  if (analyticsCtx && typeof Chart !== 'undefined') {
    const labels = ['Seg','Ter','Qua','Qui','Sex','Sáb','Dom'];
    new Chart(analyticsCtx, {
      type: 'line',
      data: {
        labels,
        datasets: [
          {
            label: 'Visitantes',
            data: [1240, 980, 1560, 1380, 2100, 1750, 1300],
            borderColor: '#6366f1',
            backgroundColor: 'rgba(99,102,241,.1)',
            tension: .4, fill: true,
            pointRadius: 4, pointBackgroundColor: '#6366f1',
          },
          {
            label: 'Conversões',
            data: [88, 72, 115, 101, 160, 132, 95],
            borderColor: '#10b981',
            backgroundColor: 'rgba(16,185,129,.1)',
            tension: .4, fill: true,
            pointRadius: 4, pointBackgroundColor: '#10b981',
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { position: 'top', align: 'end', labels: { boxWidth: 12, font: { size: 12 } } },
        },
        scales: {
          x: { grid: { display: false } },
          y: { grid: { color: 'rgba(0,0,0,.05)' } },
        },
      },
    });
  }

  /* ----------------------------------------------------------
     Notificações Toast
     ---------------------------------------------------------- */
  window.showToast = function(msg, type = 'success') {
    let container = document.getElementById('toast-container');
    if (!container) {
      container = document.createElement('div');
      container.id = 'toast-container';
      Object.assign(container.style, {
        position: 'fixed', bottom: '24px', right: '24px',
        zIndex: '9999', display: 'flex', flexDirection: 'column', gap: '10px',
      });
      document.body.appendChild(container);
    }

    const colors = {
      success: '#10b981', danger: '#ef4444',
      warning: '#f59e0b', info: '#3b82f6',
    };

    const toast = document.createElement('div');
    toast.style.cssText = `
      background:#fff; border-left:4px solid ${colors[type] || colors.info};
      padding:12px 18px; border-radius:8px; box-shadow:0 4px 16px rgba(0,0,0,.12);
      font-size:13px; color:#1e293b; min-width:240px; max-width:340px;
      display:flex; align-items:center; gap:10px;
      animation:slideIn .25s ease;
    `;
    toast.textContent = msg;

    if (!document.getElementById('toast-style')) {
      const style = document.createElement('style');
      style.id = 'toast-style';
      style.textContent = '@keyframes slideIn{from{transform:translateX(110%);opacity:0}to{transform:none;opacity:1}}';
      document.head.appendChild(style);
    }

    container.appendChild(toast);
    setTimeout(() => toast.remove(), 3500);
  };

  /* ----------------------------------------------------------
     Progress Bars Animadas
     ---------------------------------------------------------- */
  const observer = new IntersectionObserver(entries => {
    entries.forEach(e => {
      if (e.isIntersecting) {
        const fill = e.target;
        fill.style.width = fill.dataset.width || '0%';
        observer.unobserve(fill);
      }
    });
  }, { threshold: 0.2 });

  document.querySelectorAll('.progress-fill[data-width]').forEach(el => {
    el.style.width = '0%';
    observer.observe(el);
  });

  /* ----------------------------------------------------------
     Tooltips simples
     ---------------------------------------------------------- */
  document.querySelectorAll('[data-tip]').forEach(el => {
    el.style.position = 'relative';
    el.addEventListener('mouseenter', () => {
      const tip = document.createElement('div');
      tip.className = '_tip';
      tip.textContent = el.dataset.tip;
      Object.assign(tip.style, {
        position: 'absolute', bottom: 'calc(100% + 6px)', left: '50%',
        transform: 'translateX(-50%)',
        background: '#1e293b', color: '#fff',
        fontSize: '11px', padding: '4px 10px', borderRadius: '6px',
        whiteSpace: 'nowrap', zIndex: '1000', pointerEvents: 'none',
      });
      el.appendChild(tip);
    });
    el.addEventListener('mouseleave', () => {
      el.querySelectorAll('._tip').forEach(t => t.remove());
    });
  });

});
