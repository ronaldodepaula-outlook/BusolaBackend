// ============ AUTH FUNCTIONS ============

function showScreen(screenId) {
    const screens = document.querySelectorAll('.auth-screen');
    screens.forEach(screen => screen.classList.remove('active'));
    
    const screen = document.getElementById(screenId);
    if (screen) {
        screen.classList.add('active');
    }
}

function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    if (input.type === 'password') {
        input.type = 'text';
    } else {
        input.type = 'password';
    }
}

function updatePasswordStrength(inputId) {
    const input = document.getElementById(inputId);
    const password = input.value;
    let strength = 0;
    
    if (password.length >= 8) strength++;
    if (/[a-z]/.test(password)) strength++;
    if (/[A-Z]/.test(password)) strength++;
    if (/[0-9]/.test(password)) strength++;
    if (/[^a-zA-Z0-9]/.test(password)) strength++;
    
    const strengthFill = input.parentElement.parentElement.querySelector('.strength-fill');
    const strengthText = input.parentElement.parentElement.querySelector('.strength-text');
    
    if (strengthFill && strengthText) {
        const percentage = (strength / 5) * 100;
        strengthFill.style.width = percentage + '%';
        
        if (strength <= 1) {
            strengthFill.style.backgroundColor = '#E74C3C';
            strengthText.textContent = 'Força: Fraca';
        } else if (strength <= 2) {
            strengthFill.style.backgroundColor = '#F39C12';
            strengthText.textContent = 'Força: Média';
        } else if (strength <= 3) {
            strengthFill.style.backgroundColor = '#F1C40F';
            strengthText.textContent = 'Força: Boa';
        } else {
            strengthFill.style.backgroundColor = '#2ECC71';
            strengthText.textContent = 'Força: Forte';
        }
    }
}

function socialLogin(provider) {
    alert(`Login com ${provider === 'google' ? 'Google' : 'Microsoft'} simulado.\n\nEm um sistema real, isso redirecionaria para o OAuth do provedor.`);
}

function loginUser(event) {
    event.preventDefault();
    
    const email = document.getElementById('login-email').value;
    const password = document.getElementById('login-password').value;
    
    if (email && password) {
        showApp();
    }
}

function registerUser(event) {
    event.preventDefault();
    
    const name = document.getElementById('register-name').value;
    const email = document.getElementById('register-email').value;
    const password = document.getElementById('register-password').value;
    const confirm = document.getElementById('register-confirm').value;
    
    if (name && email && password && confirm && password === confirm) {
        alert('Conta criada com sucesso!');
        showScreen('login-screen');
    } else if (password !== confirm) {
        alert('As senhas não conferem!');
    }
}

function sendPasswordReset(event) {
    event.preventDefault();
    
    const email = document.getElementById('forgot-email').value;
    
    if (email) {
        alert(`Link de recuperação enviado para ${email}`);
        showScreen('reset-password-screen');
    }
}

function resetPassword(event) {
    event.preventDefault();
    
    const password = document.getElementById('reset-password').value;
    const confirm = document.getElementById('reset-confirm').value;
    
    if (password && confirm && password === confirm) {
        alert('Senha redefinida com sucesso!');
        showScreen('login-screen');
    } else if (password !== confirm) {
        alert('As senhas não conferem!');
    }
}

function showApp() {
    document.getElementById('auth-container').classList.remove('active');
    document.getElementById('app-container').classList.remove('hidden');
}

function logout() {
    if (confirm('Tem certeza que deseja fazer logout?')) {
        document.getElementById('app-container').classList.add('hidden');
        document.getElementById('auth-container').classList.add('active');
        showScreen('login-screen');
    }
}

// ============ APP FUNCTIONS ============

function navigateTo(pageId) {
    event.preventDefault();
    
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
        'matriz-risco': 'Matriz de Risco 5×5',
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

// ============ MODAL FUNCTIONS ============

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

// ============ FORM FUNCTIONS ============

function saveForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return;
    
    const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
    let isValid = true;
    
    inputs.forEach(input => {
        if (!input.value.trim()) {
            input.style.borderColor = '#E74C3C';
            isValid = false;
        } else {
            input.style.borderColor = '';
        }
    });
    
    if (isValid) {
        alert('Dados salvos com sucesso!');
        closeAllModals();
        form.reset();
    } else {
        alert('Por favor, preencha todos os campos obrigatórios!');
    }
}

function deleteItem(itemType) {
    const modal = document.getElementById('delete-modal');
    const message = document.getElementById('delete-message');
    
    if (modal && message) {
        message.textContent = `Tem certeza que deseja deletar este ${itemType}? Esta ação não pode ser desfeita.`;
        openModal('delete-modal');
    }
}

function confirmDelete() {
    alert('Item deletado com sucesso!');
    closeModal('delete-modal');
}

// ============ TAB FUNCTIONS ============

function switchTab(tabName) {
    event.preventDefault();
    
    // Remove active class from all tab buttons
    const tabButtons = document.querySelectorAll('.tab-button');
    tabButtons.forEach(btn => btn.classList.remove('active'));
    
    // Add active class to clicked button
    event.target.classList.add('active');
    
    // You can add tab content switching here if needed
}

// ============ REPORT FUNCTIONS ============

function generateReport(reportType) {
    const reportNames = {
        'nr1': 'Relatório Técnico NR-1',
        'executivo': 'Relatório Executivo',
        'rh': 'Relatório RH',
        'sst': 'Relatório SST'
    };
    
    alert(`Gerando ${reportNames[reportType]}...\n\nEm um sistema real, isso geraria um PDF com:\n- Análise técnica completa\n- Matriz de risco\n- Plano de ação\n- Assinatura digital\n- Conformidade com NR-1/PGR`);
}

// ============ UTILITY FUNCTIONS ============

function toggleNotifications() {
    alert('Notificações: Você tem 3 novas notificações');
}

function toggleSettings() {
    alert('Configurações do sistema');
}

// ============ INITIALIZATION ============

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
    
    // Add event listeners to forms
    const loginForm = document.getElementById('login-form');
    if (loginForm) {
        loginForm.addEventListener('submit', loginUser);
    }
    
    const registerForm = document.getElementById('register-form');
    if (registerForm) {
        registerForm.addEventListener('submit', registerUser);
    }
    
    const forgotForm = document.getElementById('forgot-form');
    if (forgotForm) {
        forgotForm.addEventListener('submit', sendPasswordReset);
    }
    
    const resetForm = document.getElementById('reset-form');
    if (resetForm) {
        resetForm.addEventListener('submit', resetPassword);
    }
    
    // Add password strength listeners
    const passwordInputs = document.querySelectorAll('input[type="password"]');
    passwordInputs.forEach(input => {
        input.addEventListener('input', function() {
            if (this.id === 'register-password' || this.id === 'reset-password') {
                updatePasswordStrength(this.id);
            }
        });
    });
    
    // Add tab button listeners
    const tabButtons = document.querySelectorAll('.tab-button');
    tabButtons.forEach(btn => {
        btn.addEventListener('click', switchTab);
    });
    
    // Set first tab as active
    const firstTab = document.querySelector('.tab-button');
    if (firstTab) {
        firstTab.classList.add('active');
    }
});

// ============ KEYBOARD SHORTCUTS ============

document.addEventListener('keydown', function(event) {
    // Ctrl/Cmd + S to save
    if ((event.ctrlKey || event.metaKey) && event.key === 's') {
        event.preventDefault();
        const activeForm = document.querySelector('.modal.active form');
        if (activeForm) {
            saveForm(activeForm.id);
        }
    }
    
    // Ctrl/Cmd + P to print
    if ((event.ctrlKey || event.metaKey) && event.key === 'p') {
        event.preventDefault();
        window.print();
    }
});

// ============ SEARCH FUNCTIONALITY ============

document.addEventListener('DOMContentLoaded', function() {
    const filterInputs = document.querySelectorAll('.filter-input');
    filterInputs.forEach(input => {
        input.addEventListener('keyup', function() {
            const filter = this.value.toLowerCase();
            const table = this.closest('.page-actions').nextElementSibling.querySelector('table');
            
            if (table) {
                const rows = table.querySelectorAll('tbody tr');
                rows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    row.style.display = text.includes(filter) ? '' : 'none';
                });
            }
        });
    });
});

console.log('Sistema busola - Gestão de Riscos Psicossociais NR-1 inicializado com sucesso!');
console.log('Versão: 2.0.0');
console.log('Identidade Visual: Cores oficiais busola aplicadas');
