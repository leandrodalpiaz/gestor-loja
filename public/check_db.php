<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Config/Database.php';

try {
    $db = \App\Config\Database::getConnection();
    $cim = '23709';

    $stmt = $db->prepare("SELECT senha_hash, LENGTH(senha_hash) as tamanho FROM obreiros WHERE cim = :cim");
    $stmt->execute(['cim' => $cim]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        echo "<h3>Resultado para o CIM {$cim}:</h3>";
        echo "<b>Tamanho do Hash salvo:</b> " . $user['tamanho'] . " caracteres (O correto para o PHP é 60)<br><br>";
        echo "<b>Hash exato que está no banco:</b><br>";
        echo "<pre>" . $user['senha_hash'] . "</pre>";

        if ($user['tamanho'] < 60) {
            echo "<h3 style='color:red'>🚨 PROBLEMA CONFIRMADO!</h3>";
            echo "O seu banco de dados está cortando a senha. A coluna 'senha_hash' é muito pequena.";
        } else {
            echo "<h3 style='color:green'>✅ O tamanho está correto.</h3>";
            echo "O problema não é o tamanho da coluna.";
        }
    } else {
        echo "Usuário não encontrado no banco.";
    }
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
}