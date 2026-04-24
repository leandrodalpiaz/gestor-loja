<?php
// Autoloader local (fallback ao Composer) para o namespace App.

/**
 * Autoloader PSR-4 Simplificado
 * Usado enquanto o Composer não está disponível ou caso prefira rodar sem dependências.
 */
require_once __DIR__ . '/Auth/JwtHelper.php';
spl_autoload_register(function ($class) {
    // Prefixo de namespace carregado por este autoloader.
    $prefix = 'App\\';

    // Diretório-base correspondente ao prefixo.
    $base_dir = __DIR__ . '/';

    // Ignora classes fora do namespace do projeto.
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    // Nome relativo da classe dentro do namespace App.
    $relative_class = substr($class, $len);

    // Mapeia namespace para caminho de arquivo no padrão PSR-4.
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    // Carrega somente quando o arquivo existir.
    if (file_exists($file)) {
        require $file;
    }
});