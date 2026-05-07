<?php

namespace App\Controllers;

class PwaCargosController
{
    public static function modules(): array
    {
        return [
            'secretaria' => [
                'title' => 'Secretaria',
                'permission' => 'secretaria.manage',
                'summary' => 'Agenda, sessoes, trabalhos, publicacoes, balaustres e relatorios.',
                'primary' => '/pwa/secretaria',
                'actions' => [
                    ['label' => 'Secretaria PWA', 'href' => '/pwa/secretaria', 'kind' => 'pwa'],
                    ['label' => 'Painel da Secretaria', 'href' => '/secretaria', 'kind' => 'desktop'],
                    ['label' => 'Sessoes', 'href' => '/secretaria/sessoes', 'kind' => 'desktop'],
                    ['label' => 'Balaustres', 'href' => '/secretaria/balaustres', 'kind' => 'desktop'],
                    ['label' => 'Trabalhos e publicacoes', 'href' => '/secretaria/trabalhos-publicacoes', 'kind' => 'desktop'],
                    ['label' => 'Comunicados PWA', 'href' => '/pwa/comunicacao', 'kind' => 'pwa'],
                    ['label' => 'Relatorio anual', 'href' => '/secretaria/relatorio-anual', 'kind' => 'desktop'],
                ],
            ],
            'tesouraria' => [
                'title' => 'Tesouraria',
                'permission' => 'tesouraria.manage',
                'summary' => 'Caixa, comprovantes, regularidade, fechamento, obrigacoes e relatorios.',
                'primary' => '/pwa/comprovantes',
                'actions' => [
                    ['label' => 'Comprovantes PWA', 'href' => '/pwa/comprovantes', 'kind' => 'pwa'],
                    ['label' => 'Caixa', 'href' => '/tesouraria/caixa', 'kind' => 'desktop'],
                    ['label' => 'Sessoes financeiras', 'href' => '/tesouraria/sessoes', 'kind' => 'desktop'],
                    ['label' => 'Regularidade', 'href' => '/tesouraria/regularidade', 'kind' => 'desktop'],
                    ['label' => 'Fechamento', 'href' => '/tesouraria/fechamento', 'kind' => 'desktop'],
                    ['label' => 'Obrigacoes', 'href' => '/tesouraria/obrigacoes', 'kind' => 'desktop'],
                    ['label' => 'Relatorio de gestao', 'href' => '/tesouraria/relatorio-gestao', 'kind' => 'desktop'],
                ],
            ],
            'biblioteca-gestao' => [
                'title' => 'Biblioteca',
                'permission' => 'biblioteca.manage',
                'summary' => 'Acervo, cadastro, emprestimos, devolucoes e operacao do bibliotecario.',
                'primary' => '/pwa/biblioteca',
                'actions' => [
                    ['label' => 'Catalogo PWA', 'href' => '/pwa/biblioteca', 'kind' => 'pwa'],
                    ['label' => 'Adicionar PWA', 'href' => '/pwa/biblioteca/adicionar', 'kind' => 'pwa'],
                    ['label' => 'Classificar PWA', 'href' => '/pwa/biblioteca/classificar', 'kind' => 'pwa'],
                    ['label' => 'Emprestimos PWA', 'href' => '/pwa/biblioteca/emprestimos', 'kind' => 'pwa'],
                    ['label' => 'Gestao do acervo', 'href' => '/biblioteca', 'kind' => 'desktop'],
                ],
            ],
            'veneravel' => [
                'title' => 'Veneravel Mestre',
                'permission' => 'veneravel.manage',
                'summary' => 'Decisoes de sessao, votacao de balaustres e pendencias executivas.',
                'primary' => '/veneravel',
                'actions' => [
                    ['label' => 'Painel do Veneravel', 'href' => '/veneravel', 'kind' => 'desktop'],
                    ['label' => 'Publicar sessao', 'href' => '/veneravel/sessoes/publicar', 'kind' => 'desktop'],
                    ['label' => 'Cancelar sessao', 'href' => '/veneravel/sessoes/cancelar', 'kind' => 'desktop'],
                    ['label' => 'Reabrir sessao', 'href' => '/veneravel/sessoes/reabrir', 'kind' => 'desktop'],
                    ['label' => 'Marcar realizada', 'href' => '/veneravel/sessoes/realizar', 'kind' => 'desktop'],
                ],
            ],
            'chancelaria' => [
                'title' => 'Chancelaria',
                'permission' => 'chancelaria.manage',
                'summary' => 'Check-in, nominata, confirmados, visitantes e conteudos da chancelaria.',
                'primary' => '/chanceler/sessao',
                'actions' => [
                    ['label' => 'Chancelaria PWA', 'href' => '/pwa/chancelaria', 'kind' => 'pwa'],
                    ['label' => 'Efemerides PWA', 'href' => '/pwa/chancelaria/efemerides', 'kind' => 'pwa'],
                    ['label' => 'Painel de sessao', 'href' => '/chanceler/sessao', 'kind' => 'desktop'],
                    ['label' => 'Registrar presenca', 'href' => '/chanceler/sessao/presenca', 'kind' => 'desktop'],
                    ['label' => 'Registrar visitante', 'href' => '/chanceler/sessao/visitante', 'kind' => 'desktop'],
                    ['label' => 'Comunicacao PWA', 'href' => '/pwa/comunicacao', 'kind' => 'pwa'],
                ],
            ],
            'hospitaleiro' => [
                'title' => 'Hospitaleiro',
                'permission' => 'hospitaleiro.manage',
                'summary' => 'Ocorrencias assistenciais, visitas, status e encaminhamentos.',
                'primary' => '/assistencia',
                'actions' => [
                    ['label' => 'Painel de assistencia', 'href' => '/assistencia', 'kind' => 'desktop'],
                    ['label' => 'Nova ocorrencia', 'href' => '/assistencia', 'kind' => 'desktop'],
                    ['label' => 'Pendencias', 'href' => '/assistencia', 'kind' => 'desktop'],
                ],
            ],
            'primeiro-vigilante' => [
                'title' => 'Primeiro Vigilante',
                'permission' => 'vigilancia.primeiro.manage',
                'summary' => 'Aprendizes, trilhas, etapas, leituras, devolutivas e certificados.',
                'primary' => '/primeiro-vigilante',
                'actions' => [
                    ['label' => 'Painel do cargo', 'href' => '/primeiro-vigilante', 'kind' => 'desktop'],
                    ['label' => 'Meu aprendizado', 'href' => '/meu-aprendizado', 'kind' => 'desktop'],
                    ['label' => 'Biblioteca formativa', 'href' => '/pwa/biblioteca/classificar', 'kind' => 'pwa'],
                ],
            ],
            'segundo-vigilante' => [
                'title' => 'Segundo Vigilante',
                'permission' => 'vigilancia.segundo.manage',
                'summary' => 'Companheiros, trilhas, docencia, exaltacao, historico e certificados.',
                'primary' => '/segundo-vigilante',
                'actions' => [
                    ['label' => 'Painel do cargo', 'href' => '/segundo-vigilante', 'kind' => 'desktop'],
                    ['label' => 'Meu companheirismo', 'href' => '/meu-companheirismo', 'kind' => 'desktop'],
                    ['label' => 'Biblioteca formativa', 'href' => '/pwa/biblioteca/classificar', 'kind' => 'pwa'],
                ],
            ],
            'orador' => [
                'title' => 'Orador',
                'permission' => 'orador.view',
                'summary' => 'Resumo da sessao, visitantes, pauta, cargos e lembretes operacionais.',
                'primary' => '/orador',
                'actions' => [
                    ['label' => 'Painel do Orador', 'href' => '/orador', 'kind' => 'desktop'],
                    ['label' => 'Comunicados PWA', 'href' => '/pwa/comunicacao', 'kind' => 'pwa'],
                ],
            ],
            'mestre-banquetes' => [
                'title' => 'Mestre de Banquetes',
                'permission' => 'mestre_banquetes.manage',
                'summary' => 'Agape, confirmados, lista nominal, observacoes e fechamento operacional.',
                'primary' => '/mestre-banquetes',
                'actions' => [
                    ['label' => 'Painel de Banquetes', 'href' => '/mestre-banquetes', 'kind' => 'desktop'],
                    ['label' => 'Operacao do agape', 'href' => '/mestre-banquetes', 'kind' => 'desktop'],
                ],
            ],
            'mestre-harmonia' => [
                'title' => 'Mestre de Harmonia',
                'permission' => 'mestre_harmonia.manage',
                'summary' => 'Player ritual, base musical, operador, etapas e controle de execucao.',
                'primary' => '/mestre-harmonia',
                'actions' => [
                    ['label' => 'Painel de Harmonia', 'href' => '/mestre-harmonia', 'kind' => 'desktop'],
                    ['label' => 'Scan da base musical', 'href' => '/api/mestre-harmonia/scan', 'kind' => 'api'],
                ],
            ],
            'administracao' => [
                'title' => 'Administracao',
                'permission' => 'admin.cargos.view',
                'summary' => 'Cargos, gestoes, acessos, convites, parametros e auditoria.',
                'primary' => '/admin/cargos',
                'actions' => [
                    ['label' => 'Cargos', 'href' => '/admin/cargos', 'kind' => 'desktop'],
                    ['label' => 'Acessos', 'href' => '/admin/acessos', 'kind' => 'desktop'],
                    ['label' => 'Convites', 'href' => '/admin/convites', 'kind' => 'desktop'],
                    ['label' => 'Loja', 'href' => '/admin/loja', 'kind' => 'desktop'],
                    ['label' => 'Auditoria', 'href' => '/admin/auditoria', 'kind' => 'desktop'],
                ],
            ],
        ];
    }

    public function show(string $slug): void
    {
        $modules = self::modules();
        $module = $modules[$slug] ?? null;
        if ($module === null) {
            http_response_code(404);
            echo 'Modulo PWA nao encontrado.';
            return;
        }

        require __DIR__ . '/../Views/pwa/cargo_modulo.php';
    }
}
