<?php

namespace App\Models;

use App\Config\Database;
<?php
namespace App\Models;

use PDO;
use App\Config\Database;

class Sessao
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function obterProximaSessao()
    {
        $hoje = date('Y-m-d H:i:s');
        $sql = "SELECT id, data, grau, tipo, pauta FROM sessoes WHERE data > :hoje ORDER BY data ASC LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['hoje' => $hoje]);
        $sessao = $stmt->fetch(PDO::FETCH_ASSOC);
        return $sessao ?: null;
    }
}
        $result = $stmt->fetch();
        return $result ?: null;
    }
}