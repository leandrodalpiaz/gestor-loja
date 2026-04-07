<?php

namespace App\Controllers;

use App\Models\Obreiro;
use App\Models\OcorrenciaAssistencial;

class HospitaleiroController
{
    public function index(): void
    {
        $ocorrenciaModel = new OcorrenciaAssistencial();
        $obreiroModel = new Obreiro();

        $ocorrencias = $ocorrenciaModel->listarRecentes(80);
        $resumo = $ocorrenciaModel->contarResumo();
        $obreiros = $obreiroModel->getAllAtivos();

        $roles = array_values(array_unique(array_map(
            static fn ($role) => strtolower((string) $role),
            $_SESSION['usuario_cargos'] ?? [$_SESSION['usuario_cargo'] ?? '']
        )));

        $podeOperarOcorrencias = in_array('hospitaleiro', $roles, true)
            || in_array('admin', $roles, true)
            || in_array('veneravel', $roles, true)
            || in_array('secretario', $roles, true);

        $podeTratarFinanceiro = in_array('tesoureiro', $roles, true)
            || in_array('admin', $roles, true)
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
            ? 'Ocorrencia assistencial registrada com sucesso.'
            : 'Nao foi possivel registrar a ocorrencia assistencial.';

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
            $_SESSION['mensagem_erro'] = 'Dados insuficientes para atualizar o status da ocorrencia.';
            header('Location: /assistencia');
            exit;
        }

        $model = new OcorrenciaAssistencial();
        $ok = $model->atualizarStatus($id, $status, $autorId !== '' ? $autorId : null, $observacao !== '' ? $observacao : null);
        $_SESSION[$ok ? 'mensagem_sucesso' : 'mensagem_erro'] = $ok
            ? 'Status da ocorrencia atualizado.'
            : 'Nao foi possivel atualizar o status da ocorrencia.';

        header('Location: /assistencia');
        exit;
    }
}
