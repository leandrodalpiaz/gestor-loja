<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Config/Database.php';

try {
    $db = \App\Config\Database::getConnection();
    $cim = '23709';
    $senha = '1547';
    $hash = password_hash($senha, PASSWORD_DEFAULT);

    // Tenta inserir o usuário do zero
    $sql = "INSERT INTO obreiros (nome_completo, nome_historico, cim, cargo, ativo, senha_hash) 
            VALUES ('Administrador do Sistema', 'Admin', :cim, 'admin', true, :hash)";

    $stmt = $db->prepare($sql);
    $stmt->execute(['cim' => $cim, 'hash' => $hash]);

    echo "<h2 style='color:green'>✅ SUCESSO ABSOLUTO!</h2>";
    echo "O usuário CIM <b>{$cim}</b> foi CRIADO do zero no banco de dados com a senha <b>{$senha}</b> e cargo de <b>admin</b>.";

} catch (Exception $e) {
    echo "<h2 style='color:red'>❌ Erro ao criar:</h2>";
    echo $e->getMessage();
}