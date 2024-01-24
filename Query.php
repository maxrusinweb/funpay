<?php

namespace FpDbTest;

use Exception;

class Query
{
    private const ALLOWED_TYPES = [
        'integer',
        'string',
        'double',
        'boolean',
        'NULL'
    ];
    private const POINTER = '?';
    private const START_CONDITION = '{';
    private const END_CONDITION = '}';

    private string $template;
    private array $params;

    public function setTemplate(string $template)
    {
        $this->template = $template;
    }

    public function setParams(array $params)
    {
        $this->params = $params;
    }

    public function makeSQL(): string
    {
        //check matching template to params
        if (substr_count($this->template, self::POINTER) !== count($this->params)) {
            throw new Exception('Wrong request');
        }

        $sql = $this->template;

        //handle conditional blocks
        while (($start = strpos($sql, self::START_CONDITION)) !== false && ($end = strpos($sql, self::END_CONDITION)) !== false) {
            $sql = $this->skipSpecialValues($sql, $start, $end);
        }

        //parse params
        $i = 0;
        while (($pos = strpos($sql, self::POINTER)) !== false) {
            //get specifier
            $specifier = substr($sql, $pos+1, 1);

            //format param
            $param = $this->formatParam($this->params[$i], $specifier);

            //put param into query
            $len = strlen(trim($specifier))+1;
            $sql = substr_replace($sql, $param, $pos, $len);

            $i++;
        }

        return $sql;
    }

    private function skipSpecialValues($sql, $start, $end)
    {
        //check params inside the block
        $firstIndex = substr_count(substr($sql, 0, $start), self::POINTER);
        $length = substr_count(substr($sql, $start, $end-$start), self::POINTER);
        $blockParams = array_slice($this->params, $firstIndex, $length);

        if (in_array(Database::SPECIAL_VALUE, $blockParams, true)) {
            //remove the whole block
            $sql = substr_replace($sql, '', $start, $end-$start+1);

            //remove related params
            for ($i = $firstIndex; $i < ($firstIndex + $length); $i++) {
                unset($this->params[$i]);
            }
            $this->params = array_values($this->params);

        } else {
            //remove block brackets only
            $sql = substr_replace($sql, '', $start, 1);
            $sql = substr_replace($sql, '', $end-1, 1);
        }

        return $sql;
    }


    private function formatParam($param, string $specifier)
    {
        $type = $this->getParamType($param, $specifier);

        if ($param === null) {
            $param = 'NULL';
        } elseif ($type) {
            settype($param, $type);
        }

        if ($type === 'string') {
            $param = $this->addSlashes($param, $specifier);
        }

        if ($type === 'array') {
            foreach ($param as &$item) {
                $itemType = gettype($item);

                if ($itemType === 'string') {
                    $item = $this->addSlashes($item, $specifier);
                }
                if ($item === null) {
                    $item = 'NULL';
                }
            }
            //handle associative array
            if ($param !== array_values($param)) {
                foreach ($param as $key => &$item) {
                    $item = "`$key` = $item";
                }
            }

            $param = implode(', ', $param);
        }

        return $param;
    }


    private function getParamType($param, $specifier): string
    {
        switch (trim($specifier)) {
            case 'd':
                $type = 'integer';
                break;
            case 'f':
                $type = 'double';
                break;
            case 'a':
                $type = 'array';
                break;
            case '#':
                $type = gettype($param);
                break;
            case '':
                $type = gettype($param);
                if (!in_array($type, self::ALLOWED_TYPES)) {
                    throw new Exception('Forbidden type of unspecified param: ' . $type);
                }
                break;
            default:
                throw new Exception('Unknown specifier: ' . $specifier);
                break;
        }

        if ($param === null && !in_array($specifier,['d', 'f', ''])) {
            throw new Exception('Forbidden specifier for null value: ' . $specifier);
        }

        return $type;
    }


    private function addSlashes($param, $specifier): string
    {
        $slash = ($specifier === '#') ? "`" : "'";
        $param = $slash . $param . $slash;
        return $param;
    }
}
