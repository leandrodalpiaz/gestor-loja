<?php

namespace App\Services;

class AssistenteFluxoService
{
    public function interpretar(string $comando, callable $canPermission): array
    {
        $textoOriginal = trim($comando);
        $texto = $this->normalizar($textoOriginal);

        if ($texto === '') {
            return [
                'intent' => 'vazio',
                'confidence' => 0.0,
                'allowed' => true,
                'message' => 'Digite um objetivo curto. Ex.: "fechar mes" ou "mensalidade do leandro".',
                'action' => null,
                'suggestions' => ['fechar mes', 'mensalidade do leandro', 'aniversario joao'],
            ];
        }

        if (preg_match('/\b(fechar|encerrar)\s+mes\b/', $texto)) {
            return $this->resolverAcao(
                intent: 'tesouraria.fechamento',
                confidence: 0.96,
                permission: 'tesouraria.manage',
                canPermission: $canPermission,
                successMessage: 'Posso abrir o fechamento mensal da Tesouraria.',
                denyMessage: 'Você não tem acesso ao fechamento mensal. Posso te levar para um atalho permitido.',
                target: '/tesouraria/fechamento',
                label: 'Abrir fechamento mensal'
            );
        }

        if (preg_match('/\bmensalidade\b/', $texto)) {
            if (preg_match('/\bmensalidade\s+(do|da)\s+(.+)/', $texto, $m)) {
                $nomeBusca = trim((string) ($m[2] ?? ''));
                return $this->resolverAcao(
                    intent: 'tesouraria.mensalidade.terceiro',
                    confidence: 0.9,
                    permission: 'tesouraria.manage',
                    canPermission: $canPermission,
                    successMessage: 'Posso abrir a consulta de regularidade com o filtro solicitado.',
                    denyMessage: 'Você não tem acesso para consultar mensalidade de outros membros. Posso abrir sua área financeira pessoal.',
                    target: '/tesouraria/regularidade' . ($nomeBusca !== '' ? '?q=' . rawurlencode($nomeBusca) : ''),
                    label: 'Abrir regularidade da Tesouraria',
                    fallbackAction: [
                        'permission' => 'financeiro.self',
                        'target' => '/financeiro/minhas-obrigacoes',
                        'label' => 'Abrir minhas obrigações',
                    ]
                );
            }

            return $this->resolverAcao(
                intent: 'financeiro.self.mensalidade',
                confidence: 0.88,
                permission: 'financeiro.self',
                canPermission: $canPermission,
                successMessage: 'Posso abrir suas obrigações financeiras pessoais.',
                denyMessage: 'Você não tem permissão para consulta financeira neste perfil.',
                target: '/financeiro/minhas-obrigacoes',
                label: 'Abrir minhas obrigações'
            );
        }

        if (preg_match('/\baniversar(io|io)?\b|\befemeride\b/', $texto)) {
            return $this->resolverAcao(
                intent: 'chancelaria.efemerides',
                confidence: 0.92,
                permission: 'chancelaria.manage',
                canPermission: $canPermission,
                successMessage: 'Posso abrir efemérides para localizar aniversários e datas relevantes.',
                denyMessage: 'Você não tem acesso às efemérides neste perfil.',
                target: '/chancelaria/efemerides',
                label: 'Abrir efemérides'
            );
        }

        if (preg_match('/\b(ocorrencia|assistencia|hospitaleiro)\b/', $texto)) {
            return $this->resolverAcao(
                intent: 'assistencia.painel',
                confidence: 0.86,
                permission: 'hospitaleiro.manage',
                canPermission: $canPermission,
                successMessage: 'Posso abrir o painel de assistência.',
                denyMessage: 'Você não tem acesso ao painel de assistência.',
                target: '/assistencia',
                label: 'Abrir assistência'
            );
        }

        if (preg_match('/\b(sessao|agenda|publicacao)\b/', $texto)) {
            return $this->resolverAcao(
                intent: 'secretaria.painel',
                confidence: 0.83,
                permission: 'secretaria.manage',
                canPermission: $canPermission,
                successMessage: 'Posso abrir a Secretaria para sessões, agenda e publicações.',
                denyMessage: 'Você não tem acesso à Secretaria neste perfil.',
                target: '/secretaria',
                label: 'Abrir Secretaria'
            );
        }

        if (preg_match('/\b(comprovante|pix|pagamento)\b/', $texto)) {
            return $this->resolverAcao(
                intent: 'tesouraria.comprovantes',
                confidence: 0.8,
                permission: 'tesouraria.manage',
                canPermission: $canPermission,
                successMessage: 'Posso abrir a validação de comprovantes.',
                denyMessage: 'Você não tem acesso à validação de comprovantes.',
                target: '/tesouraria/comprovantes',
                label: 'Abrir comprovantes'
            );
        }

        return [
            'intent' => 'desconhecida',
            'confidence' => 0.35,
            'allowed' => true,
            'message' => 'Ainda não reconheci esse comando. Tente uma forma curta e direta.',
            'action' => null,
            'suggestions' => ['fechar mes', 'mensalidade do leandro', 'aniversario joao', 'abrir secretaria'],
        ];
    }

    public function aplicarDesambiguacaoMensalidade(array $resultado, string $comando, callable $buscarAtivosPorNome): array
    {
        if ((string) ($resultado['intent'] ?? '') !== 'tesouraria.mensalidade.terceiro') {
            return $resultado;
        }

        if (!(bool) ($resultado['allowed'] ?? false)) {
            return $resultado;
        }

        $texto = $this->normalizar($comando);
        if (!preg_match('/\bmensalidade\s+(do|da)\s+(.+)/', $texto, $m)) {
            return $resultado;
        }

        $nomeBusca = trim((string) ($m[2] ?? ''));
        if ($nomeBusca === '') {
            return $resultado;
        }

        $candidatos = $buscarAtivosPorNome($nomeBusca);
        if (!is_array($candidatos) || $candidatos === []) {
            $resultado['message'] = 'Não encontrei um irmão ativo com esse nome. Tente com nome completo ou CIM.';
            $resultado['action'] = null;
            $resultado['suggestions'] = [];
            $resultado['needs_disambiguation'] = false;

            return $resultado;
        }

        if (count($candidatos) === 1) {
            $nomeUnico = trim((string) ($candidatos[0]['nome'] ?? ''));
            if ($nomeUnico !== '') {
                $resultado['message'] .= ' Resultado único: ' . $nomeUnico . '.';
            }
            $resultado['needs_disambiguation'] = false;

            return $resultado;
        }

        $rotulos = [];
        foreach (array_slice($candidatos, 0, 5) as $candidato) {
            $nome = trim((string) ($candidato['nome'] ?? ''));
            $cim = trim((string) ($candidato['cim'] ?? ''));
            $rotulos[] = $nome !== '' ? ($cim !== '' ? $nome . ' (CIM ' . $cim . ')' : $nome) : 'Irmão';
        }

        $resultado['message'] = 'Encontrei mais de um irmão com nome parecido. Qual deles você deseja consultar?';
        $resultado['action'] = null;
        $resultado['suggestions'] = $rotulos;
        $resultado['needs_disambiguation'] = true;

        return $resultado;
    }

    private function resolverAcao(
        string $intent,
        float $confidence,
        string $permission,
        callable $canPermission,
        string $successMessage,
        string $denyMessage,
        string $target,
        string $label,
        ?array $fallbackAction = null
    ): array {
        if ($canPermission($permission)) {
            return [
                'intent' => $intent,
                'confidence' => $confidence,
                'allowed' => true,
                'message' => $successMessage,
                'action' => [
                    'type' => 'navigate',
                    'target' => $target,
                    'label' => $label,
                ],
                'suggestions' => [],
            ];
        }

        if ($fallbackAction !== null && isset($fallbackAction['permission'], $fallbackAction['target'], $fallbackAction['label'])) {
            $fbPermission = (string) $fallbackAction['permission'];
            if ($canPermission($fbPermission)) {
                return [
                    'intent' => $intent,
                    'confidence' => $confidence,
                    'allowed' => false,
                    'message' => $denyMessage,
                    'action' => [
                        'type' => 'navigate',
                        'target' => (string) $fallbackAction['target'],
                        'label' => (string) $fallbackAction['label'],
                    ],
                    'suggestions' => [],
                ];
            }
        }

        return [
            'intent' => $intent,
            'confidence' => $confidence,
            'allowed' => false,
            'message' => $denyMessage,
            'action' => null,
            'suggestions' => [],
        ];
    }

    private function normalizar(string $texto): string
    {
        $texto = strtolower(trim($texto));
        $texto = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texto) ?: $texto;
        $texto = preg_replace('/[^a-z0-9\s]/', ' ', $texto) ?? $texto;
        $texto = preg_replace('/\s+/', ' ', $texto) ?? $texto;

        return trim($texto);
    }
}
