/**
 * busola-crud.js - Shared CRUD utilities for busola webAdm
 * Globals expected: API_TOKEN (string), API_BASE (string)
 */

/**
 * Fetch wrapper with Bearer auth.
 * @param {string} method      HTTP method
 * @param {string} endpoint    Relative endpoint (e.g. 'empresas' or 'empresas/5')
 * @param {object|null} body   JSON body for POST/PUT/PATCH
 * @param {number|null} empresaId  X-Empresa-Id header (for superadmin tenant scoping)
 * @returns {Promise<object>}
 */
async function apiFetch(method, endpoint, body = null, empresaId = null) {
  try {
    const url = API_BASE + endpoint;
    const headers = {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'Authorization': 'Bearer ' + API_TOKEN,
    };
    if (empresaId) {
      headers['X-Empresa-Id'] = String(empresaId);
    }
    const opts = {
      method: method.toUpperCase(),
      headers,
    };
    if (body !== null && method.toUpperCase() !== 'GET') {
      opts.body = JSON.stringify(body);
    }
    const res = await fetch(url, opts);

    // Token expirado ou inválido — efetua logout automático
    if (res.status === 401) {
      bslToast('Sessao expirada. Redirecionando para login...', 'warning');
      setTimeout(() => { window.location.href = 'logout.php?expired=1'; }, 2000);
      return { sucesso: false, mensagem: 'Sessao expirada.' };
    }

    let json;
    try {
      json = await res.json();
    } catch (_) {
      return { sucesso: false, mensagem: 'Resposta invalida do servidor.' };
    }
    return json;
  } catch (err) {
    console.error('[apiFetch]', err);
    return { sucesso: false, mensagem: 'Erro de conexao.' };
  }
}

/**
 * Fetch wrapper para upload de arquivo (multipart/form-data) com Bearer auth.
 * Não define 'Content-Type' manualmente — o browser define o boundary
 * correto automaticamente a partir do FormData.
 * @param {string} method       HTTP method (normalmente POST)
 * @param {string} endpoint     Relative endpoint (e.g. 'perfil/foto')
 * @param {FormData} formData   Deve conter o(s) arquivo(s) via formData.append('campo', file)
 * @param {number|null} empresaId  X-Empresa-Id header (para escopo multi-empresa do superadmin)
 * @returns {Promise<object>}
 */
async function apiUpload(method, endpoint, formData, empresaId = null) {
  try {
    const url = API_BASE + endpoint;
    const headers = {
      'Accept': 'application/json',
      'Authorization': 'Bearer ' + API_TOKEN,
    };
    if (empresaId) {
      headers['X-Empresa-Id'] = String(empresaId);
    }
    const res = await fetch(url, { method: method.toUpperCase(), headers, body: formData });

    if (res.status === 401) {
      bslToast('Sessao expirada. Redirecionando para login...', 'warning');
      setTimeout(() => { window.location.href = 'logout.php?expired=1'; }, 2000);
      return { sucesso: false, mensagem: 'Sessao expirada.' };
    }

    try {
      return await res.json();
    } catch (_) {
      return { sucesso: false, mensagem: 'Resposta invalida do servidor.' };
    }
  } catch (err) {
    console.error('[apiUpload]', err);
    return { sucesso: false, mensagem: 'Erro de conexao.' };
  }
}

/**
 * Fixed top-right Bootstrap-style toast.
 * @param {string} msg
 * @param {'success'|'danger'|'warning'|'info'} type
 */
function bslToast(msg, type = 'success') {
  const icons = {
    success: 'bi-check-circle-fill',
    danger:  'bi-x-circle-fill',
    warning: 'bi-exclamation-triangle-fill',
    info:    'bi-info-circle-fill',
  };
  const icon = icons[type] || icons.info;

  let container = document.getElementById('bsl-toast-container');
  if (!container) {
    container = document.createElement('div');
    container.id = 'bsl-toast-container';
    container.style.cssText =
      'position:fixed;top:1.25rem;right:1.25rem;z-index:9999;display:flex;flex-direction:column;gap:.5rem;min-width:280px;max-width:380px;';
    document.body.appendChild(container);
  }

  const toast = document.createElement('div');
  toast.className = `alert alert-${type} alert-dismissible d-flex align-items-center shadow-sm mb-0`;
  toast.setAttribute('role', 'alert');
  toast.style.cssText = 'animation:bslFadeIn .25s ease;';
  toast.innerHTML =
    `<i class="bi ${icon} me-2 flex-shrink-0"></i>` +
    `<span class="flex-grow-1">${msg}</span>` +
    `<button type="button" class="btn-close ms-2" onclick="this.closest('.alert').remove()"></button>`;

  container.appendChild(toast);

  setTimeout(() => {
    toast.style.transition = 'opacity .4s';
    toast.style.opacity = '0';
    setTimeout(() => toast.remove(), 420);
  }, 3200);
}

// Inject keyframe once
(function () {
  if (document.getElementById('bsl-toast-style')) return;
  const s = document.createElement('style');
  s.id = 'bsl-toast-style';
  s.textContent = '@keyframes bslFadeIn{from{opacity:0;transform:translateY(-8px)}to{opacity:1;transform:none}}';
  document.head.appendChild(s);
})();

/**
 * Toggle button loading/disabled state.
 * @param {HTMLElement} btn
 * @param {boolean} on  true = show spinner, false = restore
 */
function bslSetLoading(btn, on) {
  if (!btn) return;
  if (on) {
    btn.dataset.bslOriginal = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML =
      '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Aguarde...';
  } else {
    btn.disabled = false;
    if (btn.dataset.bslOriginal !== undefined) {
      btn.innerHTML = btn.dataset.bslOriginal;
      delete btn.dataset.bslOriginal;
    }
  }
}

/**
 * Build a plain object from a form's FormData, skipping empty strings.
 * @param {string} formId
 * @returns {object}
 */
function bslFormData(formId) {
  const form = document.getElementById(formId);
  if (!form) return {};
  const fd = new FormData(form);
  const obj = {};
  for (const [key, value] of fd.entries()) {
    if (value !== '') {
      obj[key] = value;
    }
  }
  return obj;
}

/**
 * Hide a Bootstrap modal by its element id.
 * @param {string} id  Modal element id (without #)
 */
function bslCloseModal(id) {
  const el = document.getElementById(id);
  if (!el) return;
  const instance = bootstrap.Modal.getInstance(el);
  if (instance) instance.hide();
}

/**
 * Load filiais for a given empresa into a <select> element.
 * Used by superadmin when selecting empresa in modals.
 * @param {number|string} empresaId
 * @param {string} selectId  ID of the <select> element
 * @param {number|string|null} selectedValue  Pre-select this filial_id
 */
async function bslCarregarFiliais(empresaId, selectId, selectedValue = null) {
  const selectEl = document.getElementById(selectId);
  if (!selectEl) return;
  if (!empresaId) {
    selectEl.innerHTML = '<option value="">Nenhuma</option>';
    return;
  }
  selectEl.innerHTML = '<option value="">Carregando...</option>';
  selectEl.disabled = true;
  const res = await apiFetch('GET', 'filiais?per_page=100', null, empresaId);
  selectEl.disabled = false;
  selectEl.innerHTML = '<option value="">Nenhuma</option>';
  if (res.sucesso && res.dados) {
    const lista = res.dados.data ?? (Array.isArray(res.dados) ? res.dados : []);
    lista.forEach(f => {
      const opt = document.createElement('option');
      opt.value = f.id;
      opt.textContent = f.nome;
      if (selectedValue && String(f.id) === String(selectedValue)) {
        opt.selected = true;
      }
      selectEl.appendChild(opt);
    });
  }
}
