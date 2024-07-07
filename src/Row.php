<?php

namespace Inmanturbo\ImportFieldMapper;

use Illuminate\Contracts\Support\Arrayable;

class Row implements Arrayable
{
    public function __construct(public array $row)
    {
        $this->row = collect($row)->mapWithKeys(fn($value, $key) => [(string) str()->of($key)->lower()->snake() => $value])->toArray();
    }

    public function value(string $key): mixed
    {
        return $this->row[$key] ?? null;
    }

    public function isMappableValue(string $value) : bool
    {
        return isset($this->row[$value]) && !empty($this->row[$value]);
    }

    public function isMappableKey(string $key) : bool
    {
        return isset($this->row[$key]);
    }

    public function toArray(): array
    {
        return $this->row;
    }
}