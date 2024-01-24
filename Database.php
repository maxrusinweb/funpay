<?php

namespace FpDbTest;

use Exception;
use mysqli;

class Database implements DatabaseInterface
{
    public const SPECIAL_VALUE = 'SPECIAL_VALUE';

    private mysqli $mysqli;

    public function __construct(mysqli $mysqli)
    {
        $this->mysqli = $mysqli;
    }

    public function buildQuery(string $template, array $params = []): string
    {
        $query = new Query();
        $query->setTemplate($template);
        $query->setParams($params);
        $sql = $query->makeSQL();

        return $sql;
    }

    public function skip()
    {
        return self::SPECIAL_VALUE;
    }
}
