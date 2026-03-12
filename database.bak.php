<?php

class Database {
    private $supabaseUrl;
    private $supabaseKey;

    public function __construct() {
        $this->loadEnv();
    }

    private function loadEnv() {
        $envPath = __DIR__ . '/.env';
        if (file_exists($envPath)) {
            $env = parse_ini_file($envPath);
            if ($env) {
                $this->supabaseUrl = $env['EXPO_PUBLIC_SUPABASE_URL'] ?? '';
                $this->supabaseKey = $env['EXPO_PUBLIC_SUPABASE_KEY'] ?? '';
            }
        }
    }

    /**
     * Helper to make REST requests to Supabase
     *
     * @param string $endpoint e.g., '/rest/v1/your_table_name?select=*'
     * @param string $method 'GET', 'POST', 'PATCH', 'DELETE'
     * @param array|null $data Payload for POST/PATCH
     * @return array|null Response decoded from JSON
     */
    public function request($endpoint, $method = 'GET', $data = null) {
        $url = rtrim($this->supabaseUrl, '/') . $endpoint;

        $headers = [
            "apikey: " . $this->supabaseKey,
            "Authorization: Bearer " . $this->supabaseKey,
            "Content-Type: application/json",
            "Prefer: return=representation"
        ];

        $options = [
            'http' => [
                'header'  => implode("\r\n", $headers),
                'method'  => $method,
                'ignore_errors' => true // allow reading bodies of non-2xx responses
            ]
        ];

        if ($data !== null) {
            $options['http']['content'] = json_encode($data);
        }

        $context  = stream_context_create($options);
        $result = @file_get_contents($url, false, $context);

        if ($result === false) {
            $error = error_get_last();
            echo "Erro na requisição HTTPS. Detalhes: " . ($error['message'] ?? 'Desconhecido') . "\nURL: " . $url . "\n";
            echo "\n--------------------------------------------------------------\n";
            echo "IMPORTANTE: Se o erro for 'Unable to find the wrapper \"https\"',\n";
            echo "você precisa ativar a extensão OpenSSL no seu php.ini.\n";
            echo "--------------------------------------------------------------\n";
            return null;
        }

        $decodedResponse = json_decode($result, true);

        // Optional check for HTTP response code
        if (isset($http_response_header) && count($http_response_header) > 0) {
            preg_match('#HTTP/\d+\.\d+ (\d+)#', $http_response_header[0], $match);
            $httpCode = intval($match[1]);
            
            if ($httpCode >= 400) {
                echo "Erro do Supabase (HTTP {$httpCode}): " . print_r($decodedResponse, true) . "\nURL: " . $url . "\n";
                return null;
            }
        }

        return $decodedResponse;
    }

    // A check function to test connection using an arbitrary REST endpoint
    public function testConnection() {
        // Query some metadata or an empty table endpoint if none exist, just to check auth
        return $this->request('/rest/v1/?apikey=' . $this->supabaseKey, 'GET');
    }
}
?>