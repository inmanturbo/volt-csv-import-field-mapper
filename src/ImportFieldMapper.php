<?php

namespace Inmanturbo\ImportFieldMapper;

class ImportFieldMapper
{
    public static function row(array $row): Row
    {
        return new Row($row);
    }
}