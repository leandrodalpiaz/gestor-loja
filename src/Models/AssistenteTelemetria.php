<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class AssistenteTelemetria
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function registrar(array $evento): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO public.assistente_telemetria
                (canal, comando_original, comando_normalizado, intent, confidence, allowed, action_target, user_id, tenant_id, metadata, created_at)
             VALUES
                (:canal, :comando_original, :comando_normalizado, :intent, :confidence, :allowed, :action_target, :user_id, :tenant_id, :metadata, NOW())"
        );

        $metadata = $evento['metadata'] ?? null;
        if (is_array($metadata)) {
            $metadata = json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        $userId = $this->normalizarUuidOuNulo($evento['user_id'] ?? null);
        $tenantId = $this->normalizarUuidOuNulo($evento['tenant_id'] ?? null);

        $stmt->execute([
            'canal' => (string) ($evento['canal'] ?? 'web'),
            'comando_original' => mb_substr(trim((string) ($evento['comando_original'] ?? '')), 0, 280),
            'comando_normalizado' => mb_substr(trim((string) ($evento['comando_normalizado'] ?? '')), 0, 280),
            'intent' => mb_substr(trim((string) ($evento['intent'] ?? 'desconhecida')), 0, 120),
            'confidence' => isset($evento['confidence']) ? (float) $evento['confidence'] : null,
            'allowed' => !empty($evento['allowed']),
            'action_target' => mb_substr(trim((string) ($evento['action_target'] ?? '')), 0, 255),
            'user_id' => $userId,
            'tenant_id' => $tenantId,
            'metadata' => $metadata,
        ]);
    }

    private function normalizarUuidOuNulo(mixed $valor): ?string
    {
        $texto = trim((string) ($valor ?? ''));
        if ($texto === '') {
            return null;
        }

        if (!preg_match('/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[1-5][0-9a-fA-F]{3}-[89abAB][0-9a-fA-F]{3}-[0-9a-fA-F]{12}$/', $texto)) {
            return null;
        }

        return strtolower($texto);
    }

    public function resumo(int $dias = 14): array
    {
        $dias = max(1, min(120, $dias));

        $totaisStmt = $this->db->prepare(
            "SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN allowed THEN 1 ELSE 0 END) AS total_allowed,
                SUM(CASE WHEN allowed THEN 0 ELSE 1 END) AS total_denied,
                SUM(CASE WHEN intent = 'desconhecida' THEN 1 ELSE 0 END) AS total_unknown
             FROM public.assistente_telemetria
             WHERE created_at >= NOW() - ((:dias::text || ' days')::interval)"
        );
        $totaisStmt->execute(['dias' => $dias]);
        $totais = $totaisStmt->fetch(PDO::FETCH_ASSOC) ?: [];

        $topIntentsStmt = $this->db->prepare(
            "SELECT intent, COUNT(*) AS total
             FROM public.assistente_telemetria
             WHERE created_at >= NOW() - ((:dias::text || ' days')::interval)
             GROUP BY intent
             ORDER BY total DESC, intent ASC
             LIMIT 8"
        );
        $topIntentsStmt->execute(['dias' => $dias]);

        $topComandosStmt = $this->db->prepare(
            "SELECT comando_normalizado, COUNT(*) AS total
             FROM public.assistente_telemetria
             WHERE created_at >= NOW() - ((:dias::text || ' days')::interval)
               AND COALESCE(comando_normalizado, '') <> ''
             GROUP BY comando_normalizado
             ORDER BY total DESC, comando_normalizado ASC
             LIMIT 10"
        );
        $topComandosStmt->execute(['dias' => $dias]);

        $errosStmt = $this->db->prepare(
            "SELECT intent, COUNT(*) AS total
             FROM public.assistente_telemetria
             WHERE created_at >= NOW() - ((:dias::text || ' days')::interval)
               AND (allowed = FALSE OR intent = 'desconhecida')
             GROUP BY intent
             ORDER BY total DESC, intent ASC
             LIMIT 8"
        );
        $errosStmt->execute(['dias' => $dias]);

        $topTelasStmt = $this->db->prepare(
            "SELECT action_target, COUNT(*) AS total
             FROM public.assistente_telemetria
             WHERE created_at >= NOW() - ((:dias::text || ' days')::interval)
               AND COALESCE(action_target, '') <> ''
             GROUP BY action_target
             ORDER BY total DESC, action_target ASC
             LIMIT 8"
        );
        $topTelasStmt->execute(['dias' => $dias]);

        return [
            'dias' => $dias,
            'totais' => [
                'total' => (int) ($totais['total'] ?? 0),
                'total_allowed' => (int) ($totais['total_allowed'] ?? 0),
                'total_denied' => (int) ($totais['total_denied'] ?? 0),
                'total_unknown' => (int) ($totais['total_unknown'] ?? 0),
            ],
            'top_intents' => $topIntentsStmt->fetchAll(PDO::FETCH_ASSOC),
            'top_comandos' => $topComandosStmt->fetchAll(PDO::FETCH_ASSOC),
            'erros_frequentes' => $errosStmt->fetchAll(PDO::FETCH_ASSOC),
            'top_telas' => $topTelasStmt->fetchAll(PDO::FETCH_ASSOC),
        ];
    }
}
