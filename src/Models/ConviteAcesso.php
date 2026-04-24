<?php

namespace App\Models;

use App\Config\Env;
use App\Config\Database;
use App\Core\Tenant\ResolvesStoreTenant;
use PDO;
use Throwable;

class ConviteAcesso
{
    use ResolvesStoreTenant;

    private static ?string $cachedDeepLinkBase = null;
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function gerarParaObreiro(string $obreiroId): array
    {
        $obreiroId = trim($obreiroId);
        if ($obreiroId === '') {
            return ['ok' => false, 'erro' => 'Informe o obreiro para gerar o convite.'];
        }

        if (!$this->isUuid($obreiroId)) {
            return ['ok' => false, 'erro' => 'Obreiro inválido para gerar convite.'];
        }

        try {
            $obreiro = (new Obreiro())->findById($obreiroId);
        } catch (Throwable $e) {
            return ['ok' => false, 'erro' => 'Obreiro inválido para gerar convite.'];
        }
        if (!$obreiro) {
            return ['ok' => false, 'erro' => 'Obreiro não encontrado para gerar convite.'];
        }

        $lojaAtual = $this->buscarLojaAtualId();
        $lojaObreiro = (int) ($obreiro['loja_id'] ?? 0);
        if ($lojaObreiro <= 0 || $lojaObreiro !== $lojaAtual) {
            return ['ok' => false, 'erro' => 'Obreiro não pertence à loja atual.'];
        }

        $status = strtolower(trim((string) ($obreiro['acesso_status'] ?? '')));
        if ($status === 'inativo') {
            return ['ok' => false, 'erro' => 'Obreiro está inativo. Não gerar convite.'];
        }

        if (!empty($obreiro['telegram_id'])) {
            return ['ok' => false, 'erro' => 'Obreiro ja possui Telegram vinculado.'];
        }

        $token = $this->gerarTokenSeguro();
        $expiraEm = (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))
            ->modify('+7 days')
            ->format('Y-m-d H:i:sP');

        try {
            $stmt = $this->db->prepare(
                "INSERT INTO public.convites_acesso (obreiro_id, token, expira_em)
                 VALUES (:obreiro_id, :token, :expira_em)"
            );
            $ok = $stmt->execute([
                'obreiro_id' => $obreiroId,
                'token' => $token,
                'expira_em' => $expiraEm,
            ]);

            if (!$ok) {
                return ['ok' => false, 'erro' => 'Não foi possível gerar o convite.'];
            }

            return [
                'ok' => true,
                'token' => $token,
                'deep_link' => $this->deepLinkForToken($token),
                'expira_em' => $expiraEm,
            ];
        } catch (Throwable $e) {
            return ['ok' => false, 'erro' => 'Falha ao gerar convite: ' . $e->getMessage()];
        }
    }

    public function listarRecentes(int $limite = 30): array
    {
        $limite = max(1, min(200, $limite));
        $stmt = $this->db->prepare(
            "SELECT
                c.id,
                c.obreiro_id,
                c.token,
                c.expira_em,
                c.usado_em,
                c.criado_em,
                o.nome,
                o.cim
             FROM public.convites_acesso c
             JOIN public.obreiros o ON o.id = c.obreiro_id
             WHERE o.loja_id = :loja_id
             ORDER BY c.criado_em DESC
             LIMIT :limite"
        );
        $stmt->bindValue('loja_id', $this->buscarLojaAtualId(), PDO::PARAM_INT);
        $stmt->bindValue('limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function consumir(string $token, int $telegramId): array
    {
        $token = trim($token);
        if ($token === '' || $telegramId <= 0) {
            return ['ok' => false, 'erro' => 'Token inválido para ativação.'];
        }

        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare(
                "SELECT *
                 FROM public.convites_acesso
                 WHERE token = :token
                 LIMIT 1
                 FOR UPDATE"
            );
            $stmt->execute(['token' => $token]);
            $convite = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
            if (!$convite) {
                $this->db->rollBack();
                return ['ok' => false, 'erro' => 'Convite inválido.'];
            }

            if (!empty($convite['usado_em'])) {
                $this->db->rollBack();
                return ['ok' => false, 'erro' => 'Convite ja utilizado.'];
            }

            $expiraEm = (string) ($convite['expira_em'] ?? '');
            if ($expiraEm !== '' && strtotime($expiraEm) !== false && strtotime($expiraEm) <= time()) {
                $this->db->rollBack();
                return ['ok' => false, 'erro' => 'Convite expirado.'];
            }

            $obreiroId = trim((string) ($convite['obreiro_id'] ?? ''));
            if ($obreiroId === '') {
                $this->db->rollBack();
                return ['ok' => false, 'erro' => 'Convite sem obreiro vinculado.'];
            }

            if (!$this->isUuid($obreiroId)) {
                $this->db->rollBack();
                return ['ok' => false, 'erro' => 'Obreiro do convite inválido.'];
            }

            $obreiro = (new Obreiro())->findById($obreiroId);
            if (!$obreiro) {
                $this->db->rollBack();
                return ['ok' => false, 'erro' => 'Obreiro do convite não encontrado.'];
            }

            $lojaObreiro = (int) ($obreiro['loja_id'] ?? 0);
            if ($lojaObreiro <= 0) {
                $this->db->rollBack();
                return ['ok' => false, 'erro' => 'Loja do obreiro inválida.'];
            }

            $statusObreiro = strtolower(trim((string) ($obreiro['acesso_status'] ?? '')));
            if ($statusObreiro === 'inativo') {
                $this->db->rollBack();
                return ['ok' => false, 'erro' => 'Obreiro está inativo. Procure o secretário.'];
            }

            if (!empty($obreiro['telegram_id'])) {
                $this->db->rollBack();
                return ['ok' => false, 'erro' => 'Obreiro ja possui Telegram vinculado.'];
            }

            $stmtCheck = $this->db->prepare(
                "SELECT id
                 FROM public.obreiros
                 WHERE telegram_id = :telegram_id
                 LIMIT 1"
            );
            $stmtCheck->execute(['telegram_id' => $telegramId]);
            $existing = $stmtCheck->fetch(PDO::FETCH_ASSOC) ?: null;
            if ($existing) {
                $this->db->rollBack();
                return ['ok' => false, 'erro' => 'Este Telegram ja esta vinculado a outro obreiro.'];
            }

            $stmtUp = $this->db->prepare(
                "UPDATE public.obreiros
                 SET telegram_id = :telegram_id,
                     acesso_status = 'ativo',
                     ativo = TRUE
                 WHERE id = :id"
            );
            $okUp = $stmtUp->execute([
                'telegram_id' => $telegramId,
                'id' => $obreiroId,
            ]);
            if (!$okUp) {
                $this->db->rollBack();
                return ['ok' => false, 'erro' => 'Não foi possível ativar o acesso.'];
            }

            $stmtUse = $this->db->prepare(
                "UPDATE public.convites_acesso
                 SET usado_em = NOW()
                 WHERE token = :token"
            );
            $stmtUse->execute(['token' => $token]);

            $this->db->commit();
            return ['ok' => true];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return ['ok' => false, 'erro' => 'Falha ao consumir convite: ' . $e->getMessage()];
        }
    }

    public function maskToken(string $token): string
    {
        $token = trim($token);
        if ($token === '') {
            return '';
        }

        if (strlen($token) <= 12) {
            return substr($token, 0, 3) . '...' . substr($token, -2);
        }

        return substr($token, 0, 6) . '...' . substr($token, -4);
    }

    public function deepLinkForToken(string $token): string
    {
        $token = trim($token);
        if ($token === '') {
            return '';
        }

        $base = $this->resolverDeepLinkBase();

        if ($base === '') {
            return 'ativar_' . $token;
        }

        $joiner = str_contains($base, '?') ? '&' : '?';
        return $base . $joiner . 'start=ativar_' . $token;
    }

    private function resolverDeepLinkBase(): string
    {
        if (self::$cachedDeepLinkBase !== null) {
            return self::$cachedDeepLinkBase;
        }

        $base = trim((string) (Env::get('TELEGRAM_BOT_DEEPLINK_BASE', '') ?: ''));
        if ($base !== '') {
            self::$cachedDeepLinkBase = $base;
            return $base;
        }

        $username = trim((string) (Env::get('TELEGRAM_BOT_USERNAME', '') ?: ''));
        if ($username !== '') {
            $base = 'https://t.me/' . ltrim($username, '@');
            self::$cachedDeepLinkBase = $base;
            return $base;
        }

        $token = trim((string) (Env::get('TELEGRAM_BOT_TOKEN', '') ?: ''));
        if ($token === '') {
            self::$cachedDeepLinkBase = '';
            return '';
        }

        try {
            $apiUrl = 'https://api.telegram.org/bot' . rawurlencode($token) . '/getMe';
            $response = @file_get_contents($apiUrl);
            if (!is_string($response) || $response === '') {
                self::$cachedDeepLinkBase = '';
                return '';
            }

            $payload = json_decode($response, true);
            $apiUsername = trim((string) ($payload['result']['username'] ?? ''));
            if ($apiUsername === '') {
                self::$cachedDeepLinkBase = '';
                return '';
            }

            $base = 'https://t.me/' . ltrim($apiUsername, '@');
            self::$cachedDeepLinkBase = $base;
            return $base;
        } catch (Throwable $e) {
            self::$cachedDeepLinkBase = '';
            return '';
        }
    }

    private function buscarLojaAtualId(): int
    {
        return $this->resolveCurrentStoreId($this->db);
    }

    private function gerarTokenSeguro(): string
    {
        $bytes = random_bytes(24);
        $token = rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
        return $token;
    }

    private function isUuid(string $value): bool
    {
        return (bool) preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $value);
    }

    // O método público deepLinkForToken monta o link de ativação usado no onboarding.
}
