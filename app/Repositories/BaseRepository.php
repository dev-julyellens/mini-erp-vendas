<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

/**
 * Classe base para repositórios: injeção de PDO e acesso padronizado.
 * Repositórios com multi-tenant podem usar traits (ex.: `CompanyScope`) nos filhos.
 */
abstract class BaseRepository
{
    protected PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    public function getConnection(): PDO
    {
        return $this->db;
    }
}
