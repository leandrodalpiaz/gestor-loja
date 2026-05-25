<?php

namespace App\Bot;

use App\Config\Env;
use App\Core\Auth\AccountGate;
use App\Core\Authorization\PermissionMap;
use App\Models\ComprovantePix;
use App\Models\ConfiguracaoLoja;
use App\Models\EfemerideRegistro;
use App\Models\ObrigacaoFinanceira;
use App\Models\ConviteAcesso;

class CommandHandler
{
    private $telegram;
    private $obreiroModel;
    private $sessaoModel;
    private $presencaModel;
    private array $accessStateCache = [];
    private array $obreiroByTelegramCache = [];
    private array $permissionsByKeyCache = [];

    private array $devIds = [8062119710];

    public function __construct($telegram, $obreiroModel, $sessaoModel, $presencaModel)
    {
        $this->telegram = $telegram;
        $this->obreiroModel = $obreiroModel;
        $this->sessaoModel = $sessaoModel;
        $this->presencaModel = $presencaModel;
    }

    private function getAppBaseUrl(): string
    {
        $base = trim((string) Env::get('APP_URL', ''));
        if ($base === '') {
            error_log('[bot] APP_URL ausente no .env; links web_app foram bloqueados até a configuração.');
            return '';
        }

        return rtrim($base, '/');
    }

    private function buildAppUrl(string $path): string
    {
        $base = $this->getAppBaseUrl();
        if ($base === '') {
            return '';
        }

        $path = '/' . ltrim($path, '/');
        $separator = strpos($path, '?') === false ? '?' : '&';
        return $base . $path . $separator . 'tg_webapp=1&v=' . time();
    }

    private function getGroupChatId(): ?string
    {
        $candidates = [
            trim((string) Env::get('TELEGRAM_CHAT_ID_GROUP', '')),
            trim((string) Env::get('TELEGRAM_GRUPO_ID', '')),
            trim((string) Env::get('TELEGRAM_GROUP_ID', '')),
            trim((string) Env::get('TELEGRAM_CHAT_ID', '')),
        ];

        foreach ($candidates as $chatId) {
            if ($chatId !== '') {
                return $chatId;
            }
        }

        return null;
    }

    private function getObreiroRoles(?array $obreiro): array
    {
        if (!$obreiro) {
            return [];
        }

        $normalizeRole = function ($role): string {
            $normalized = strtolower(trim((string) $role));
            $normalized = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $normalized) ?: $normalized;
            $normalized = preg_replace('/[^a-z0-9_]+/', '', $normalized) ?? '';

            $aliases = [
                'veneravelmestre' => 'veneravel',
                'vm' => 'veneravel',
                'mestredeharmonia' => 'mestre_harmonia',
            ];

            return $aliases[$normalized] ?? $normalized;
        };

        if (!empty($obreiro['cargos']) && is_array($obreiro['cargos'])) {
            $roles = array_values(array_unique(array_filter(array_map($normalizeRole, $obreiro['cargos']))));
            if ($roles !== [] && !in_array('obreiro', $roles, true)) {
                $roles[] = 'obreiro';
            }

            return $roles;
        }

        $fallback = $normalizeRole((string) ($obreiro['cargo_principal'] ?? $obreiro['cargo'] ?? ''));
        if ($fallback === '') {
            return [];
        }

        return $fallback === 'obreiro' ? ['obreiro'] : [$fallback, 'obreiro'];
    }

    private function obreiroHasRole(?array $obreiro, string ...$roles): bool
    {
        $current = $this->getObreiroRoles($obreiro);
        foreach ($roles as $role) {
            if (in_array(strtolower($role), $current, true)) {
                return true;
            }
        }

        return false;
    }

    private function obreiroHasPermission(?array $obreiro, string $permission): bool
    {
        if (!$obreiro) {
            return false;
        }

        $permissions = $this->getPermissionsForObreiro($obreiro);
        return in_array('*', $permissions, true) || in_array($permission, $permissions, true);
    }

    private function getPermissionsForObreiro(?array $obreiro): array
    {
        if (!$obreiro) {
            return [];
        }

        $roles = $this->getObreiroRoles($obreiro);
        sort($roles);
        $cacheKey = implode('|', $roles);
        if (array_key_exists($cacheKey, $this->permissionsByKeyCache)) {
            return $this->permissionsByKeyCache[$cacheKey];
        }

        $permissions = (new PermissionMap())->permissionsForRoles($roles);
        $this->permissionsByKeyCache[$cacheKey] = $permissions;
        return $permissions;
    }

    private function isDev(int $telegramId): bool
    {
        if (in_array($telegramId, $this->devIds, true)) {
            return true;
        }

        $raw = trim((string) Env::get('SYSTEM_ADMIN_TELEGRAM_IDS', ''));
        if ($raw === '') {
            return false;
        }

        $ids = preg_split('/\s*,\s*/', $raw, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        foreach ($ids as $id) {
            if ((int) trim((string) $id) === $telegramId) {
                return true;
            }
        }

        return false;
    }

    private function isPrivateChat($chatId): bool
    {
        return (int) $chatId > 0;
    }

    private function isPrivateChatType(string $chatType): bool
    {
        return strtolower(trim($chatType)) === 'private';
    }

    private function extractCommandLabel(array $update): string
    {
        if (isset($update['message']['text'])) {
            $text = trim((string) $update['message']['text']);
            if ($text !== '') {
                return explode(' ', $text)[0];
            }
        }

        if (isset($update['callback_query']['data'])) {
            return 'callback:' . (string) $update['callback_query']['data'];
        }

        return 'unknown';
    }

    private function logBotEvent(string $source, array $update, ?string $appUrl = null): void
    {
        $chatType = (string) ($update['message']['chat']['type'] ?? $update['callback_query']['message']['chat']['type'] ?? 'unknown');
        $chatId = (string) ($update['message']['chat']['id'] ?? $update['callback_query']['message']['chat']['id'] ?? 'n/a');
        $userId = (string) ($update['message']['from']['id'] ?? $update['callback_query']['from']['id'] ?? 'n/a');
        $command = $this->extractCommandLabel($update);
        $safeAppUrl = $appUrl ?? $this->getAppBaseUrl();
        $safeAppUrl = $safeAppUrl !== '' ? $safeAppUrl : 'missing';

        error_log("[bot] source={$source} chat_type={$chatType} chat_id={$chatId} user_id={$userId} command={$command} app_url={$safeAppUrl}");
    }

    private function privateMenuHint(): string
    {
        return "\n\nSe algum botão não abrir, envie /painel novamente.";
    }

    private function ensureAppUrlConfigured(int|string $chatId): bool
    {
        if ($this->getAppBaseUrl() !== '') {
            return true;
        }

        $this->telegram->sendMessage(
            $chatId,
            'Mini app indisponível no momento. APP_URL não configurada. Reenvie /painel após atualizar o ambiente local.'
        );
        return false;
    }

    private function resolvePrivateAccess(int $telegramId): array
    {
        if (array_key_exists($telegramId, $this->accessStateCache)) {
            return $this->accessStateCache[$telegramId];
        }

        $gate = new AccountGate($this->obreiroModel);
        $access = $gate->byTelegramId($telegramId);
        $this->accessStateCache[$telegramId] = $access;
        return $access;
    }

    private function findObreiroByTelegramId(int $telegramId): ?array
    {
        if (array_key_exists($telegramId, $this->obreiroByTelegramCache)) {
            return $this->obreiroByTelegramCache[$telegramId];
        }

        $obreiro = $this->obreiroModel->findByTelegramId($telegramId);
        $this->obreiroByTelegramCache[$telegramId] = $obreiro ?: null;
        return $this->obreiroByTelegramCache[$telegramId];
    }

    private function sendAccessStateMessage(int|string $chatId, string $state): void
    {
        if ($state === 'pendente') {
            $this->telegram->sendMessage($chatId, 'Seu acesso está pendente. Aguarde aprovação do secretário.');
            return;
        }

        if ($state === 'inativo') {
            $this->telegram->sendMessage($chatId, 'Seu acesso está inativo. Procure o secretário.');
            return;
        }

        $this->telegram->sendMessage(
            $chatId,
            'Registro não localizado. Use /solicitar <CIM> <senha> ou procure o secretário para cadastro.'
        );
    }

    private function handleSolicitarAcessoTelegram(int|string $chatId, int $telegramId, string $text): void
    {
        $parts = preg_split('/\s+/', trim($text)) ?: [];
        if (count($parts) < 3) {
            $this->telegram->sendMessage($chatId, 'Use: /solicitar <CIM> <senha>');
            return;
        }

        $cim = trim((string) ($parts[1] ?? ''));
        $senha = trim((string) ($parts[2] ?? ''));
        if ($cim === '' || $senha === '') {
            $this->telegram->sendMessage($chatId, 'Use: /solicitar <CIM> <senha>');
            return;
        }

        $solicitacao = $this->obreiroModel->solicitarAcessoPorCim($cim, $senha, $telegramId);
        if (!($solicitacao['ok'] ?? false)) {
            $this->telegram->sendMessage($chatId, 'Procure o secretário para cadastro');
            return;
        }

            $this->telegram->sendMessage($chatId, 'Solicitação registrada. Aguarde aprovação do secretário.');
    }

    private function notifyPrivateOnly($chatId): void
    {
        $this->telegram->sendMessage(
            $chatId,
            'Para acessar o sistema, fale comigo no privado.'
        );
    }

    private function ensureChancelariaAccess($chatId, int $requesterTelegramId): bool
    {
        $obreiro = $this->findObreiroByTelegramId($requesterTelegramId);
        if (!$this->isDev($requesterTelegramId) && (!$obreiro || !$this->obreiroHasPermission($obreiro, 'chancelaria.manage'))) {
            $this->telegram->sendMessage($chatId, 'Acesso restrito ao Chanceler, Venerável Mestre ou Administrador.');
            return false;
        }

        return true;
    }


    public function handlePainelAdmin($chatId, $requesterTelegramId)
    {
        if (!$this->isPrivateChat($chatId)) {
            $this->notifyPrivateOnly($chatId);
            return;
        }

        $obreiro = $this->findObreiroByTelegramId($requesterTelegramId);
        if (!$this->isDev($requesterTelegramId) && !$this->obreiroHasPermission($obreiro, 'admin.cargos.view')) {
            $this->telegram->sendMessage($chatId, 'Acesso restrito aos responsáveis do sistema.');
            return;
        }

        $mensagem = "*Painel do Sistema*\n\nSelecione o módulo que deseja acessar:";
        $teclado = [
            'inline_keyboard' => [
                [
                    ['text' => 'Chancelaria', 'callback_data' => 'admin_chancelaria'],
                    ['text' => 'Tesouraria', 'callback_data' => 'admin_tesouraria'],
                ],
                [
                    ['text' => 'Biblioteca', 'callback_data' => 'admin_biblioteca'],
                    ['text' => 'Secretaria', 'callback_data' => 'admin_secretaria'],
                ],
                [
                    ['text' => 'Hospitaleiro', 'callback_data' => 'admin_hospitaleiro'],
                    ['text' => '1º Vigilante', 'callback_data' => 'admin_primeiro_vigilante'],
                ],
                [
                    ['text' => '2º Vigilante', 'callback_data' => 'admin_segundo_vigilante'],
                    ['text' => 'Orador', 'callback_data' => 'admin_orador'],
                ],
                [
                    ['text' => 'Mestre de Banquetes', 'callback_data' => 'admin_mestre_banquetes'],
                    ['text' => 'Mestre de Harmonia', 'callback_data' => 'admin_mestre_harmonia'],
                ],
                [
                    ['text' => 'Venerável', 'callback_data' => 'admin_veneravel'],
                    ['text' => 'Assistente', 'callback_data' => 'admin_assistente'],
                ],
                [
                ],
                [
                    ['text' => 'Voltar', 'callback_data' => 'start_menu'],
                ],
            ],
        ];

        $this->telegram->sendMessage($chatId, $mensagem, ['parse_mode' => 'Markdown', 'reply_markup' => $teclado]);
    }

    private function handlePrimeiroVigilanteMenu($chatId, int $requesterTelegramId): void
    {
        $obreiro = $this->findObreiroByTelegramId($requesterTelegramId);
        if (!$this->isDev($requesterTelegramId) && !$this->obreiroHasPermission($obreiro, 'vigilancia.primeiro.manage')) {
            $this->telegram->sendMessage($chatId, 'Acesso restrito ao 1º Vigilante, Venerável Mestre ou Administrador.');
            return;
        }

        $mensagem = "*Painel do 1º Vigilante*\n\nEscolha o modo de trabalho:";
        $teclado = [
            'inline_keyboard' => [
                [
                    ['text' => 'Aprendizado', 'web_app' => ['url' => $this->buildAppUrl('/miniapp/aprendizado')]],
                    ['text' => 'Em Loja', 'web_app' => ['url' => $this->buildAppUrl('/miniapp/primeiro-vigilante')]],
                ],
                [
                    ['text' => 'Voltar', 'callback_data' => 'start_menu'],
                ],
            ],
        ];

        $this->telegram->sendMessage($chatId, $mensagem, ['parse_mode' => 'Markdown', 'reply_markup' => $teclado]);
    }

    private function handleSegundoVigilanteMenu($chatId, int $requesterTelegramId): void
    {
        $obreiro = $this->findObreiroByTelegramId($requesterTelegramId);
        if (!$this->isDev($requesterTelegramId) && !$this->obreiroHasPermission($obreiro, 'vigilancia.segundo.manage')) {
            $this->telegram->sendMessage($chatId, 'Acesso restrito ao 2º Vigilante, Venerável Mestre ou Administrador.');
            return;
        }

        $mensagem = "*Painel do 2º Vigilante*\n\nEscolha o modo de trabalho:";
        $teclado = [
            'inline_keyboard' => [
                [
                    ['text' => 'Companheirismo', 'web_app' => ['url' => $this->buildAppUrl('/miniapp/companheirismo')]],
                    ['text' => 'Em Loja', 'web_app' => ['url' => $this->buildAppUrl('/miniapp/segundo-vigilante')]],
                ],
                [
                    ['text' => 'Voltar', 'callback_data' => 'start_menu'],
                ],
            ],
        ];

        $this->telegram->sendMessage($chatId, $mensagem, ['parse_mode' => 'Markdown', 'reply_markup' => $teclado]);
    }

    private function handleOradorMenu($chatId, int $requesterTelegramId): void
    {
        $obreiro = $this->findObreiroByTelegramId($requesterTelegramId);
        if (!$this->isDev($requesterTelegramId) && !$this->obreiroHasPermission($obreiro, 'orador.view')) {
            $this->telegram->sendMessage($chatId, 'Acesso restrito ao Orador, Venerável Mestre ou Administrador.');
            return;
        }

        $mensagem = "*Painel do Orador*\n\nAcesse o painel operacional:";
        $teclado = [
            'inline_keyboard' => [
                [
                    ['text' => 'Em Loja', 'web_app' => ['url' => $this->buildAppUrl('/miniapp/orador')]],
                ],
                [
                    ['text' => 'Voltar', 'callback_data' => 'start_menu'],
                ],
            ],
        ];

        $this->telegram->sendMessage($chatId, $mensagem, ['parse_mode' => 'Markdown', 'reply_markup' => $teclado]);
    }

    private function handleMestreBanquetesMenu($chatId, int $requesterTelegramId): void
    {
        $obreiro = $this->findObreiroByTelegramId($requesterTelegramId);
        if (!$this->isDev($requesterTelegramId) && !$this->obreiroHasPermission($obreiro, 'mestre_banquetes.manage')) {
            $this->telegram->sendMessage($chatId, 'Acesso restrito ao Mestre de Banquetes, Venerável Mestre ou Administrador.');
            return;
        }

        $mensagem = "*Painel do Mestre de Banquetes*\n\nAcesse o painel operacional:";
        $teclado = [
            'inline_keyboard' => [
                [
                    ['text' => 'Em Loja', 'web_app' => ['url' => $this->buildAppUrl('/miniapp/mestre-banquetes')]],
                ],
                [
                    ['text' => 'Voltar', 'callback_data' => 'start_menu'],
                ],
            ],
        ];

        $this->telegram->sendMessage($chatId, $mensagem, ['parse_mode' => 'Markdown', 'reply_markup' => $teclado]);
    }

    private function handleMestreHarmoniaMenu($chatId, int $requesterTelegramId): void
    {
        $obreiro = $this->findObreiroByTelegramId($requesterTelegramId);
        if (!$this->isDev($requesterTelegramId) && !$this->obreiroHasPermission($obreiro, 'mestre_harmonia.manage')) {
            $this->telegram->sendMessage($chatId, 'Acesso restrito ao Mestre de Harmonia, Venerável Mestre ou Administrador.');
            return;
        }

        $mensagem = "*Painel do Mestre de Harmonia*\n\nAcesse o painel operacional:";
        $teclado = [
            'inline_keyboard' => [
                [
                    ['text' => 'Em Loja', 'web_app' => ['url' => $this->buildAppUrl('/miniapp/mestre-harmonia')]],
                ],
                [
                    ['text' => 'Voltar', 'callback_data' => 'start_menu'],
                ],
            ],
        ];

        $this->telegram->sendMessage($chatId, $mensagem, ['parse_mode' => 'Markdown', 'reply_markup' => $teclado]);
    }

    private function handleVeneravelMenu($chatId, int $requesterTelegramId): void
    {
        $obreiro = $this->findObreiroByTelegramId($requesterTelegramId);
        if (!$this->isDev($requesterTelegramId) && !$this->obreiroHasPermission($obreiro, 'veneravel.manage')) {
            $this->telegram->sendMessage($chatId, 'Acesso restrito ao Venerável Mestre ou Administrador.');
            return;
        }

        $mensagem = "*Painel do Venerável Mestre*\n\nEscolha o modo de trabalho:";
        $teclado = [
            'inline_keyboard' => [
                [
                    ['text' => 'Em Loja', 'web_app' => ['url' => $this->buildAppUrl('/miniapp/veneravel')]],
                    ['text' => 'Venerável Mobile', 'web_app' => ['url' => $this->buildAppUrl('/miniapp/veneravel')]],
                ],
                [
                    ['text' => 'Voltar', 'callback_data' => 'start_menu'],
                ],
            ],
        ];

        $this->telegram->sendMessage($chatId, $mensagem, ['parse_mode' => 'Markdown', 'reply_markup' => $teclado]);
    }

    private function handleAssistenteBotMenu($chatId, int $requesterTelegramId): void
    {
        $obreiro = $this->findObreiroByTelegramId($requesterTelegramId);
        if (
            !$this->isDev($requesterTelegramId)
            && (!$obreiro || !$this->obreiroHasRole(
                $obreiro,
                'veneravel',
                'secretario',
                'tesoureiro',
                'chanceler',
                'orador',
                'hospitaleiro',
                'mestre_banquetes',
                'mestre_harmonia',
                'primeiro_vigilante',
                'segundo_vigilante',
                'bibliotecario'
            ))
        ) {
            $this->telegram->sendMessage($chatId, 'Acesso restrito ao painel de assistência operacional.');
            return;
        }

        $mensagem = "*Assistente Operacional*\n\nAcesse o assistente por cargo:";
        $teclado = [
            'inline_keyboard' => [
                [
                    ['text' => 'Abrir Assistente', 'web_app' => ['url' => $this->buildAppUrl('/miniapp/assistente')]],
                ],
                [
                    ['text' => 'Voltar', 'callback_data' => 'start_menu'],
                ],
            ],
        ];

        $this->telegram->sendMessage($chatId, $mensagem, ['parse_mode' => 'Markdown', 'reply_markup' => $teclado]);
    }

    public function sendMenuFrequencia($chatId)
    {
        $this->handleSessaoInfo($chatId, null);
    }

    public function sendMenuPrincipal($chatId, $fromId)
    {
        if (!$this->isPrivateChat($chatId)) {
            $this->notifyPrivateOnly($chatId);
            return;
        }

        $obreiro = $this->findObreiroByTelegramId((int) $fromId);
        $isDev = $this->isDev((int) $fromId);
        $this->logBotEvent('menu_principal', [
            'message' => [
                'chat' => ['id' => $chatId, 'type' => 'private'],
                'from' => ['id' => $fromId],
                'text' => '/painel',
            ],
        ], $this->getAppBaseUrl());
                $mensagem = "Bem-vindo ao painel da Loja, meu Irmão!" . $this->privateMenuHint();
        $teclado = [
            'inline_keyboard' => [
                [
                    ['text' => 'Meu cadastro', 'callback_data' => 'menu_meu_cadastro'],
                    ['text' => 'Minhas informações', 'callback_data' => 'menu_minhas_info'],
                ],
                [
                    ['text' => 'Abrir PWA', 'web_app' => ['url' => $this->buildAppUrl('/pwa')]],
                ],
                array_values(array_filter([
                    \App\Config\FeatureFlags::pwaSessoes() ? ['text' => 'Sessões (PWA)', 'web_app' => ['url' => $this->buildAppUrl('/pwa/sessoes')]] : null,
                    \App\Config\FeatureFlags::pwaBiblioteca() ? ['text' => 'Biblioteca (PWA)', 'web_app' => ['url' => $this->buildAppUrl('/pwa/biblioteca')]] : null,
                ])),
                array_values(array_filter([
                    \App\Config\FeatureFlags::pwaComunicacao() ? ['text' => 'Comunicados (PWA)', 'web_app' => ['url' => $this->buildAppUrl('/pwa/comunicacao')]] : null,
                    \App\Config\FeatureFlags::pwaAdminCrud() ? ['text' => 'Admin (PWA)', 'web_app' => ['url' => $this->buildAppUrl('/pwa/admin')]] : null,
                ])),
                [
                    ['text' => 'Ajuda / contato', 'callback_data' => 'menu_ajuda_contato'],
                ],
            ],
        ];
        $teclado['inline_keyboard'] = array_values(array_filter(
            $teclado['inline_keyboard'],
            static fn ($row): bool => is_array($row) && $row !== []
        ));

        if ($isDev || $this->obreiroHasPermission($obreiro, 'chancelaria.manage')) {
            $teclado['inline_keyboard'][] = [
                ['text' => 'Chancelaria', 'callback_data' => 'admin_chancelaria'],
            ];
        }

        if ($isDev || $this->obreiroHasPermission($obreiro, 'tesouraria.manage')) {
            $teclado['inline_keyboard'][] = [
                ['text' => 'Tesouraria', 'callback_data' => 'tesouraria_menu'],
            ];
        }

        if ($isDev || $this->obreiroHasPermission($obreiro, 'secretaria.manage')) {
            $teclado['inline_keyboard'][] = [
                ['text' => 'Secretaria', 'callback_data' => 'secretaria_menu'],
            ];
        }

        if ($isDev || $this->obreiroHasPermission($obreiro, 'hospitaleiro.manage')) {
            $teclado['inline_keyboard'][] = [
                ['text' => 'Assistência', 'callback_data' => 'assistencia_menu'],
            ];
        }

        if ($isDev || $this->obreiroHasPermission($obreiro, 'vigilancia.primeiro.manage')) {
            $teclado['inline_keyboard'][] = [
                ['text' => '1º Vigilante', 'callback_data' => 'admin_primeiro_vigilante'],
            ];
        }

        if ($isDev || $this->obreiroHasPermission($obreiro, 'vigilancia.segundo.manage')) {
            $teclado['inline_keyboard'][] = [
                ['text' => '2º Vigilante', 'callback_data' => 'admin_segundo_vigilante'],
            ];
        }

        if ($isDev || $this->obreiroHasPermission($obreiro, 'orador.view')) {
            $teclado['inline_keyboard'][] = [
                ['text' => 'Orador', 'callback_data' => 'admin_orador'],
            ];
        }

        if ($isDev || $this->obreiroHasPermission($obreiro, 'mestre_banquetes.manage')) {
            $teclado['inline_keyboard'][] = [
                ['text' => 'Mestre de Banquetes', 'callback_data' => 'admin_mestre_banquetes'],
            ];
        }

        if ($isDev || $this->obreiroHasPermission($obreiro, 'mestre_harmonia.manage')) {
            $teclado['inline_keyboard'][] = [
                ['text' => 'Mestre de Harmonia', 'callback_data' => 'admin_mestre_harmonia'],
            ];
        }

        if ($isDev || $this->obreiroHasPermission($obreiro, 'veneravel.manage')) {
            $teclado['inline_keyboard'][] = [
                ['text' => 'Venerável', 'callback_data' => 'admin_veneravel'],
            ];
        }

        if ($isDev || $this->obreiroHasPermission($obreiro, 'biblioteca.self')) {
            $teclado['inline_keyboard'][] = [
                ['text' => 'Biblioteca', 'callback_data' => 'biblioteca_menu'],
            ];
        }

        if ($isDev || $this->obreiroHasPermission($obreiro, '*') || $this->obreiroHasPermission($obreiro, 'admin.cargos.view')) {
            $teclado['inline_keyboard'][] = [            ];
        }

        $this->telegram->sendMessage($chatId, $mensagem, ['reply_markup' => $teclado]);
    }

    private function montarResumoSessaoPublicado(array $sessao): string
    {
        $dataHora = (string) ($sessao['data_hora_inicio'] ?? '');
        $grau = (string) ($sessao['grau_sessao'] ?? '-');
        $tipo = (string) ($sessao['tipo_sessao'] ?? '-');
        $traje = (string) (($sessao['traje_tipo'] ?? 'maconico') === 'livre'
            ? 'Livre'
            : (($sessao['traje_tipo'] ?? 'maconico') === 'outro'
                ? ((string) ($sessao['traje_personalizado'] ?? 'Outro'))
                : 'Maçônico'));
        $agape = match ((string) ($sessao['agape_modalidade'] ?? 'nao_havera')) {
            'gratuito' => 'Sim (gratuito)',
            'pago' => 'Sim (pago)',
            default => 'Não haverá',
        };

        $config = (new ConfiguracaoLoja())->obter();
        $nomeLoja = trim((string) ($config['nome_loja'] ?? ''));
        $numeroLoja = trim((string) ($config['numero_loja'] ?? ''));
        $linhaLoja = trim($nomeLoja . ($numeroLoja !== '' ? ' nº ' . $numeroLoja : ''));
        $ordemDia = trim((string) ($sessao['ordem_dia'] ?? $sessao['resumo_publico'] ?? ''));

        return "NOVA SESSÃO\n\n"
            . $dataHora . "\n"
            . "Grau: {$grau}\n\n"
            . $linhaLoja . "\n\n"
            . "Sessão:\n"
            . "Tipo: {$tipo}\n"
            . "Traje: {$traje}\n"
            . "Ordem do dia: " . ($ordemDia !== '' ? $ordemDia : '-') . "\n"
            . "Ágape: {$agape}";
    }

    private function montarBotoesSessao(array $sessao): array
    {
        $modalidade = (string) ($sessao['agape_modalidade'] ?? 'nao_havera');
        $linhas = [];
        if ($modalidade === 'gratuito') {
            $linhas[] = [
                ['text' => 'Participar com ágape (gratuito)', 'callback_data' => 'presenca_agape_gratuito'],
            ];
            $linhas[] = [
                ['text' => 'Participar sem ágape', 'callback_data' => 'presenca_sem_agape'],
            ];
        } elseif ($modalidade === 'pago') {
            $linhas[] = [
                ['text' => 'Participar com ágape (pago)', 'callback_data' => 'presenca_agape_pago'],
            ];
            $linhas[] = [
                ['text' => 'Participar sem ágape', 'callback_data' => 'presenca_sem_agape'],
            ];
        } else {
            $linhas[] = [
                ['text' => 'Confirmar presença', 'callback_data' => 'presenca_confirmar'],
            ];
        }

        $linhas[] = [
            ['text' => 'Cancelar confirmação', 'callback_data' => 'presenca_cancelar'],
            ['text' => 'Informar ausência', 'callback_data' => 'presenca_ausencia'],
        ];
        $linhas[] = [
            ['text' => 'Ver confirmados', 'callback_data' => 'presenca_ver_confirmados'],
        ];

        return $linhas;
    }

    private function handleSessaoInfo($chatId, ?int $fromId): void
    {
        $sessao = $this->sessaoModel->obterProximaSessao();
        if (!$sessao) {
            $this->telegram->sendMessage($chatId, 'Ainda não há sessão futura cadastrada.');
            return;
        }

        $mensagem = $this->montarResumoSessaoPublicado($sessao);
        $this->telegram->sendMessage($chatId, $mensagem, [
            'reply_markup' => ['inline_keyboard' => $this->montarBotoesSessao($sessao)],
        ]);
    }

    private function handleConfirmacaoProximaSessao($chatId, int $fromId, string $acao): void
    {
        $obreiro = $this->findObreiroByTelegramId((int) $fromId);
        if (!$obreiro) {
            $this->telegram->sendMessage($chatId, 'Não foi possível localizar seu cadastro agora. Tente novamente ou contate a Secretaria.');
            return;
        }

        $sessao = $this->sessaoModel->obterProximaSessao();
        if (!$sessao || empty($sessao['id'])) {
            $this->telegram->sendMessage($chatId, 'Não há sessão disponível para confirmar no momento.');
            return;
        }

        $sessaoId = (int) $sessao['id'];
        $obreiroId = (string) ($obreiro['id'] ?? '');
        $ok = false;
        $mensagem = 'Não conseguimos registrar sua resposta agora. Tente novamente em alguns minutos.';

        switch ($acao) {
            case 'confirmar':
                $ok = $this->presencaModel->registrar($sessaoId, $obreiroId, 'confirmado', false);
                $mensagem = $ok ? 'Presença confirmada.' : $mensagem;
                break;
            case 'com_agape':
                $ok = $this->presencaModel->registrar($sessaoId, $obreiroId, 'confirmado', true);
                $mensagem = $ok ? 'Presença confirmada com ágape.' : $mensagem;
                break;
            case 'sem_agape':
                $ok = $this->presencaModel->registrar($sessaoId, $obreiroId, 'confirmado', false);
                $mensagem = $ok ? 'Presença confirmada sem ágape.' : $mensagem;
                break;
            case 'ausencia':
                $ok = $this->presencaModel->registrar($sessaoId, $obreiroId, 'ausente', false);
                $mensagem = $ok ? 'Ausência registrada.' : $mensagem;
                break;
            case 'cancelar':
                $ok = $this->presencaModel->cancelar($sessaoId, $obreiroId);
                $mensagem = $ok ? 'Confirmação cancelada. Sua resposta voltou para pendente.' : $mensagem;
                break;
            case 'ver_confirmados':
                $confirmados = $this->presencaModel->listarConfirmadosPorSessao($sessaoId);
                if ($confirmados === []) {
                    $this->telegram->sendMessage($chatId, 'Ainda não há confirmações para esta sessão.');
                    return;
                }
                $linhas = ["Confirmados da próxima sessão:\n"];
                foreach ($confirmados as $item) {
                    $linhas[] = '- ' . (string) ($item['nome'] ?? 'Obreiro') . (!empty($item['participara_agape']) ? ' (com ágape)' : ' (sem ágape)');
                }
                $this->telegram->sendMessage($chatId, implode("\n", $linhas));
                return;
        }

        $this->telegram->sendMessage($chatId, $mensagem);
    }

    public function handleHelp($chatId)
    {
        $mensagem = "<b>Ajuda do Gestor da Loja</b>\n\n";
        $mensagem .= "Comandos disponíveis:\n";
        $mensagem .= "/start - abre o menu principal\n";
        $mensagem .= "/chancelaria - painel da chancelaria\n";
        $mensagem .= "/tesouraria - painel da tesouraria\n";
        $mensagem .= "/biblioteca - painel da biblioteca\n";
        $mensagem .= "/assistencia - painel de assistência\n";
        $mensagem .= "/painel - painel administrativo\n";
        $mensagem .= "/solicitar <CIM> <senha> - solicitar liberação de acesso\n";

        $this->telegram->sendMessage($chatId, $mensagem, ['parse_mode' => 'HTML']);
    }

    public function handleChancelaria($chatId, $requesterTelegramId)
    {
        if (!$this->ensureAppUrlConfigured($chatId)) {
            return;
        }

        if (!$this->ensureChancelariaAccess($chatId, (int) $requesterTelegramId)) {
            return;
        }

        $mensagem = "*Painel do Chanceler*\n\nEscolha o modo de trabalho:";
        $teclado = [
            'inline_keyboard' => [
                [
                    ['text' => '📱 App Em Loja', 'web_app' => ['url' => $this->buildAppUrl('/miniapp/chanceler')]],
                ],
                [
                    ['text' => '📋 Efemérides do Dia', 'callback_data' => 'chancelaria_neste_dia'],
                ],

                [
                    ['text' => '« Voltar', 'callback_data' => 'start_menu'],
                ],
            ],
        ];

        $this->telegram->sendMessage($chatId, $mensagem, ['parse_mode' => 'Markdown', 'reply_markup' => $teclado]);
    }

    public function handleTesouraria($chatId, $requesterTelegramId)
    {
        if (!$this->ensureAppUrlConfigured($chatId)) {
            return;
        }

        $obreiro = $this->findObreiroByTelegramId($requesterTelegramId);
        if (!$this->isDev($requesterTelegramId) && (!$obreiro || !$this->obreiroHasRole($obreiro, 'tesoureiro', 'veneravel'))) {
            $this->telegram->sendMessage($chatId, 'Acesso restrito ao Tesoureiro, Venerável Mestre ou Administrador.');
            return;
        }

        $mensagem = "*Painel da Tesouraria*\n\nSelecione uma opção:";
        $teclado = [
            'inline_keyboard' => [
                [
                    ['text' => 'Abrir Tesouraria Mobile', 'web_app' => ['url' => $this->buildAppUrl('/miniapp/tesouraria')]],
                ],
                [
                    ['text' => 'Minhas Obrigações', 'web_app' => ['url' => $this->buildAppUrl('/financeiro/minhas-obrigacoes')]],
                    ['text' => 'Como pagar via PIX', 'callback_data' => 'tesouraria_orientacao_pix'],
                ],
                [
                    ['text' => 'Caixa da Loja', 'web_app' => ['url' => $this->buildAppUrl('/miniapp/tesouraria?dest=%2Ftesouraria%2Fcaixa')]],
                    ['text' => 'Comprovantes', 'web_app' => ['url' => $this->buildAppUrl('/miniapp/tesouraria?dest=%2Ftesouraria%2Fcomprovantes')]],
                ],
                [
                    ['text' => 'Regularidade', 'web_app' => ['url' => $this->buildAppUrl('/miniapp/tesouraria?dest=%2Ftesouraria%2Fregularidade')]],
                    ['text' => 'Fechamento Mensal', 'web_app' => ['url' => $this->buildAppUrl('/miniapp/tesouraria?dest=%2Ftesouraria%2Ffechamento')]],
                ],
                [
                    ['text' => 'Obrigações', 'web_app' => ['url' => $this->buildAppUrl('/miniapp/tesouraria?dest=%2Ftesouraria%2Fobrigacoes')]],
                    ['text' => 'Sessões', 'web_app' => ['url' => $this->buildAppUrl('/miniapp/tesouraria?dest=%2Ftesouraria%2Fsessoes')]],
                ],
                [
                    ['text' => 'Validar Pix', 'web_app' => ['url' => $this->buildAppUrl('/miniapp/tesouraria?dest=%2Ftesouraria%2Fcomprovantes')]],
                    ['text' => 'Relatório de Gestão', 'web_app' => ['url' => $this->buildAppUrl('/miniapp/tesouraria?dest=%2Ftesouraria%2Frelatorio-gestao')]],
                ],
                [
                    ['text' => 'Voltar', 'callback_data' => 'start_menu'],
                ],
            ],
        ];

        $this->telegram->sendMessage($chatId, $mensagem, ['parse_mode' => 'Markdown', 'reply_markup' => $teclado]);
    }

    public function handleBiblioteca($chatId, $requesterTelegramId)
    {
        if (!$this->ensureAppUrlConfigured($chatId)) {
            return;
        }

        $obreiro = $this->findObreiroByTelegramId($requesterTelegramId);
        $isBibliotecario = $this->obreiroHasRole($obreiro, 'bibliotecario', 'veneravel');
        $canClassificar = $this->obreiroHasRole($obreiro, 'primeiro_vigilante', 'segundo_vigilante', 'bibliotecario', 'veneravel');
        $isDev = $this->isDev($requesterTelegramId);

        $mensagem = "<b>Biblioteca da Loja</b>\n\nSelecione uma opção:";
        $botoes = [];


        $botoes[] = [
            ['text' => 'Abrir Biblioteca Web', 'web_app' => ['url' => $this->buildAppUrl('/biblioteca')]],
            ['text' => 'Biblioteca Mobile', 'web_app' => ['url' => $this->buildAppUrl('/miniapp/biblioteca')]],
        ];

        if ($isBibliotecario || $isDev) {
            $botoes[] = [
                ['text' => 'Cadastrar por ISBN', 'web_app' => ['url' => $this->buildAppUrl('/biblioteca/scanner')]],
                ['text' => 'Cadastrar Manual', 'web_app' => ['url' => $this->buildAppUrl('/biblioteca/novo')]],
            ];
            $botoes[] = [
                ['text' => 'Gerenciar Empréstimos', 'web_app' => ['url' => $this->buildAppUrl('/biblioteca/emprestimos')]],
            ];
        }
        if ($canClassificar || $isDev) {
            $botoes[] = [
                ['text' => 'Classificar Leituras', 'web_app' => ['url' => $this->buildAppUrl('/biblioteca')]],
            ];
        }

        $botoes[] = [
            ['text' => 'Voltar', 'callback_data' => 'start_menu'],
        ];

        $this->telegram->sendMessage($chatId, $mensagem, ['parse_mode' => 'HTML', 'reply_markup' => ['inline_keyboard' => $botoes]]);
    }


    private function getRegistrosConsolidadosDoDia(string $dataYmd): array
    {
        $registroModel = new \App\Models\EfemerideRegistro();
        $historiaModel = new \App\Models\HistoriaMaconica();
        
        $timezone = new \DateTimeZone(trim((string) ($_ENV['APP_TIMEZONE'] ?? 'America/Sao_Paulo')));
        $dtHoje = \DateTimeImmutable::createFromFormat('Y-m-d', $dataYmd, $timezone);
        if ($dtHoje === false) {
            $dtHoje = new \DateTimeImmutable('today', $timezone);
        }

        $diaHoje = (int) $dtHoje->format('d');
        $mesHoje = (int) $dtHoje->format('m');
        
        $registros = $registroModel->getRegistrosDoDia();

        try {
            $historiasHoje = $historiaModel->buscarPorDiaMes($diaHoje, $mesHoje, true);
            foreach ($historiasHoje as $hist) {
                $ano = $hist['ano_ref'] ?? $dtHoje->format('Y');
                $registros[] = [
                    'id' => (int) ($hist['id'] ?? 0),
                    'nome' => trim((string) ($hist['titulo'] ?? 'Nossa História')),
                    'tipo' => 'História',
                    'data_evento' => sprintf('%04d-%02d-%02d', $ano, $mesHoje, $diaHoje),
                    'mensagem_custom' => trim((string) ($hist['texto'] ?? '')),
                    'local' => trim((string) ($hist['fonte'] ?? '')),
                    'vinculo' => 'Nossa História',
                ];
            }
        } catch (\Throwable $e) {
            error_log('[bot] Erro ao injetar historias: ' . $e->getMessage());
        }

        try {
            $previaCardModel = new \App\Models\EfemerideCardPrevia();
            $overrides = $previaCardModel->findByDate($dtHoje->format('Y-m-d'));
            $mapOverrides = [];
            foreach ($overrides as $ov) {
                $rid = (int) ($ov['registro_id'] ?? 0);
                if ($rid > 0 && !empty($ov['texto_custom_card'])) {
                    $mapOverrides[$rid] = trim((string) $ov['texto_custom_card']);
                }
            }
            if (!empty($mapOverrides)) {
                foreach ($registros as &$regRef) {
                    $rid = (int) ($regRef['id'] ?? 0);
                    if ($rid > 0 && isset($mapOverrides[$rid])) {
                        $regRef['mensagem_custom'] = $mapOverrides[$rid];
                    }
                }
                unset($regRef);
            }
        } catch (\Throwable $e) {
            error_log('[bot] Falha ao aplicar overrides: ' . $e->getMessage());
        }

        return $registros;
    }

    private function handleNesteDia($chatId, int $requesterTelegramId)
    {
        if (!$this->ensureChancelariaAccess($chatId, $requesterTelegramId)) {
            return;
        }
        $this->handleEfemeridesMenu($chatId, $requesterTelegramId);
    }

    private function handleAprovarEfemeride($chatId, int $requesterTelegramId)
    {
        if (!$this->ensureChancelariaAccess($chatId, $requesterTelegramId)) {
            return;
        }

        $grupoId = $this->getGroupChatId();
        if (!$grupoId) {
            $this->telegram->sendMessage($chatId, "Não foi possível enviar: o grupo oficial ainda não está configurado.");
            return;
        }

        $hoje = date('Y-m-d');
        $registros = $this->getRegistrosConsolidadosDoDia($hoje);
        $previaModel = new \App\Models\EfemeridePreviaDiaria();
        $previa = $previaModel->buscarPorData($hoje);
        $mensagem = trim((string) ($previa['mensagem'] ?? ''));
        if ($mensagem === '') {
            $composer = new \App\Services\EfemeridesComposer();
            $mensagem = trim($composer->composeDailyPreview($registros));
            if ($mensagem !== '') {
                $previaModel->salvarOuAtualizar($hoje, $mensagem, true);
            }
        }
        
        $cards = [];
        if (!empty($registros)) {
            $cards = (new \App\Services\EfemeridesCardService())->buildCardsForDate($hoje, $registros);
        }

        if ($mensagem === '' && empty($cards)) {
            $this->telegram->sendMessage($chatId, "Nenhuma efeméride encontrada para envio.");
            return;
        }

        if ($mensagem !== '' && !$this->telegram->sendMessage($grupoId, $mensagem, ['parse_mode' => 'HTML'])) {
            $this->telegram->sendMessage($chatId, "Não foi possível enviar a mensagem escrita ao grupo oficial.");
            return;
        }

        $erros = 0;
        foreach ($cards as $c) {
            $absPath = $c['card_path'] ?? '';
            if ($absPath !== '' && file_exists($absPath)) {
                if (!$this->telegram->sendPhoto($grupoId, $absPath, '')) {
                    $erros++;
                }
            } else {
                $erros++;
            }
        }

        if ($erros === 0) {
            $this->telegram->sendMessage($chatId, "Mensagem e cards enviados para o grupo oficial com sucesso.");
        } else {
            $this->telegram->sendMessage($chatId, "Mensagem enviada, porém houve falha no envio de $erros cards.");
        }
    }

    private function handleEfemeridesMenu($chatId, int $requesterTelegramId): void
    {
        $hoje = date('Y-m-d');
        $sel = new \App\Models\EfemeridesSelecaoEnvio();
        $selected = $sel->getSelectedIds($hoje, $requesterTelegramId);

        $msg = "<b>Efemérides do Dia</b>\n\n";
        $msg .= "Seleção atual: <b>" . count($selected) . "</b> item(ns).";

        $teclado = [
            'inline_keyboard' => [
                [
                    ['text' => '📄 Prévia (texto)', 'callback_data' => 'ef_prev_text'],
                ],
                [
                    ['text' => '🖼️ Prévia (texto + cards)', 'callback_data' => 'ef_prev_cards'],
                ],
                [
                    ['text' => '✅ Selecionar itens', 'callback_data' => 'ef_sel_menu'],
                ],
                [
                    ['text' => '📤 Enviar selecionados', 'callback_data' => 'ef_send_menu'],
                ],
                [
                    ['text' => '🧹 Limpar seleção', 'callback_data' => 'ef_sel_clear'],
                    ['text' => 'Voltar', 'callback_data' => 'admin_chancelaria'],
                ],
            ],
        ];

        $this->telegram->sendMessage($chatId, $msg, ['parse_mode' => 'HTML', 'reply_markup' => $teclado]);
    }

    /**
     * @return array<int, array<string,mixed>>
     */
    private function getEfemeridesSelecionadasOuTodas(string $dataRef, int $telegramId): array
    {
        $registros = $this->getRegistrosConsolidadosDoDia($dataRef);
        $sel = new \App\Models\EfemeridesSelecaoEnvio();
        $selected = $sel->getSelectedIds($dataRef, $telegramId);
        if (empty($selected)) {
            return $registros;
        }

        $map = [];
        foreach ($registros as $r) {
            $rid = (int) ($r['id'] ?? 0);
            if ($rid > 0) {
                $map[$rid] = $r;
            }
        }

        $out = [];
        foreach ($selected as $rid) {
            if (isset($map[$rid])) {
                $out[] = $map[$rid];
            }
        }

        return $out;
    }

    private function handleEfemeridesPreviewTexto($chatId, int $requesterTelegramId): void
    {
        $hoje = date('Y-m-d');
        $registros = $this->getEfemeridesSelecionadasOuTodas($hoje, $requesterTelegramId);
        $composer = new \App\Services\EfemeridesComposer();
        $mensagem = trim($composer->composeDailyPreview($registros));
        if ($mensagem === '') {
            $this->telegram->sendMessage($chatId, "Nenhuma efeméride encontrada para hoje.");
            return;
        }

        $teclado = ['inline_keyboard' => [[['text' => 'Voltar', 'callback_data' => 'chancelaria_neste_dia']]]];
        $this->telegram->sendMessage($chatId, $mensagem, ['parse_mode' => 'HTML', 'reply_markup' => $teclado]);
    }

    private function handleEfemeridesPreviewTextoECards($chatId, int $requesterTelegramId): void
    {
        $hoje = date('Y-m-d');
        $registros = $this->getEfemeridesSelecionadasOuTodas($hoje, $requesterTelegramId);
        $composer = new \App\Services\EfemeridesComposer();
        $mensagem = trim($composer->composeDailyPreview($registros));
        $cards = [];
        if (!empty($registros)) {
            $cards = (new \App\Services\EfemeridesCardService())->buildCardsForDate($hoje, $registros);
        }

        if ($mensagem === '' && empty($cards)) {
            $this->telegram->sendMessage($chatId, "Nenhuma efeméride encontrada para hoje.");
            return;
        }

        $teclado = ['inline_keyboard' => [[['text' => 'Voltar', 'callback_data' => 'chancelaria_neste_dia']]]];
        if ($mensagem !== '') {
            $this->telegram->sendMessage($chatId, $mensagem, ['parse_mode' => 'HTML', 'reply_markup' => $teclado]);
        }

        $erros = 0;
        foreach ($cards as $c) {
            $absPath = $c['card_path'] ?? '';
            if ($absPath !== '' && file_exists($absPath)) {
                if (!$this->telegram->sendPhoto($chatId, $absPath, '')) {
                    $erros++;
                }
            } else {
                $erros++;
            }
        }

        $this->telegram->sendMessage(
            $chatId,
            $erros === 0
                ? "Prévia gerada com " . count($cards) . " cards. Use o menu para enviar apenas o que desejar."
                : "Prévia gerada, porém houve falha em $erros cards. Use o menu para enviar apenas o que desejar."
        );
    }

    private function handleEfemeridesSelecaoMenu($chatId, int $requesterTelegramId): void
    {
        $hoje = date('Y-m-d');
        $registros = $this->getRegistrosConsolidadosDoDia($hoje);
        if (empty($registros)) {
            $this->telegram->sendMessage($chatId, "Nenhuma efeméride encontrada para hoje.");
            return;
        }

        $sel = new \App\Models\EfemeridesSelecaoEnvio();
        $selected = $sel->getSelectedIds($hoje, $requesterTelegramId);
        $selectedMap = array_fill_keys($selected, true);

        $botoes = [];
        $max = min(20, count($registros));
        for ($i = 0; $i < $max; $i++) {
            $r = $registros[$i];
            $rid = (int) ($r['id'] ?? 0);
            if ($rid <= 0) {
                continue;
            }
            $mark = isset($selectedMap[$rid]) ? '✅' : '☑️';
            $tipo = trim((string) ($r['tipo'] ?? ''));
            $nome = trim((string) ($r['nome'] ?? ''));
            $label = trim($tipo . ': ' . $nome);
            if (mb_strlen($label, 'UTF-8') > 34) {
                $label = mb_substr($label, 0, 33, 'UTF-8') . '…';
            }
            $botoes[] = [[
                'text' => $mark . ' ' . $label,
                'callback_data' => 'ef_t_' . $rid,
            ]];
        }

        $botoes[] = [
            ['text' => 'Voltar', 'callback_data' => 'chancelaria_neste_dia'],
            ['text' => 'Enviar', 'callback_data' => 'ef_send_menu'],
        ];

        $msg = "<b>Selecionar itens</b>\n\n";
        $msg .= "Marcados: <b>" . count($selected) . "</b>\n";
        if (count($registros) > $max) {
            $msg .= "<i>Mostrando os primeiros {$max} itens.</i>\n";
        }

        $this->telegram->sendMessage($chatId, $msg, ['parse_mode' => 'HTML', 'reply_markup' => ['inline_keyboard' => $botoes]]);
    }

    private function handleEfemeridesToggleSelecao($chatId, int $requesterTelegramId, int $registroId): void
    {
        $hoje = date('Y-m-d');
        (new \App\Models\EfemeridesSelecaoEnvio())->toggle($hoje, $requesterTelegramId, $registroId);
        $this->handleEfemeridesSelecaoMenu($chatId, $requesterTelegramId);
    }

    private function handleEfemeridesClearSelecao($chatId, int $requesterTelegramId): void
    {
        $hoje = date('Y-m-d');
        (new \App\Models\EfemeridesSelecaoEnvio())->clear($hoje, $requesterTelegramId);
        $this->telegram->sendMessage($chatId, "Seleção limpa.");
        $this->handleEfemeridesMenu($chatId, $requesterTelegramId);
    }

    private function handleEfemeridesSendMenu($chatId, int $requesterTelegramId): void
    {
        $hoje = date('Y-m-d');
        $sel = new \App\Models\EfemeridesSelecaoEnvio();
        $selected = $sel->getSelectedIds($hoje, $requesterTelegramId);
        if (empty($selected)) {
            $this->telegram->sendMessage($chatId, "Nenhum item selecionado. Use 'Selecionar itens' para marcar o que vai para o grupo.");
            return;
        }

        $msg = "<b>Enviar selecionados</b>\n\n";
        $msg .= "Itens marcados: <b>" . count($selected) . "</b>\n";
        $msg .= "Escolha o que enviar ao grupo:";

        $kb = [
            'inline_keyboard' => [
                [
                    ['text' => 'Enviar texto', 'callback_data' => 'ef_send_text'],
                ],
                [
                    ['text' => 'Enviar cards', 'callback_data' => 'ef_send_cards'],
                ],
                [
                    ['text' => 'Enviar texto + cards', 'callback_data' => 'ef_send_both'],
                ],
                [
                    ['text' => 'Voltar', 'callback_data' => 'chancelaria_neste_dia'],
                ],
            ],
        ];

        $this->telegram->sendMessage($chatId, $msg, ['parse_mode' => 'HTML', 'reply_markup' => $kb]);
    }

    private function handleEfemeridesSendSelecionados($chatId, int $requesterTelegramId, string $modo): void
    {
        $grupoId = $this->getGroupChatId();
        if (!$grupoId) {
            $this->telegram->sendMessage($chatId, "Não foi possível enviar: o grupo oficial ainda não está configurado.");
            return;
        }

        $hoje = date('Y-m-d');
        $sel = new \App\Models\EfemeridesSelecaoEnvio();
        $selected = $sel->getSelectedIds($hoje, $requesterTelegramId);
        if (empty($selected)) {
            $this->telegram->sendMessage($chatId, "Nenhum item selecionado.");
            return;
        }

        $registrosAll = $this->getRegistrosConsolidadosDoDia($hoje);
        $map = [];
        foreach ($registrosAll as $r) {
            $rid = (int) ($r['id'] ?? 0);
            if ($rid > 0) {
                $map[$rid] = $r;
            }
        }
        $registros = [];
        foreach ($selected as $rid) {
            if (isset($map[$rid])) {
                $registros[] = $map[$rid];
            }
        }

        if (empty($registros)) {
            $this->telegram->sendMessage($chatId, "Nenhum item selecionado válido para envio.");
            return;
        }

        $hashBase = implode(',', $selected);
        $actionHash = sha1($hoje . '|' . $requesterTelegramId . '|' . $modo . '|' . $hashBase);
        $log = new \App\Models\EfemeridesEnvioLog();
        if (!$log->tryRegister($hoje, $requesterTelegramId, $modo, $actionHash)) {
            $this->telegram->sendMessage($chatId, "Envio já registrado. Se precisar reenviar, limpe e refaça a seleção.");
            return;
        }

        $composer = new \App\Services\EfemeridesComposer();
        $mensagem = trim($composer->composeDailyPreview($registros));
        $cards = [];
        if ($modo !== 'text' && !empty($registros)) {
            $cards = (new \App\Services\EfemeridesCardService())->buildCardsForDate($hoje, $registros);
        }

        if (($modo === 'text' || $modo === 'both') && $mensagem !== '') {
            if (!$this->telegram->sendMessage($grupoId, $mensagem, ['parse_mode' => 'HTML'])) {
                $this->telegram->sendMessage($chatId, "Falha ao enviar texto ao grupo.");
                return;
            }
        }

        if ($modo === 'cards' || $modo === 'both') {
            $erros = 0;
            foreach ($cards as $c) {
                $absPath = $c['card_path'] ?? '';
                if ($absPath !== '' && file_exists($absPath)) {
                    if (!$this->telegram->sendPhoto($grupoId, $absPath, '')) {
                        $erros++;
                    }
                } else {
                    $erros++;
                }
            }

            if ($erros > 0) {
                $this->telegram->sendMessage($chatId, "Envio concluído, mas houve falha em $erros card(s).");
                return;
            }
        }

        $this->telegram->sendMessage($chatId, "Envio concluído para o grupo (modo: {$modo}).");
    }

    public function handle($update)
    {
        try {
            $this->logBotEvent('receive', $update);

            $chatType = (string) ($update['message']['chat']['type'] ?? $update['callback_query']['message']['chat']['type'] ?? 'unknown');
            $chatId = $update['message']['chat']['id'] ?? $update['callback_query']['message']['chat']['id'] ?? null;
            if ($chatId !== null && !$this->isPrivateChatType($chatType)) {
                $this->notifyPrivateOnly($chatId);
                if (isset($update['callback_query']['id'])) {
                    $this->telegram->answerCallbackQuery($update['callback_query']['id']);
                }
                $this->logBotEvent('group_blocked', $update);
                return;
            }

            if (isset($update['message'])) {
                $message = $update['message'];
                $chatId = $message['chat']['id'];
                $text = $message['text'] ?? '';
                $caption = $message['caption'] ?? '';
                $fromId = (int) ($message['from']['id'] ?? 0);

                if (strpos($text, '/solicitar') === 0) {
                    $this->handleSolicitarAcessoTelegram($chatId, $fromId, (string) $text);
                    return;
                }

                if (strpos($text, '/start') === 0) {
                    $parts = preg_split('/\s+/', trim((string) $text)) ?: [];
                    $payload = (string) ($parts[1] ?? '');
                    if ($payload !== '' && str_starts_with($payload, 'ativar_')) {
                        $token = trim(substr($payload, strlen('ativar_')));
                        if ($token === '') {
                            $this->telegram->sendMessage($chatId, 'Token de ativação inválido. Procure o secretário.');
                            return;
                        }

                        $resultado = (new ConviteAcesso())->consumir($token, $fromId);
                        if (!($resultado['ok'] ?? false)) {
                            $this->telegram->sendMessage($chatId, (string) ($resultado['erro'] ?? 'Não foi possível ativar seu acesso. Procure o secretário.'));
                            return;
                        }

                        $this->telegram->sendMessage($chatId, 'Acesso ativado com sucesso. Envie /painel para acessar.' . $this->privateMenuHint());
                        $this->sendMenuPrincipal($chatId, $fromId);
                        return;
                    }
                }

                if (!$this->isDev($fromId)) {
                    $access = $this->resolvePrivateAccess($fromId);
                    $state = (string) ($access['state'] ?? 'inexistente');
                    if ($state !== 'ativo') {
                        $this->sendAccessStateMessage($chatId, $state);
                        return;
                    }
                }

                if (strpos($text, '/start') === 0) {
                    $this->sendMenuPrincipal($chatId, $fromId);
                } elseif (strpos($text, '/painel') === 0) {
                    $this->sendMenuPrincipal($chatId, $fromId);
                } elseif (strpos($text, '/ajuda') === 0 || strpos($text, '/help') === 0) {
                    $this->handleHelp($chatId);
                } elseif (strpos($text, '/chancelaria') === 0) {
                    $this->handleChancelaria($chatId, $fromId);
                } elseif (strpos($text, '/tesouraria') === 0) {
                    $this->handleTesouraria($chatId, $fromId);
                } elseif (strpos($text, '/biblioteca') === 0) {
                    $this->handleBiblioteca($chatId, $fromId);
                } elseif (strpos($text, '/assistencia') === 0) {
                    $this->handleAssistenciaMenu($chatId, $fromId);
                } elseif (strpos($text, '/pix') === 0 || strpos($text, '/financeiro') === 0) {
                    $this->handleOrientacaoFinanceira($chatId, (int) $fromId);
                } elseif (isset($message['photo']) || isset($message['document'])) {
                    $this->handleComprovantePixRecebido($chatId, (int) $fromId, $message);
                } elseif (trim((string) $caption) !== '') {
                    $this->telegram->sendMessage($chatId, 'Se você for enviar um comprovante PIX, anexe a imagem ou PDF junto com a legenda informando o que está sendo pago. Ex.: "mensalidade 05/2026 150,00".');
                } else {
                    $this->telegram->sendMessage($chatId, "Não reconheci este comando. Use /ajuda para ver as opções disponíveis.");
                }
            } elseif (isset($update['callback_query'])) {
                $callback = $update['callback_query'];
                $chatId = $callback['message']['chat']['id'];
                $data = $callback['data'];
                $fromId = (int) ($callback['from']['id'] ?? 0);
                $callbackId = (string) ($callback['id'] ?? '');
                if ($callbackId !== '') {
                    $this->telegram->answerCallbackQuery($callbackId);
                }

                if (!$this->isDev($fromId)) {
                    $access = $this->resolvePrivateAccess($fromId);
                    $state = (string) ($access['state'] ?? 'inexistente');
                    if ($state !== 'ativo') {
                        $this->sendAccessStateMessage($chatId, $state);
                        return;
                    }
                }

                switch ($data) {
                    case 'admin_chancelaria':
                        $this->handleChancelaria($chatId, $fromId);
                        break;
                    case 'chancelaria_neste_dia':
                        $this->handleEfemeridesMenu($chatId, (int) $fromId);
                        break;
                    case 'chancelaria_aprovar_efemeride':
                        $this->handleAprovarEfemeride($chatId, (int) $fromId);
                        break;
                    case 'ef_prev_text':
                        $this->handleEfemeridesPreviewTexto($chatId, (int) $fromId);
                        break;
                    case 'ef_prev_cards':
                        $this->handleEfemeridesPreviewTextoECards($chatId, (int) $fromId);
                        break;
                    case 'ef_sel_menu':
                        $this->handleEfemeridesSelecaoMenu($chatId, (int) $fromId);
                        break;
                    case 'ef_send_menu':
                        $this->handleEfemeridesSendMenu($chatId, (int) $fromId);
                        break;
                    case 'ef_sel_clear':
                        $this->handleEfemeridesClearSelecao($chatId, (int) $fromId);
                        break;
                    case 'ef_send_text':
                        $this->handleEfemeridesSendSelecionados($chatId, (int) $fromId, 'text');
                        break;
                    case 'ef_send_cards':
                        $this->handleEfemeridesSendSelecionados($chatId, (int) $fromId, 'cards');
                        break;
                    case 'ef_send_both':
                        $this->handleEfemeridesSendSelecionados($chatId, (int) $fromId, 'both');
                        break;


                    case 'admin_tesouraria':
                    case 'tesouraria_menu':
                        $this->handleTesouraria($chatId, $fromId);
                        break;
                    case 'tesouraria_caixa':
                        $this->handleTesourariaCaixa($chatId);
                        break;
                    case 'tesouraria_comprovantes':
                        $this->handleTesourariaComprovantes($chatId);
                        break;
                    case 'tesouraria_regularidade':
                        $this->handleTesourariaRegularidade($chatId);
                        break;
                    case 'tesouraria_fechamento':
                        $this->handleTesourariaFechamento($chatId);
                        break;
                    case 'tesouraria_validar_pix':
                        $this->handleTesourariaValidarPix($chatId);
                        break;
                    case 'tesouraria_orientacao_pix':
                        $this->handleOrientacaoFinanceira($chatId, (int) $fromId);
                        break;

                    case 'admin_biblioteca':
                    case 'biblioteca_menu':
                        $this->handleBiblioteca($chatId, $fromId);
                        break;

                    case 'biblioteca_cadastrar':
                        $this->handleBibliotecaCadastrar($chatId, $fromId);
                        break;
                    case 'biblioteca_gerenciar':
                        $this->handleBibliotecaGerenciar($chatId, $fromId);
                        break;

                    case 'admin_secretaria':
                    case 'secretaria_menu':
                        $this->handleSecretariaMenu($chatId, $fromId);
                        break;
                    case 'admin_hospitaleiro':
                        $this->handleAssistenciaMenu($chatId, $fromId);
                        break;
                    case 'admin_primeiro_vigilante':
                        $this->handlePrimeiroVigilanteMenu($chatId, $fromId);
                        break;
                    case 'admin_segundo_vigilante':
                        $this->handleSegundoVigilanteMenu($chatId, $fromId);
                        break;
                    case 'admin_orador':
                        $this->handleOradorMenu($chatId, $fromId);
                        break;
                    case 'admin_mestre_banquetes':
                        $this->handleMestreBanquetesMenu($chatId, $fromId);
                        break;
                    case 'admin_mestre_harmonia':
                        $this->handleMestreHarmoniaMenu($chatId, $fromId);
                        break;
                    case 'admin_veneravel':
                        $this->handleVeneravelMenu($chatId, $fromId);
                        break;
                    case 'admin_assistente':
                        $this->handleAssistenteBotMenu($chatId, $fromId);
                        break;
                    case 'assistencia_menu':
                        $this->handleAssistenciaMenu($chatId, $fromId);
                        break;
                    case 'sec_agendas':
                        $this->handleSecAgendas($chatId);
                        break;

                    case 'presenca_confirmar':
                        $this->handleConfirmacaoProximaSessao($chatId, (int) $fromId, 'confirmar');
                        break;
                    case 'presenca_agape_gratuito':
                    case 'presenca_agape_pago':
                        $this->handleConfirmacaoProximaSessao($chatId, (int) $fromId, 'com_agape');
                        break;
                    case 'presenca_sem_agape':
                        $this->handleConfirmacaoProximaSessao($chatId, (int) $fromId, 'sem_agape');
                        break;
                    case 'presenca_cancelar':
                        $this->handleConfirmacaoProximaSessao($chatId, (int) $fromId, 'cancelar');
                        break;
                    case 'presenca_ausencia':
                        $this->handleConfirmacaoProximaSessao($chatId, (int) $fromId, 'ausencia');
                        break;
                    case 'presenca_ver_confirmados':
                        $this->handleConfirmacaoProximaSessao($chatId, (int) $fromId, 'ver_confirmados');
                        break;
                    case 'sessao_info':
                        $this->handleSessaoInfo($chatId, (int) $fromId);
                        break;

                    case 'start_menu':
                        $this->sendMenuPrincipal($chatId, $fromId);
                        break;
                    case 'menu_meu_cadastro':
                        $this->telegram->sendMessage($chatId, 'Meu cadastro: procure a Secretaria para ajustes cadastrais e validação de dados.');
                        break;
                    case 'menu_minhas_info':
                        $this->telegram->sendMessage($chatId, 'Minhas informações: use o painel web para consultar dados e situação atual.');
                        break;
                    case 'menu_ajuda_contato':
                        $this->telegram->sendMessage($chatId, 'Ajuda / contato: em caso de dúvidas, fale com a Secretaria da Loja.');
                        break;
                    case 'menu_admin_total':
                        $this->telegram->sendMessage($chatId, 'Recurso indisponivel neste perfil.');
                        break;

                    default:
                        if (is_string($data) && preg_match('/^ef_t_(\d+)$/', $data, $m)) {
                            $rid = (int) ($m[1] ?? 0);
                            if ($rid > 0) {
                                $this->handleEfemeridesToggleSelecao($chatId, (int) $fromId, $rid);
                                break;
                            }
                        }
                        $this->telegram->sendMessage($chatId, "Não reconheci esta ação. Volte ao menu principal e tente novamente.");
                        break;
                }

            } else {
                error_log('[handle] Update não suportado: ' . json_encode($update));
            }

            error_log('[webhook] update processado com sucesso');
        } catch (\Throwable $e) {
            error_log('[webhook] erro ao processar update: ' . $e->getMessage());
        }
    }

    private function handleTesourariaCaixa($chatId)
    {
        $msg = "<b>Caixa da Loja</b>\n\nAcesse o painel para registrar entradas e saídas, revisar movimentos e excluir lançamentos quando necessário.";
        $this->telegram->sendMessage($chatId, $msg, [
            'parse_mode' => 'HTML',
            'reply_markup' => [
                'inline_keyboard' => [
                    [
                        ['text' => 'Abrir Caixa da Loja', 'web_app' => ['url' => $this->buildAppUrl('/miniapp/tesouraria?dest=%2Ftesouraria%2Fcaixa')]],
                    ],
                    [
                        ['text' => 'Voltar', 'callback_data' => 'tesouraria_menu'],
                    ],
                ],
            ],
        ]);
    }

    private function handleTesourariaComprovantes($chatId)
    {
        $msg = "<b>Comprovantes PIX</b>\n\nAcesse o painel para aprovar, rejeitar e revisar comprovantes enviados.";
        $this->telegram->sendMessage($chatId, $msg, [
            'parse_mode' => 'HTML',
            'reply_markup' => [
                'inline_keyboard' => [
                    [
                        ['text' => 'Abrir Comprovantes', 'web_app' => ['url' => $this->buildAppUrl('/miniapp/tesouraria?dest=%2Ftesouraria%2Fcomprovantes')]],
                    ],
                    [
                        ['text' => 'Voltar', 'callback_data' => 'tesouraria_menu'],
                    ],
                ],
            ],
        ]);
    }

    private function handleTesourariaRegularidade($chatId)
    {
        $msg = "<b>Regularidade</b>\n\nAcesse o painel para atualizar a regularidade de forma individual ou em lote.";
        $this->telegram->sendMessage($chatId, $msg, [
            'parse_mode' => 'HTML',
            'reply_markup' => [
                'inline_keyboard' => [
                    [
                        ['text' => 'Abrir Regularidade', 'web_app' => ['url' => $this->buildAppUrl('/miniapp/tesouraria?dest=%2Ftesouraria%2Fregularidade')]],
                    ],
                    [
                        ['text' => 'Voltar', 'callback_data' => 'tesouraria_menu'],
                    ],
                ],
            ],
        ]);
    }

    private function handleTesourariaFechamento($chatId)
    {
        $msg = "<b>Fechamento Mensal</b>\n\nAcesse o painel para revisar lançamentos, ajustar o saldo inicial e concluir o fechamento do período.";
        $this->telegram->sendMessage($chatId, $msg, [
            'parse_mode' => 'HTML',
            'reply_markup' => [
                'inline_keyboard' => [
                    [
                        ['text' => 'Abrir Fechamento', 'web_app' => ['url' => $this->buildAppUrl('/miniapp/tesouraria?dest=%2Ftesouraria%2Ffechamento')]],
                    ],
                    [
                        ['text' => 'Voltar', 'callback_data' => 'tesouraria_menu'],
                    ],
                ],
            ],
        ]);
    }

    private function handleTesourariaValidarPix($chatId)
    {
        $msg = "<b>Validação de PIX</b>\n\nAcesse o painel de comprovantes para validar ou rejeitar os envios pendentes.";
        $this->telegram->sendMessage($chatId, $msg, [
            'parse_mode' => 'HTML',
            'reply_markup' => [
                'inline_keyboard' => [
                    [
                        ['text' => 'Abrir Validação PIX', 'web_app' => ['url' => $this->buildAppUrl('/miniapp/tesouraria?dest=%2Ftesouraria%2Fcomprovantes')]],
                    ],
                    [
                        ['text' => 'Voltar', 'callback_data' => 'tesouraria_menu'],
                    ],
                ],
            ],
        ]);
    }

    private function handleOrientacaoFinanceira($chatId, int $fromId): void
    {
        $obreiro = $this->findObreiroByTelegramId((int) $fromId);
        if (!$obreiro) {
            $this->telegram->sendMessage($chatId, 'Não conseguimos localizar seu cadastro para consulta financeira agora. Tente novamente em alguns minutos.');
            return;
        }

        $config = (new ConfiguracaoLoja())->obter();
        $pixTipo = trim((string) ($config['pix_chave_tipo'] ?? 'CNPJ'));
        $pixValor = trim((string) ($config['pix_chave_valor'] ?? ''));
        $pixBeneficiario = trim((string) ($config['pix_beneficiario'] ?? ''));
        $mensalidade = number_format((float) ($config['mensalidade_valor_padrao'] ?? 150), 2, ',', '.');
        $biblioteca = number_format((float) ($config['contribuicao_biblioteca_valor_padrao'] ?? 44), 2, ',', '.');

        $msg = "<b>Orientações financeiras</b>\n\n";
        $msg .= "Contribuição mensal padrão: <b>R$ {$mensalidade}</b>\n";
        $msg .= "Biblioteca por contribuinte designado: <b>R$ {$biblioteca}</b>\n\n";
        if ($pixValor !== '') {
            $msg .= "PIX da Loja: <b>{$pixTipo} {$pixValor}</b>";
            if ($pixBeneficiario !== '') {
                $msg .= "\nBeneficiário: <b>{$pixBeneficiario}</b>";
            }
            $msg .= "\n\nAo enviar comprovante, use legenda com o que está pagando.\n";
            $msg .= "Ex.: <code>mensalidade 05/2026 150,00</code>";
        }

        $teclado = [
            'inline_keyboard' => [
                [
                    ['text' => 'Ver minhas obrigações', 'web_app' => ['url' => $this->buildAppUrl('/financeiro/minhas-obrigacoes')]],
                ],
            ],
        ];

        $this->telegram->sendMessage($chatId, $msg, ['parse_mode' => 'HTML', 'reply_markup' => $teclado]);
    }

    private function handleComprovantePixRecebido($chatId, int $fromId, array $message): void
    {
        $obreiro = $this->findObreiroByTelegramId((int) $fromId);
        if (!$obreiro) {
            $this->telegram->sendMessage($chatId, 'Não foi possível localizar seu cadastro agora. Tente novamente ou contate a Secretaria.');
            return;
        }

        $caption = trim((string) ($message['caption'] ?? ''));
        $photo = $message['photo'] ?? null;
        $document = $message['document'] ?? null;
        $fileId = '';
        $tipoArquivo = 'desconhecido';
        $nomeArquivo = null;

        if (is_array($photo) && $photo !== []) {
            $ultimaFoto = end($photo);
            $fileId = (string) ($ultimaFoto['file_id'] ?? '');
            $tipoArquivo = 'foto';
        } elseif (is_array($document)) {
            $fileId = (string) ($document['file_id'] ?? '');
            $tipoArquivo = 'documento';
            $nomeArquivo = (string) ($document['file_name'] ?? '');
        }

        if ($fileId === '') {
            $this->telegram->sendMessage($chatId, 'Não consegui identificar o arquivo do comprovante. Reenvie a imagem ou PDF com a legenda do pagamento.');
            return;
        }

        $dadosExtraidos = $this->extrairDadosLegendaComprovante($caption);
        $ok = (new ComprovantePix())->registrar([
            'obreiro_id' => $obreiro['id'] ?? null,
            'telegram_id' => $fromId,
            'nome_telegram' => trim((string) (($message['from']['first_name'] ?? '') . ' ' . ($message['from']['last_name'] ?? ''))),
            'telegram_file_id' => $fileId,
            'tipo_arquivo' => $tipoArquivo,
            'nome_arquivo' => $nomeArquivo,
            'descricao_usuario' => $caption !== '' ? $caption : 'Comprovante PIX enviado sem legenda',
            'rotulo_pagamento' => $dadosExtraidos['rotulo_pagamento'] ?? ($caption !== '' ? $caption : 'Comprovante PIX'),
            'valor_informado' => $dadosExtraidos['valor_informado'] ?? null,
            'mes_ref_informado' => $dadosExtraidos['mes_ref_informado'] ?? null,
            'ano_ref_informado' => $dadosExtraidos['ano_ref_informado'] ?? null,
            'data_envio' => date('Y-m-d H:i:s'),
        ]);

        if (!$ok) {
            $this->telegram->sendMessage($chatId, 'Não conseguimos registrar seu comprovante agora. Tente novamente em alguns minutos.');
            return;
        }

        $config = (new ConfiguracaoLoja())->obter();
        $pixTipo = trim((string) ($config['pix_chave_tipo'] ?? 'CNPJ'));
        $pixValor = trim((string) ($config['pix_chave_valor'] ?? ''));
        $parcelas = (new ObrigacaoFinanceira())->listarParcelasEmAbertoObreiro((string) ($obreiro['id'] ?? ''));

        $msg = "Comprovante recebido e encaminhado para validação da Tesouraria.";
        if (($dadosExtraidos['rotulo_pagamento'] ?? '') !== '') {
            $msg .= "\n\nRótulo identificado: <b>" . htmlspecialchars((string) $dadosExtraidos['rotulo_pagamento']) . "</b>";
        }
        if ($pixValor !== '') {
            $msg .= "\nPIX da Loja: <b>{$pixTipo} {$pixValor}</b>";
        }

        $sugestao = $this->montarSugestaoParcelas($parcelas);
        if ($sugestao !== '') {
            $msg .= "\n\nSugestões em aberto:\n" . $sugestao;
        }

        $msg .= "\n\nPara facilitar a baixa, envie sempre o comprovante com legenda do pagamento.";
        $this->telegram->sendMessage($chatId, $msg, ['parse_mode' => 'HTML']);
    }

    private function extrairDadosLegendaComprovante(string $caption): array
    {
        $caption = trim($caption);
        if ($caption === '') {
            return [];
        }

        $resultado = ['rotulo_pagamento' => $caption];

        if (preg_match('/(\d{1,2})[\/\-](\d{4})/', $caption, $match)) {
            $resultado['mes_ref_informado'] = (int) $match[1];
            $resultado['ano_ref_informado'] = (int) $match[2];
        }

        if (preg_match('/(\d+[.,]\d{2})/', $caption, $matchValor)) {
            $resultado['valor_informado'] = (float) str_replace(',', '.', $matchValor[1]);
        }

        return $resultado;
    }

    private function montarSugestaoParcelas(array $parcelas): string
    {
        if ($parcelas === []) {
            return '';
        }

        $linhas = [];
        foreach (array_slice($parcelas, 0, 3) as $parcela) {
            $valor = number_format((float) ($parcela['valor_previsto'] ?? 0), 2, ',', '.');
            $linhas[] = '• ' . (string) ($parcela['titulo'] ?? 'Obrigação') . ' - ' . (string) ($parcela['competencia_label'] ?? '-') . ' - R$ ' . $valor;
        }

        return implode("\n", $linhas);
    }

    public function handleSecretariaMenu($chatId, $fromId)
    {
        if (!$this->ensureAppUrlConfigured($chatId)) {
            return;
        }

        $obreiro = $this->findObreiroByTelegramId((int) $fromId);
        if (!$this->isDev($fromId) && (!$obreiro || !$this->obreiroHasRole($obreiro, 'secretario', 'veneravel'))) {
            $this->telegram->sendMessage($chatId, 'Acesso restrito a Secretaria da Loja.');
            return;
        }

        $mensagem = "*Painel da Secretaria*\n\nSelecione uma opção para continuar:";
        $botoes = [
            [
                ['text' => 'Secretaria Mobile', 'web_app' => ['url' => $this->buildAppUrl('/miniapp/secretaria')]],
                ['text' => 'Sessão em Loja', 'web_app' => ['url' => $this->buildAppUrl('/miniapp/secretaria?foco=balaustre')]],
            ],
            [
                ['text' => 'Agendas e Sessões', 'web_app' => ['url' => $this->buildAppUrl('/miniapp/secretaria')]],
                ['text' => 'Publicações', 'web_app' => ['url' => $this->buildAppUrl('/miniapp/secretaria')]],
            ],
        ];

        if ($this->isDev($fromId) || $this->obreiroHasRole($obreiro, 'veneravel')) {
            $botoes[] = [
                ['text' => 'Painel do Venerável Mestre', 'web_app' => ['url' => $this->buildAppUrl('/miniapp/veneravel')]],
                ['text' => 'Venerável Mobile', 'web_app' => ['url' => $this->buildAppUrl('/miniapp/veneravel')]],
            ];
        }

        $botoes[] = [
            ['text' => 'Voltar', 'callback_data' => 'start_menu'],
        ];

        $teclado = [
            'inline_keyboard' => $botoes,
        ];

        $this->telegram->sendMessage($chatId, $mensagem, ['parse_mode' => 'Markdown', 'reply_markup' => $teclado]);
    }

    public function handleAssistenciaMenu($chatId, $fromId): void
    {
        if (!$this->ensureAppUrlConfigured($chatId)) {
            return;
        }

        $obreiro = $this->findObreiroByTelegramId((int) $fromId);
        if (!$this->isDev($fromId) && (!$obreiro || !$this->obreiroHasRole($obreiro, 'hospitaleiro', 'secretario', 'tesoureiro', 'veneravel'))) {
            $this->telegram->sendMessage($chatId, 'Acesso restrito ao Mestre Hospitaleiro, Secretaria, Tesouraria, Venerável Mestre ou Administrador.');
            return;
        }

        $mensagem = "*Painel de Assistência*\n\nRegistre e acompanhe ocorrências assistenciais com encaminhamento ao Venerável e à Tesouraria.";
        $teclado = [
            'inline_keyboard' => [
                [
                    ['text' => 'Assistência em Loja', 'web_app' => ['url' => $this->buildAppUrl('/miniapp/hospitaleiro')]],
                    ['text' => 'Hospitaleiro Mobile', 'web_app' => ['url' => $this->buildAppUrl('/miniapp/hospitaleiro')]],
                ],
                [
                    ['text' => 'Voltar', 'callback_data' => 'start_menu'],
                ],
            ],
        ];

        $this->telegram->sendMessage($chatId, $mensagem, ['parse_mode' => 'Markdown', 'reply_markup' => $teclado]);
    }

    public function handleSecAgendas($chatId)
    {
        if (!$this->ensureAppUrlConfigured($chatId)) {
            return;
        }

        $this->telegram->sendMessage(
            $chatId,
            "Acesse diretamente o fluxo operacional da Secretaria:",
            [
                'reply_markup' => [
                    'inline_keyboard' => [
                        [
                            ['text' => 'Secretaria Mobile', 'web_app' => ['url' => $this->buildAppUrl('/miniapp/secretaria')]],
                            ['text' => 'Sessão em Loja', 'web_app' => ['url' => $this->buildAppUrl('/miniapp/secretaria?foco=balaustre')]],
                        ],
                        [
                            ['text' => 'Voltar', 'callback_data' => 'secretaria_menu'],
                        ],
                    ],
                ],
            ]
        );
    }
}

