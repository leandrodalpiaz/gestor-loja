<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Config/Database.php';

try {
    $db = \App\Config\Database::getConnection();

    // ⚠️ COLOQUE SEUS DADOS AQUI
    $meuCim = '23709'; 
    $minhaSenha = '1547'; 

    $hash = password_hash($minhaSenha, PASSWORD_DEFAULT);

    // Este comando resolve os 3 possíveis bloqueios de uma vez só:
    // 1. Grava a senha correta
    // 2. Força o usuário a ficar ativo
    // 3. Define o cargo definitivo como 'admin'
    $stmt = $db->prepare("UPDATE obreiros SET senha_hash = :hash, ativo = true, cargo = 'admin' WHERE cim = :cim");
    $stmt->execute(['hash' => $hash, 'cim' => $meuCim]);

    echo "✅ Sucesso! O CIM {$meuCim} agora é um Administrador ativo e com senha configurada.";
} catch (Exception $e) {
    echo "❌ Erro no banco: " . $e->getMessage();
}