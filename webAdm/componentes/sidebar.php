<?php
$paginaAtual = $_GET['paginas'] ?? 'home';

function navAtivo(string $pagina, string $atual): string {
    return $pagina === $atual ? 'active' : 'collapsed';
}

/** Verifica se a página atual pertence a um grupo (para expandir o submenu certo). */
function emGrupo(array $paginas, string $atual): bool {
    return in_array($atual, $paginas, true);
}

$paginasPesquisa = ['formularios', 'formulario-estrutura', 'padroes-formulario', 'conceitos', 'pesquisas', 'pesquisa-wizard', 'setores-ghe', 'colaboradores', 'pesquisa-plano-acao', 'pesquisa-relatorios'];
$grupoPesquisaAberto = emGrupo($paginasPesquisa, $paginaAtual);
?>
<aside id="sidebar" class="sidebar">
  <ul class="sidebar-nav" id="sidebar-nav">

    <!-- Dashboard -->
    <li class="nav-item">
      <a class="nav-link <?php echo navAtivo('home', $paginaAtual); ?>" href="?paginas=home">
        <i class="bi bi-grid-1x2-fill"></i>
        <span>Dashboard</span>
      </a>
    </li>

    <!-- ── Gestão ──────────────────────────────── -->
    <li class="nav-heading">Gestão</li>

    <?php if (Auth::isSuperAdmin()): ?>
    <li class="nav-item">
      <a class="nav-link <?php echo navAtivo('empresas', $paginaAtual); ?>" href="?paginas=empresas">
        <i class="bi bi-building"></i>
        <span>Empresas</span>
      </a>
    </li>
    <?php endif; ?>

    <li class="nav-item">
      <a class="nav-link <?php echo navAtivo('filiais', $paginaAtual); ?>" href="?paginas=filiais">
        <i class="bi bi-diagram-3"></i>
        <span>Filiais</span>
      </a>
    </li>

    <li class="nav-item">
      <a class="nav-link <?php echo navAtivo('usuarios', $paginaAtual); ?>" href="?paginas=usuarios">
        <i class="bi bi-people"></i>
        <span>Usuários</span>
      </a>
    </li>

    <?php if (Auth::hasPermission('formulario.listar') || Auth::hasPermission('conceito.listar') || Auth::hasPermission('pesquisa.listar')): ?>
    <li class="nav-item">
      <a class="nav-link <?php echo $grupoPesquisaAberto ? '' : 'collapsed'; ?>" data-bs-target="#pesquisas-nav" data-bs-toggle="collapse" href="#">
        <i class="bi bi-clipboard2-pulse"></i>
        <span>Pesquisas Psicossociais</span>
        <i class="bi bi-chevron-down ms-auto"></i>
      </a>
      <ul id="pesquisas-nav" class="nav-content collapse <?php echo $grupoPesquisaAberto ? 'show' : ''; ?>" data-bs-parent="#sidebar-nav">
        <?php if (Auth::hasPermission('formulario.listar')): ?>
        <li>
          <a class="<?php echo in_array($paginaAtual, ['formularios', 'formulario-estrutura'], true) ? 'active' : ''; ?>" href="?paginas=formularios">
            <i class="bi bi-circle"></i><span>Formulários</span>
          </a>
        </li>
        <?php endif; ?>
        <?php if (Auth::hasPermission('padrao_formulario.listar')): ?>
        <li>
          <a class="<?php echo $paginaAtual === 'padroes-formulario' ? 'active' : ''; ?>" href="?paginas=padroes-formulario">
            <i class="bi bi-circle"></i><span>Padrões de Formulário</span>
          </a>
        </li>
        <?php endif; ?>
        <?php if (Auth::hasPermission('conceito.listar')): ?>
        <li>
          <a class="<?php echo $paginaAtual === 'conceitos' ? 'active' : ''; ?>" href="?paginas=conceitos">
            <i class="bi bi-circle"></i><span>Conceitos de Avaliação</span>
          </a>
        </li>
        <?php endif; ?>
        <?php if (Auth::hasPermission('pesquisa.listar')): ?>
        <li>
          <a class="<?php echo in_array($paginaAtual, ['pesquisas', 'pesquisa-wizard'], true) ? 'active' : ''; ?>" href="?paginas=pesquisas">
            <i class="bi bi-circle"></i><span>Campanhas</span>
          </a>
        </li>
        <?php endif; ?>
        <?php if (Auth::hasPermission('colaborador.listar')): ?>
        <li>
          <a class="<?php echo $paginaAtual === 'colaboradores' ? 'active' : ''; ?>" href="?paginas=colaboradores">
            <i class="bi bi-circle"></i><span>Colaboradores</span>
          </a>
        </li>
        <?php endif; ?>
        <?php if (Auth::hasPermission('setor.listar') || Auth::hasPermission('ghe.listar')): ?>
        <li>
          <a class="<?php echo $paginaAtual === 'setores-ghe' ? 'active' : ''; ?>" href="?paginas=setores-ghe">
            <i class="bi bi-circle"></i><span>Setores e GHE</span>
          </a>
        </li>
        <?php endif; ?>
      </ul>
    </li>
    <?php endif; ?>

    <?php if (Auth::isSuperAdmin() || Auth::hasPermission('relatorio.listar_todas')): ?>
    <li class="nav-item">
      <a class="nav-link <?php echo navAtivo('relatorios-tecnicos-admin', $paginaAtual); ?>" href="?paginas=relatorios-tecnicos-admin">
        <i class="bi bi-file-earmark-pdf"></i>
        <span>Relatórios Técnicos</span>
      </a>
    </li>
    <?php endif; ?>

    <!-- ── Controle de Acesso ─────────────────── -->
    <li class="nav-heading">Controle de Acesso</li>

    <li class="nav-item">
      <a class="nav-link <?php echo navAtivo('perfis', $paginaAtual); ?>" href="?paginas=perfis">
        <i class="bi bi-shield-check"></i>
        <span>Perfis de Acesso</span>
      </a>
    </li>

    <?php if (Auth::isSuperAdmin()): ?>
    <li class="nav-item">
      <a class="nav-link <?php echo navAtivo('permissoes', $paginaAtual); ?>" href="?paginas=permissoes">
        <i class="bi bi-key"></i>
        <span>Permissões</span>
      </a>
    </li>
    <?php endif; ?>

    <!-- ── Sistema ────────────────────────────── -->
    <li class="nav-heading">Sistema</li>

    <li class="nav-item">
      <a class="nav-link <?php echo navAtivo('configuracoes', $paginaAtual); ?>" href="?paginas=configuracoes">
        <i class="bi bi-sliders"></i>
        <span>Configurações</span>
      </a>
    </li>

    <li class="nav-item">
      <a class="nav-link <?php echo navAtivo('logs', $paginaAtual); ?>" href="?paginas=logs">
        <i class="bi bi-journal-text"></i>
        <span>Logs de Auditoria</span>
      </a>
    </li>

    <!-- ── Conta ──────────────────────────────── -->
    <li class="nav-heading">Conta</li>

    <li class="nav-item">
      <a class="nav-link <?php echo navAtivo('perfil', $paginaAtual); ?>" href="?paginas=perfil">
        <i class="bi bi-person-circle"></i>
        <span>Meu Perfil</span>
      </a>
    </li>

    <li class="nav-item">
      <a class="nav-link" href="logout.php">
        <i class="bi bi-box-arrow-right"></i>
        <span>Sair</span>
      </a>
    </li>

  </ul>
</aside>
