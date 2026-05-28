<?php

namespace App\Controllers;

use App\Models\Obreiro;
use App\Models\OcorrenciaAssistencial;
use App\Models\TroncoSolidariedade;

class HospitaleiroController
{
    public function index(): void
    {
        $ocorrenciaModel = new OcorrenciaAssistencial();
        $obreiroModel = new Obreiro();
        $troncoModel = new TroncoSolidariedade();

        $ocorrencias = $ocorrenciaModel->listarRecentes(80);
        $pendenciasVisita = $ocorrenciaModel->listarPendentesVisita(20);
        $resumo = $ocorrenciaModel->contarResumo();
        $obreiros = $obreiroModel->getAllAtivos();

        $saldoTronco = $troncoModel->obterSaldo();
        $movimentosTronco = $troncoModel->listarRecentes(50);

        $roles = array_values(array_unique(array_map(
            static fn ($role) => strtolower((string) $role),
            $_SESSION['usuario_cargos'] ?? [$_SESSION['usuario_cargo'] ?? '']
        )));

        $podeOperarOcorrencias = in_array('hospitaleiro', $roles, true)
            || in_array('veneravel', $roles, true)
            || in_array('secretario', $roles, true);

        $podeTratarFinanceiro = in_array('tesoureiro', $roles, true)
            || in_array('veneravel', $roles, true);

        require_once __DIR__ . '/../Views/hospitaleiro/index.php';
    }

    public function salvarOcorrencia(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /assistencia');
            exit;
        }

        $descricao = trim((string) ($_POST['descricao'] ?? ''));
        if ($descricao === '') {
            $_SESSION['mensagem_erro'] = 'Informe a descricao da ocorrencia assistencial.';
            header('Location: /assistencia');
            exit;
        }

        $autorId = (string) ($_SESSION['usuario_id'] ?? '');
        $payload = [
            'tipo_ocorrencia' => trim((string) ($_POST['tipo_ocorrencia'] ?? 'assistencia_geral')),
            'status' => 'aberta',
            'prioridade' => trim((string) ($_POST['prioridade'] ?? 'media')),
            'obreiro_id' => trim((string) ($_POST['obreiro_id'] ?? '')),
            'nome_familiar' => trim((string) ($_POST['nome_familiar'] ?? '')),
            'parentesco' => trim((string) ($_POST['parentesco'] ?? '')),
            'descricao' => $descricao,
            'necessita_visita' => isset($_POST['necessita_visita']),
            'necessita_apoio_financeiro' => isset($_POST['necessita_apoio_financeiro']),
            'valor_solicitado' => trim((string) ($_POST['valor_solicitado'] ?? '')),
            'valor_aprovado' => trim((string) ($_POST['valor_aprovado'] ?? '')),
            'encaminhar_para' => trim((string) ($_POST['encaminhar_para'] ?? 'nenhum')),
            'data_ocorrencia' => trim((string) ($_POST['data_ocorrencia'] ?? '')),
            'data_proxima_acao' => trim((string) ($_POST['data_proxima_acao'] ?? '')),
            'origem_registro' => 'manual',
            'created_by' => $autorId !== '' ? $autorId : null,
            'updated_by' => $autorId !== '' ? $autorId : null,
        ];

        $model = new OcorrenciaAssistencial();
        $ok = $model->criar($payload);
        $_SESSION[$ok ? 'mensagem_sucesso' : 'mensagem_erro'] = $ok
            ? 'Ocorrência assistencial registrada com sucesso.'
            : 'Não foi possível registrar a ocorrência assistencial.';

        header('Location: /assistencia');
        exit;
    }

    public function atualizarStatusOcorrencia(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /assistencia');
            exit;
        }

        $id = (int) ($_POST['ocorrencia_id'] ?? 0);
        $status = trim((string) ($_POST['status'] ?? ''));
        $observacao = trim((string) ($_POST['observacao_status'] ?? ''));
        $autorId = (string) ($_SESSION['usuario_id'] ?? '');

        if ($id <= 0 || $status === '') {
            $_SESSION['mensagem_erro'] = 'Dados insuficientes para atualizar o status da ocorrência.';
            header('Location: /assistencia');
            exit;
        }

        $model = new OcorrenciaAssistencial();
        $ok = $model->atualizarStatus($id, $status, $autorId !== '' ? $autorId : null, $observacao !== '' ? $observacao : null);
        $_SESSION[$ok ? 'mensagem_sucesso' : 'mensagem_erro'] = $ok
            ? 'Status da ocorrência atualizado.'
            : 'Não foi possível atualizar o status da ocorrência.';

        header('Location: /assistencia');
        exit;
    }

    public function registrarVisita(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /assistencia');
            exit;
        }

        $id = (int) ($_POST['ocorrencia_id'] ?? 0);
        $observacao = trim((string) ($_POST['observacao_visita'] ?? ''));
        $dataProximaAcao = trim((string) ($_POST['data_proxima_acao'] ?? ''));
        $autorId = (string) ($_SESSION['usuario_id'] ?? '');

        if ($id <= 0) {
            $_SESSION['mensagem_erro'] = 'Ocorrência inválida para registrar visita.';
            header('Location: /assistencia');
            exit;
        }

        $ok = (new OcorrenciaAssistencial())->registrarVisita(
            $id,
            $autorId !== '' ? $autorId : null,
            $observacao !== '' ? $observacao : null,
            $dataProximaAcao !== '' ? $dataProximaAcao : null
        );

        $_SESSION[$ok ? 'mensagem_sucesso' : 'mensagem_erro'] = $ok
            ? 'Visita/retorno registrado com sucesso.'
            : 'Não foi possível registrar a visita/retorno.';
        header('Location: /assistencia');
        exit;
    }

    public function registrarMovimentoTronco(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /assistencia');
            exit;
        }

        $tipo = trim((string) ($_POST['tipo'] ?? 'entrada'));
        $valorStr = trim((string) ($_POST['valor'] ?? '0'));
        $descricao = trim((string) ($_POST['descricao'] ?? ''));

        $valor = $this->normalizarValor($valorStr);

        if ($valor <= 0) {
            $_SESSION['mensagem_erro'] = 'Informe um valor válido maior que zero.';
            header('Location: /assistencia');
            exit;
        }

        $autorId = (string) ($_SESSION['usuario_id'] ?? '');
        $troncoModel = new TroncoSolidariedade();

        $ok = $troncoModel->registrar([
            'tipo' => $tipo === 'saida' ? 'saida' : 'entrada',
            'valor' => $valor,
            'descricao' => $descricao !== '' ? $descricao : null,
            'created_by' => $autorId !== '' ? $autorId : null,
        ]);

        $_SESSION[$ok ? 'mensagem_sucesso' : 'mensagem_erro'] = $ok
            ? 'Movimentação do Tronco registrada com sucesso.'
            : 'Não foi possível registrar a movimentação.';

        header('Location: /assistencia');
        exit;
    }

    public function registrarRepasseApoio(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /assistencia');
            exit;
        }

        $ocorrenciaId = (int) ($_POST['ocorrencia_id'] ?? 0);
        if ($ocorrenciaId <= 0) {
            $_SESSION['mensagem_erro'] = 'Ocorrência inválida para registrar repasse.';
            header('Location: /assistencia');
            exit;
        }

        $ocorrenciaModel = new OcorrenciaAssistencial();
        $lista = $ocorrenciaModel->listarRecentes(200);
        $ocorrencia = null;
        foreach ($lista as $item) {
            if ((int) $item['id'] === $ocorrenciaId) {
                $ocorrencia = $item;
                break;
            }
        }

        if (!$ocorrencia) {
            $_SESSION['mensagem_erro'] = 'Ocorrência não encontrada.';
            header('Location: /assistencia');
            exit;
        }

        $valorAprovado = (float) ($ocorrencia['valor_aprovado'] ?? 0);
        if ($valorAprovado <= 0) {
            $_SESSION['mensagem_erro'] = 'Esta ocorrência não possui valor aprovado para repasse.';
            header('Location: /assistencia');
            exit;
        }

        $autorId = (string) ($_SESSION['usuario_id'] ?? '');
        $troncoModel = new TroncoSolidariedade();

        $desc = 'Repasse Assistencial - Ocorrência #' . $ocorrenciaId;
        if (!empty($ocorrencia['obreiro_nome'])) {
            $desc .= ' (Beneficiário: ' . $ocorrencia['obreiro_nome'] . ')';
        }
        $ok = $troncoModel->registrar([
            'tipo' => 'saida',
            'valor' => $valorAprovado,
            'descricao' => $desc,
            'created_by' => $autorId !== '' ? $autorId : null,
        ]);

        if ($ok) {
            $ocorrenciaModel->atualizarStatus($ocorrenciaId, 'concluida', $autorId !== '' ? $autorId : null, 'Repasse do auxílio financeiro efetuado via Tronco de Solidariedade.');
            $_SESSION['mensagem_sucesso'] = 'Repasse registrado e ocorrência concluída com sucesso.';
        } else {
            $_SESSION['mensagem_erro'] = 'Falha ao registrar a saída no saldo do Tronco.';
        }

        header('Location: /assistencia');
        exit;
    }

    private function normalizarValor(mixed $valor): float
    {
        if ($valor === null) {
            return 0.0;
        }

        $texto = trim((string) $valor);
        if ($texto === '') {
            return 0.0;
        }

        $texto = str_replace(' ', '', $texto);
        if (str_contains($texto, ',') && str_contains($texto, '.')) {
            $texto = str_replace('.', '', $texto);
            $texto = str_replace(',', '.', $texto);
        } elseif (str_contains($texto, ',')) {
            $texto = str_replace(',', '.', $texto);
        }

        return is_numeric($texto) ? (float) $texto : 0.0;
    }

    public function montarPayloadMiniapp(): array
    {
        $ocorrenciaModel = new OcorrenciaAssistencial();
        $obreiroModel = new Obreiro();

        return [
            'resumo' => $ocorrenciaModel->contarResumo(),
            'ocorrencias' => $ocorrenciaModel->listarRecentes(30),
            'pendencias_visita' => $ocorrenciaModel->listarPendentesVisita(20),
            'obreiros' => array_map(static function (array $obreiro): array {
                return [
                    'id' => (string) ($obreiro['id'] ?? ''),
                    'nome' => (string) ($obreiro['nome_historico'] ?? $obreiro['nome'] ?? 'Obreiro'),
                    'cim' => (string) ($obreiro['cim'] ?? ''),
                ];
            }, $obreiroModel->getAllAtivos()),
        ];
    }

    public function salvarOcorrenciaMiniapp(array $dados, ?string $autorId = null): array
    {
        $descricao = trim((string) ($dados['descricao'] ?? ''));
        if ($descricao === '') {
            return ['ok' => false, 'erro' => 'Informe a descrição da ocorrência assistencial.'];
        }

        $payload = [
            'tipo_ocorrencia' => trim((string) ($dados['tipo_ocorrencia'] ?? 'assistencia_geral')),
            'status' => 'aberta',
            'prioridade' => trim((string) ($dados['prioridade'] ?? 'media')),
            'obreiro_id' => trim((string) ($dados['obreiro_id'] ?? '')),
            'nome_familiar' => trim((string) ($dados['nome_familiar'] ?? '')),
            'parentesco' => trim((string) ($dados['parentesco'] ?? '')),
            'descricao' => $descricao,
            'necessita_visita' => !empty($dados['necessita_visita']),
            'necessita_apoio_financeiro' => !empty($dados['necessita_apoio_financeiro']),
            'valor_solicitado' => trim((string) ($dados['valor_solicitado'] ?? '')),
            'valor_aprovado' => trim((string) ($dados['valor_aprovado'] ?? '')),
            'encaminhar_para' => trim((string) ($dados['encaminhar_para'] ?? 'nenhum')),
            'data_ocorrencia' => trim((string) ($dados['data_ocorrencia'] ?? '')),
            'data_proxima_acao' => trim((string) ($dados['data_proxima_acao'] ?? '')),
            'origem_registro' => 'miniapp',
            'created_by' => $autorId,
            'updated_by' => $autorId,
        ];

        $ok = (new OcorrenciaAssistencial())->criar($payload);
        return ['ok' => $ok, 'erro' => $ok ? null : 'Não foi possível registrar a ocorrência assistencial.'];
    }

    public function atualizarStatusMiniapp(int $id, string $status, ?string $autorId = null, ?string $observacao = null): array
    {
        if ($id <= 0 || trim($status) === '') {
            return ['ok' => false, 'erro' => 'Dados insuficientes para atualizar o status da ocorrência.'];
        }

        $ok = (new OcorrenciaAssistencial())->atualizarStatus($id, $status, $autorId, $observacao);
        return ['ok' => $ok, 'erro' => $ok ? null : 'Não foi possível atualizar o status da ocorrência.'];
    }

    public function registrarVisitaMiniapp(int $id, ?string $autorId = null, ?string $observacao = null, ?string $dataProximaAcao = null): array
    {
        if ($id <= 0) {
            return ['ok' => false, 'erro' => 'Ocorrência inválida para registrar visita.'];
        }

        $ok = (new OcorrenciaAssistencial())->registrarVisita($id, $autorId, $observacao, $dataProximaAcao);
        return ['ok' => $ok, 'erro' => $ok ? null : 'Não foi possível registrar a visita/retorno.'];
    }
}
