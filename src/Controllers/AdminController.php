<?php

namespace App\Controllers;

use App\Models\Cargo;
use App\Models\AuditoriaAdministrativa;
use App\Models\ConfiguracaoLoja;
use App\Models\ConteudoPublico;
use App\Models\Gestao;
use App\Models\Obreiro;
use App\Models\ConviteAcesso;

class AdminController
{
    public function acessosPendentes()
    {
        $itens = (new Obreiro())->listarPendentesAcesso();
        require_once __DIR__ . '/../Views/admin/acessos.php';
    }

    public function atualizarAcesso()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = trim((string) ($_POST['id'] ?? ''));
            $status = trim((string) ($_POST['status'] ?? ''));

            if ($id === '' || $status === '') {
                $_SESSION['mensagem_erro'] = 'Informe o obreiro e o status desejado.';
            } else {
                $ok = (new Obreiro())->atualizarAcessoStatus($id, $status);
                if ($ok) {
                    $_SESSION['mensagem_sucesso'] = 'Status de acesso atualizado com sucesso.';
                } else {
                    $_SESSION['mensagem_erro'] = 'Não foi possível atualizar o status de acesso.';
                }
            }
        }

        header('Location: /admin/acessos');
        exit;
    }

    public function conteudoPublico()
    {
        $model = new ConteudoPublico();
        $itens = $model->listarParaAdmin();

        require_once __DIR__ . '/../Views/admin/conteudo_publico.php';
    }

    public function salvarConteudoPublico()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                (new ConteudoPublico())->salvar($_POST);
                $_SESSION['mensagem_sucesso'] = 'Conteúdo público atualizado com sucesso.';
            } catch (\Throwable $e) {
                error_log('AdminController::salvarConteudoPublico - ' . $e->getMessage());
                $_SESSION['mensagem_erro'] = 'Não foi possível salvar o conteúdo agora. Revise os dados e tente novamente.';
            }
        }

        header('Location: /admin/conteudo-publico');
        exit;
    }

    public function excluirConteudoPublico()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (int) ($_POST['id'] ?? 0);
            try {
                (new ConteudoPublico())->excluir($id);
                $_SESSION['mensagem_sucesso'] = 'Conteúdo removido com sucesso.';
            } catch (\Throwable $e) {
                error_log('AdminController::excluirConteudoPublico - ' . $e->getMessage());
                $_SESSION['mensagem_erro'] = 'Não foi possível remover o conteúdo agora. Tente novamente.';
            }
        }

        header('Location: /admin/conteudo-publico');
        exit;
    }

    public function listarCargos()
    {
        $obreiroModel = new Obreiro();
        $cargoModel = new Cargo();
        $gestaoModel = new Gestao();

        $obreiros = $obreiroModel->getAllAtivos();
        $gestoes = $gestaoModel->listar();
        $gestaoAtual = $gestaoModel->obterAberta();
        $cargosResumo = $cargoModel->listarResumoCargos($gestaoAtual ? (int) $gestaoAtual['id'] : null);
        $historico = $cargoModel->listarHistorico(120, null, $gestaoAtual ? (int) $gestaoAtual['id'] : null);

        require_once __DIR__ . '/../Views/admin/cargos.php';
    }

    public function salvarCargo()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $obreiroId = trim((string) ($_POST['obreiro_id'] ?? ''));
            $cargoCodigo = trim((string) ($_POST['cargo_codigo'] ?? ''));
            $observacao = trim((string) ($_POST['observacao'] ?? ''));
            $gestaoId = (int) ($_POST['gestao_id'] ?? 0);
            $inicioEm = trim((string) ($_POST['inicio_em'] ?? ''));

            if ($obreiroId !== '' && $cargoCodigo !== '') {
                try {
                    (new Cargo())->atribuirPorCodigo(
                        $cargoCodigo,
                        $obreiroId,
                        $observacao !== '' ? $observacao : null,
                        $gestaoId > 0 ? $gestaoId : null,
                        $inicioEm !== '' ? $inicioEm : null
                    );
                    $_SESSION['mensagem_sucesso'] = 'Titularidade atualizada com sucesso.';
                } catch (\Throwable $e) {
                    error_log('AdminController::salvarCargo - ' . $e->getMessage());
                    $_SESSION['mensagem_erro'] = 'Não foi possível atualizar o cargo agora. Confira os dados e tente novamente.';
                }
            } else {
                $_SESSION['mensagem_erro'] = 'Selecione um cargo e um obreiro para concluir a troca.';
            }
        }

        header('Location: /admin/cargos');
        exit;
    }

    public function salvarGestao()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $titulo = trim((string) ($_POST['titulo'] ?? ''));
            $inicioEm = trim((string) ($_POST['inicio_em'] ?? ''));
            $observacao = trim((string) ($_POST['observacao'] ?? ''));

            if ($titulo !== '' && $inicioEm !== '') {
                try {
                    (new Gestao())->criar($titulo, $inicioEm, $observacao !== '' ? $observacao : null);
                    $_SESSION['mensagem_sucesso'] = 'Gestão aberta com sucesso.';
                } catch (\Throwable $e) {
                    error_log('AdminController::salvarGestao - ' . $e->getMessage());
                    $_SESSION['mensagem_erro'] = 'Não foi possível abrir a gestão agora. Verifique as informações e tente novamente.';
                }
            } else {
                $_SESSION['mensagem_erro'] = 'Informe título e data de início para abrir a gestão.';
            }
        }

        header('Location: /admin/cargos');
        exit;
    }

    public function encerrarGestao()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $gestaoId = (int) ($_POST['gestao_id'] ?? 0);
            $encerradaEm = trim((string) ($_POST['encerrada_em'] ?? ''));

            if ($gestaoId > 0) {
                try {
                    (new Gestao())->encerrar($gestaoId, $encerradaEm !== '' ? $encerradaEm : null);
                    $_SESSION['mensagem_sucesso'] = 'Gestão encerrada com sucesso.';
                } catch (\Throwable $e) {
                    error_log('AdminController::encerrarGestao - ' . $e->getMessage());
                    $_SESSION['mensagem_erro'] = 'Não foi possível encerrar a gestão agora. Tente novamente.';
                }
            } else {
                $_SESSION['mensagem_erro'] = 'Gestão inválida para encerramento.';
            }
        }

        header('Location: /admin/cargos');
        exit;
    }

    public function configuracoesLoja()
    {
        $configuracao = (new ConfiguracaoLoja())->obter();
        require_once __DIR__ . '/../Views/admin/configuracoes_loja.php';
    }

    public function salvarConfiguracoesLoja()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                (new ConfiguracaoLoja())->salvar($_POST);
                $_SESSION['mensagem_sucesso'] = 'Parâmetros gerais da Loja atualizados com sucesso.';
            } catch (\Throwable $e) {
                error_log('AdminController::salvarConfiguracoesLoja - ' . $e->getMessage());
                $_SESSION['mensagem_erro'] = 'Não foi possível salvar os parâmetros da Loja agora. Tente novamente.';
            }
        }

        header('Location: /admin/loja');
        exit;
    }

    public function auditoriaCritica()
    {
        $itens = (new AuditoriaAdministrativa())->listarRecentes(80);
        require_once __DIR__ . '/../Views/admin/auditoria.php';
    }

    public function convitesAcesso()
    {
        $obreiroModel = new Obreiro();
        $conviteModel = new ConviteAcesso();

        $pendentes = $obreiroModel->listarPendentesAcesso();
        $convites = $conviteModel->listarRecentes(40);

        require_once __DIR__ . '/../Views/admin/convites.php';
    }

    public function gerarConviteAcesso()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $obreiroId = trim((string) ($_POST['obreiro_id'] ?? ''));
            $resultado = (new ConviteAcesso())->gerarParaObreiro($obreiroId);

            if (!($resultado['ok'] ?? false)) {
                $_SESSION['mensagem_erro'] = (string) ($resultado['erro'] ?? 'Não foi possível gerar o convite.');
            } else {
                $_SESSION['mensagem_sucesso'] = 'Convite gerado com sucesso.';
                $_SESSION['convite_gerado_link'] = (string) ($resultado['deep_link'] ?? '');
                $_SESSION['convite_gerado_expira_em'] = (string) ($resultado['expira_em'] ?? '');
            }
        }

        $returnTo = trim((string) ($_POST['return_to'] ?? ''));
        if ($returnTo === '' || $returnTo[0] !== '/') {
            $returnTo = '/admin/convites';
        }

        header('Location: ' . $returnTo);
        exit;
    }

    public function montarPayloadMiniapp(): array
    {
        $cargoModel = new Cargo();
        $gestaoModel = new Gestao();
        $obreiroModel = new Obreiro();
        $configuracao = (new ConfiguracaoLoja())->obter();
        $auditoria = (new AuditoriaAdministrativa())->listarRecentes(20);
        $gestaoAtual = $gestaoModel->obterAberta();
        $cargosResumo = $cargoModel->listarResumoCargos($gestaoAtual ? (int) $gestaoAtual['id'] : null);
        $gestoes = $gestaoModel->listar();
        $obreiros = $obreiroModel->getAllAtivos();

        return [
            'gestao_atual' => $gestaoAtual ? [
                'id' => (int) ($gestaoAtual['id'] ?? 0),
                'titulo' => (string) ($gestaoAtual['titulo'] ?? ''),
                'inicio_em' => (string) ($gestaoAtual['inicio_em'] ?? ''),
                'status' => (string) ($gestaoAtual['status'] ?? ''),
            ] : null,
            'gestoes' => array_map(static function (array $item): array {
                return [
                    'id' => (int) ($item['id'] ?? 0),
                    'titulo' => (string) ($item['titulo'] ?? ''),
                    'inicio_em' => (string) ($item['inicio_em'] ?? ''),
                    'encerrada_em' => (string) ($item['encerrada_em'] ?? ''),
                    'status' => (string) ($item['status'] ?? ''),
                ];
            }, $gestoes),
            'cargos' => array_map(static function (array $item): array {
                return [
                    'codigo' => (string) ($item['codigo'] ?? ''),
                    'nome_exibicao' => (string) Cargo::rotuloOficial((string) ($item['codigo'] ?? ''), (string) ($item['nome_exibicao'] ?? '')),
                    'titular_nome' => (string) ($item['titular_nome'] ?? ''),
                    'titular_cim' => (string) ($item['titular_cim'] ?? ''),
                    'inicio_em' => (string) ($item['inicio_em'] ?? ''),
                ];
            }, $cargosResumo),
            'obreiros' => array_map(static function (array $item): array {
                return [
                    'id' => (string) ($item['id'] ?? ''),
                    'nome' => (string) ($item['nome_historico'] ?? $item['nome'] ?? ''),
                    'cim' => (string) ($item['cim'] ?? ''),
                ];
            }, $obreiros),
            'configuracao' => [
                'nome_loja' => (string) ($configuracao['nome_loja'] ?? ''),
                'numero_loja' => (string) ($configuracao['numero_loja'] ?? ''),
                'cidade' => (string) ($configuracao['cidade'] ?? ''),
                'uf' => (string) ($configuracao['uf'] ?? ''),
                'rito' => (string) ($configuracao['rito'] ?? ''),
                'oriente' => (string) ($configuracao['oriente'] ?? ''),
                'mensalidade_valor_padrao' => (float) ($configuracao['mensalidade_valor_padrao'] ?? 150),
                'pix_chave_tipo' => (string) ($configuracao['pix_chave_tipo'] ?? 'CNPJ'),
                'pix_chave_valor' => (string) ($configuracao['pix_chave_valor'] ?? ''),
            ],
            'auditoria' => array_map(static function (array $item): array {
                return [
                    'origem' => (string) ($item['origem'] ?? ''),
                    'entidade' => (string) ($item['entidade'] ?? ''),
                    'acao' => (string) ($item['acao'] ?? ''),
                    'resumo' => (string) ($item['resumo'] ?? ''),
                    'criado_por_nome' => (string) ($item['criado_por_nome'] ?? ''),
                    'created_at' => (string) ($item['created_at'] ?? ''),
                ];
            }, $auditoria),
        ];
    }

    public function abrirGestaoMiniapp(string $titulo, string $inicioEm, ?string $observacao = null): array
    {
        $titulo = trim($titulo);
        $inicioEm = trim($inicioEm);
        if ($titulo === '' || $inicioEm === '') {
            return ['ok' => false, 'erro' => 'Titulo e data de inicio sao obrigatorios para abrir a gestao.'];
        }

        try {
            (new Gestao())->criar($titulo, $inicioEm, $observacao);
            return ['ok' => true, 'erro' => null];
        } catch (\Throwable $e) {
            error_log('AdminController::abrirGestaoMiniapp - ' . $e->getMessage());
            return ['ok' => false, 'erro' => 'Não foi possível abrir a gestão agora. Tente novamente em alguns minutos.'];
        }
    }

    public function encerrarGestaoMiniapp(int $gestaoId, ?string $encerradaEm = null): array
    {
        if ($gestaoId <= 0) {
            return ['ok' => false, 'erro' => 'Gestão inválida para encerramento.'];
        }

        try {
            (new Gestao())->encerrar($gestaoId, $encerradaEm);
            return ['ok' => true, 'erro' => null];
        } catch (\Throwable $e) {
            error_log('AdminController::encerrarGestaoMiniapp - ' . $e->getMessage());
            return ['ok' => false, 'erro' => 'Não foi possível encerrar a gestão agora. Tente novamente em alguns minutos.'];
        }
    }

    public function atribuirCargoMiniapp(string $cargoCodigo, string $obreiroId, ?int $gestaoId, ?string $inicioEm, ?string $observacao = null): array
    {
        $cargoCodigo = trim($cargoCodigo);
        $obreiroId = trim($obreiroId);
        if ($cargoCodigo === '' || $obreiroId === '') {
            return ['ok' => false, 'erro' => 'Cargo e obreiro sao obrigatorios para a atribuicao.'];
        }

        try {
            (new Cargo())->atribuirPorCodigo($cargoCodigo, $obreiroId, $observacao, $gestaoId, $inicioEm);
            return ['ok' => true, 'erro' => null];
        } catch (\Throwable $e) {
            error_log('AdminController::atribuirCargoMiniapp - ' . $e->getMessage());
            return ['ok' => false, 'erro' => 'Não foi possível atualizar o cargo agora. Tente novamente em alguns minutos.'];
        }
    }

    public function salvarConfiguracaoMiniapp(array $input): array
    {
        try {
            $configModel = new ConfiguracaoLoja();
            $atual = $configModel->obter();
            $configModel->salvar(array_merge($atual, $input));
            return ['ok' => true, 'erro' => null];
        } catch (\Throwable $e) {
            error_log('AdminController::salvarConfiguracaoMiniapp - ' . $e->getMessage());
            return ['ok' => false, 'erro' => 'Não foi possível salvar as configurações agora. Tente novamente em alguns minutos.'];
        }
    }
}
