// Navigation
function navigateTo(pageId) {
    // Hide all pages
    const pages = document.querySelectorAll('.page');
    pages.forEach(page => page.classList.remove('active'));

    // Show selected page
    const selectedPage = document.getElementById(pageId);
    if (selectedPage) {
        selectedPage.classList.add('active');
    }

    // Update nav links
    const navLinks = document.querySelectorAll('.nav-link');
    navLinks.forEach(link => link.classList.remove('active'));

    // Find and activate the clicked link
    const clickedLink = event.target.closest('.nav-link');
    if (clickedLink) {
        clickedLink.classList.add('active');
    }

    // Update page title
    const titleMap = {
        'dashboard': 'Dashboard',
        'empresas': 'Gestão de Empresas',
        'ghe': 'Grupos Homogêneos de Exposição',
        'colaboradores': 'Gestão de Colaboradores',
        'questionarios': 'Questionários',
        'aplicacoes': 'Aplicações de Questionários',
        'respostas': 'Respostas de Questionários',
        'matriz-risco': 'Matriz de Risco 5x5',
        'inventario': 'Inventário de Riscos Psicossociais',
        'qualitativa': 'Análise Qualitativa',
        'plano-acao': 'Plano de Ação (PDCA)',
        'relatorios': 'Relatórios',
        'auditoria': 'Trilha de Auditoria'
    };

    const pageTitle = document.getElementById('page-title');
    if (pageTitle) {
        pageTitle.textContent = titleMap[pageId] || 'Dashboard';
    }

    // Scroll to top
    document.querySelector('.pages-container').scrollTop = 0;
}

// Modal Functions
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    const overlay = document.getElementById('modal-overlay');

    if (modal && overlay) {
        modal.classList.add('active');
        overlay.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    const overlay = document.getElementById('modal-overlay');

    if (modal && overlay) {
        modal.classList.remove('active');
        overlay.classList.remove('active');
        document.body.style.overflow = 'auto';
    }
}

function closeAllModals() {
    const modals = document.querySelectorAll('.modal');
    const overlay = document.getElementById('modal-overlay');

    modals.forEach(modal => modal.classList.remove('active'));
    overlay.classList.remove('active');
    document.body.style.overflow = 'auto';
}

// Close modal when clicking outside
document.addEventListener('click', function(event) {
    const overlay = document.getElementById('modal-overlay');
    if (event.target === overlay) {
        closeAllModals();
    }
});

// Close modal with Escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeAllModals();
    }
});

// Form Validation
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return true;

    const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
    let isValid = true;

    inputs.forEach(input => {
        if (!input.value.trim()) {
            input.style.borderColor = '#ef4444';
            isValid = false;
        } else {
            input.style.borderColor = '';
        }
    });

    return isValid;
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    // Set dashboard as active page
    const dashboardPage = document.getElementById('dashboard');
    if (dashboardPage) {
        dashboardPage.classList.add('active');
    }

    // Set first nav link as active
    const firstNavLink = document.querySelector('.nav-link');
    if (firstNavLink) {
        firstNavLink.classList.add('active');
    }

    // Add event listeners to all nav links
    const navLinks = document.querySelectorAll('.nav-link');
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const pageId = this.getAttribute('onclick').match(/'([^']+)'/)[1];
            navigateTo(pageId);
        });
    });

    // Add smooth scroll behavior
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
        });
    });

    // Initialize tooltips
    initializeTooltips();

    // Add animations to stat cards
    animateStatCards();
});

// Animate stat cards on load
function animateStatCards() {
    const statCards = document.querySelectorAll('.stat-card');
    statCards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        setTimeout(() => {
            card.style.transition = 'all 0.5s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
    });
}

// Initialize tooltips
function initializeTooltips() {
    const tooltipElements = document.querySelectorAll('[title]');
    tooltipElements.forEach(element => {
        element.addEventListener('mouseenter', function() {
            // Tooltip functionality can be expanded here
        });
    });
}

// Search functionality
function searchTable(inputId, tableId) {
    const input = document.getElementById(inputId);
    const table = document.getElementById(tableId);

    if (!input || !table) return;

    input.addEventListener('keyup', function() {
        const filter = this.value.toUpperCase();
        const rows = table.getElementsByTagName('tr');

        for (let i = 1; i < rows.length; i++) {
            const cells = rows[i].getElementsByTagName('td');
            let found = false;

            for (let j = 0; j < cells.length; j++) {
                if (cells[j].textContent.toUpperCase().indexOf(filter) > -1) {
                    found = true;
                    break;
                }
            }

            rows[i].style.display = found ? '' : 'none';
        }
    });
}

// Export table to CSV
function exportTableToCSV(tableId, filename = 'export.csv') {
    const table = document.getElementById(tableId);
    if (!table) return;

    let csv = [];
    const rows = table.querySelectorAll('tr');

    rows.forEach(row => {
        const cols = row.querySelectorAll('td, th');
        const csvRow = [];

        cols.forEach(col => {
            csvRow.push('"' + col.textContent.trim().replace(/"/g, '""') + '"');
        });

        csv.push(csvRow.join(','));
    });

    downloadCSV(csv.join('\n'), filename);
}

// Download CSV file
function downloadCSV(csv, filename) {
    const csvFile = new Blob([csv], { type: 'text/csv' });
    const downloadLink = document.createElement('a');
    downloadLink.href = URL.createObjectURL(csvFile);
    downloadLink.download = filename;
    document.body.appendChild(downloadLink);
    downloadLink.click();
    document.body.removeChild(downloadLink);
}

// Print functionality
function printPage() {
    window.print();
}

// Generate PDF (simulated)
function generatePDF(reportType) {
    alert(`Gerando ${reportType}...\n\nEm um sistema real, isso geraria um PDF completo com:\n- Introdução\n- Metodologia\n- Análise Estatística\n- Matriz de Risco\n- Plano de Ação\n- Assinatura Digital`);
}

// Notification system
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 16px 20px;
        background-color: ${type === 'success' ? '#22c55e' : type === 'error' ? '#ef4444' : '#06b6d4'};
        color: white;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        z-index: 2000;
        animation: slideInRight 0.3s ease;
    `;

    document.body.appendChild(notification);

    setTimeout(() => {
        notification.style.animation = 'slideOutRight 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Add CSS animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInRight {
        from {
            transform: translateX(400px);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    @keyframes slideOutRight {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(400px);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);

// Data export functionality
function exportData(format = 'json') {
    const data = {
        timestamp: new Date().toISOString(),
        system: 'busola - Gestão de Riscos Psicossociais',
        version: '1.0.0',
        data: {
            companies: 2,
            ghe: 3,
            employees: 190,
            responses: 245,
            risks: {
                critical: 3,
                high: 5,
                moderate: 8,
                low: 15,
                irrelevant: 4
            }
        }
    };

    if (format === 'json') {
        downloadJSON(data);
    } else if (format === 'csv') {
        downloadCSV(JSON.stringify(data, null, 2), 'busola-export.csv');
    }
}

function downloadJSON(data) {
    const jsonFile = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
    const downloadLink = document.createElement('a');
    downloadLink.href = URL.createObjectURL(jsonFile);
    downloadLink.download = `busola-export-${new Date().getTime()}.json`;
    document.body.appendChild(downloadLink);
    downloadLink.click();
    document.body.removeChild(downloadLink);
}

// Real-time search in tables
function setupTableSearch(inputSelector, tableSelector) {
    const input = document.querySelector(inputSelector);
    const table = document.querySelector(tableSelector);

    if (!input || !table) return;

    input.addEventListener('keyup', function() {
        const filter = this.value.toLowerCase();
        const rows = table.querySelectorAll('tbody tr');

        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(filter) ? '' : 'none';
        });
    });
}

// Sort table by column
function sortTable(tableId, columnIndex) {
    const table = document.getElementById(tableId);
    if (!table) return;

    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));

    rows.sort((a, b) => {
        const aValue = a.cells[columnIndex].textContent.trim();
        const bValue = b.cells[columnIndex].textContent.trim();

        // Try numeric sort first
        const aNum = parseFloat(aValue);
        const bNum = parseFloat(bValue);

        if (!isNaN(aNum) && !isNaN(bNum)) {
            return aNum - bNum;
        }

        // Fall back to string sort
        return aValue.localeCompare(bValue);
    });

    rows.forEach(row => tbody.appendChild(row));
}

// Filter by status
function filterByStatus(status) {
    const rows = document.querySelectorAll('tbody tr');
    rows.forEach(row => {
        const statusCell = row.querySelector('.badge');
        if (statusCell) {
            const rowStatus = statusCell.textContent.toLowerCase();
            row.style.display = rowStatus.includes(status.toLowerCase()) ? '' : 'none';
        }
    });
}

// Calculate risk level
function calculateRiskLevel(probability, severity) {
    const risk = probability * severity;

    if (risk <= 2) return { level: 'Irrelevante', color: '#0066cc', emoji: '🔵' };
    if (risk <= 4) return { level: 'Baixo', color: '#22c55e', emoji: '🟢' };
    if (risk <= 9) return { level: 'Moderado', color: '#eab308', emoji: '🟡' };
    if (risk <= 16) return { level: 'Alto', color: '#f97316', emoji: '🟠' };
    return { level: 'Crítico', color: '#ef4444', emoji: '🔴' };
}

// Generate report summary
function generateReportSummary() {
    const summary = {
        totalCompanies: 2,
        totalEmployees: 190,
        totalResponses: 245,
        responseRate: '87%',
        criticalRisks: 3,
        highRisks: 5,
        moderateRisks: 8,
        actionsPending: 6,
        actionsCompleted: 12,
        generatedAt: new Date().toLocaleString('pt-BR')
    };

    return summary;
}

// Initialize charts (simulated with CSS)
function initializeCharts() {
    // Chart animations can be added here
    const chartElements = document.querySelectorAll('.chart-container');
    chartElements.forEach((chart, index) => {
        chart.style.opacity = '0';
        chart.style.transform = 'translateY(20px)';
        setTimeout(() => {
            chart.style.transition = 'all 0.5s ease';
            chart.style.opacity = '1';
            chart.style.transform = 'translateY(0)';
        }, index * 150);
    });
}

// Export to Excel (simulated)
function exportToExcel() {
    alert('Funcionalidade de exportação para Excel.\n\nEm um sistema real, isso geraria um arquivo .xlsx com:\n- Dados de empresas\n- Respostas de questionários\n- Matriz de risco\n- Plano de ação\n- Indicadores');
}

// Schedule report generation
function scheduleReport(reportType, frequency) {
    alert(`Relatório ${reportType} agendado para ${frequency}.\n\nVocê receberá um email com o relatório gerado automaticamente.`);
}

// User preferences
const userPreferences = {
    theme: 'light',
    language: 'pt-BR',
    notifications: true,
    autoSave: true
};

function saveUserPreferences() {
    localStorage.setItem('busola-preferences', JSON.stringify(userPreferences));
}

function loadUserPreferences() {
    const saved = localStorage.getItem('busola-preferences');
    if (saved) {
        Object.assign(userPreferences, JSON.parse(saved));
    }
}

// Initialize preferences on load
loadUserPreferences();

// Keyboard shortcuts
document.addEventListener('keydown', function(event) {
    // Ctrl/Cmd + S to save
    if ((event.ctrlKey || event.metaKey) && event.key === 's') {
        event.preventDefault();
        showNotification('Dados salvos com sucesso!', 'success');
    }

    // Ctrl/Cmd + P to print
    if ((event.ctrlKey || event.metaKey) && event.key === 'p') {
        event.preventDefault();
        printPage();
    }

    // Ctrl/Cmd + E to export
    if ((event.ctrlKey || event.metaKey) && event.key === 'e') {
        event.preventDefault();
        exportData('json');
    }
});

// Performance monitoring
const performanceMetrics = {
    pageLoadTime: performance.now(),
    navigationCount: 0,
    modalCount: 0
};

function trackNavigation() {
    performanceMetrics.navigationCount++;
}

function trackModal() {
    performanceMetrics.modalCount++;
}

// Accessibility enhancements
function improveAccessibility() {
    // Add ARIA labels
    const buttons = document.querySelectorAll('button');
    buttons.forEach(button => {
        if (!button.getAttribute('aria-label')) {
            button.setAttribute('aria-label', button.textContent.trim());
        }
    });

    // Add role attributes
    const tables = document.querySelectorAll('.table');
    tables.forEach(table => {
        table.setAttribute('role', 'table');
    });
}

// Initialize accessibility on load
document.addEventListener('DOMContentLoaded', improveAccessibility);

// Help system
function showHelp(topic) {
    const helpContent = {
        dashboard: 'O Dashboard fornece uma visão geral dos riscos psicossociais da organização com indicadores-chave e gráficos.',
        matriz: 'A Matriz de Risco calcula o nível de risco multiplicando Probabilidade × Gravidade.',
        planoacao: 'O Plano de Ação permite rastrear e gerenciar ações corretivas para mitigar riscos identificados.',
        relatorios: 'Os Relatórios fornecem análises técnicas detalhadas para auditorias e conformidade regulatória.'
    };

    const content = helpContent[topic] || 'Tópico de ajuda não encontrado.';
    alert(`Ajuda: ${topic}\n\n${content}`);
}

// System status
function getSystemStatus() {
    return {
        status: 'Operacional',
        uptime: '99.9%',
        lastBackup: new Date().toLocaleString('pt-BR'),
        activeUsers: 15,
        dataSize: '2.4 GB'
    };
}

console.log('Sistema busola - Gestão de Riscos Psicossociais NR-1 inicializado com sucesso!');
console.log('Versão: 1.0.0');
console.log('Status:', getSystemStatus());
