<?php

declare(strict_types=1);

namespace App\Core;

final class DashboardSections
{
    /**
     * Monta seÃ§Ãµes do menu por cargo responsÃ¡vel (dono do mÃ³dulo),
     * filtrando cada item por permissÃ£o do usuÃ¡rio autenticado.
     */
    public static function build(callable $can, bool $isSystemAdmin): array
    {
        $definitions = [
            [
                'titulo' => 'Obreiro',
                'itens' => [
                    ['label' => 'Minhas ObrigaÃ§Ãµes', 'href' => '/financeiro/minhas-obrigacoes', 'permission' => 'financeiro.self'],
                ],
            ],
            [
                'titulo' => 'VenerÃ¡vel Mestre',
                'itens' => [
                    ['label' => 'Painel do VenerÃ¡vel', 'href' => '/veneravel', 'permission' => 'veneravel.manage'],
                ],
            ],
            [
                'titulo' => 'Secretaria',
                'itens' => [
                    ['label' => 'Painel da Secretaria', 'href' => '/secretaria', 'permission' => 'secretaria.manage'],
                    ['label' => 'Sessoes', 'href' => '/secretaria/sessoes', 'permission' => 'secretaria.manage'],
                    ['label' => 'Balaustres', 'href' => '/secretaria/balaustres', 'permission' => 'secretaria.manage'],
                    ['label' => 'Trabalhos/Publicacoes', 'href' => '/secretaria/trabalhos-publicacoes', 'permission' => 'secretaria.manage'],
                    ['label' => 'Convites Externos', 'href' => '/secretaria/convites-externos', 'permission' => 'secretaria.manage'],
                    ['label' => 'Relatorio Anual', 'href' => '/secretaria/relatorio-anual', 'permission' => 'secretaria.manage'],
                    ['label' => 'Relatorio de Gestao', 'href' => '/secretaria/relatorio-gestao', 'permission' => 'secretaria.manage'],
                    ['label' => 'BalaÃºstres em votaÃ§Ã£o', 'href' => '/secretaria/votacao', 'permission' => 'secretaria.manage'],
                    ['label' => 'Obreiros', 'href' => '/obreiros', 'permission' => 'obreiros.view'],
                    ['label' => 'Cadastrar Obreiro', 'href' => '/obreiros/novo', 'permission' => 'obreiros.manage'],
                    ['label' => 'Nominata e Cargos', 'href' => '/secretaria/nominata', 'permission' => 'admin.cargos.view'],
                    ['label' => 'Convites de acesso', 'href' => '/secretaria/convites', 'permission' => 'access.manage'],
                    ['label' => 'Acessos', 'href' => '/secretaria/acessos', 'permission' => 'access.manage'],
                    ['label' => 'ConteÃºdo PÃºblico', 'href' => '/secretaria/conteudo-publico', 'permission' => 'public_content.manage'],
                ],
            ],
            [
                'titulo' => 'Chancelaria',
                'itens' => [
                    ['label' => 'SessÃ£o da Chancelaria', 'href' => '/chanceler/sessao', 'permission' => 'chancelaria.manage'],
                    ['label' => 'EfemÃ©rides', 'href' => '/chancelaria/efemerides', 'permission' => 'chancelaria.manage'],
                ],
            ],
            [
                'titulo' => 'Tesouraria',
                'itens' => [
                    ['label' => 'Caixa', 'href' => '/tesouraria/caixa', 'permission' => 'tesouraria.manage'],
                    ['label' => 'ObrigaÃ§Ãµes', 'href' => '/tesouraria/obrigacoes', 'permission' => 'tesouraria.manage'],
                    ['label' => 'Comprovantes', 'href' => '/tesouraria/comprovantes', 'permission' => 'tesouraria.manage'],
                ],
            ],
            [
                'titulo' => 'Hospitaleiro',
                'itens' => [
                    ['label' => 'AssistÃªncia', 'href' => '/assistencia', 'permission' => 'hospitaleiro.manage'],
                ],
            ],
            [
                'titulo' => 'Primeiro Vigilante',
                'itens' => [
                    ['label' => 'Painel do 1Âº Vigilante', 'href' => '/primeiro-vigilante', 'permission' => 'vigilancia.primeiro.manage'],
                ],
            ],
            [
                'titulo' => 'Segundo Vigilante',
                'itens' => [
                    ['label' => 'Painel do 2Âº Vigilante', 'href' => '/segundo-vigilante', 'permission' => 'vigilancia.segundo.manage'],
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
                    ['label' => 'Meus EmprÃ©stimos', 'href' => '/biblioteca/meus-emprestimos', 'permission' => 'biblioteca.self'],
                    ['label' => 'Gerenciar EmprÃ©stimos', 'href' => '/biblioteca/emprestimos', 'permission' => 'biblioteca.manage'],
                    ['label' => 'ClassificaÃ§Ã£o', 'href' => '/biblioteca/classificar', 'permission' => 'biblioteca.classificar'],
                ],
            ],
            [
                'titulo' => 'Sistema',
                'itens' => [
                    ['label' => 'Painel TÃ©cnico', 'href' => '/sistema', 'system_only' => true],
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
                    'titulo' => (string) ($section['titulo'] ?? 'SeÃ§Ã£o'),
                    'itens' => $items,
                ];
            }
        }

        return $sections;
    }
}
