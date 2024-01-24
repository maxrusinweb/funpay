<?php

namespace FpDbTest;

interface DatabaseInterface
{
    public function buildQuery(string $template, array $params = []): string;

    public function skip();
}
