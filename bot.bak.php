<?php
require_once 'database.php';

// Carrega as variáveis do .env
$envPath = __DIR__ . '/.env';
$env = [];
if (file_exists($envPath)) {
    $env = parse_ini_file($envPath);
}

// Obtém o token do Telegram do .env
$botToken = $env['TELEGRAM_BOT_TOKEN'] ?? '';

// Instancia o banco de dados (engatilhado para uso futuro)
$database = new Database();

// Recebe o payload do Telegram via php://input
$content = file_get_contents("php://input");
$update = json_decode($content, true);

// Verifica se a estrutura esperada (mensagem) existe no JSON recebido
if (isset($update["message"])) {
    // Extrai o chat_id e o text
    $chat_id = $update["message"]["chat"]["id"];
    $text = $update["message"]["text"] ?? '';

    // Condição para o comando /start
    if ($text === '/start') {
        $mensagem_resposta = "Olá, Irmão! O Bot da Loja Renascença está online. Seu ID do Telegram é: " . $chat_id;
        
        // URL da API do Telegram para enviar a mensagem
        $url = "https://api.telegram.org/bot" . $botToken . "/sendMessage";
        
        // Dados para enviar via POST
        $data = [
            'chat_id' => $chat_id,
            'text' => $mensagem_resposta
        ];

        // Opções do stream context para fazer o POST
        $options = [
            'http' => [
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                'method'  => 'POST',
                'content' => http_build_query($data),
            ]
        ];
        
        $context = stream_context_create($options);
        
        // Envia a requisição usando file_get_contents
        file_get_contents($url, false, $context);
    }
}

// Responde com HTTP 200 no final
http_response_code(200);
echo "OK";
?>