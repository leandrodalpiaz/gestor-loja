<?php

declare(strict_types=1);

namespace App\Core;

final class DashboardSections
{
    /**
     * Monta secoes do menu por cargo responsavel (dono do modulo),
     * filtrando cada item por permissao do usuario autenticado.
     */
    public static function build(callable $can, bool $isSystemAdmin): array
    {
        $definitions = [
            [
                'titulo' => 'Obreiro',
                'itens' => [
                    ['label' => 'Minhas Obrigacoes', 'href' => '/financeiro/minhas-obrigacoes', 'permission' => 'financeiro.self'],
                ],
            ],
            [
                'titulo' => 'Veneravel Mestre',
                'itens' => [
                    ['label' => 'Painel do Veneravel', 'href' => '/veneravel', 'permission' => 'veneravel.manage'],
                ],
            ],
            [
                'titulo' => 'Secretaria',
                'itens' => [
                    ['label' => 'Painel da Secretaria', 'href' => '/secretaria', 'permission' => 'secretaria.manage'],
                    ['label' => 'Votacoes', 'href' => '/secretaria/votacao', 'permission' => 'secretaria.manage'],
                    ['label' => 'Obreiros', 'href' => '/obreiros', 'permission' => 'obreiros.view'],
                    ['label' => 'Cadastrar Obreiro', 'href' => '/obreiros/novo', 'permission' => 'obreiros.manage'],
                ],
            ],
            [
                'titulo' => 'Chancelaria',
                'itens' => [
                    ['label' => 'Sessao da Chancelaria', 'href' => '/chanceler/sessao', 'permission' => 'chancelaria.manage'],
                    ['label' => 'Efemerides', 'href' => '/chancelaria/efemerides', 'permission' => 'chancelaria.manage'],
                ],
            ],
            [
                'titulo' => 'Tesouraria',
                'itens' => [
                    ['label' => 'Caixa', 'href' => '/tesouraria/caixa', 'permission' => 'tesouraria.manage'],
                    ['label' => 'Obrigacoes', 'href' => '/tesouraria/obrigacoes', 'permission' => 'tesouraria.manage'],
                    ['label' => 'Comprovantes', 'href' => '/tesouraria/comprovantes', 'permission' => 'tesouraria.manage'],
                ],
            ],
            [
                'titulo' => 'Hospitaleiro',
                'itens' => [
                    ['label' => 'Assistencia', 'href' => '/assistencia', 'permission' => 'hospitaleiro.manage'],
                ],
            ],
            [
                'titulo' => 'Primeiro Vigilante',
                'itens' => [
                    ['label' => 'Painel do 1o Vigilante', 'href' => '/primeiro-vigilante', 'permission' => 'vigilancia.primeiro.manage'],
                ],
            ],
            [
                'titulo' => 'Segundo Vigilante',
                'itens' => [
                    ['label' => 'Painel do 2o Vigilante', 'href' => '/segundo-vigilante', 'permission' => 'vigilancia.segundo.manage'],
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
                    ['label' => 'Meus Emprestimos', 'href' => '/biblioteca/meus-emprestimos', 'permission' => 'biblioteca.self'],
                    ['label' => 'Gerenciar Emprestimos', 'href' => '/biblioteca/emprestimos', 'permission' => 'biblioteca.manage'],
                    ['label' => 'Classificacao', 'href' => '/biblioteca/classificar', 'permission' => 'biblioteca.classificar'],
                ],
            ],
            [
                'titulo' => 'Administracao',
                'itens' => [
                    ['label' => 'Nominata e Cargos', 'href' => '/admin/cargos', 'permission' => 'admin.cargos.view'],
                    ['label' => 'Configuracoes da Loja', 'href' => '/admin/loja', 'permission' => 'admin.loja.view'],
                    ['label' => 'Gestao de Acessos', 'href' => '/admin/acessos', 'permission' => 'access.manage'],
                    ['label' => 'Conteudo Publico', 'href' => '/admin/conteudo-publico', 'permission' => 'public_content.manage'],
                ],
            ],
            [
                'titulo' => 'Sistema',
                'itens' => [
                    ['label' => 'Painel Tecnico', 'href' => '/sistema', 'system_only' => true],
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
                    'titulo' => (string) ($section['titulo'] ?? 'Secao'),
                    'itens' => $items,
                ];
            }
        }

        return $sections;
    }
}
