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
                'summary' => 'Agenda, sessões, trabalhos, publicações, balaustres e relatórios.',
                'primary' => '/pwa/secretaria',
                'actions' => [
                    ['label' => 'Secretaria PWA', 'href' => '/pwa/secretaria', 'kind' => 'pwa'],
                    ['label' => 'Painel da Secretaria', 'href' => '/secretaria', 'kind' => 'desktop'],
                    ['label' => 'Sessões', 'href' => '/secretaria/sessoes', 'kind' => 'desktop'],
                    ['label' => 'Balaustres', 'href' => '/secretaria/balaustres', 'kind' => 'desktop'],
                    ['label' => 'Trabalhos e publicações', 'href' => '/secretaria/trabalhos-publicacoes', 'kind' => 'desktop'],
                    ['label' => 'Comunicados PWA', 'href' => '/pwa/comunicacao', 'kind' => 'pwa'],
                    ['label' => 'Relatório anual', 'href' => '/secretaria/relatorio-anual', 'kind' => 'desktop'],
                ],
            ],
            'tesouraria' => [
                'title' => 'Tesouraria',
                'permission' => 'tesouraria.manage',
                'summary' => 'Caixa, comprovantes, regularidade, fechamento, obrigações e relatórios.',
                'primary' => '/pwa/comprovantes',
                'actions' => [
                    ['label' => 'Comprovantes PWA', 'href' => '/pwa/comprovantes', 'kind' => 'pwa'],
                    ['label' => 'Caixa', 'href' => '/tesouraria/caixa', 'kind' => 'desktop'],
                    ['label' => 'Sessões financeiras', 'href' => '/tesouraria/sessoes', 'kind' => 'desktop'],
                    ['label' => 'Regularidade', 'href' => '/tesouraria/regularidade', 'kind' => 'desktop'],
                    ['label' => 'Fechamento', 'href' => '/tesouraria/fechamento', 'kind' => 'desktop'],
                    ['label' => 'Obrigações', 'href' => '/tesouraria/obrigacoes', 'kind' => 'desktop'],
                    ['label' => 'Relatório de gestão', 'href' => '/tesouraria/relatorio-gestao', 'kind' => 'desktop'],
                ],
            ],
            'biblioteca-gestao' => [
                'title' => 'Biblioteca',
                'permission' => 'biblioteca.manage',
                'summary' => 'Acervo, cadastro, empréstimos, devoluções e operação do bibliotecário.',
                'primary' => '/pwa/biblioteca',
                'actions' => [
                    ['label' => 'Catálogo PWA', 'href' => '/pwa/biblioteca', 'kind' => 'pwa'],
                    ['label' => 'Adicionar PWA', 'href' => '/pwa/biblioteca/adicionar', 'kind' => 'pwa'],
                    ['label' => 'Classificar PWA', 'href' => '/pwa/biblioteca/classificar', 'kind' => 'pwa'],
                    ['label' => 'Empréstimos PWA', 'href' => '/pwa/biblioteca/emprestimos', 'kind' => 'pwa'],
                    ['label' => 'Gestão do acervo', 'href' => '/biblioteca', 'kind' => 'desktop'],
                ],
            ],
            'veneravel' => [
                'title' => 'Venerável Mestre',
                'permission' => 'veneravel.manage',
                'summary' => 'Decisões de sessão, votação de balaustres e pendências executivas.',
                'primary' => '/pwa/veneravel',
                'actions' => [
                    ['label' => 'Painel do Venerável', 'href' => '/pwa/veneravel', 'kind' => 'pwa'],
                    ['label' => 'Publicar sessão', 'href' => '/veneravel/sessoes/publicar', 'kind' => 'desktop'],
                    ['label' => 'Cancelar sessão', 'href' => '/veneravel/sessoes/cancelar', 'kind' => 'desktop'],
                    ['label' => 'Reabrir sessão', 'href' => '/veneravel/sessoes/reabrir', 'kind' => 'desktop'],
                    ['label' => 'Marcar realizada', 'href' => '/veneravel/sessoes/realizar', 'kind' => 'desktop'],
                ],
            ],
            'chancelaria' => [
                'title' => 'Chancelaria',
                'permission' => 'chancelaria.manage',
                'summary' => 'Check-in, nominata, confirmados, visitantes e conteúdos da chancelaria.',
                'primary' => '/chanceler/sessao',
                'actions' => [
                    ['label' => 'Chancelaria PWA', 'href' => '/pwa/chancelaria', 'kind' => 'pwa'],
                    ['label' => 'Efemérides PWA', 'href' => '/pwa/chancelaria/efemerides', 'kind' => 'pwa'],
                    ['label' => 'Painel de sessão', 'href' => '/chanceler/sessao', 'kind' => 'desktop'],
                    ['label' => 'Registrar presença', 'href' => '/chanceler/sessao/presenca', 'kind' => 'desktop'],
                    ['label' => 'Registrar visitante', 'href' => '/chanceler/sessao/visitante', 'kind' => 'desktop'],
                    ['label' => 'Comunicação PWA', 'href' => '/pwa/comunicacao', 'kind' => 'pwa'],
                ],
            ],
            'hospitaleiro' => [
                'title' => 'Hospitaleiro',
                'permission' => 'hospitaleiro.manage',
                'summary' => 'Ocorrências assistenciais, visitas, status e encaminhamentos.',
                'primary' => '/pwa/hospitaleiro',
                'actions' => [
                    ['label' => 'Painel de assistência', 'href' => '/pwa/hospitaleiro', 'kind' => 'pwa'],
                    ['label' => 'Nova ocorrência', 'href' => '/pwa/hospitaleiro', 'kind' => 'pwa'],
                    ['label' => 'Pendências', 'href' => '/pwa/hospitaleiro', 'kind' => 'pwa'],
                ],
            ],
            'primeiro-vigilante' => [
                'title' => 'Primeiro Vigilante',
                'permission' => 'vigilancia.primeiro.manage',
                'summary' => 'Aprendizes, trilhas, etapas, leituras, devolutivas e certificados.',
                'primary' => '/pwa/primeiro-vigilante',
                'actions' => [
                    ['label' => 'Painel do cargo', 'href' => '/pwa/primeiro-vigilante', 'kind' => 'pwa'],
                    ['label' => 'Meu aprendizado', 'href' => '/meu-aprendizado', 'kind' => 'desktop'],
                    ['label' => 'Biblioteca formativa', 'href' => '/pwa/biblioteca/classificar', 'kind' => 'pwa'],
                ],
            ],
            'segundo-vigilante' => [
                'title' => 'Segundo Vigilante',
                'permission' => 'vigilancia.segundo.manage',
                'summary' => 'Companheiros, trilhas, docência, exaltação, histórico e certificados.',
                'primary' => '/pwa/segundo-vigilante',
                'actions' => [
                    ['label' => 'Painel do cargo', 'href' => '/pwa/segundo-vigilante', 'kind' => 'pwa'],
                    ['label' => 'Meu companheirismo', 'href' => '/meu-companheirismo', 'kind' => 'desktop'],
                    ['label' => 'Biblioteca formativa', 'href' => '/pwa/biblioteca/classificar', 'kind' => 'pwa'],
                ],
            ],
            'orador' => [
                'title' => 'Orador',
                'permission' => 'orador.view',
                'summary' => 'Resumo da sessão, visitantes, pauta, cargos e lembretes operacionais.',
                'primary' => '/pwa/orador',
                'actions' => [
                    ['label' => 'Painel do Orador', 'href' => '/pwa/orador', 'kind' => 'pwa'],
                    ['label' => 'Comunicação PWA', 'href' => '/pwa/comunicacao', 'kind' => 'pwa'],
                ],
            ],
            'mestre-banquetes' => [
                'title' => 'Mestre de Banquetes',
                'permission' => 'mestre_banquetes.manage',
                'summary' => 'Ágape, confirmados, lista nominal, observações e fechamento operacional.',
                'primary' => '/pwa/mestre-banquetes',
                'actions' => [
                    ['label' => 'Painel de Banquetes', 'href' => '/pwa/mestre-banquetes', 'kind' => 'pwa'],
                    ['label' => 'Operação do ágape', 'href' => '/pwa/mestre-banquetes', 'kind' => 'pwa'],
                ],
            ],
            'mestre-harmonia' => [
                'title' => 'Mestre de Harmonia',
                'permission' => 'mestre_harmonia.manage',
                'summary' => 'Player ritual, base musical, operador, etapas e controle de execução.',
                'primary' => '/pwa/mestre-harmonia',
                'actions' => [
                    ['label' => 'Painel de Harmonia', 'href' => '/pwa/mestre-harmonia', 'kind' => 'pwa'],
                    ['label' => 'Scan da base musical', 'href' => '/api/mestre-harmonia/scan', 'kind' => 'api'],
                ],
            ],
            'administracao' => [
                'title' => 'Administração',
                'permission' => 'admin.cargos.view',
                'summary' => 'Cargos, gestões, acessos, convites, parâmetros e auditoria.',
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
            echo 'Módulo PWA não encontrado.';
            return;
        }

        require __DIR__ . '/../Views/pwa/cargo_modulo.php';
    }
}
