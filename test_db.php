<?php
require_once 'database.php';

try {
    $database = new Database();
    
    // Exemplo de como você vai consultar uma tabela real agora.
    // Substitua 'sua_tabela_aqui' pelo nome de uma tabela que exista no seu banco.
    echo "Testando requisicao em uma tabela...\n";
    $nomeDaTabela = 'sua_tabela_aqui'; 
    $response = $database->request('/rest/v1/' . $nomeDaTabela . '?select=*&limit=1');
    
    if ($response !== null) {
        echo "\nSucesso: Conectado à API REST do Supabase!\n";
        echo "Exemplo de dados retornados:\n";
        print_r($response);
    } else {
        echo "\nFalha ou bloqueio de permissão (RLS) ao ler a tabela '{$nomeDaTabela}'.\n";
    }

} catch (Exception $e) {
    echo "Erro inesperado: " . $e->getMessage();
}
?>