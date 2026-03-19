<?php
// 1. Carrega o autoloader para o PHP encontrar as classes (como a Env)
require_once __DIR__ . '/../vendor/autoload.php';
// 2. Carrega a conexão com o banco
require_once __DIR__ . '/../src/Config/Database.php';

try {
    $db = \App\Config\Database::getConnection();

    // ⚠️ ALTERE ESTAS DUAS LINHAS COM OS SEUS DADOS
    $meuCim = '23709'; // Coloque o seu CIM real aqui
    $minhaSenha = '1547'; // Coloque a sua senha de 4 dígitos aqui

    $hash = password_hash($minhaSenha, PASSWORD_DEFAULT);

    $stmt = $db->prepare("UPDATE obreiros SET senha_hash = :hash WHERE cim = :cim");
    $stmt->execute(['hash' => $hash, 'cim' => $meuCim]);

    echo "✅ Senha configurada com sucesso para o CIM: " . $meuCim;
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage();
}