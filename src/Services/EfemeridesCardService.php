<?php

namespace App\Services;

use App\Models\EfemerideCardPrevia;
use App\Models\EfemerideCardCategoriaTemplate;
use App\Models\MensagemComplementar;

class EfemeridesCardService
{
    private ?MensagemComplementar $mensagensComplementares = null;

    public function buildCardsForDate(string $ymd, array $registros): array
    {
        $templateDir = dirname(__DIR__, 2) . '/public/assets/images/templates/efemerides';
        $imageComposer = new ImageComposer();
        $previaModel = new EfemerideCardPrevia();
        $cards = [];

        $categoriaTemplateMap = (new EfemerideCardCategoriaTemplate())->mapa();
        foreach ($registros as $registro) {
            $card = $this->buildCardPayload($registro, $templateDir);
            $categoria = (string) ($card['categoria'] ?? '');
            if ($categoria !== '' && !empty($categoriaTemplateMap[$categoria])) {
                $card['template'] = $categoriaTemplateMap[$categoria];
                $card['template_file'] = $categoriaTemplateMap[$categoria];
                $card['template_slug'] = $categoriaTemplateMap[$categoria];
                $card['gold_theme'] = $this->isGoldTheme($categoriaTemplateMap[$categoria]);
                $card['cache_key'] = sha1(json_encode([$card['template'], $card['mensagem'], $card['ocultar_idade'], 'v8'], JSON_UNESCAPED_UNICODE) ?: uniqid('', true));
                $card['card_hash'] = $card['cache_key'];
            }
            $compose = $imageComposer->compose($card);
            if (!empty($compose['ok'])) {
                $card['card_path'] = (string) $compose['path'];
                $card['image_url'] = '/assets/images/efemerides_geradas/' . basename((string) $compose['path']);
            }
            $previaModel->upsert($ymd, (int) ($registro['id'] ?? 0), $card);
            $cards[] = $card;
        }

        return $cards;
    }

    private function buildCardPayload(array $registro, string $templateDir): array
    {
        $categoria = strtolower(trim((string) ($registro['vinculo'] ?? '')));
        if ($categoria === 'esposa') {
            $categoria = 'cunhada';
        }
        if ($categoria === '' && $this->normalizarTipo((string) ($registro['tipo'] ?? '')) === 'aniversario') {
            // Sem vínculo familiar registrado: é aniversário do próprio Irmão do quadro.
            $categoria = 'irmao';
        }
        $idade = $this->calcularIdade((string) ($registro['data_evento'] ?? ''));
        $ocultar = $this->defaultOcultarIdade($categoria, $idade);
        $template = $this->resolverTemplate($categoria, $idade, (string) ($registro['tipo'] ?? ''));
        $mensagem = $this->resolverMensagem($registro, $idade, $ocultar);
        $goldTheme = $this->isGoldTheme($template);
        $hash = sha1(json_encode([$template, $mensagem, $ocultar, 'v8'], JSON_UNESCAPED_UNICODE) ?: uniqid('', true));

        return [
            'registro_id' => (int) ($registro['id'] ?? 0),
            'categoria' => $categoria,
            'template' => $template,
            'template_file' => $template,
            'template_slug' => $template,
            'template_dir' => $templateDir,
            'titulo' => trim((string) ($registro['nome'] ?? '')),
            'mensagem' => $mensagem,
            'idade_exibida' => !$ocultar && $idade !== null,
            'ocultar_idade' => $ocultar || $idade === null,
            'cache_key' => $hash,
            'card_hash' => $hash,
            'gold_theme' => $goldTheme,
            'texto_custom_card' => trim((string) ($registro['mensagem_custom'] ?? '')) ?: null,
            'aprovado' => false,
        ];
    }

    public function buildCardForRegistro(string $ymd, array $registro, ?bool $ocultarIdadeOverride = null, ?string $textoCustomOverride = null, ?string $templateOverride = null): array
    {
        $templateDir = dirname(__DIR__, 2) . '/public/assets/images/templates/efemerides';
        $card = $this->buildCardPayload($registro, $templateDir);
        if ($ocultarIdadeOverride !== null) {
            $card['ocultar_idade'] = $ocultarIdadeOverride;
        }
        if ($textoCustomOverride !== null && trim($textoCustomOverride) !== '') {
            $card['mensagem'] = trim($textoCustomOverride);
            $card['texto_custom_card'] = trim($textoCustomOverride);
        }
        if ($templateOverride !== null && trim($templateOverride) !== '') {
            $card['template'] = trim($templateOverride);
            $card['template_file'] = trim($templateOverride);
            $card['template_slug'] = trim($templateOverride);
            $card['gold_theme'] = $this->isGoldTheme(trim($templateOverride));
        }
        $card['idade_exibida'] = !$card['ocultar_idade'] && !empty($card['idade_exibida']);
        $card['cache_key'] = sha1(json_encode([$card['template'], $card['mensagem'], $card['ocultar_idade'], 'v8'], JSON_UNESCAPED_UNICODE) ?: uniqid('', true));
        $card['card_hash'] = $card['cache_key'];
        $compose = (new ImageComposer())->compose($card);
        if (!empty($compose['ok'])) {
            $card['card_path'] = (string) $compose['path'];
            $card['image_url'] = '/assets/images/efemerides_geradas/' . basename((string) $compose['path']);
        }
        (new EfemerideCardPrevia())->upsert($ymd, (int) ($registro['id'] ?? 0), $card);
        return $card;
    }

    private function calcularIdade(string $data): ?int
    {
        if ($data === '' || strlen($data) < 4) {
            return null;
        }
        try {
            $date = new \DateTimeImmutable($data);
            return (int) $date->diff(new \DateTimeImmutable('today'))->y;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function defaultOcultarIdade(string $categoria, ?int $idade): bool
    {
        if ($idade === null) {
            return true;
        }
        if (str_contains($categoria, 'esposa') || str_contains($categoria, 'cunhada')) {
            return true;
        }
        if (str_contains($categoria, 'filha') || str_contains($categoria, 'sobrinha')) {
            return $idade >= 16;
        }
        return false;
    }

    private function resolverMensagem(array $registro, ?int $idade, bool $ocultar): string
    {
        $tipo = strtolower(trim((string) ($registro['tipo'] ?? '')));
        if (str_contains($tipo, 'historia') || str_contains($tipo, 'história')) {
            $titulo = trim((string) ($registro['nome'] ?? ''));
            $corpo = trim((string) ($registro['mensagem_custom'] ?? $registro['texto'] ?? ''));
            // Evita duplicação quando título e corpo são idênticos
            if ($titulo !== '' && $corpo !== '' && $titulo !== $corpo) {
                // Título em negrito, seguido de linha decorativa e corpo em itálico pergaminho
                return $this->limparTextoCard('**' . $titulo . "**\n\n─── ◆ ───\n\n" . $corpo);
            }

            return $this->limparTextoCard($corpo !== '' ? $corpo : $titulo);
        }

        $custom = trim((string) ($registro['mensagem_custom'] ?? ''));
        if ($custom !== '') {
            return $this->limparTextoCard($custom);
        }

        $tipoOriginal = trim((string) ($registro['tipo'] ?? ''));
        $tipoNorm = $this->normalizarTipo($tipoOriginal);

        if ($tipoNorm === 'aniversario') {
            return $this->montarMensagemAniversario($registro, $idade, $ocultar);
        }

        if (in_array($tipoNorm, ['iniciacao', 'elevacao', 'exaltacao', 'instalacao'], true)) {
            return $this->montarMensagemCerimonia($registro, $tipoOriginal !== '' ? $tipoOriginal : 'Efeméride', $idade);
        }

        if (in_array($tipoNorm, ['possegraomestre', 'concessaodemembrohonorario', 'filiacao'], true)) {
            return $this->montarMensagemEspecial($registro, $tipoOriginal !== '' ? $tipoOriginal : 'Efeméride', $idade);
        }

        if ($tipoNorm === 'orienteeterno') {
            return $this->montarMensagemOrienteEterno($registro);
        }

        return $this->montarMensagemGenerica($registro, $tipoOriginal, $idade);
    }

    private function montarMensagemAniversario(array $registro, ?int $idade, bool $ocultar): string
    {
        $nome = trim((string) ($registro['nome'] ?? '')) ?: 'Nome não informado';
        $tratamento = $this->tratamentoPorVinculo(
            (int) ($registro['cod_vinculo'] ?? 0),
            (string) ($registro['vinculo'] ?? ''),
            (string) ($registro['parentesco'] ?? '')
        );
        $tratamentoNorm = $this->normalizarTipo($tratamento);
        $complementar = $this->mensagemComplementar('aniversario_' . $tratamentoNorm);
        $idadeTexto = ($idade !== null && !$ocultar) ? $this->textoAnos($idade) : '';
        $parentesco = $this->normalizarParentesco((string) ($registro['parentesco'] ?? ''));
        $vinculo = trim((string) ($registro['vinculo'] ?? ''));

        if ($tratamentoNorm === 'irmao') {
            $base = $idadeTexto !== ''
                ? "Com fraterna alegria, hoje celebramos os **{$idadeTexto}** de vida do nosso Irmão **{$nome}**."
                : "Com fraterna alegria, hoje celebramos o aniversário do nosso Irmão **{$nome}**.";
        } elseif ($tratamentoNorm === 'cunhada') {
            $referencia = $parentesco !== '' ? " do nosso Irmão **{$parentesco}**" : '';
            $vinculoTexto = $vinculo !== '' ? ", {$vinculo}{$referencia}" : $referencia;
            $base = "Hoje celebramos, com fraterna alegria, o aniversário de nossa Cunhada **{$nome}**{$vinculoTexto}.";
        } else {
            $artigo = in_array($tratamentoNorm, ['filha', 'sobrinha'], true) ? 'nossa' : 'nosso';
            $referencia = $parentesco !== '' ? " do nosso Irmão **{$parentesco}**" : '';
            $vinculoTexto = $vinculo !== '' ? ", {$vinculo}{$referencia}" : $referencia;
            $idadeParte = $idadeTexto !== '' ? " os **{$idadeTexto}** de vida de" : " o aniversário de";
            $base = "Hoje celebramos, com fraterna alegria,{$idadeParte} {$artigo} {$tratamento} **{$nome}**{$vinculoTexto}.";
        }

        return $this->limparTextoCard($this->comMensagemComplementar($base, $complementar));
    }

    private function montarMensagemCerimonia(array $registro, string $tipo, ?int $idade): string
    {
        $nome = trim((string) ($registro['nome'] ?? '')) ?: 'Nome não informado';
        $anos = $idade !== null ? $this->textoAnos($idade) : 'mais um marco';
        $data = $this->formatarData((string) ($registro['data_evento'] ?? ''));
        $local = $this->formatarLocal((string) ($registro['local'] ?? ''));
        $sufixoLocal = $local !== '' ? " ({$local})" : '';
        $complementar = $this->mensagemComplementar($this->normalizarTipo($tipo));

        $base = "Neste dia, registramos com honra **{$anos}** da **{$tipo}** do querido Irmão **{$nome}**";
        if ($data !== '') {
            $base .= " - {$data}{$sufixoLocal}";
        } elseif ($sufixoLocal !== '') {
            $base .= " {$sufixoLocal}";
        }
        $base .= '.';

        return $this->limparTextoCard($this->comMensagemComplementar($base, $complementar));
    }

    private function montarMensagemEspecial(array $registro, string $tipo, ?int $idade): string
    {
        $nome = trim((string) ($registro['nome'] ?? '')) ?: 'Nome não informado';
        $anos = $idade !== null ? $this->textoAnos($idade) : 'esta data';
        $data = $this->formatarData((string) ($registro['data_evento'] ?? ''));
        $local = $this->formatarLocal((string) ($registro['local'] ?? ''));
        $sufixoLocal = $local !== '' ? " ({$local})" : '';
        $complementar = $this->mensagemComplementar($this->normalizarTipo($tipo));

        $base = "Neste dia, registramos com honra **{$anos}** da **{$tipo}** do querido Irmão **{$nome}**";
        if ($data !== '') {
            $base .= " - {$data}{$sufixoLocal}";
        } elseif ($sufixoLocal !== '') {
            $base .= " {$sufixoLocal}";
        }
        $base .= '.';

        return $this->limparTextoCard($this->comMensagemComplementar($base, $complementar));
    }

    private function montarMensagemOrienteEterno(array $registro): string
    {
        $nome = trim((string) ($registro['nome'] ?? '')) ?: 'Nome não informado';
        $data = $this->formatarData((string) ($registro['data_evento'] ?? ''));
        $base = "Com profundo pesar e saudade, lembramos de nosso Irmão **{$nome}**";
        if ($data !== '') {
            $base .= ", que partiu para o Oriente Eterno em {$data}";
        }
        $base .= '.';

        return $this->limparTextoCard($this->comMensagemComplementar($base, $this->mensagemComplementar('orienteeterno')));
    }

    private function montarMensagemGenerica(array $registro, string $tipo, ?int $idade): string
    {
        $nome = trim((string) ($registro['nome'] ?? '')) ?: 'Nome não informado';
        $tipoTexto = $tipo !== '' ? $tipo : 'efeméride';
        $anos = $idade !== null ? $this->textoAnos($idade) . ' de ' : '';
        $data = $this->formatarData((string) ($registro['data_evento'] ?? ''));
        $base = "Neste dia, registramos com honra **{$anos}**{$tipoTexto} de **{$nome}**";
        if ($data !== '') {
            $base .= " - {$data}";
        }
        $base .= '.';

        return $this->limparTextoCard($this->comMensagemComplementar($base, $this->mensagemComplementar($this->normalizarTipo($tipoTexto))));
    }

    private function comMensagemComplementar(string $base, string $complementar): string
    {
        $base = trim($base);
        $complementar = trim($complementar);
        return $complementar !== '' ? $base . "\n\n" . $complementar : $base;
    }

    private function mensagemComplementar(string $tipo): string
    {
        $tipo = trim($tipo);
        if ($tipo === '') {
            return '';
        }

        try {
            if (!$this->mensagensComplementares instanceof MensagemComplementar) {
                $this->mensagensComplementares = new MensagemComplementar();
            }
            return trim($this->mensagensComplementares->sortear($tipo));
        } catch (\Throwable $e) {
            error_log('Falha ao sortear mensagem complementar do card: ' . $e->getMessage());
            return '';
        }
    }

    private function tratamentoPorVinculo(int $codVinculo, string $vinculo = '', string $parentesco = ''): string
    {
        $porCodigo = [
            1 => 'Irmão',
            2 => 'Cunhada',
            3 => 'Sobrinha',
            4 => 'Sobrinho',
            5 => 'Sobrinha',
            6 => 'Sobrinho',
        ];

        if (isset($porCodigo[$codVinculo])) {
            return $porCodigo[$codVinculo];
        }

        $texto = $this->toLower($vinculo . ' ' . $parentesco);
        if (str_contains($texto, 'cunhada') || str_contains($texto, 'esposa')) return 'Cunhada';
        if (str_contains($texto, 'filha') || str_contains($texto, 'enteada') || str_contains($texto, 'sobrinha')) return 'Sobrinha';
        if (str_contains($texto, 'filho') || str_contains($texto, 'enteado') || str_contains($texto, 'sobrinho')) return 'Sobrinho';
        return 'Irmão';
    }

    private function normalizarParentesco(string $parentesco): string
    {
        $parentesco = trim($parentesco);
        if ($parentesco === '') {
            return '';
        }

        $parentesco = preg_replace('/^\s*irm[aã]o\s+/iu', '', $parentesco) ?? $parentesco;
        return trim($parentesco);
    }

    private function textoAnos(int $anos): string
    {
        return $anos === 1 ? '1 ano' : "{$anos} anos";
    }

    private function formatarData(string $data): string
    {
        if ($data === '') {
            return '';
        }

        try {
            return (new \DateTimeImmutable($data))->format('d/m/Y');
        } catch (\Throwable $e) {
            return '';
        }
    }

    private function formatarLocal(string $local): string
    {
        $local = trim($local);
        if ($local === '') {
            return '';
        }

        return preg_match('/^loja\b/iu', $local) ? $local : "Loja {$local}";
    }

    private function normalizarTipo(string $tipo): string
    {
        $tipo = $this->toLower(trim($tipo));
        $tipo = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $tipo) ?: $tipo;
        $tipo = preg_replace('/[^a-z0-9]+/', '', $tipo) ?? '';

        return $tipo;
    }

    private function toLower(string $value): string
    {
        return function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value);
    }

    private function limparTextoCard(string $texto): string
    {
        $texto = html_entity_decode(strip_tags($texto), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $texto = str_replace(["\r\n", "\r"], "\n", $texto);
        $texto = preg_replace("/[ \t]+/", ' ', $texto) ?? $texto;
        $texto = preg_replace("/\n{3,}/", "\n\n", $texto) ?? $texto;

        return trim($texto);
    }

    private function resolverTemplate(string $categoria, ?int $idade, string $tipo): string
    {
        $tipoNorm = strtolower($tipo);
        if (str_contains($tipoNorm, 'historia') || str_contains($tipoNorm, 'história')) return 'card_historia_sepia.png';
        if (str_contains($tipoNorm, 'inicia')) return 'card_grau_iniciacao.png';
        if (str_contains($tipoNorm, 'eleva')) return 'card_grau_elevacao.png';
        if (str_contains($tipoNorm, 'exalta')) return 'card_grau_exaltacao.png';
        if (str_contains($tipoNorm, 'instala')) return 'card_grau_instalacao.png';
        if (str_contains($categoria, 'irma')) return 'card_irmao_bedrock.png';
        if (str_contains($categoria, 'esposa') || str_contains($categoria, 'cunhada')) return 'card_cunhada_solar.png';
        if (str_contains($categoria, 'filh') || str_contains($categoria, 'sobrinh')) {
            if (($idade ?? 0) < 10) return 'card_familia_kids.png';
            if (($idade ?? 0) <= 25) return 'card_sobrinho_jovem.png';
            return str_contains($categoria, 'filha') || str_contains($categoria, 'sobrinha') ? 'card_sobrinha_adulta.png' : 'card_sobrinho_adulto.png';
        }
        return 'card_oficial_convite.png';
    }

    private function isGoldTheme(string $template): bool
    {
        foreach (['pop', 'elevacao', 'exaltacao', 'instalacao', 'grao_mestre'] as $token) {
            if (str_contains($template, $token)) return true;
        }
        return false;
    }

    public static function resolveLocalPath(?string $savedPath): string
    {
        if (empty($savedPath)) {
            return '';
        }
        $filename = basename($savedPath);
        // O diretório correto em tempo de execução para os cards gerados é sempre:
        $runtimeDir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'efemerides_geradas';
        return $runtimeDir . DIRECTORY_SEPARATOR . $filename;
    }
}
