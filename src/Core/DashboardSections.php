<?php

declare(strict_types=1);

namespace App\Core;

final class DashboardSections
{
    /**
     * Monta seções do menu por cargo responsável (dono do módulo),
     * filtrando cada item por permissão do usuário autenticado.
     */
    public static function build(callable $can, bool $isSystemAdmin): array
    {
        $definitions = [
            [
                'titulo' => 'Minha Loja',
                'itens' => [
                    ['label' => 'Área do Irmão', 'href' => '/minha-loja', 'permission' => 'dashboard.view'],
                    ['label' => 'Início (Resumo pessoal)', 'href' => '/financeiro/minhas-obrigacoes', 'permission' => 'financeiro.self'],
                    ['label' => 'Meu Cadastro', 'href' => '/meu-cadastro', 'permission' => 'dashboard.view'],
                    ['label' => 'Meus Trabalhos', 'href' => '/minha-loja/trabalhos', 'permission' => 'dashboard.view'],
                    ['label' => 'Irmãos da Loja', 'href' => '/minha-loja/irmaos', 'permission' => 'dashboard.view'],
                ],
            ],
            [
                'titulo' => 'Obreiro',
                'itens' => [
                    ['label' => 'Minhas Obrigações', 'href' => '/financeiro/minhas-obrigacoes', 'permission' => 'financeiro.self'],
                ],
            ],
            [
                'titulo' => 'Venerável Mestre',
                'itens' => [
                    ['label' => 'Painel do Venerável', 'href' => '/veneravel', 'permission' => 'veneravel.manage'],
                ],
            ],
            [
                'titulo' => 'Secretaria',
                'itens' => [
                    ['label' => 'Painel da Secretaria', 'href' => '/secretaria', 'permission' => 'secretaria.manage'],
                    ['label' => 'Sessões', 'href' => '/secretaria/sessoes', 'permission' => 'secretaria.manage'],
                    ['label' => 'Balaústres', 'href' => '/secretaria/balaustres', 'permission' => 'secretaria.manage'],
                    ['label' => 'Trabalhos/Publicações', 'href' => '/secretaria/trabalhos-publicacoes', 'permission' => 'secretaria.manage'],
                    ['label' => 'Convites Externos', 'href' => '/secretaria/convites-externos', 'permission' => 'secretaria.manage'],
                    ['label' => 'Relatório Anual', 'href' => '/secretaria/relatorio-anual', 'permission' => 'secretaria.manage'],
                    ['label' => 'Relatório de Gestão', 'href' => '/secretaria/relatorio-gestao', 'permission' => 'secretaria.manage'],
                    ['label' => 'Balaústres em votação', 'href' => '/secretaria/votacao', 'permission' => 'secretaria.manage'],
                    ['label' => 'Obreiros', 'href' => '/obreiros', 'permission' => 'obreiros.view'],
                    ['label' => 'Cadastrar Obreiro', 'href' => '/obreiros/novo', 'permission' => 'obreiros.manage'],
                    ['label' => 'Nominata e Cargos', 'href' => '/secretaria/nominata', 'permission' => 'admin.cargos.view'],
                    ['label' => 'Convites de acesso', 'href' => '/secretaria/convites', 'permission' => 'access.manage'],
                    ['label' => 'Acessos', 'href' => '/secretaria/acessos', 'permission' => 'access.manage'],
                    ['label' => 'Conteúdo Público', 'href' => '/secretaria/conteudo-publico', 'permission' => 'public_content.manage'],
                ],
            ],
            [
                'titulo' => 'Chancelaria',
                'itens' => [
                    ['label' => 'Sessão da Chancelaria', 'href' => '/chanceler/sessao', 'permission' => 'chancelaria.manage'],
                    ['label' => 'Efemérides', 'href' => '/chancelaria/efemerides', 'permission' => 'chancelaria.manage'],
                ],
            ],
            [
                'titulo' => 'Tesouraria',
                'itens' => [
                    ['label' => 'Caixa', 'href' => '/tesouraria/caixa', 'permission' => 'tesouraria.manage'],
                    ['label' => 'Obrigações', 'href' => '/tesouraria/obrigacoes', 'permission' => 'tesouraria.manage'],
                    ['label' => 'Comprovantes', 'href' => '/tesouraria/comprovantes', 'permission' => 'tesouraria.manage'],
                    ['label' => 'Regularidade', 'href' => '/tesouraria/regularidade', 'permission' => 'tesouraria.manage'],
                    ['label' => 'Sessões Financeiras', 'href' => '/tesouraria/sessoes', 'permission' => 'tesouraria.manage'],
                    ['label' => 'Fechamento de Mês', 'href' => '/tesouraria/fechamento', 'permission' => 'tesouraria.manage'],
                    ['label' => 'Relatório de Gestão', 'href' => '/tesouraria/relatorio-gestao', 'permission' => 'tesouraria.manage'],
                ],
            ],
            [
                'titulo' => 'Hospitaleiro',
                'itens' => [
                    ['label' => 'Assistência', 'href' => '/assistencia', 'permission' => 'hospitaleiro.manage'],
                ],
            ],
            [
                'titulo' => 'Primeiro Vigilante',
                'itens' => [
                    ['label' => 'Painel do 1º Vigilante', 'href' => '/primeiro-vigilante', 'permission' => 'vigilancia.primeiro.manage'],
                ],
            ],
            [
                'titulo' => 'Segundo Vigilante',
                'itens' => [
                    ['label' => 'Painel do 2º Vigilante', 'href' => '/segundo-vigilante', 'permission' => 'vigilancia.segundo.manage'],
                ],
            ],
            [
                'titulo' => 'Orador',
                'itens' => [
                    ['label' => 'Painel do Orador', 'href' => '/orador', 'permission' => 'orador.view'],
                ],
            ],
            [
                'titulo' => 'Mestre de Banquetes',
                'itens' => [
                    ['label' => 'Painel de Banquetes', 'href' => '/mestre-banquetes', 'permission' => 'mestre_banquetes.manage'],
                ],
            ],
            [
                'titulo' => 'Mestre de Harmonia',
                'itens' => [
                    ['label' => 'Painel de Harmonia', 'href' => '/mestre-harmonia', 'permission' => 'mestre_harmonia.manage'],
                    ['label' => 'Miniapp Harmonia', 'href' => '/miniapp/mestre-harmonia', 'permission' => 'mestre_harmonia.manage'],
                ],
            ],
            [
                'titulo' => 'Biblioteca',
                'itens' => [
                    ['label' => 'Acervo', 'href' => '/biblioteca', 'permission' => 'biblioteca.self'],
                    ['label' => 'Meus Empréstimos', 'href' => '/biblioteca/meus-emprestimos', 'permission' => 'biblioteca.self'],
                    ['label' => 'Gerenciar Empréstimos', 'href' => '/biblioteca/emprestimos', 'permission' => 'biblioteca.manage'],
                    ['label' => 'Classificação', 'href' => '/biblioteca/classificar', 'permission' => 'biblioteca.classificar'],
                ],
            ],
            [
                'titulo' => 'Sistema',
                'itens' => [
                    ['label' => 'Painel Técnico', 'href' => '/sistema', 'system_only' => true],
                ],
            ],
        ];

        $sections = [];
        $seenHrefs = [];
        foreach ($definitions as $section) {
            $items = [];
            foreach (($section['itens'] ?? []) as $item) {
                $systemOnly = (bool) ($item['system_only'] ?? false);
                if ($systemOnly && !$isSystemAdmin) {
                    continue;
                }

                $permission = (string) ($item['permission'] ?? '');
                if ($permission !== '' && !$can($permission)) {
                    continue;
                }

                $href = (string) ($item['href'] ?? '#');
                if ($href !== '' && isset($seenHrefs[$href])) {
                    continue;
                }

                $items[] = [
                    'label' => (string) ($item['label'] ?? 'Item'),
                    'href' => $href,
                ];

                if ($href !== '') {
                    $seenHrefs[$href] = true;
                }
            }

            if ($items !== []) {
                $sections[] = [
                    'titulo' => (string) ($section['titulo'] ?? 'Seção'),
                    'itens' => $items,
                ];
            }
        }

        return $sections;
    }
}
