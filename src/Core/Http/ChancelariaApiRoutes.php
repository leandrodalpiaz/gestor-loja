<?php

namespace App\Core\Http;

use App\Models\EfemerideRegistro;

class ChancelariaApiRoutes
{
    /**
     * Tipos de efeméride válidos.
     */
    private const TIPOS_VALIDOS = [
        'Aniversario',
        'Iniciacao',
        'Elevacao',
        'Exaltacao',
        'Instalacao',
        'Oriente Eterno',
        'Historia',
        'Posse Grao Mestre',
        'Concessao de Membro Honorario',
        'Filiacao',
    ];

    public static function dispatch(
        string $requestUri,
        string $method,
        array $session,
        callable $requireChancelariaApiAccess
    ): bool {
        if (!str_starts_with($requestUri, '/api/chancelaria/')) {
            return false;
        }

        header('Content-Type: application/json; charset=utf-8');
        $requireChancelariaApiAccess();

        // --- GET /api/chancelaria/efemerides/hoje ---
        if ($requestUri === '/api/chancelaria/efemerides/hoje' && $method === 'GET') {
            $model = new EfemerideRegistro();
            $registros = $model->getRegistrosDoDia();
            JsonResponse::send(['ok' => true, 'registros' => $registros]);
            return true;
        }

        // --- GET /api/chancelaria/efemerides ---
        if ($requestUri === '/api/chancelaria/efemerides' && $method === 'GET') {
            $filtros = [
                'termo'    => trim((string) ($_GET['termo'] ?? '')),
                'tipo'     => trim((string) ($_GET['tipo'] ?? '')),
                'ativo'    => trim((string) ($_GET['ativo'] ?? '1')),
                'data_ini' => trim((string) ($_GET['data_ini'] ?? '')),
                'data_fim' => trim((string) ($_GET['data_fim'] ?? '')),
            ];

            $model = new EfemerideRegistro();
            $registros = $model->buscarComFiltros($filtros);
            JsonResponse::send(['ok' => true, 'registros' => $registros]);
            return true;
        }

        // --- POST /api/chancelaria/efemerides/salvar ---
        if ($requestUri === '/api/chancelaria/efemerides/salvar' && $method === 'POST') {
            $body = RequestBody::json();

            $nome = trim((string) ($body['nome'] ?? ''));
            if ($nome === '') {
                JsonResponse::send(['ok' => false, 'erro' => 'O campo Nome é obrigatório.']);
                return true;
            }

            $tipo = trim((string) ($body['tipo'] ?? ''));
            if (!in_array($tipo, self::TIPOS_VALIDOS, true)) {
                JsonResponse::send(['ok' => false, 'erro' => 'Tipo de efeméride inválido.']);
                return true;
            }

            $dataEvento = trim((string) ($body['data_evento'] ?? ''));
            if ($dataEvento === '') {
                JsonResponse::send(['ok' => false, 'erro' => 'A data do evento é obrigatória.']);
                return true;
            }

            $dados = [
                'nome'           => $nome,
                'tipo'           => $tipo,
                'data_evento'    => $dataEvento,
                'vinculo'        => trim((string) ($body['vinculo'] ?? '')) ?: null,
                'parentesco'     => trim((string) ($body['parentesco'] ?? '')) ?: null,
                'local'          => trim((string) ($body['local'] ?? '')) ?: null,
                'mensagem_custom' => trim((string) ($body['mensagem_custom'] ?? '')) ?: null,
            ];

            $model = new EfemerideRegistro();
            $ok = $model->create($dados);
            JsonResponse::send([
                'ok'  => (bool) $ok,
                'erro' => $ok ? null : 'Falha ao criar efeméride.',
            ]);
            return true;
        }

        // --- POST /api/chancelaria/efemerides/atualizar ---
        if ($requestUri === '/api/chancelaria/efemerides/atualizar' && $method === 'POST') {
            $body = RequestBody::json();

            $id = (int) ($body['id'] ?? 0);
            if ($id <= 0) {
                JsonResponse::send(['ok' => false, 'erro' => 'ID da efeméride não informado.']);
                return true;
            }

            $nome = trim((string) ($body['nome'] ?? ''));
            if ($nome === '') {
                JsonResponse::send(['ok' => false, 'erro' => 'O campo Nome é obrigatório.']);
                return true;
            }

            $tipo = trim((string) ($body['tipo'] ?? ''));
            if (!in_array($tipo, self::TIPOS_VALIDOS, true)) {
                JsonResponse::send(['ok' => false, 'erro' => 'Tipo de efeméride inválido.']);
                return true;
            }

            $dados = [
                'id'             => $id,
                'nome'           => $nome,
                'tipo'           => $tipo,
                'data_evento'    => trim((string) ($body['data_evento'] ?? '')),
                'vinculo'        => trim((string) ($body['vinculo'] ?? '')) ?: null,
                'parentesco'     => trim((string) ($body['parentesco'] ?? '')) ?: null,
                'local'          => trim((string) ($body['local'] ?? '')) ?: null,
                'mensagem_custom' => trim((string) ($body['mensagem_custom'] ?? '')) ?: null,
            ];

            $model = new EfemerideRegistro();
            $ok = $model->atualizar($dados);
            JsonResponse::send([
                'ok'  => (bool) $ok,
                'erro' => $ok ? null : 'Falha ao atualizar efeméride.',
            ]);
            return true;
        }

        // --- POST /api/chancelaria/efemerides/desativar ---
        if ($requestUri === '/api/chancelaria/efemerides/desativar' && $method === 'POST') {
            $body = RequestBody::json();
            $id = (int) ($body['id'] ?? 0);
            if ($id <= 0) {
                JsonResponse::send(['ok' => false, 'erro' => 'ID da efeméride não informado.']);
                return true;
            }

            $model = new EfemerideRegistro();
            $ok = $model->desativar($id);
            JsonResponse::send(['ok' => (bool) $ok]);
            return true;
        }

        // --- POST /api/chancelaria/efemerides/excluir ---
        if ($requestUri === '/api/chancelaria/efemerides/excluir' && $method === 'POST') {
            $body = RequestBody::json();
            $id = (int) ($body['id'] ?? 0);
            if ($id <= 0) {
                JsonResponse::send(['ok' => false, 'erro' => 'ID da efeméride não informado.']);
                return true;
            }

            $model = new EfemerideRegistro();
            $ok = $model->excluir($id);
            JsonResponse::send(['ok' => (bool) $ok]);
            return true;
        }

        // --- POST /api/chancelaria/certificado/gerar ---
        if ($requestUri === '/api/chancelaria/certificado/gerar' && $method === 'POST') {
            $body = RequestBody::json();

            $nome = trim((string) ($body['nome_visitante'] ?? ''));
            $loja = trim((string) ($body['loja_visitante'] ?? ''));
            $oriente = trim((string) ($body['oriente'] ?? ''));
            $tipoSessao = trim((string) ($body['tipo_sessao'] ?? ''));
            $grauSessao = trim((string) ($body['grau_sessao'] ?? ''));
            $dataSessao = trim((string) ($body['data_sessao'] ?? ''));
            $chatId = trim((string) ($body['chat_id'] ?? ''));

            if ($nome === '' || $loja === '' || $oriente === '' || $tipoSessao === '' || $grauSessao === '' || $dataSessao === '') {
                JsonResponse::send(['ok' => false, 'erro' => 'Todos os campos obrigatórios (*) devem ser preenchidos.']);
                return true;
            }

            try {
                require_once __DIR__ . '/../../Services/CertificadoGenerator.php';
                $generator = new \App\Services\CertificadoGenerator();
                $caminhoImagem = $generator->gerar($nome, $loja, $oriente, $tipoSessao, $grauSessao, $dataSessao);

                if (!empty($chatId)) {
                    require_once __DIR__ . '/../../Bot/TelegramClient.php';
                    $telegram = new \App\Bot\TelegramClient();
                    $telegram->sendPhoto($chatId, $caminhoImagem, "Certificado gerado com sucesso!\n\nAgora é só encaminhar para o Irmão {$nome}.");
                }

                $relativeUrl = '/temp/' . basename($caminhoImagem);
                JsonResponse::send([
                    'ok' => true,
                    'caminho_imagem' => $relativeUrl,
                    'mensagem' => 'Certificado gerado com sucesso!' . (!empty($chatId) ? ' Enviado também via Telegram.' : '')
                ]);
            } catch (\Exception $e) {
                JsonResponse::send([
                    'ok' => false,
                    'erro' => 'Erro ao gerar o certificado: ' . $e->getMessage()
                ]);
            }
            return true;
        }

        return false;
    }
}
