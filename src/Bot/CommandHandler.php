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
            error_log('[bot] APP_URL ausente no .env; links web_app foram bloqueados atÃ© a configuraÃ§Ã£o.');
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
        return $base . $path . $separator . 'v=' . time();
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

        if (!empty($obreiro['cargos']) && is_array($obreiro['cargos'])) {
            return array_values(array_unique(array_map(
                static fn ($role) => strtolower((string) $role),
                $obreiro['cargos']
            )));
        }

        $fallback = strtolower(trim((string) ($obreiro['cargo_principal'] ?? $obreiro['cargo'] ?? '')));
        return $fallback !== '' ? [$fallback] : [];
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
        return "\n\nSe algum botÃ£o nÃ£o abrir, envie /painel novamente.";
    }

    private function ensureAppUrlConfigured(int|string $chatId): bool
    {
        if ($this->getAppBaseUrl() !== '') {
            return true;
        }

        $this->telegram->sendMessage(
            $chatId,
            'Mini app indisponÃ­vel no momento. APP_URL nÃ£o configurada. Reenvie /painel apÃ³s atualizar o ambiente local.'
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
            $this->telegram->sendMessage($chatId, 'Seu acesso estÃ¡ pendente. Aguarde aprovaÃ§Ã£o do secretÃ¡rio/admin.');
            return;
        }

        if ($state === 'inativo') {
            $this->telegram->sendMessage($chatId, 'Seu acesso estÃ¡ inativo. Procure o secretÃ¡rio/admin.');
            return;
        }

        $this->telegram->sendMessage(
            $chatId,
            'Registro nÃ£o localizado. Use /solicitar <CIM> <senha> ou procure o secretÃ¡rio para cadastro.'
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
            $this->telegram->sendMessage($chatId, 'Procure o secretÃ¡rio para cadastro');
            return;
        }

        $this->telegram->sendMessage($chatId, 'SolicitaÃ§Ã£o registrada. Aguarde aprovaÃ§Ã£o do secretÃ¡rio/admin.');
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
            $this->telegram->sendMessage($chatId, 'Acesso restrito ao Chanceler, VenerÃ¡vel Mestre ou Administrador.');
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
            $this->telegram->sendMessage($chatId, 'Acesso restrito aos Administradores do sistema.');
            return;
        }

        $mensagem = "*Painel do Administrador*\n\nSelecione o mÃ³dulo que deseja acessar:";
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
                    ['text' => 'Admin Mobile', 'web_app' => ['url' => $this->buildAppUrl('/miniapp/admin')]],
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
        $mensagem = "Bem-vindo ao painel da Loja, meu IrmÃ£o!" . $this->privateMenuHint();
        $teclado = [
            'inline_keyboard' => [
                [
                    ['text' => 'Meu cadastro', 'callback_data' => 'menu_meu_cadastro'],
                    ['text' => 'Minhas informaÃ§Ãµes', 'callback_data' => 'menu_minhas_info'],
                ],
                [
                    ['text' => 'Ajuda / contato', 'callback_data' => 'menu_ajuda_contato'],
                ],
            ],
        ];

        if ($isDev || $this->obreiroHasPermission($obreiro, 'chancelaria.manage')) {
            $teclado['inline_keyboard'][] = [
                ['text' => 'Chancelaria', 'callback_data' => 'admin_chancelaria'],
            ];
        }

        if ($isDev || $this->obreiroHasPermission($obreiro, 'biblioteca.self')) {
            $teclado['inline_keyboard'][] = [
                ['text' => 'Biblioteca', 'callback_data' => 'biblioteca_menu'],
            ];
        }

        if ($isDev || $this->obreiroHasPermission($obreiro, '*') || $this->obreiroHasPermission($obreiro, 'admin.cargos.view')) {
            $teclado['inline_keyboard'][] = [
                ['text' => 'Admin', 'callback_data' => 'menu_admin_total'],
            ];
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
                : 'MaÃ§Ã´nico'));
        $agape = match ((string) ($sessao['agape_modalidade'] ?? 'nao_havera')) {
            'gratuito' => 'Sim (gratuito)',
            'pago' => 'Sim (pago)',
            default => 'NÃ£o haverÃ¡',
        };

        $config = (new ConfiguracaoLoja())->obter();
        $nomeLoja = trim((string) ($config['nome_loja'] ?? ''));
        $numeroLoja = trim((string) ($config['numero_loja'] ?? ''));
        $linhaLoja = trim($nomeLoja . ($numeroLoja !== '' ? ' nÂº ' . $numeroLoja : ''));
        $ordemDia = trim((string) ($sessao['ordem_dia'] ?? $sessao['resumo_publico'] ?? ''));

        return "NOVA SESSÃƒO\n\n"
            . $dataHora . "\n"
            . "Grau: {$grau}\n\n"
            . $linhaLoja . "\n\n"
            . "SessÃ£o:\n"
            . "Tipo: {$tipo}\n"
            . "Traje: {$traje}\n"
            . "Ordem do dia: " . ($ordemDia !== '' ? $ordemDia : '-') . "\n"
            . "Ãgape: {$agape}";
    }

    private function montarBotoesSessao(array $sessao): array
    {
        $modalidade = (string) ($sessao['agape_modalidade'] ?? 'nao_havera');
        $linhas = [];
        if ($modalidade === 'gratuito') {
            $linhas[] = [
                ['text' => 'Participar com Ã¡gape (gratuito)', 'callback_data' => 'presenca_agape_gratuito'],
            ];
            $linhas[] = [
                ['text' => 'Participar sem Ã¡gape', 'callback_data' => 'presenca_sem_agape'],
            ];
        } elseif ($modalidade === 'pago') {
            $linhas[] = [
                ['text' => 'Participar com Ã¡gape (pago)', 'callback_data' => 'presenca_agape_pago'],
            ];
            $linhas[] = [
                ['text' => 'Participar sem Ã¡gape', 'callback_data' => 'presenca_sem_agape'],
            ];
        } else {
            $linhas[] = [
                ['text' => 'Confirmar presenÃ§a', 'callback_data' => 'presenca_confirmar'],
            ];
        }

        $linhas[] = [
            ['text' => 'Cancelar confirmaÃ§Ã£o', 'callback_data' => 'presenca_cancelar'],
            ['text' => 'Informar ausÃªncia', 'callback_data' => 'presenca_ausencia'],
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
            $this->telegram->sendMessage($chatId, 'Ainda nÃ£o hÃ¡ sessÃ£o futura cadastrada.');
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
            $this->telegram->sendMessage($chatId, 'NÃ£o foi possÃ­vel localizar seu cadastro agora. Tente novamente ou contate a Secretaria.');
            return;
        }

        $sessao = $this->sessaoModel->obterProximaSessao();
        if (!$sessao || empty($sessao['id'])) {
            $this->telegram->sendMessage($chatId, 'NÃ£o hÃ¡ sessÃ£o disponÃ­vel para confirmar no momento.');
            return;
        }

        $sessaoId = (int) $sessao['id'];
        $obreiroId = (string) ($obreiro['id'] ?? '');
        $ok = false;
        $mensagem = 'NÃ£o conseguimos registrar sua resposta agora. Tente novamente em alguns minutos.';

        switch ($acao) {
            case 'confirmar':
                $ok = $this->presencaModel->registrar($sessaoId, $obreiroId, 'confirmado', false);
                $mensagem = $ok ? 'PresenÃ§a confirmada.' : $mensagem;
                break;
            case 'com_agape':
                $ok = $this->presencaModel->registrar($sessaoId, $obreiroId, 'confirmado', true);
                $mensagem = $ok ? 'PresenÃ§a confirmada com Ã¡gape.' : $mensagem;
                break;
            case 'sem_agape':
                $ok = $this->presencaModel->registrar($sessaoId, $obreiroId, 'confirmado', false);
                $mensagem = $ok ? 'PresenÃ§a confirmada sem Ã¡gape.' : $mensagem;
                break;
            case 'ausencia':
                $ok = $this->presencaModel->registrar($sessaoId, $obreiroId, 'ausente', false);
                $mensagem = $ok ? 'AusÃªncia registrada.' : $mensagem;
                break;
            case 'cancelar':
                $ok = $this->presencaModel->cancelar($sessaoId, $obreiroId);
                $mensagem = $ok ? 'ConfirmaÃ§Ã£o cancelada. Sua resposta voltou para pendente.' : $mensagem;
                break;
            case 'ver_confirmados':
                $confirmados = $this->presencaModel->listarConfirmadosPorSessao($sessaoId);
                if ($confirmados === []) {
                    $this->telegram->sendMessage($chatId, 'Ainda nÃ£o hÃ¡ confirmaÃ§Ãµes para esta sessÃ£o.');
                    return;
                }
                $linhas = ["Confirmados da prÃ³xima sessÃ£o:\n"];
                foreach ($confirmados as $item) {
                    $linhas[] = '- ' . (string) ($item['nome'] ?? 'Obreiro') . (!empty($item['participara_agape']) ? ' (com Ã¡gape)' : ' (sem Ã¡gape)');
                }
                $this->telegram->sendMessage($chatId, implode("\n", $linhas));
                return;
        }

        $this->telegram->sendMessage($chatId, $mensagem);
    }

    public function handleHelp($chatId)
    {
        $mensagem = "<b>Ajuda do Gestor da Loja</b>\n\n";
        $mensagem .= "Comandos disponÃ­veis:\n";
        $mensagem .= "/start - abre o menu principal\n";
        $mensagem .= "/chancelaria - painel da chancelaria\n";
        $mensagem .= "/tesouraria - painel da tesouraria\n";
        $mensagem .= "/biblioteca - painel da biblioteca\n";
        $mensagem .= "/assistencia - painel de assistencia\n";
        $mensagem .= "/painel - painel administrativo\n";
        $mensagem .= "/solicitar <CIM> <senha> - solicitar liberaÃ§Ã£o de acesso\n";

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

        $mensagem = "*Painel da Chancelaria*\n\nSelecione uma opÃ§Ã£o:";
        $teclado = [
            'inline_keyboard' => [
                [
                    ['text' => 'Emitir Certificado', 'web_app' => ['url' => $this->buildAppUrl('/chancelaria/certificado')]],
                ],
                [
                    ['text' => 'Certificado (Alternativo)', 'url' => $this->buildAppUrl('/chancelaria/certificado')],
                ],
                [
                    ['text' => 'Miniapp do Chanceler', 'web_app' => ['url' => $this->buildAppUrl('/miniapp/chanceler')]],
                ],
                [
                    ['text' => 'Neste Dia', 'callback_data' => 'chancelaria_neste_dia'],
                ],
                [
                    ['text' => 'AniversÃ¡rios Hoje', 'callback_data' => 'chancelaria_aniversarios'],
                    ['text' => 'Datas MaÃ§Ã´nicas', 'callback_data' => 'chancelaria_datas'],
                ],
                [
                    ['text' => 'Fatos HistÃ³ricos', 'callback_data' => 'chancelaria_historico'],
                ],
                [
                    ['text' => 'Miniapp HistÃ³rico', 'web_app' => ['url' => $this->buildAppUrl('/miniapp/historico')]],
                    ['text' => 'Miniapp Complementar', 'web_app' => ['url' => $this->buildAppUrl('/miniapp/fallback')]],
                ],
                [
                    ['text' => 'Miniapp AniversÃ¡rio', 'web_app' => ['url' => $this->buildAppUrl('/miniapp/aniversario')]],
                    ['text' => 'Miniapp Data MaÃ§Ã´nica', 'web_app' => ['url' => $this->buildAppUrl('/miniapp/data-maconica')]],
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
        if (!$this->isDev($requesterTelegramId) && (!$obreiro || !$this->obreiroHasRole($obreiro, 'tesoureiro', 'veneravel', 'admin'))) {
            $this->telegram->sendMessage($chatId, 'Acesso restrito ao Tesoureiro, VenerÃ¡vel Mestre ou Administrador.');
            return;
        }

        $mensagem = "*Painel da Tesouraria*\n\nSelecione uma opÃ§Ã£o:";
        $teclado = [
            'inline_keyboard' => [
                [
                    ['text' => 'Abrir Tesouraria Mobile', 'web_app' => ['url' => $this->buildAppUrl('/miniapp/tesouraria')]],
                ],
                [
                    ['text' => 'Minhas ObrigaÃ§Ãµes', 'web_app' => ['url' => $this->buildAppUrl('/financeiro/minhas-obrigacoes')]],
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
                    ['text' => 'ObrigaÃ§Ãµes', 'web_app' => ['url' => $this->buildAppUrl('/miniapp/tesouraria?dest=%2Ftesouraria%2Fobrigacoes')]],
                    ['text' => 'SessÃµes', 'web_app' => ['url' => $this->buildAppUrl('/miniapp/tesouraria?dest=%2Ftesouraria%2Fsessoes')]],
                ],
                [
                    ['text' => 'Validar Pix', 'web_app' => ['url' => $this->buildAppUrl('/miniapp/tesouraria?dest=%2Ftesouraria%2Fcomprovantes')]],
                    ['text' => 'RelatÃ³rio de GestÃ£o', 'web_app' => ['url' => $this->buildAppUrl('/miniapp/tesouraria?dest=%2Ftesouraria%2Frelatorio-gestao')]],
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
        $isBibliotecario = $this->obreiroHasRole($obreiro, 'bibliotecario', 'admin', 'veneravel');
        $canClassificar = $this->obreiroHasRole($obreiro, 'primeiro_vigilante', 'segundo_vigilante', 'bibliotecario', 'admin', 'veneravel');
        $isDev = $this->isDev($requesterTelegramId);

        $mensagem = "<b>Biblioteca da Loja</b>\n\nSelecione uma opÃ§Ã£o:";
        $botoes = [];

        $botoes[] = [
            ['text' => 'Meus EmprÃ©stimos', 'callback_data' => 'biblioteca_meus_emprestimos'],
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
                ['text' => 'Gerenciar EmprÃ©stimos', 'web_app' => ['url' => $this->buildAppUrl('/biblioteca/emprestimos')]],
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
            $this->telegram->sendMessage($chatId, "NÃ£o foi possÃ­vel localizar seu cadastro agora. Tente novamente ou contate a Secretaria.");
            return;
        }

        $emprestimos = $emprestimoModel->listarPendentesPorObreiro($obreiro['id']);

        if (empty($emprestimos)) {
            $mensagem = "<b>Meus EmprÃ©stimos</b>\n\nVocÃª nÃ£o possui emprÃ©stimos ativos.";
        } else {
            $mensagem = "<b>Meus EmprÃ©stimos</b>\n\n";
            foreach ($emprestimos as $e) {
                $mensagem .= "- <b>" . htmlspecialchars($e['titulo']) . "</b> - DevoluÃ§Ã£o prevista: " . date('d/m/Y', strtotime($e['data_devolucao_prevista'])) . "\n";
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
        $mensagem = "<b>Gerenciar EmprÃ©stimos</b>\n\nUse o painel web da biblioteca para aprovar devoluÃ§Ãµes e acompanhar pendÃªncias.";
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
        $aniversariantes = $this->getEfemeridesDoDiaPorTipos(['aniversÃ¡rio', 'aniversario']);

        if (empty($aniversariantes)) {
            $msg = "NÃ£o hÃ¡ aniversariantes de vida registrados para hoje.";
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
            'iniciaÃ§Ã£o',
            'iniciacao',
            'elevaÃ§Ã£o',
            'elevacao',
            'exaltaÃ§Ã£o',
            'exaltacao',
            'instalaÃ§Ã£o',
            'instalacao',
            'filiaÃ§Ã£o',
            'filiacao',
            'posse grÃ£o mestre',
            'posse grao mestre',
            'concessÃ£o de membro honorÃ¡rio',
            'concessao de membro honorario',
            'oriente eterno',
        ]);

        if (empty($maconicos)) {
            $msg = "NÃ£o hÃ¡ aniversÃ¡rios maÃ§Ã´nicos registrados para hoje.";
        } else {
            $msg = "<b>AniversÃ¡rios MaÃ§Ã´nicos Hoje</b>\n\n";
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
            return $tipo === 'historia' || $tipo === 'histÃ³ria';
        }));

        if (empty($fatosHistoricos)) {
            $msg = "NÃ£o hÃ¡ fatos histÃ³ricos registrados para hoje.";
        } else {
            $msg = "<b>Fatos HistÃ³ricos do Dia</b>\n\n";
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

                $linha = htmlspecialchars($texto !== '' ? $texto : 'Registro histÃ³rico sem descriÃ§Ã£o.');
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
                    ['text' => 'Revisar Mensagem', 'web_app' => ['url' => $this->buildAppUrl('/chancelaria/efemerides?foco=mensagem')]],
                ],
                [
                    ['text' => 'Corrigir Dados', 'web_app' => ['url' => $this->buildAppUrl('/chancelaria/efemerides?foco=dados')]],
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
                    "A prÃ©via de 'Neste Dia' foi enviada no seu privado para revisÃ£o.",
                    ['parse_mode' => 'HTML']
                );
            }
            return;
        }

        $this->telegram->sendMessage(
            $chatId,
                "NÃ£o consegui entregar a prÃ©via no privado. Abra o chat com o bot e tente novamente.",
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
                $this->telegram->sendMessage($chatId, "NÃ£o foi possÃ­vel enviar: o grupo oficial ainda nÃ£o estÃ¡ configurado.", ['parse_mode' => 'HTML']);
                return;
            }

            $this->telegram->sendMessage($grupoId, $mensagem, ['parse_mode' => 'HTML']);
            $this->telegram->sendMessage($chatId, "Mensagem enviada para o grupo oficial com sucesso.", ['parse_mode' => 'HTML']);
            return;
        }

        $this->telegram->sendMessage($chatId, "NÃ£o encontrei a mensagem de hoje para envio. Gere a prÃ©via e tente novamente.");
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
                            $this->telegram->sendMessage($chatId, 'Token de ativaÃ§Ã£o invÃ¡lido. Procure o secretÃ¡rio.');
                            return;
                        }

                        $resultado = (new ConviteAcesso())->consumir($token, $fromId);
                        if (!($resultado['ok'] ?? false)) {
                            $this->telegram->sendMessage($chatId, (string) ($resultado['erro'] ?? 'NÃ£o foi possÃ­vel ativar seu acesso. Procure o secretÃ¡rio.'));
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
                    $this->telegram->sendMessage($chatId, 'Se voce for enviar um comprovante PIX, anexe a imagem ou PDF junto com a legenda informando o que esta sendo pago. Ex.: "mensalidade 05/2026 150,00".');
                } else {
                    $this->telegram->sendMessage($chatId, "NÃ£o reconheci este comando. Use /ajuda para ver as opÃ§Ãµes disponÃ­veis.");
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
                        $this->telegram->sendMessage($chatId, 'Meu cadastro: procure a Secretaria para ajustes cadastrais e validaÃ§Ã£o de dados.');
                        break;
                    case 'menu_minhas_info':
                        $this->telegram->sendMessage($chatId, 'Minhas informaÃ§Ãµes: use o painel web para consultar dados e situaÃ§Ã£o atual.');
                        break;
                    case 'menu_ajuda_contato':
                        $this->telegram->sendMessage($chatId, 'Ajuda / contato: em caso de dÃºvidas, fale com a Secretaria da Loja.');
                        break;
                    case 'menu_admin_total':
                        $this->handlePainelAdmin($chatId, $fromId);
                        break;

                    default:
                        $this->telegram->sendMessage($chatId, "NÃ£o reconheci esta aÃ§Ã£o. Volte ao menu principal e tente novamente.");
                        break;
                }

            } else {
                error_log('[handle] Update nao suportado: ' . json_encode($update));
            }

            error_log('[webhook] update processado com sucesso');
        } catch (\Throwable $e) {
            error_log('[webhook] erro ao processar update: ' . $e->getMessage());
        }
    }

    private function handleTesourariaCaixa($chatId)
    {
        $msg = "<b>Caixa da Loja</b>\n\nAcesse o painel para registrar entradas e saÃ­das, revisar movimentos e excluir lanÃ§amentos quando necessario.";
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
        $msg = "<b>Fechamento Mensal</b>\n\nAcesse o painel para revisar lanÃ§amentos, ajustar o saldo inicial e concluir o fechamento do periodo.";
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
        $msg = "<b>ValidaÃ§Ã£o de PIX</b>\n\nAcesse o painel de comprovantes para validar ou rejeitar os envios pendentes.";
        $this->telegram->sendMessage($chatId, $msg, [
            'parse_mode' => 'HTML',
            'reply_markup' => [
                'inline_keyboard' => [
                    [
                        ['text' => 'Abrir ValidaÃ§Ã£o PIX', 'web_app' => ['url' => $this->buildAppUrl('/miniapp/tesouraria?dest=%2Ftesouraria%2Fcomprovantes')]],
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
            $this->telegram->sendMessage($chatId, 'NÃ£o conseguimos localizar seu cadastro para consulta financeira agora. Tente novamente em alguns minutos.');
            return;
        }

        $config = (new ConfiguracaoLoja())->obter();
        $pixTipo = trim((string) ($config['pix_chave_tipo'] ?? 'CNPJ'));
        $pixValor = trim((string) ($config['pix_chave_valor'] ?? ''));
        $pixBeneficiario = trim((string) ($config['pix_beneficiario'] ?? ''));
        $mensalidade = number_format((float) ($config['mensalidade_valor_padrao'] ?? 150), 2, ',', '.');
        $biblioteca = number_format((float) ($config['contribuicao_biblioteca_valor_padrao'] ?? 44), 2, ',', '.');

        $msg = "<b>OrientaÃ§Ãµes financeiras</b>\n\n";
        $msg .= "Contribuicao mensal padrÃ£o: <b>R$ {$mensalidade}</b>\n";
        $msg .= "Biblioteca por contribuinte designado: <b>R$ {$biblioteca}</b>\n\n";
        if ($pixValor !== '') {
            $msg .= "PIX da Loja: <b>{$pixTipo} {$pixValor}</b>";
            if ($pixBeneficiario !== '') {
                $msg .= "\nBeneficiÃ¡rio: <b>{$pixBeneficiario}</b>";
            }
            $msg .= "\n\nAo enviar comprovante, use legenda com o que estÃ¡ pagando.\n";
            $msg .= "Ex.: <code>mensalidade 05/2026 150,00</code>";
        }

        $teclado = [
            'inline_keyboard' => [
                [
                    ['text' => 'Ver minhas obrigaÃ§Ãµes', 'web_app' => ['url' => $this->buildAppUrl('/financeiro/minhas-obrigacoes')]],
                ],
            ],
        ];

        $this->telegram->sendMessage($chatId, $msg, ['parse_mode' => 'HTML', 'reply_markup' => $teclado]);
    }

    private function handleComprovantePixRecebido($chatId, int $fromId, array $message): void
    {
        $obreiro = $this->findObreiroByTelegramId((int) $fromId);
        if (!$obreiro) {
            $this->telegram->sendMessage($chatId, 'NÃ£o foi possÃ­vel localizar seu cadastro agora. Tente novamente ou contate a Secretaria.');
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
            $this->telegram->sendMessage($chatId, 'NÃ£o consegui identificar o arquivo do comprovante. Reenvie a imagem ou PDF com a legenda do pagamento.');
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
            $this->telegram->sendMessage($chatId, 'NÃ£o conseguimos registrar seu comprovante agora. Tente novamente em alguns minutos.');
            return;
        }

        $config = (new ConfiguracaoLoja())->obter();
        $pixTipo = trim((string) ($config['pix_chave_tipo'] ?? 'CNPJ'));
        $pixValor = trim((string) ($config['pix_chave_valor'] ?? ''));
        $parcelas = (new ObrigacaoFinanceira())->listarParcelasEmAbertoObreiro((string) ($obreiro['id'] ?? ''));

        $msg = "Comprovante recebido e encaminhado para validaÃ§Ã£o da Tesouraria.";
        if (($dadosExtraidos['rotulo_pagamento'] ?? '') !== '') {
            $msg .= "\n\nRotulo identificado: <b>" . htmlspecialchars((string) $dadosExtraidos['rotulo_pagamento']) . "</b>";
        }
        if ($pixValor !== '') {
            $msg .= "\nPIX da Loja: <b>{$pixTipo} {$pixValor}</b>";
        }

        $sugestao = $this->montarSugestaoParcelas($parcelas);
        if ($sugestao !== '') {
            $msg .= "\n\nSugestoes em aberto:\n" . $sugestao;
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
            $linhas[] = 'â€¢ ' . (string) ($parcela['titulo'] ?? 'ObrigaÃ§Ã£o') . ' - ' . (string) ($parcela['competencia_label'] ?? '-') . ' - R$ ' . $valor;
        }

        return implode("\n", $linhas);
    }

    public function handleSecretariaMenu($chatId, $fromId)
    {
        if (!$this->ensureAppUrlConfigured($chatId)) {
            return;
        }

        $obreiro = $this->findObreiroByTelegramId((int) $fromId);
        if (!$this->isDev($fromId) && (!$obreiro || !$this->obreiroHasRole($obreiro, 'secretario', 'admin', 'veneravel'))) {
            $this->telegram->sendMessage($chatId, 'Acesso restrito a Secretaria da Loja.');
            return;
        }

        $mensagem = "*Painel da Secretaria*\n\nSelecione uma opÃ§Ã£o para continuar:";
        $botoes = [
            [
                ['text' => 'Painel Web da Secretaria', 'web_app' => ['url' => $this->buildAppUrl('/secretaria')]],
            ],
            [
                ['text' => 'Agendas e SessÃµes', 'callback_data' => 'sec_agendas'],
            ],
        ];

        if ($this->isDev($fromId) || $this->obreiroHasRole($obreiro, 'admin', 'veneravel')) {
            $botoes[] = [
                ['text' => 'Painel do VenerÃ¡vel Mestre', 'web_app' => ['url' => $this->buildAppUrl('/veneravel')]],
                ['text' => 'VenerÃ¡vel Mobile', 'web_app' => ['url' => $this->buildAppUrl('/miniapp/veneravel')]],
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
        if (!$this->isDev($fromId) && (!$obreiro || !$this->obreiroHasRole($obreiro, 'hospitaleiro', 'secretario', 'tesoureiro', 'veneravel', 'admin'))) {
            $this->telegram->sendMessage($chatId, 'Acesso restrito ao Mestre Hospitaleiro, Secretaria, Tesouraria, VenerÃ¡vel Mestre ou Administrador.');
            return;
        }

        $mensagem = "*Painel de AssistÃªncia*\n\nRegistre e acompanhe ocorrÃªncias assistenciais com encaminhamento ao VenerÃ¡vel e Ã  Tesouraria.";
        $teclado = [
            'inline_keyboard' => [
                [
                    ['text' => 'Abrir painel de Assistencia', 'web_app' => ['url' => $this->buildAppUrl('/assistencia')]],
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
        $this->telegram->sendMessage($chatId, "Use o painel web da Secretaria para operar sessÃµes, publicaÃ§Ãµes e trabalhos da ordem do dia.");
    }
}


