<?php
// O "../" faz o sistema voltar uma pasta para encontrar a "src" corretamente
require_once __DIR__ . '/../src/Config/Database.php';

try {
    $db = \App\Config\Database::getConnection();

    // ⚠️ ALTERE ESTAS DUAS LINHAS COM OS SEUS DADOS
    $meuCim = '123456'; // Coloque o seu CIM real aqui
    $minhaSenha = '1234'; // Coloque a sua senha de 4 dígitos aqui

    $hash = password_hash($minhaSenha, PASSWORD_DEFAULT);

    $stmt = $db->prepare("UPDATE obreiros SET senha_hash = :hash WHERE cim = :cim");
    $stmt->execute(['hash' => $hash, 'cim' => $meuCim]);

    echo "✅ Senha configurada com sucesso para o CIM: " . $meuCim;
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage();
}