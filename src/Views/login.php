<?php
declare(strict_types=1);

$tenantSlug = trim((string) ($tenantSlug ?? $_SESSION['tenant_slug'] ?? ''));
$tenantName = trim((string) ($tenantName ?? $_SESSION['tenant_name'] ?? ''));
$tenantResolved = !empty($tenantResolved) && $tenantSlug !== '';
$tenantUnavailableMessage = trim((string) ($tenantUnavailableMessage ?? ''));
$logoLogin = \App\Core\Tenant\TenantAssetResolver::resolveLogo($tenantSlug);

$publicConteudos = isset($publicConteudos) && is_array($publicConteudos) ? $publicConteudos : [];
$publicAds = isset($publicAds) && is_array($publicAds) ? $publicAds : [];
$publicAdsEnabled = (bool) ($publicAdsEnabled ?? false);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acesso Restrito - <?= htmlspecialchars($tenantName ?: 'Gestor de Loja') ?></title>
    <link rel="stylesheet" href="/assets/css/erp_design_system.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { color-scheme: light dark; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: Inter, system-ui, -apple-system, Segoe UI, sans-serif;
            background: var(--erp-bg);
            color: var(--erp-text);
        }
        .page {
            max-width: 1240px;
            margin: 0 auto;
            padding: 16px;
        }
        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            background: var(--erp-surface);
            border: 1px solid var(--erp-border);
            border-radius: 10px;
            padding: 12px 14px;
        }
        .brand {
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 0;
        }
        .logo {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            border: 1px solid var(--erp-border);
            background: var(--erp-surface-2);
            object-fit: contain;
            flex: 0 0 auto;
        }
        .brand-title { font-size: 14px; font-weight: 600; }
        .brand-sub { font-size: 12px; color: var(--erp-muted); }
        .grid {
            margin-top: 14px;
            display: grid;
            grid-template-columns: 1fr;
            gap: 14px;
        }
        @media (min-width: 980px) {
            .grid { grid-template-columns: 1.2fr 0.8fr; align-items: start; }
        }
        .panel {
            background: var(--erp-surface);
            border: 1px solid var(--erp-border);
            border-radius: 10px;
            overflow: hidden;
        }
        .panel-head {
            padding: 12px 14px;
            border-bottom: 1px solid var(--erp-border);
        }
        .panel-title { font-size: 14px; font-weight: 600; }
        .panel-sub { margin-top: 4px; font-size: 12px; color: var(--erp-muted); }
        .panel-body { padding: 14px; }
        .content-list { display: grid; gap: 10px; }
        .content-card {
            display: block;
            color: inherit;
            text-decoration: none;
            border: 1px solid var(--erp-border);
            border-radius: 10px;
            padding: 12px;
            background: var(--erp-surface);
        }
        .content-card:hover { background: var(--erp-surface-2); }
        .badge {
            display: inline-block;
            font-size: 11px;
            letter-spacing: .04em;
            text-transform: uppercase;
            color: var(--erp-muted);
            border: 1px solid var(--erp-border);
            background: var(--erp-surface-2);
            border-radius: 999px;
            padding: 3px 8px;
        }
        .content-title { margin-top: 8px; font-size: 14px; font-weight: 600; color: var(--erp-text); }
        .content-resume { margin-top: 4px; font-size: 12px; color: var(--erp-muted); line-height: 1.45; }
        .ad-wrap { display: grid; gap: 10px; margin-bottom: 10px; }
        @media (min-width: 680px) { .ad-wrap { grid-template-columns: 1fr 1fr; } }
        .ad-media {
            width: 48px;
            height: 48px;
            border-radius: 8px;
            border: 1px solid var(--erp-border);
            object-fit: cover;
            background: var(--erp-surface-2);
            flex: 0 0 auto;
        }
        .ad-row { display: flex; gap: 10px; align-items: flex-start; }
        .form-row { margin-bottom: 12px; }
        .label {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: var(--erp-muted);
            font-weight: 600;
            margin-bottom: 6px;
            display: block;
        }
        .input {
            width: 100%;
            border: 1px solid var(--erp-border);
            background: var(--erp-surface);
            color: var(--erp-text);
            border-radius: 8px;
            padding: 11px 12px;
            font-size: 14px;
        }
        .input:focus {
            outline: none;
            border-color: var(--erp-info);
            box-shadow: 0 0 0 3px rgba(29, 78, 216, 0.18);
        }
        .btn {
            width: 100%;
            border-radius: 8px;
            padding: 11px 12px;
            font-size: 14px;
            font-weight: 600;
            border: 1px solid transparent;
            cursor: pointer;
        }
        .btn + .btn { margin-top: 10px; }
        .btn-primary { background: var(--erp-brand); color: #fff; }
        .btn-secondary { background: var(--erp-surface-2); color: var(--erp-text); border-color: var(--erp-border); }
        .btn[disabled] { opacity: .55; cursor: not-allowed; }
        .alert {
            border-radius: 8px;
            border: 1px solid var(--erp-border);
            padding: 10px 12px;
            font-size: 13px;
            margin-bottom: 12px;
        }
        .alert-warning { background: #fff8e8; border-color: #f2cf84; color: #6d4700; }
        .alert-danger { background: #fff1f2; border-color: #fecdd3; color: #9f1239; }
        .note { font-size: 12px; color: var(--erp-muted); margin-top: 10px; line-height: 1.4; }
    </style>
</head>
<body>
    <div class="page">
        <header class="topbar">
            <div class="brand">
                <?php if ($logoLogin): ?>
                    <img src="<?= htmlspecialchars($logoLogin) ?>" alt="Logo da Loja" class="logo" width="40" height="40">
                <?php else: ?>
                    <div class="logo"></div>
                <?php endif; ?>
                <div style="min-width:0;">
                    <div class="brand-title"><?= htmlspecialchars($tenantName ?: 'Gestor de Loja') ?></div>
                    <div class="brand-sub">Acesso e informações públicas</div>
                </div>
            </div>
            <div class="brand-sub">Painel Administrativo</div>
        </header>

        <div class="grid">
            <section class="panel">
                <div class="panel-head">
                    <div class="panel-title">Comunicados e agenda</div>
                    <div class="panel-sub">Informações públicas exibidas em /login.</div>
                </div>
                <div class="panel-body">
                    <?php if ($publicAdsEnabled && !empty($publicAds)): ?>
                        <div class="ad-wrap">
                            <?php foreach ($publicAds as $ad): ?>
                                <a href="<?= htmlspecialchars((string) ($ad['link_url'] ?? '#')) ?>" class="content-card">
                                    <div class="ad-row">
                                        <?php if (!empty($ad['imagem_url'])): ?>
                                            <img src="<?= htmlspecialchars((string) $ad['imagem_url']) ?>" alt="" class="ad-media" width="48" height="48">
                                        <?php endif; ?>
                                        <div>
                                            <div class="content-title" style="margin-top:0;"><?= htmlspecialchars((string) ($ad['titulo'] ?? 'Apoio')) ?></div>
                                            <div class="content-resume"><?= htmlspecialchars((string) ($ad['resumo'] ?? '')) ?></div>
                                        </div>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <div class="content-list">
                        <?php foreach ($publicConteudos as $item): ?>
                            <?php
                            $tipo = strtoupper(trim((string) ($item['tipo'] ?? '')));
                            $titulo = (string) ($item['titulo'] ?? '');
                            $resumo = (string) ($item['resumo'] ?? '');
                            $inicioEm = (string) ($item['inicio_em'] ?? '');
                            $linkUrl = (string) ($item['link_url'] ?? '');
                            ?>
                            <a href="<?= htmlspecialchars($linkUrl !== '' ? $linkUrl : '#') ?>" class="content-card">
                                <?php if ($tipo !== ''): ?>
                                    <span class="badge"><?= htmlspecialchars($tipo) ?></span>
                                <?php endif; ?>
                                <?php if ($inicioEm !== ''): ?>
                                    <span class="badge" style="margin-left:6px;"><?= htmlspecialchars($inicioEm) ?></span>
                                <?php endif; ?>
                                <div class="content-title"><?= htmlspecialchars($titulo) ?></div>
                                <?php if ($resumo !== ''): ?>
                                    <div class="content-resume"><?= htmlspecialchars($resumo) ?></div>
                                <?php endif; ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>

            <aside class="panel">
                <div class="panel-head">
                    <div class="panel-title">Acesso restrito</div>
                    <div class="panel-sub">Entre com seu CIM e senha cadastrada.</div>
                </div>
                <div class="panel-body">
                    <?php if (!$tenantResolved): ?>
                        <div class="alert alert-warning"><?= htmlspecialchars($tenantUnavailableMessage ?: 'Loja não identificada. Verifique a configuração do ambiente.') ?></div>
                    <?php endif; ?>

                    <?php if (isset($erroLogin)): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars((string) $erroLogin) ?></div>
                    <?php endif; ?>

                    <form action="/login" method="POST">
                        <div class="form-row">
                            <label for="matricula" class="label">CIM / Matrícula</label>
                            <input id="matricula" name="matricula" type="text" required <?= !$tenantResolved ? 'disabled' : '' ?> class="input" placeholder="Digite seu CIM">
                        </div>
                        <div class="form-row">
                            <label for="password" class="label">Senha</label>
                            <input id="password" name="password" type="password" required <?= !$tenantResolved ? 'disabled' : '' ?> class="input" placeholder="Digite sua senha">
                        </div>
                        <button type="submit" name="acao" value="login" <?= !$tenantResolved ? 'disabled' : '' ?> class="btn btn-primary">Entrar</button>
                        <button type="submit" name="acao" value="solicitar" <?= !$tenantResolved ? 'disabled' : '' ?> class="btn btn-secondary">Solicitar acesso</button>
                    </form>

                    <div class="note">Em caso de dúvida, procure a Secretaria da sua Loja para validação cadastral.</div>
                </div>
            </aside>
        </div>
    </div>
    <script>
        (function () {
            try {
                if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                    document.documentElement.classList.add('dark');
                }
            } catch (e) {}
        })();
    </script>
</body>
</html>

