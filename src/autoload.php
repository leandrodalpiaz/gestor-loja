<?php
// src/autoload.php

/**
 * Autoloader PSR-4 Simplificado
 * Usado enquanto o Composer não está disponível ou caso prefira rodar sem dependências.
 */
spl_autoload_register(function ($class) {
    // prefixo do namespace do projeto
    $prefix = 'App\\';

    // diretório base para o namespace
    $base_dir = __DIR__ . '/';

    // verifica se a classe usa o prefixo
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    // pega o nome relativo da classe
    $relative_class = substr($class, $len);

    // substitui o namespace pelo diretório base no nome do arquivo
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    // se o arquivo existe, requer o arquivo
    if (file_exists($file)) {
        require $file;
    }
});