<?php
$api = new ApiClient(Auth::getToken());

$resp = $api->get('perfil');
$user = $resp['data']['dados'] ?? [];
?>

<!-- Page Title -->
<div class="pagetitle">
  <h1>Meu Perfil</h1>
  <nav>
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="?paginas=home">Início</a></li>
      <li class="breadcrumb-item active">Meu Perfil</li>
    </ol>
  </nav>
</div>

<section class="section">
  <div class="row">

    <!-- Foto de Perfil -->
    <div class="col-12">
      <div class="card">
        <div class="card-body d-flex flex-wrap align-items-center gap-4">
          <img id="fotoPerfilPreview"
               src="<?php echo htmlspecialchars($user['foto_url'] ?? 'assets/img/profile-img.jpg'); ?>"
               alt="Foto de perfil" class="rounded-circle" style="width:96px;height:96px;object-fit:cover;">
          <div class="flex-grow-1">
            <h5 class="mb-1"><?php echo htmlspecialchars($user['nome'] ?? ''); ?></h5>
            <p class="text-muted small mb-2">JPG, PNG ou WEBP — máximo 2MB.</p>
            <div class="d-flex align-items-center gap-2 flex-wrap">
              <input type="file" id="inputFotoPerfil" class="form-control form-control-sm" style="max-width:280px" accept="image/jpeg,image/png,image/webp">
              <button type="button" class="btn btn-primary btn-sm" onclick="enviarFotoPerfil(this)">
                <i class="bi bi-upload me-1"></i> Enviar foto
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Dados Pessoais -->
    <div class="col-12 col-lg-6">
      <div class="card">
        <div class="card-header">
          <h5 class="card-title mb-0"><i class="bi bi-person me-2"></i>Dados Pessoais</h5>
        </div>
        <div class="card-body pt-3">
          <form id="formPerfil">
            <div class="row g-3">
              <div class="col-12">
                <label class="form-label">Nome</label>
                <input type="text" name="nome" class="form-control"
                       value="<?php echo htmlspecialchars($user['nome'] ?? ''); ?>">
              </div>
              <div class="col-12">
                <label class="form-label">E-mail</label>
                <input type="email" name="email" class="form-control"
                       value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>">
              </div>
              <div class="col-12">
                <label class="form-label">Telefone</label>
                <input type="text" name="telefone" class="form-control"
                       value="<?php echo htmlspecialchars($user['telefone'] ?? ''); ?>">
              </div>
              <div class="col-12 pt-1">
                <button type="button" class="btn btn-primary" onclick="salvarPerfil(this)">
                  <i class="bi bi-save me-1"></i> Salvar Dados
                </button>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Alterar Senha -->
    <div class="col-12 col-lg-6">
      <div class="card">
        <div class="card-header">
          <h5 class="card-title mb-0"><i class="bi bi-lock me-2"></i>Alterar Senha</h5>
        </div>
        <div class="card-body pt-3">
          <form id="formSenha">
            <div class="row g-3">
              <div class="col-12">
                <label class="form-label">Senha Atual <span class="text-danger">*</span></label>
                <input type="password" name="senha_atual" class="form-control" required>
              </div>
              <div class="col-12">
                <label class="form-label">Nova Senha <span class="text-danger">*</span></label>
                <input type="password" name="nova_senha" id="novaSenha" class="form-control" required minlength="8">
                <div class="form-text">Mínimo de 8 caracteres.</div>
              </div>
              <div class="col-12">
                <label class="form-label">Confirmar Nova Senha <span class="text-danger">*</span></label>
                <input type="password" name="confirmar_senha" id="confirmarSenha" class="form-control" required minlength="8">
              </div>
              <div class="col-12 pt-1">
                <button type="button" class="btn btn-warning" onclick="salvarSenha(this)">
                  <i class="bi bi-key me-1"></i> Alterar Senha
                </button>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>

  </div>
</section>

<script>
const TAMANHO_MAX_FOTO = 2 * 1024 * 1024;
const TIPOS_FOTO_ACEITOS = ['image/jpeg', 'image/png', 'image/webp'];

async function enviarFotoPerfil(btn) {
  const input = document.getElementById('inputFotoPerfil');
  const arquivo = input.files[0];

  if (!arquivo) {
    bslToast('Selecione uma imagem para enviar.', 'warning');
    return;
  }
  if (!TIPOS_FOTO_ACEITOS.includes(arquivo.type)) {
    bslToast('Formatos aceitos: JPG, PNG ou WEBP.', 'warning');
    return;
  }
  if (arquivo.size > TAMANHO_MAX_FOTO) {
    bslToast('A imagem deve ter no máximo 2MB.', 'warning');
    return;
  }

  bslSetLoading(btn, true);
  const formData = new FormData();
  formData.append('foto', arquivo);

  const res = await apiUpload('POST', 'perfil/foto', formData);
  bslSetLoading(btn, false);

  if (!res.sucesso) {
    bslToast(res.mensagem || 'Erro ao enviar foto.', 'danger');
    return;
  }

  const novaUrl = res.dados.foto + '?t=' + Date.now();
  document.getElementById('fotoPerfilPreview').src = novaUrl;
  const avatarHeader = document.getElementById('headerFotoPerfil');
  if (avatarHeader) avatarHeader.src = novaUrl;
  input.value = '';

  // Mantém a sessão PHP do webAdm em sincronia (header é renderizado no servidor nas próximas páginas)
  fetch('atualizar-sessao-foto.php').catch(() => {});

  bslToast('Foto de perfil atualizada com sucesso!', 'success');
}

async function salvarPerfil(btn) {
  bslSetLoading(btn, true);
  const data = bslFormData('formPerfil');
  const res  = await apiFetch('PUT', 'perfil', data);
  bslSetLoading(btn, false);
  if (res.sucesso) {
    bslToast('Dados atualizados com sucesso!', 'success');
    // Update display name if header element exists
    const nomeEl = document.querySelector('.header-username, .profile-name, [data-auth-nome]');
    if (nomeEl && data.nome) nomeEl.textContent = data.nome;
  } else {
    bslToast(res.mensagem || 'Erro ao atualizar dados.', 'danger');
  }
}

async function salvarSenha(btn) {
  const novaSenha     = document.getElementById('novaSenha').value;
  const confirmarSenha = document.getElementById('confirmarSenha').value;

  if (novaSenha !== confirmarSenha) {
    bslToast('A nova senha e a confirmação não coincidem.', 'warning');
    return;
  }

  if (novaSenha.length < 8) {
    bslToast('A nova senha deve ter pelo menos 8 caracteres.', 'warning');
    return;
  }

  bslSetLoading(btn, true);
  const formData = bslFormData('formSenha');
  const payload  = {
    senha_atual: formData.senha_atual,
    nova_senha:  formData.nova_senha,
    confirmacao: formData.confirmar_senha,
  };
  const res = await apiFetch('POST', 'perfil/trocar-senha', payload);
  bslSetLoading(btn, false);

  if (res.sucesso) {
    bslToast('Senha alterada com sucesso!', 'success');
    document.getElementById('formSenha').reset();
  } else {
    bslToast(res.mensagem || 'Erro ao alterar senha.', 'danger');
  }
}
</script>
