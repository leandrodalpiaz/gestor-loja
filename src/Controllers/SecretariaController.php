<?php

namespace App\Controllers;

use App\Models\Obreiro;
use App\Models\Balaustre;
use App\Models\Cargo;
use App\Models\ConfiguracaoLoja;
use App\Models\PublicacaoSessao;
use App\Models\PublicacaoSecretaria;
use App\Models\RelatorioSecretariaAnual;
use App\Models\Sessao;
use App\Models\TrabalhoSessao;

class SecretariaController
{
    private const LOJAS_VISITANTES_FREQUENTES = [
        'Fraternidade 1234',
        'Luz e Verdade 0001',
        'Uniao e Trabalho 0002',
        'Ordem e Progresso 0003',
        'Estrela do Oriente 0004',
        'Cavaleiros da Arte Real 0005',
    ];

    private function resolveRedirectDestino(string $padrao = '/secretaria'): string
    {
        $destino = trim((string) ($_POST['return_to'] ?? $_GET['return_to'] ?? ''));
        if ($destino === '' || $destino[0] !== '/') {
            return $padrao;
        }
        return $destino;
    }

    private function normalizarTextoComparacao(?string $valor): string
    {
        $valor = strtolower(trim((string) $valor));
        $valor = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $valor) ?: $valor;
        $valor = preg_replace('/[^a-z0-9]+/', ' ', $valor) ?? '';
        return trim($valor);
    }

    private function carregarNominataOficial(): array
    {
        $nominataMap = [];
        try {
            $cargoModel = new Cargo();
            foreach ($cargoModel->listarResumoCargos() as $cargoResumo) {
                $codigo = strtoupper((string) ($cargoResumo['codigo'] ?? ''));
                if ($codigo === '') {
                    continue;
                }
                $nominataMap[$codigo] = trim((string) ($cargoResumo['titular_nome'] ?? ''));
            }
        } catch (\Throwable $e) {
            $nominataMap = [];
        }

        return $nominataMap;
    }

    private function obterCargosSessaoBase(array $nominataMap): array
    {
        $base = [
            ['codigo' => 'VENERAVEL', 'label' => 'Veneravel Mestre'],
            ['codigo' => 'PRIMEIRO_VIGILANTE', 'label' => '1 Vigilante'],
            ['codigo' => 'SEGUNDO_VIGILANTE', 'label' => '2 Vigilante'],
            ['codigo' => 'ORADOR', 'label' => 'Orador'],
            ['codigo' => 'GUARDA_DA_LEI', 'label' => 'Guarda da Lei'],
            ['codigo' => 'SECRETARIO', 'label' => 'Secretario'],
            ['codigo' => 'TESOUREIRO', 'label' => 'Tesoureiro'],
            ['codigo' => 'CHANCELER', 'label' => 'Chanceler'],
            ['codigo' => 'MESTRE_BANQUETES', 'label' => 'Mestre de Banquetes'],
            ['codigo' => 'MESTRE_DE_CERIMONIAS', 'label' => 'Mestre de Cerimonias'],
            ['codigo' => 'GUARDA_DO_TEMPLO', 'label' => 'Guarda do Templo'],
            ['codigo' => 'HOSPITALEIRO', 'label' => 'Hospitaleiro'],
            ['codigo' => 'PRIMEIRO_DIACONO', 'label' => '1 Diacono'],
            ['codigo' => 'SEGUNDO_DIACONO', 'label' => '2 Diacono'],
            ['codigo' => 'ARQUITETO', 'label' => 'Arquiteto'],
            ['codigo' => 'MESTRE_DE_HARMONIA', 'label' => 'Mestre de Harmonia'],
            ['codigo' => 'PORTA_BANDEIRA', 'label' => 'Porta Bandeira'],
            ['codigo' => 'PORTA_ESPADA', 'label' => 'Porta Espada'],
            ['codigo' => 'COBRIDOR_INTERNO', 'label' => 'Cobridor Interno'],
            ['codigo' => 'COBRIDOR_EXTERNO', 'label' => 'Cobridor Externo'],
        ];

        $vistos = [];
        foreach ($base as &$item) {
            $codigo = strtoupper((string) ($item['codigo'] ?? ''));
            $item['titular_oficial'] = (string) ($nominataMap[$codigo] ?? '');
            $vistos[$codigo] = true;
        }
        unset($item);

        foreach ($nominataMap as $codigo => $titular) {
            if (isset($vistos[$codigo])) {
                continue;
            }
            $base[] = [
                'codigo' => $codigo,
                'label' => ucwords(strtolower(str_replace('_', ' ', $codigo))),
                'titular_oficial' => (string) $titular,
            ];
        }

        return $base;
    }

    public function index(): void
    {
        $sessaoModel = new Sessao();
        $obreiroModel = new Obreiro();
        $trabalhoModel = new TrabalhoSessao();
        $publicacaoModel = new PublicacaoSecretaria();
        $balaustreModel = new Balaustre();

        $proximaSessao = $sessaoModel->obterProximaSessao();
        $sessoes = $sessaoModel->listarFuturas(8);
        $obreiros = $obreiroModel->getAllAtivos();
        $trabalhos = $trabalhoModel->listarRecentes(8);
        $publicacoes = $publicacaoModel->listarRecentes(8);
        $balaustres = $balaustreModel->listarRecentes(8);
        $configuracaoLoja = (new ConfiguracaoLoja())->obter();
        $sessaoRascunho = $_SESSION['secretaria_sessao_rascunho'] ?? null;
        $sessaoDuplicada = null;
        $resumoRascunhoSessao = null;
        $acoesConfirmacaoRascunho = [];
        if (is_array($sessaoRascunho)) {
            $sessaoDuplicada = $sessaoModel->buscarDuplicidade($sessaoRascunho);
            $resumoRascunhoSessao = $sessaoModel->comporResumoPublicacao($sessaoRascunho, $configuracaoLoja);
            $acoesConfirmacaoRascunho = $sessaoModel->obterAcoesConfirmacao($sessaoRascunho);
        }
        $nominataOficialMap = $this->carregarNominataOficial();
        $cargosSessaoBase = $this->obterCargosSessaoBase($nominataOficialMap);
        $lojasVisitantesFrequentes = self::LOJAS_VISITANTES_FREQUENTES;

        foreach ($balaustres as &$balaustre) {
            $capturado = $balaustre['dados_capturados'] ?? null;
            if (is_string($capturado)) {
                $decoded = json_decode($capturado, true);
                $capturado = is_array($decoded) ? $decoded : null;
            }

            $palavras = is_array($capturado) ? ($capturado['palavra_bem_ordem']['visitantes'] ?? []) : [];
            $cargos = is_array($capturado) ? ($capturado['cargos_sessao'] ?? []) : [];
            $adHoc = 0;
            foreach ($cargos as $cargoSessao) {
                if (($cargoSessao['tipo_ocupacao'] ?? '') === 'ad_hoc') {
                    $adHoc++;
                }
            }

            $balaustre['resumo_palavra_bem_ordem'] = is_array($palavras) ? count($palavras) : 0;
            $balaustre['resumo_cargos_ad_hoc'] = $adHoc;
        }
        unset($balaustre);
        $usuarioId = (string) ($_SESSION['usuario_id'] ?? '');
        $elegibilidadeVoto = $balaustreModel->listarElegibilidadeDoObreiroNosBalaustres(
            $usuarioId,
            array_map(static fn ($row) => (int) ($row['id'] ?? 0), $balaustres)
        );
        $roles = array_values(array_unique(array_map(
            static fn ($role) => strtolower((string) $role),
            $_SESSION['usuario_cargos'] ?? [$_SESSION['usuario_cargo'] ?? '']
        )));
        $podeOperarSecretaria = in_array('secretario', $roles, true) || in_array('admin', $roles, true);
        $podeAbrirVotacao = in_array('veneravel', $roles, true) || in_array('admin', $roles, true);

        $resumo = [
            'obreiros_ativos' => count($obreiros),
            'sessoes_futuras' => count($sessoes),
            'trabalhos_pendentes' => count(array_filter($trabalhos, static fn ($item) => ($item['status_envio_potencia'] ?? '') === 'pendente')),
            'publicacoes_rascunho' => count(array_filter($publicacoes, static fn ($item) => ($item['status_publicacao'] ?? '') === 'rascunho')),
            'balaustres_aptos' => count(array_filter($balaustres, static fn ($item) => ($item['status'] ?? '') === 'apto_votacao')),
        ];

        require_once __DIR__ . '/../Views/secretaria/index.php';
    }

    public function votacao(): void
    {
        $balaustreModel = new Balaustre();
        $usuarioId = (string) ($_SESSION['usuario_id'] ?? '');
        $roles = array_values(array_unique(array_map(
            static fn ($role) => strtolower((string) $role),
            $_SESSION['usuario_cargos'] ?? [$_SESSION['usuario_cargo'] ?? '']
        )));

        $podeAcompanharTodas = in_array('secretario', $roles, true)
            || in_array('veneravel', $roles, true)
            || in_array('admin', $roles, true);

        $votacoesAbertas = $balaustreModel->listarAbertosParaObreiro($usuarioId, $podeAcompanharTodas);
        $elegibilidadeVoto = $balaustreModel->listarElegibilidadeDoObreiroNosBalaustres(
            $usuarioId,
            array_map(static fn ($row) => (int) ($row['id'] ?? 0), $votacoesAbertas)
        );

        require_once __DIR__ . '/../Views/secretaria/votacao.php';
    }

    public function relatorioAnual(): void
    {
        $ano = (int) ($_GET['ano'] ?? date('Y'));
        if ($ano < 2000 || $ano > 2100) {
            $ano = (int) date('Y');
        }

        $relatorio = (new RelatorioSecretariaAnual())->montar($ano);
        $anosDisponiveis = [];
        $anoAtual = (int) date('Y');
        for ($i = $anoAtual; $i >= max(2000, $anoAtual - 8); $i--) {
            $anosDisponiveis[] = $i;
        }

        require_once __DIR__ . '/../Views/secretaria/relatorio_anual.php';
    }

    public function salvarSessao(): void
    {
        $this->revisarSessao();
    }

    public function revisarSessao(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /secretaria');
            exit;
        }

        $sessaoModel = new Sessao();
        $payload = [
            'data_hora_inicio' => trim((string) ($_POST['data_hora_inicio'] ?? '')),
            'data_hora_fim' => trim((string) ($_POST['data_hora_fim'] ?? '')),
            'grau_sessao' => trim((string) ($_POST['grau_sessao'] ?? '')),
            'grau_personalizado' => trim((string) ($_POST['grau_personalizado'] ?? '')),
            'tipo_sessao_principal' => trim((string) ($_POST['tipo_sessao_principal'] ?? '')),
            'tipo_sessao_subtipo' => trim((string) ($_POST['tipo_sessao_subtipo'] ?? '')),
            'tipo_sessao_personalizado' => trim((string) ($_POST['tipo_sessao_personalizado'] ?? '')),
            'traje_tipo' => trim((string) ($_POST['traje_tipo'] ?? '')),
            'traje_personalizado' => trim((string) ($_POST['traje_personalizado'] ?? '')),
            'agape_modalidade' => trim((string) ($_POST['agape_modalidade'] ?? 'nao_havera')),
            'agape_modelo_financeiro' => trim((string) ($_POST['agape_modelo_financeiro'] ?? 'oficial_loja')),
            'agape_valor' => trim((string) ($_POST['agape_valor'] ?? '')),
            'gestao_referencia' => trim((string) ($_POST['gestao_referencia'] ?? '')),
            'natureza_sessao' => trim((string) ($_POST['natureza_sessao'] ?? '')),
            'formato_sessao' => trim((string) ($_POST['formato_sessao'] ?? '')),
            'finalidade_ritual' => trim((string) ($_POST['finalidade_ritual'] ?? '')),
            'templo_local' => trim((string) ($_POST['templo_local'] ?? '')),
            'sessao_a_campo' => isset($_POST['sessao_a_campo']),
            'conta_relatorio_potencia' => isset($_POST['conta_relatorio_potencia']),
            'observacao_relatorio' => trim((string) ($_POST['observacao_relatorio'] ?? '')),
            'titulo' => trim((string) ($_POST['titulo'] ?? '')),
            'ordem_dia' => trim((string) ($_POST['ordem_dia'] ?? '')),
            'observacao_interna' => trim((string) ($_POST['observacao_interna'] ?? '')),
        ];
        $payload = $this->normalizarRascunhoSessao($payload);
        $_SESSION['secretaria_sessao_rascunho'] = $payload;
        $_SESSION['mensagem_sucesso'] = 'Sessao preparada para revisao final. Confira o resumo antes de publicar.';

        header('Location: /secretaria');
        exit;
    }

    public function publicarSessaoRascunho(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /secretaria');
            exit;
        }

        $rascunho = $_SESSION['secretaria_sessao_rascunho'] ?? null;
        if (!is_array($rascunho)) {
            $_SESSION['mensagem_erro'] = 'Nao existe rascunho de sessao aguardando revisao.';
            header('Location: /secretaria');
            exit;
        }

        $sessaoModel = new Sessao();
        $configuracaoLoja = (new ConfiguracaoLoja())->obter();
        $autorId = (string) ($_SESSION['usuario_id'] ?? '');
        $autorId = $autorId !== '' ? $autorId : null;
        $sessaoId = $sessaoModel->criar($rascunho, $autorId);

        if (!$sessaoId) {
            $_SESSION['mensagem_erro'] = 'Nao foi possivel persistir a sessao revisada.';
            header('Location: /secretaria');
            exit;
        }

        $sessaoModel->marcarPublicada($sessaoId, $autorId, 'Publicacao confirmada pela Secretaria apos revisao final.');
        $sessaoCriada = $sessaoModel->findById($sessaoId) ?? $rascunho;
        $conteudo = $sessaoModel->comporResumoPublicacao($sessaoCriada, $configuracaoLoja);
        (new PublicacaoSessao())->registrar($sessaoId, 'resumo_proxima_sessao', 'erp', $conteudo, $autorId);

        unset($_SESSION['secretaria_sessao_rascunho']);
        $_SESSION['mensagem_sucesso'] = 'Sessao publicada com sucesso e pronta para confirmacoes.';
        header('Location: /secretaria');
        exit;
    }

    public function cancelarRascunhoSessao(): void
    {
        unset($_SESSION['secretaria_sessao_rascunho']);
        $_SESSION['mensagem_sucesso'] = 'Rascunho da sessao descartado.';
        header('Location: /secretaria');
        exit;
    }

    public function salvarTrabalho(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /secretaria');
            exit;
        }

        $trabalhoModel = new TrabalhoSessao();
        $autorId = (string) ($_SESSION['usuario_id'] ?? '');
        $ok = $trabalhoModel->criar($_POST, $autorId !== '' ? $autorId : null);

        $_SESSION[$ok ? 'mensagem_sucesso' : 'mensagem_erro'] = $ok
            ? 'Trabalho registrado com sucesso.'
            : 'Nao foi possivel registrar o trabalho.';

        header('Location: /secretaria');
        exit;
    }

    public function salvarPublicacao(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /secretaria');
            exit;
        }

        $publicacaoModel = new PublicacaoSecretaria();
        $autorId = (string) ($_SESSION['usuario_id'] ?? '');
        $ok = $publicacaoModel->criar($_POST, $autorId !== '' ? $autorId : null);

        $_SESSION[$ok ? 'mensagem_sucesso' : 'mensagem_erro'] = $ok
            ? 'Publicacao registrada com sucesso.'
            : 'Nao foi possivel registrar a publicacao.';

        header('Location: /secretaria');
        exit;
    }

    public function salvarBalaustre(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /secretaria');
            exit;
        }

        $sessaoId = (int) ($_POST['sessao_id'] ?? 0);
        if ($sessaoId <= 0) {
            $_SESSION['mensagem_erro'] = 'Selecione a sessao para salvar o balaustre.';
            header('Location: /secretaria');
            exit;
        }

        $model = new Balaustre();
        $autorId = (string) ($_SESSION['usuario_id'] ?? '');
        $ok = $model->salvarPorSessao($sessaoId, $_POST, $autorId !== '' ? $autorId : null);
        $_SESSION[$ok ? 'mensagem_sucesso' : 'mensagem_erro'] = $ok
            ? 'Balaustre salvo em rascunho.'
            : 'Nao foi possivel salvar o balaustre.';

        header('Location: /secretaria');
        exit;
    }

    public function marcarBalaustreApto(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /secretaria');
            exit;
        }

        $balaustreId = (int) ($_POST['balaustre_id'] ?? 0);
        if ($balaustreId <= 0) {
            $_SESSION['mensagem_erro'] = 'Balaustre invalido para marcar como apto.';
            header('Location: /secretaria');
            exit;
        }

        $model = new Balaustre();
        $autorId = (string) ($_SESSION['usuario_id'] ?? '');
        $ok = $model->marcarAptoVotacao($balaustreId, $autorId !== '' ? $autorId : null);
        $_SESSION[$ok ? 'mensagem_sucesso' : 'mensagem_erro'] = $ok
            ? 'Balaustre apto para votacao. O Veneravel Mestre ja pode abrir a votacao.'
            : 'Nao foi possivel marcar o balaustre como apto.';

        header('Location: /secretaria');
        exit;
    }

    public function abrirVotacaoBalaustre(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /secretaria');
            exit;
        }

        $balaustreId = (int) ($_POST['balaustre_id'] ?? 0);
        if ($balaustreId <= 0) {
            $_SESSION['mensagem_erro'] = 'Balaustre invalido para abrir votacao.';
            header('Location: /secretaria');
            exit;
        }

        $model = new Balaustre();
        $autorId = (string) ($_SESSION['usuario_id'] ?? '');
        $resultado = $model->abrirVotacao($balaustreId, $autorId !== '' ? $autorId : null);
        if (($resultado['ok'] ?? false) === true) {
            $_SESSION['mensagem_sucesso'] = 'Votacao aberta com sucesso pelo Veneravel Mestre. Votantes aptos: ' . (int) ($resultado['total_votantes'] ?? 0) . '.';
        } else {
            $_SESSION['mensagem_erro'] = (string) ($resultado['erro'] ?? 'Nao foi possivel abrir votacao.');
        }

        header('Location: /secretaria');
        exit;
    }

    public function votarBalaustre(): void
    {
        $destino = $this->resolveRedirectDestino('/secretaria');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . $destino);
            exit;
        }

        $balaustreId = (int) ($_POST['balaustre_id'] ?? 0);
        $voto = trim((string) ($_POST['voto'] ?? ''));
        $justificativa = trim((string) ($_POST['justificativa'] ?? ''));
        $obreiroId = (string) ($_SESSION['usuario_id'] ?? '');

        if ($balaustreId <= 0 || $obreiroId === '') {
            $_SESSION['mensagem_erro'] = 'Dados insuficientes para registrar voto.';
            header('Location: ' . $destino);
            exit;
        }

        $model = new Balaustre();
        $resultado = $model->registrarVotoPorBalaustre(
            $balaustreId,
            $obreiroId,
            $voto,
            $justificativa !== '' ? $justificativa : null
        );
        if (($resultado['ok'] ?? false) === true) {
            $_SESSION['mensagem_sucesso'] = 'Voto registrado com sucesso.';
        } else {
            $_SESSION['mensagem_erro'] = (string) ($resultado['erro'] ?? 'Nao foi possivel registrar voto.');
        }

        header('Location: ' . $destino);
        exit;
    }

    public function encerrarVotacaoBalaustre(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /secretaria');
            exit;
        }

        $balaustreId = (int) ($_POST['balaustre_id'] ?? 0);
        if ($balaustreId <= 0) {
            $_SESSION['mensagem_erro'] = 'Balaustre invalido para encerrar votacao.';
            header('Location: /secretaria');
            exit;
        }

        $model = new Balaustre();
        $resultado = $model->encerrarVotacaoPorBalaustre($balaustreId);
        if (($resultado['ok'] ?? false) === true) {
            $_SESSION['mensagem_sucesso'] = 'Votacao encerrada. Resultado do balaustre: ' . (string) ($resultado['status'] ?? 'indefinido') . '.';
        } else {
            $_SESSION['mensagem_erro'] = (string) ($resultado['erro'] ?? 'Nao foi possivel encerrar votacao.');
        }

        header('Location: /secretaria');
        exit;
    }

    private function normalizarRascunhoSessao(array $payload): array
    {
        $configLoja = (new ConfiguracaoLoja())->obter();
        $payload['templo_local'] = trim((string) ($payload['templo_local'] ?? '')) !== ''
            ? trim((string) $payload['templo_local'])
            : trim((string) ($configLoja['nome_templo'] ?? ''));
        $payload['natureza_sessao'] = $payload['natureza_sessao'] !== '' ? $payload['natureza_sessao'] : 'ordinaria';
        $payload['formato_sessao'] = $payload['formato_sessao'] !== '' ? $payload['formato_sessao'] : 'templo';
        $payload['finalidade_ritual'] = $payload['finalidade_ritual'] !== '' ? $payload['finalidade_ritual'] : 'economica';
        $payload['gestao_referencia'] = $payload['gestao_referencia'] !== '' ? $payload['gestao_referencia'] : date('Y');
        $payload['sessao_branca'] = false;
        $payload['agape_modelo_financeiro'] = in_array(
            $payload['agape_modelo_financeiro'] ?? '',
            Sessao::AGAPE_MODELOS_FINANCEIROS,
            true
        ) ? $payload['agape_modelo_financeiro'] : 'oficial_loja';
        $payload['agape_ativo'] = in_array($payload['agape_modalidade'], ['gratuito', 'pago'], true);
        return $payload;
    }
}
