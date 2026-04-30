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
            error_log('[bot] APP_URL ausente no .env; links web_app foram bloqueados atÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â© a configuraÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â£o.');
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
        return "\n\nSe algum botÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â£o nÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â£o abrir, envie /painel novamente.";
    }

    private function ensureAppUrlConfigured(int|string $chatId): bool
    {
        if ($this->getAppBaseUrl() !== '') {
            return true;
        }

        $this->telegram->sendMessage(
            $chatId,
            'Mini app indisponÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â­vel no momento. APP_URL nÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â£o configurada. Reenvie /painel apÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â³s atualizar o ambiente local.'
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
            $this->telegram->sendMessage($chatId, 'Seu acesso estÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡ pendente. Aguarde aprovaÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â£o do secretÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡rio.');
            return;
        }

        if ($state === 'inativo') {
            $this->telegram->sendMessage($chatId, 'Seu acesso estÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡ inativo. Procure o secretÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡rio.');
            return;
        }

        $this->telegram->sendMessage(
            $chatId,
            'Registro nÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â£o localizado. Use /solicitar <CIM> <senha> ou procure o secretÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡rio para cadastro.'
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
            $this->telegram->sendMessage($chatId, 'Procure o secretÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡rio para cadastro');
            return;
        }

            $this->telegram->sendMessage($chatId, 'SolicitaÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â£o registrada. Aguarde aprovaÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â£o do secretÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡rio.');
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
            $this->telegram->sendMessage($chatId, 'Acesso restrito ao Chanceler, VenerÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡vel Mestre ou Administrador.');
            return false;
        }

        return true;
    }

    private function getEfemeridesDoDiaPorTipos(array $tipos): array
    {
        $tiposNormalizados = array_values(array_unique(array_filter(array_map(
            static fn (string $tipo): string => strtolower(trim($tipo)),
            $tipos
        ))));

        $registros = (new EfemerideRegistro())->getRegistrosDoDia();

        return array_values(array_filter($registros, static function (array $registro) use ($tiposNormalizados): bool {
            $tipo = strtolower(trim((string) ($registro['tipo'] ?? '')));
            return in_array($tipo, $tiposNormalizados, true);
        }));
    }

    private function formatarLinhaEfemeride(array $registro): string
    {
        $nome = trim((string) ($registro['nome'] ?? '')) ?: 'Registro sem nome';
        $texto = trim((string) ($registro['mensagem_custom'] ?? ''));
        $dataAtividade = trim((string) ($registro['data_evento'] ?? ''));
        $tipo = trim((string) ($registro['tipo'] ?? ''));

        if ($texto !== '') {
            return "- <b>{$nome}</b>: {$texto}";
        }

        $sufixo = $tipo !== '' ? " ({$tipo})" : '';
        if ($dataAtividade !== '') {
            $timestamp = strtotime($dataAtividade);
            $dataAtividade = $timestamp ? date('d/m/Y', $timestamp) : $dataAtividade;
            $sufixo .= " - {$dataAtividade}";
        }

        return "- <b>{$nome}</b>{$sufixo}";
    }

    public function handlePainelAdmin($chatId, $requesterTelegramId)
    {
        if (!$this->isPrivateChat($chatId)) {
            $this->notifyPrivateOnly($chatId);
            return;
        }

        $obreiro = $this->findObreiroByTelegramId($requesterTelegramId);
        if (!$this->isDev($requesterTelegramId) && !$this->obreiroHasPermission($obreiro, 'admin.cargos.view')) {
            $this->telegram->sendMessage($chatId, 'Acesso restrito aos responsaveis do sistema.');
            return;
        }

        $mensagem = "*Painel do Sistema*\n\nSelecione o mÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â³dulo que deseja acessar:";
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
                    ['text' => '1ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Âº Vigilante', 'callback_data' => 'admin_primeiro_vigilante'],
                ],
                [
                    ['text' => '2ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Âº Vigilante', 'callback_data' => 'admin_segundo_vigilante'],
                    ['text' => 'Orador', 'callback_data' => 'admin_orador'],
                ],
                [
                    ['text' => 'Mestre de Banquetes', 'callback_data' => 'admin_mestre_banquetes'],
                    ['text' => 'Mestre de Harmonia', 'callback_data' => 'admin_mestre_harmonia'],
                ],
                [
                    ['text' => 'VenerÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡vel', 'callback_data' => 'admin_veneravel'],
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
            $this->telegram->sendMessage($chatId, 'Acesso restrito ao 1ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Âº Vigilante, VenerÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡vel Mestre ou Administrador.');
            return;
        }

        $mensagem = "*Painel do 1ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Âº Vigilante*\n\nEscolha o modo de trabalho:";
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
            $this->telegram->sendMessage($chatId, 'Acesso restrito ao 2ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Âº Vigilante, VenerÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡vel Mestre ou Administrador.');
            return;
        }

        $mensagem = "*Painel do 2ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Âº Vigilante*\n\nEscolha o modo de trabalho:";
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
            $this->telegram->sendMessage($chatId, 'Acesso restrito ao Orador, VenerÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡vel Mestre ou Administrador.');
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
            $this->telegram->sendMessage($chatId, 'Acesso restrito ao Mestre de Banquetes, VenerÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡vel Mestre ou Administrador.');
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
            $this->telegram->sendMessage($chatId, 'Acesso restrito ao Mestre de Harmonia, VenerÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡vel Mestre ou Administrador.');
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
            $this->telegram->sendMessage($chatId, 'Acesso restrito ao VenerÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡vel Mestre ou Administrador.');
            return;
        }

        $mensagem = "*Painel do VenerÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡vel Mestre*\n\nEscolha o modo de trabalho:";
        $teclado = [
            'inline_keyboard' => [
                [
                    ['text' => 'Em Loja', 'web_app' => ['url' => $this->buildAppUrl('/miniapp/veneravel')]],
                    ['text' => 'VenerÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡vel Mobile', 'web_app' => ['url' => $this->buildAppUrl('/miniapp/veneravel')]],
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
            $this->telegram->sendMessage($chatId, 'Acesso restrito ao painel de assistÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Âªncia operacional.');
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
                $mensagem = "Bem-vindo ao painel da Loja, meu IrmÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â£o!" . $this->privateMenuHint();
        $teclado = [
            'inline_keyboard' => [
                [
                    ['text' => 'Meu cadastro', 'callback_data' => 'menu_meu_cadastro'],
                    ['text' => 'Minhas informaÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Âµes', 'callback_data' => 'menu_minhas_info'],
                ],
                [
                    ['text' => 'Abrir PWA', 'web_app' => ['url' => $this->buildAppUrl('/pwa')]],
                ],
                array_values(array_filter([
                    \App\Config\FeatureFlags::pwaSessoes() ? ['text' => 'SessÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Âµes (PWA)', 'web_app' => ['url' => $this->buildAppUrl('/pwa/sessoes')]] : null,
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
                ['text' => 'AssistÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Âªncia', 'callback_data' => 'assistencia_menu'],
            ];
        }

        if ($isDev || $this->obreiroHasPermission($obreiro, 'vigilancia.primeiro.manage')) {
            $teclado['inline_keyboard'][] = [
                ['text' => '1ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Âº Vigilante', 'callback_data' => 'admin_primeiro_vigilante'],
            ];
        }

        if ($isDev || $this->obreiroHasPermission($obreiro, 'vigilancia.segundo.manage')) {
            $teclado['inline_keyboard'][] = [
                ['text' => '2ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Âº Vigilante', 'callback_data' => 'admin_segundo_vigilante'],
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
                ['text' => 'VenerÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡vel', 'callback_data' => 'admin_veneravel'],
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
                : 'MaÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â´nico'));
        $agape = match ((string) ($sessao['agape_modalidade'] ?? 'nao_havera')) {
            'gratuito' => 'Sim (gratuito)',
            'pago' => 'Sim (pago)',
            default => 'NÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â£o haverÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡',
        };

        $config = (new ConfiguracaoLoja())->obter();
        $nomeLoja = trim((string) ($config['nome_loja'] ?? ''));
        $numeroLoja = trim((string) ($config['numero_loja'] ?? ''));
        $linhaLoja = trim($nomeLoja . ($numeroLoja !== '' ? ' nÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Âº ' . $numeroLoja : ''));
        $ordemDia = trim((string) ($sessao['ordem_dia'] ?? $sessao['resumo_publico'] ?? ''));

        return "NOVA SESSÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢O\n\n"
            . $dataHora . "\n"
            . "Grau: {$grau}\n\n"
            . $linhaLoja . "\n\n"
            . "SessÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â£o:\n"
            . "Tipo: {$tipo}\n"
            . "Traje: {$traje}\n"
            . "Ordem do dia: " . ($ordemDia !== '' ? $ordemDia : '-') . "\n"
            . "ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Âgape: {$agape}";
    }

    private function montarBotoesSessao(array $sessao): array
    {
        $modalidade = (string) ($sessao['agape_modalidade'] ?? 'nao_havera');
        $linhas = [];
        if ($modalidade === 'gratuito') {
            $linhas[] = [
                ['text' => 'Participar com ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡gape (gratuito)', 'callback_data' => 'presenca_agape_gratuito'],
            ];
            $linhas[] = [
                ['text' => 'Participar sem ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡gape', 'callback_data' => 'presenca_sem_agape'],
            ];
        } elseif ($modalidade === 'pago') {
            $linhas[] = [
                ['text' => 'Participar com ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡gape (pago)', 'callback_data' => 'presenca_agape_pago'],
            ];
            $linhas[] = [
                ['text' => 'Participar sem ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡gape', 'callback_data' => 'presenca_sem_agape'],
            ];
        } else {
            $linhas[] = [
                ['text' => 'Confirmar presenÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§a', 'callback_data' => 'presenca_confirmar'],
            ];
        }

        $linhas[] = [
            ['text' => 'Cancelar confirmaÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â£o', 'callback_data' => 'presenca_cancelar'],
            ['text' => 'Informar ausÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Âªncia', 'callback_data' => 'presenca_ausencia'],
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
            $this->telegram->sendMessage($chatId, 'Ainda nÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â£o hÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡ sessÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â£o futura cadastrada.');
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
            $this->telegram->sendMessage($chatId, 'NÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â£o foi possÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â­vel localizar seu cadastro agora. Tente novamente ou contate a Secretaria.');
            return;
        }

        $sessao = $this->sessaoModel->obterProximaSessao();
        if (!$sessao || empty($sessao['id'])) {
            $this->telegram->sendMessage($chatId, 'NÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â£o hÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡ sessÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â£o disponÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â­vel para confirmar no momento.');
            return;
        }

        $sessaoId = (int) $sessao['id'];
        $obreiroId = (string) ($obreiro['id'] ?? '');
        $ok = false;
        $mensagem = 'NÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â£o conseguimos registrar sua resposta agora. Tente novamente em alguns minutos.';

        switch ($acao) {
            case 'confirmar':
                $ok = $this->presencaModel->registrar($sessaoId, $obreiroId, 'confirmado', false);
                $mensagem = $ok ? 'PresenÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§a confirmada.' : $mensagem;
                break;
            case 'com_agape':
                $ok = $this->presencaModel->registrar($sessaoId, $obreiroId, 'confirmado', true);
                $mensagem = $ok ? 'PresenÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§a confirmada com ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡gape.' : $mensagem;
                break;
            case 'sem_agape':
                $ok = $this->presencaModel->registrar($sessaoId, $obreiroId, 'confirmado', false);
                $mensagem = $ok ? 'PresenÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§a confirmada sem ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡gape.' : $mensagem;
                break;
            case 'ausencia':
                $ok = $this->presencaModel->registrar($sessaoId, $obreiroId, 'ausente', false);
                $mensagem = $ok ? 'AusÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Âªncia registrada.' : $mensagem;
                break;
            case 'cancelar':
                $ok = $this->presencaModel->cancelar($sessaoId, $obreiroId);
                $mensagem = $ok ? 'ConfirmaÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â£o cancelada. Sua resposta voltou para pendente.' : $mensagem;
                break;
            case 'ver_confirmados':
                $confirmados = $this->presencaModel->listarConfirmadosPorSessao($sessaoId);
                if ($confirmados === []) {
                    $this->telegram->sendMessage($chatId, 'Ainda nÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â£o hÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡ confirmaÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Âµes para esta sessÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â£o.');
                    return;
                }
                $linhas = ["Confirmados da prÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â³xima sessÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â£o:\n"];
                foreach ($confirmados as $item) {
                    $linhas[] = '- ' . (string) ($item['nome'] ?? 'Obreiro') . (!empty($item['participara_agape']) ? ' (com ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡gape)' : ' (sem ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡gape)');
                }
                $this->telegram->sendMessage($chatId, implode("\n", $linhas));
                return;
        }

        $this->telegram->sendMessage($chatId, $mensagem);
    }

    public function handleHelp($chatId)
    {
        $mensagem = "<b>Ajuda do Gestor da Loja</b>\n\n";
        $mensagem .= "Comandos disponÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â­veis:\n";
        $mensagem .= "/start - abre o menu principal\n";
        $mensagem .= "/chancelaria - painel da chancelaria\n";
        $mensagem .= "/tesouraria - painel da tesouraria\n";
        $mensagem .= "/biblioteca - painel da biblioteca\n";
        $mensagem .= "/assistencia - painel de assistÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Âªncia\n";
        $mensagem .= "/painel - painel administrativo\n";
        $mensagem .= "/solicitar <CIM> <senha> - solicitar liberaÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â£o de acesso\n";

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
                    ['text' => 'Em Loja', 'web_app' => ['url' => $this->buildAppUrl('/miniapp/chanceler')]],
                    ['text' => 'Efemerides', 'callback_data' => 'chancelaria_neste_dia'],
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
            $this->telegram->sendMessage($chatId, 'Acesso restrito ao Tesoureiro, VenerÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡vel Mestre ou Administrador.');
            return;
        }

        $mensagem = "*Painel da Tesouraria*\n\nSelecione uma opÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â£o:";
        $teclado = [
            'inline_keyboard' => [
                [
                    ['text' => 'Abrir Tesouraria Mobile', 'web_app' => ['url' => $this->buildAppUrl('/miniapp/tesouraria')]],
                ],
                [
                    ['text' => 'Minhas ObrigaÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Âµes', 'web_app' => ['url' => $this->buildAppUrl('/financeiro/minhas-obrigacoes')]],
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
                    ['text' => 'ObrigaÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Âµes', 'web_app' => ['url' => $this->buildAppUrl('/miniapp/tesouraria?dest=%2Ftesouraria%2Fobrigacoes')]],
                    ['text' => 'SessÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Âµes', 'web_app' => ['url' => $this->buildAppUrl('/miniapp/tesouraria?dest=%2Ftesouraria%2Fsessoes')]],
                ],
                [
                    ['text' => 'Validar Pix', 'web_app' => ['url' => $this->buildAppUrl('/miniapp/tesouraria?dest=%2Ftesouraria%2Fcomprovantes')]],
                    ['text' => 'RelatÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â³rio de GestÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â£o', 'web_app' => ['url' => $this->buildAppUrl('/miniapp/tesouraria?dest=%2Ftesouraria%2Frelatorio-gestao')]],
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

        $mensagem = "<b>Biblioteca da Loja</b>\n\nSelecione uma opÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â£o:";
        $botoes = [];

        $botoes[] = [
            ['text' => 'Meus EmprÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â©stimos', 'callback_data' => 'biblioteca_meus_emprestimos'],
            ['text' => 'Ver Acervo', 'callback_data' => 'biblioteca_acervo'],
        ];
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
                ['text' => 'Gerenciar EmprÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â©stimos', 'web_app' => ['url' => $this->buildAppUrl('/biblioteca/emprestimos')]],
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

    public function handleBibliotecaMeusEmprestimos($chatId, $requesterTelegramId)
    {
        $obreiroModel = new \App\Models\Obreiro();
        $emprestimoModel = new \App\Models\Emprestimo();

        $obreiro = $this->findObreiroByTelegramId($requesterTelegramId);
        if (!$obreiro) {
            $this->telegram->sendMessage($chatId, "NÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â£o foi possÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â­vel localizar seu cadastro agora. Tente novamente ou contate a Secretaria.");
            return;
        }

        $emprestimos = $emprestimoModel->listarPendentesPorObreiro($obreiro['id']);

        if (empty($emprestimos)) {
            $mensagem = "<b>Meus EmprÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â©stimos</b>\n\nVocÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Âª nÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â£o possui emprÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â©stimos ativos.";
        } else {
            $mensagem = "<b>Meus EmprÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â©stimos</b>\n\n";
            foreach ($emprestimos as $e) {
                $mensagem .= "- <b>" . htmlspecialchars($e['titulo']) . "</b> - DevoluÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â£o prevista: " . date('d/m/Y', strtotime($e['data_devolucao_prevista'])) . "\n";
            }
        }

        $this->telegram->sendMessage($chatId, $mensagem, [
            'parse_mode' => 'HTML',
            'reply_markup' => ['inline_keyboard' => [[['text' => 'Voltar', 'callback_data' => 'biblioteca_menu']]]],
        ]);
    }

    private function handleBibliotecaAcervo($chatId)
    {
        $acervoModel = new \App\Models\Acervo();
        $livros = $acervoModel->listarTodos();

        if (empty($livros)) {
            $mensagem = "<b>Acervo da Biblioteca</b>\n\nNenhum livro cadastrado.";
        } else {
            $mensagem = "<b>Acervo da Biblioteca</b>\n\n";
            foreach ($livros as $i => $livro) {
                $mensagem .= ($i + 1) . ". <b>" . htmlspecialchars($livro['titulo']) . "</b> - " . htmlspecialchars($livro['autor']);
                if (!empty($livro['grau_recomendado'])) {
                    $mensagem .= " (Grau: " . htmlspecialchars($livro['grau_recomendado']) . ")";
                }
                $mensagem .= "\n";
            }
        }

        $this->telegram->sendMessage($chatId, $mensagem, [
            'parse_mode' => 'HTML',
            'reply_markup' => ['inline_keyboard' => [[['text' => 'Voltar', 'callback_data' => 'biblioteca_menu']]]],
        ]);
    }

    private function handleBibliotecaCadastrar($chatId, $fromId = null)
    {
        $mensagem = "<b>Cadastrar Novo Livro</b>\n\nEscolha o metodo de cadastro:";
        $teclado = [
            'inline_keyboard' => [
                [
                    ['text' => 'Ler Codigo de Barras', 'web_app' => ['url' => $this->buildAppUrl('/biblioteca/scanner')]],
                ],
                [
                    ['text' => 'Preencher Manualmente', 'web_app' => ['url' => $this->buildAppUrl('/biblioteca/novo')]],
                ],
                [
                    ['text' => 'Voltar', 'callback_data' => 'biblioteca_menu'],
                ],
            ],
        ];

        $this->telegram->sendMessage($chatId, $mensagem, ['parse_mode' => 'HTML', 'reply_markup' => $teclado]);
    }

    private function handleBibliotecaGerenciar($chatId, $fromId = null)
    {
        $mensagem = "<b>Gerenciar EmprÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â©stimos</b>\n\nUse o painel web da biblioteca para aprovar devoluÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Âµes e acompanhar pendÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Âªncias.";
        $this->telegram->sendMessage($chatId, $mensagem, [
            'parse_mode' => 'HTML',
            'reply_markup' => ['inline_keyboard' => [
                [['text' => 'Abrir painel', 'web_app' => ['url' => $this->buildAppUrl('/biblioteca/emprestimos')]],
                ['text' => 'Voltar', 'callback_data' => 'biblioteca_menu']]
            ]],
        ]);
    }

    private function handleAniversarios($chatId)
    {
        $aniversariantes = $this->getEfemeridesDoDiaPorTipos(['aniversÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡rio', 'aniversario']);

        if (empty($aniversariantes)) {
            $msg = "NÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â£o hÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡ aniversariantes de vida registrados para hoje.";
        } else {
            $msg = "<b>Aniversariantes de Vida Hoje</b>\n\n";
            foreach ($aniversariantes as $o) {
                $msg .= $this->formatarLinhaEfemeride($o) . "\n";
            }
        }

        $this->telegram->sendMessage($chatId, $msg, ['parse_mode' => 'HTML']);
    }

    private function handleDatasMaconicas($chatId)
    {
        $maconicos = $this->getEfemeridesDoDiaPorTipos([
            'iniciaÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â£o',
            'iniciacao',
            'elevaÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â£o',
            'elevacao',
            'exaltaÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â£o',
            'exaltacao',
            'instalaÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â£o',
            'instalacao',
            'filiaÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â£o',
            'filiacao',
            'posse grÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â£o mestre',
            'posse grao mestre',
            'concessÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â£o de membro honorÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡rio',
            'concessao de membro honorario',
            'oriente eterno',
        ]);

        if (empty($maconicos)) {
            $msg = "NÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â£o hÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡ aniversÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡rios maÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â´nicos registrados para hoje.";
        } else {
            $msg = "<b>AniversÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡rios MaÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â´nicos Hoje</b>\n\n";
            foreach ($maconicos as $o) {
                $msg .= $this->formatarLinhaEfemeride($o) . "\n";
            }
        }

        $this->telegram->sendMessage($chatId, $msg, ['parse_mode' => 'HTML']);
    }

    private function handleFatosHistoricos($chatId)
    {
        $efemerideModel = new \App\Models\EfemerideRegistro();
        $hoje = date('m-d');
        $fatos = $efemerideModel->buscarPorData($hoje);

        $fatosHistoricos = array_values(array_filter($fatos, static function (array $item): bool {
            $tipo = strtolower(trim((string) ($item['tipo'] ?? '')));
            return $tipo === 'historia' || $tipo === 'histÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â³ria';
        }));

        if (empty($fatosHistoricos)) {
            $msg = "NÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â£o hÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡ fatos histÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â³ricos registrados para hoje.";
        } else {
            $msg = "<b>Fatos HistÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â³ricos do Dia</b>\n\n";
            foreach ($fatosHistoricos as $f) {
                $texto = trim((string) ($f['mensagem_custom'] ?? ''));
                if ($texto === '') {
                    $texto = trim((string) ($f['nome'] ?? ''));
                }

                $dataAtividade = '';
                if (!empty($f['data_evento'])) {
                    $timestamp = strtotime((string) $f['data_evento']);
                    $dataAtividade = $timestamp ? date('d/m/Y', $timestamp) : (string) $f['data_evento'];
                }

                $linha = htmlspecialchars($texto !== '' ? $texto : 'Registro histÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â³rico sem descriÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â£o.');
                if ($dataAtividade !== '') {
                    $linha .= " ({$dataAtividade})";
                }
                $msg .= "- {$linha}\n";
            }
        }

        $this->telegram->sendMessage($chatId, $msg, ['parse_mode' => 'HTML']);
    }

    private function handleNesteDia($chatId, int $requesterTelegramId)
    {
        if (!$this->ensureChancelariaAccess($chatId, $requesterTelegramId)) {
            return;
        }

        $composer = new \App\Services\EfemeridesComposer();
        $hoje = date('Y-m-d');
        $msg = $composer->gerarMensagemParaDia($hoje);

        $teclado = [
            'inline_keyboard' => [
                [
                    ['text' => 'Aprovar e Enviar p/ Grupo', 'callback_data' => 'chancelaria_aprovar_efemeride'],
                ],
                [
                    ['text' => 'Revisar Mensagem', 'web_app' => ['url' => $this->buildAppUrl('/miniapp/chanceler?foco=mensagem')]],
                ],
                [
                    ['text' => 'Corrigir Dados', 'web_app' => ['url' => $this->buildAppUrl('/miniapp/chanceler?foco=dados')]],
                ],
                [
                    ['text' => 'Voltar', 'callback_data' => 'admin_chancelaria'],
                ],
            ],
        ];

        $enviadoNoPrivado = $this->telegram->sendMessage(
            $requesterTelegramId,
            $msg,
            ['parse_mode' => 'HTML', 'reply_markup' => $teclado]
        );

        if ($enviadoNoPrivado) {
            if ((string) $chatId !== (string) $requesterTelegramId) {
                $this->telegram->sendMessage(
                    $chatId,
                    "A prÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â©via de 'Neste Dia' foi enviada no seu privado para revisÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â£o.",
                    ['parse_mode' => 'HTML']
                );
            }
            return;
        }

        $this->telegram->sendMessage(
            $chatId,
                "NÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â£o consegui entregar a prÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â©via no privado. Abra o chat com o bot e tente novamente.",
            ['parse_mode' => 'HTML']
        );
    }

    private function handleAprovarEfemeride($chatId, int $requesterTelegramId)
    {
        if (!$this->ensureChancelariaAccess($chatId, $requesterTelegramId)) {
            return;
        }

        $previaModel = new \App\Models\EfemeridePreviaDiaria();
        $hoje = date('Y-m-d');
        $previa = $previaModel->buscarPorData($hoje);

        $mensagem = trim((string) ($previa['mensagem'] ?? ''));
        if ($mensagem === '') {
            $composer = new \App\Services\EfemeridesComposer();
            $mensagem = trim($composer->gerarMensagemParaDia($hoje));
            if ($mensagem !== '') {
                $previaModel->salvarOuAtualizar($hoje, $mensagem, true);
            }
        }

        if ($mensagem !== '') {
            $grupoId = $this->getGroupChatId();
            if (!$grupoId) {
                $this->telegram->sendMessage($chatId, "NÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â£o foi possÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â­vel enviar: o grupo oficial ainda nÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â£o estÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡ configurado.", ['parse_mode' => 'HTML']);
                return;
            }

            $this->telegram->sendMessage($grupoId, $mensagem, ['parse_mode' => 'HTML']);
            $this->telegram->sendMessage($chatId, "Mensagem enviada para o grupo oficial com sucesso.", ['parse_mode' => 'HTML']);
            return;
        }

        $this->telegram->sendMessage($chatId, "NÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â£o encontrei a mensagem de hoje para envio. Gere a prÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â©via e tente novamente.");
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
                            $this->telegram->sendMessage($chatId, 'Token de ativaÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â£o invÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡lido. Procure o secretÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡rio.');
                            return;
                        }

                        $resultado = (new ConviteAcesso())->consumir($token, $fromId);
                        if (!($resultado['ok'] ?? false)) {
                            $this->telegram->sendMessage($chatId, (string) ($resultado['erro'] ?? 'NÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â£o foi possÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â­vel ativar seu acesso. Procure o secretÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡rio.'));
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
                    $this->telegram->sendMessage($chatId, 'Se vocÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Âª for enviar um comprovante PIX, anexe a imagem ou PDF junto com a legenda informando o que estÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡ sendo pago. Ex.: "mensalidade 05/2026 150,00".');
                } else {
                    $this->telegram->sendMessage($chatId, "NÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â£o reconheci este comando. Use /ajuda para ver as opÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Âµes disponÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â­veis.");
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
                        $this->handleNesteDia($chatId, (int) $fromId);
                        break;
                    case 'chancelaria_aprovar_efemeride':
                        $this->handleAprovarEfemeride($chatId, (int) $fromId);
                        break;
                    case 'chancelaria_aniversarios':
                        if (!$this->ensureChancelariaAccess($chatId, (int) $fromId)) { break; }
                        $this->handleAniversarios($chatId);
                        break;
                    case 'chancelaria_datas':
                        if (!$this->ensureChancelariaAccess($chatId, (int) $fromId)) { break; }
                        $this->handleDatasMaconicas($chatId);
                        break;
                    case 'chancelaria_historico':
                        if (!$this->ensureChancelariaAccess($chatId, (int) $fromId)) { break; }
                        $this->handleFatosHistoricos($chatId);
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
                    case 'biblioteca_meus_emprestimos':
                        $this->handleBibliotecaMeusEmprestimos($chatId, $fromId);
                        break;
                    case 'biblioteca_acervo':
                        $this->handleBibliotecaAcervo($chatId);
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
                        $this->telegram->sendMessage($chatId, 'Meu cadastro: procure a Secretaria para ajustes cadastrais e validaÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â£o de dados.');
                        break;
                    case 'menu_minhas_info':
                        $this->telegram->sendMessage($chatId, 'Minhas informaÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Âµes: use o painel web para consultar dados e situaÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â£o atual.');
                        break;
                    case 'menu_ajuda_contato':
                        $this->telegram->sendMessage($chatId, 'Ajuda / contato: em caso de dÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Âºvidas, fale com a Secretaria da Loja.');
                        break;
                    case 'menu_admin_total':
                        $this->telegram->sendMessage($chatId, 'Recurso indisponivel neste perfil.');
                        break;

                    default:
                        $this->telegram->sendMessage($chatId, "NÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â£o reconheci esta aÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â£o. Volte ao menu principal e tente novamente.");
                        break;
                }

            } else {
                error_log('[handle] Update nÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â£o suportado: ' . json_encode($update));
            }

            error_log('[webhook] update processado com sucesso');
        } catch (\Throwable $e) {
            error_log('[webhook] erro ao processar update: ' . $e->getMessage());
        }
    }

    private function handleTesourariaCaixa($chatId)
    {
        $msg = "<b>Caixa da Loja</b>\n\nAcesse o painel para registrar entradas e saÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â­das, revisar movimentos e excluir lanÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§amentos quando necessÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡rio.";
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
        $msg = "<b>Fechamento Mensal</b>\n\nAcesse o painel para revisar lanÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§amentos, ajustar o saldo inicial e concluir o fechamento do perÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â­odo.";
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
        $msg = "<b>ValidaÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â£o de PIX</b>\n\nAcesse o painel de comprovantes para validar ou rejeitar os envios pendentes.";
        $this->telegram->sendMessage($chatId, $msg, [
            'parse_mode' => 'HTML',
            'reply_markup' => [
                'inline_keyboard' => [
                    [
                        ['text' => 'Abrir ValidaÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â£o PIX', 'web_app' => ['url' => $this->buildAppUrl('/miniapp/tesouraria?dest=%2Ftesouraria%2Fcomprovantes')]],
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
            $this->telegram->sendMessage($chatId, 'NÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â£o conseguimos localizar seu cadastro para consulta financeira agora. Tente novamente em alguns minutos.');
            return;
        }

        $config = (new ConfiguracaoLoja())->obter();
        $pixTipo = trim((string) ($config['pix_chave_tipo'] ?? 'CNPJ'));
        $pixValor = trim((string) ($config['pix_chave_valor'] ?? ''));
        $pixBeneficiario = trim((string) ($config['pix_beneficiario'] ?? ''));
        $mensalidade = number_format((float) ($config['mensalidade_valor_padrao'] ?? 150), 2, ',', '.');
        $biblioteca = number_format((float) ($config['contribuicao_biblioteca_valor_padrao'] ?? 44), 2, ',', '.');

        $msg = "<b>OrientaÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Âµes financeiras</b>\n\n";
        $msg .= "ContribuiÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â£o mensal padrÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â£o: <b>R$ {$mensalidade}</b>\n";
        $msg .= "Biblioteca por contribuinte designado: <b>R$ {$biblioteca}</b>\n\n";
        if ($pixValor !== '') {
            $msg .= "PIX da Loja: <b>{$pixTipo} {$pixValor}</b>";
            if ($pixBeneficiario !== '') {
                $msg .= "\nBeneficiÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡rio: <b>{$pixBeneficiario}</b>";
            }
            $msg .= "\n\nAo enviar comprovante, use legenda com o que estÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡ pagando.\n";
            $msg .= "Ex.: <code>mensalidade 05/2026 150,00</code>";
        }

        $teclado = [
            'inline_keyboard' => [
                [
                    ['text' => 'Ver minhas obrigaÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Âµes', 'web_app' => ['url' => $this->buildAppUrl('/financeiro/minhas-obrigacoes')]],
                ],
            ],
        ];

        $this->telegram->sendMessage($chatId, $msg, ['parse_mode' => 'HTML', 'reply_markup' => $teclado]);
    }

    private function handleComprovantePixRecebido($chatId, int $fromId, array $message): void
    {
        $obreiro = $this->findObreiroByTelegramId((int) $fromId);
        if (!$obreiro) {
            $this->telegram->sendMessage($chatId, 'NÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â£o foi possÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â­vel localizar seu cadastro agora. Tente novamente ou contate a Secretaria.');
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
            $this->telegram->sendMessage($chatId, 'NÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â£o consegui identificar o arquivo do comprovante. Reenvie a imagem ou PDF com a legenda do pagamento.');
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
            $this->telegram->sendMessage($chatId, 'NÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â£o conseguimos registrar seu comprovante agora. Tente novamente em alguns minutos.');
            return;
        }

        $config = (new ConfiguracaoLoja())->obter();
        $pixTipo = trim((string) ($config['pix_chave_tipo'] ?? 'CNPJ'));
        $pixValor = trim((string) ($config['pix_chave_valor'] ?? ''));
        $parcelas = (new ObrigacaoFinanceira())->listarParcelasEmAbertoObreiro((string) ($obreiro['id'] ?? ''));

        $msg = "Comprovante recebido e encaminhado para validaÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â£o da Tesouraria.";
        if (($dadosExtraidos['rotulo_pagamento'] ?? '') !== '') {
            $msg .= "\n\nRÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â³tulo identificado: <b>" . htmlspecialchars((string) $dadosExtraidos['rotulo_pagamento']) . "</b>";
        }
        if ($pixValor !== '') {
            $msg .= "\nPIX da Loja: <b>{$pixTipo} {$pixValor}</b>";
        }

        $sugestao = $this->montarSugestaoParcelas($parcelas);
        if ($sugestao !== '') {
            $msg .= "\n\nSugestÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Âµes em aberto:\n" . $sugestao;
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
            $linhas[] = 'ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€šÃ‚Â¢ ' . (string) ($parcela['titulo'] ?? 'ObrigaÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â£o') . ' - ' . (string) ($parcela['competencia_label'] ?? '-') . ' - R$ ' . $valor;
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

        $mensagem = "*Painel da Secretaria*\n\nSelecione uma opÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â£o para continuar:";
        $botoes = [
            [
                ['text' => 'Secretaria Mobile', 'web_app' => ['url' => $this->buildAppUrl('/miniapp/secretaria')]],
                ['text' => 'SessÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â£o em Loja', 'web_app' => ['url' => $this->buildAppUrl('/miniapp/secretaria?foco=balaustre')]],
            ],
            [
                ['text' => 'Agendas e SessÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Âµes', 'web_app' => ['url' => $this->buildAppUrl('/miniapp/secretaria')]],
                ['text' => 'PublicaÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Âµes', 'web_app' => ['url' => $this->buildAppUrl('/miniapp/secretaria')]],
            ],
        ];

        if ($this->isDev($fromId) || $this->obreiroHasRole($obreiro, 'veneravel')) {
            $botoes[] = [
                ['text' => 'Painel do VenerÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡vel Mestre', 'web_app' => ['url' => $this->buildAppUrl('/miniapp/veneravel')]],
                ['text' => 'VenerÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡vel Mobile', 'web_app' => ['url' => $this->buildAppUrl('/miniapp/veneravel')]],
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
            $this->telegram->sendMessage($chatId, 'Acesso restrito ao Mestre Hospitaleiro, Secretaria, Tesouraria, VenerÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡vel Mestre ou Administrador.');
            return;
        }

        $mensagem = "*Painel de AssistÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Âªncia*\n\nRegistre e acompanhe ocorrÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Âªncias assistenciais com encaminhamento ao VenerÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡vel e ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â  Tesouraria.";
        $teclado = [
            'inline_keyboard' => [
                [
                    ['text' => 'AssistÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Âªncia em Loja', 'web_app' => ['url' => $this->buildAppUrl('/miniapp/hospitaleiro')]],
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
                            ['text' => 'SessÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â£o em Loja', 'web_app' => ['url' => $this->buildAppUrl('/miniapp/secretaria?foco=balaustre')]],
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

