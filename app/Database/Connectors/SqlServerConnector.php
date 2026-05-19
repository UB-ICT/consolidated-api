<?php

namespace App\Database\Connectors;

use Illuminate\Database\Connectors\SqlServerConnector as BaseSqlServerConnector;
use PDO;

/**
 * Microsoft pdo_sqlsrv rejects several PDO attributes that Laravel sets by default
 * (e.g. ATTR_ORACLE_NULLS, ATTR_STRINGIFY_FETCHES), which causes:
 * SQLSTATE[IMSSP]: An invalid attribute was designated on the PDO object.
 */
class SqlServerConnector extends BaseSqlServerConnector
{
    protected $options = [
        PDO::ATTR_CASE => PDO::CASE_NATURAL,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ];
}
