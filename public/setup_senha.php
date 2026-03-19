<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Config/Database.php';

try {
    $db = \App\Config\Database::getConnection();

    // ⚠️ COLOQUE SEU CIM REAL AQUI:
    $meuCim = '23709'; 
    $minhaSenha = '1547'; 

    $hash = password_hash($minhaSenha, PASSWORD_DEFAULT);

    // Atualiza a senha e FORÇA o usuário a ficar ativo
    $stmt = $db->prepare("UPDATE obreiros SET senha_hash = :hash, ativo = true WHERE cim = :cim");
    $stmt->execute(['hash' => $hash, 'cim' => $meuCim]);

    echo "✅ Senha configurada e usuário ativado para o CIM: " . $meuCim;
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage();
}