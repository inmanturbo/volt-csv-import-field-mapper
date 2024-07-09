<?php

namespace Inmanturbo\ImportFieldMapper;

use Illuminate\Support\Facades\Schema;

trait HandlesMappedEloquentAttributes
{
    /**
     * Create a instance.
     */
    public function __construct(
        public array $row,
        public array $importMap,
        public array $mappedImportFields,
     ) {
         //
     }

     public function handle() : void
     {
        $model = $this->modelExists() ? $this->updateModel() : $this->createModel();
     }

     public function createModel(): mixed
     {
         return $this->modelClass()::create($this->attributes());
     }

    public function updateModel(): mixed
    {
        return $this->model()->update($this->attributes());
    }

    protected function model(): mixed
    {
        return $this->query()->first();
    }

     protected function uniqueFields(): array
     {
         return array_merge(['id', 'uuid'], [$this->modelKey()]);
     }

     protected function newModel(): mixed
     {
         return new $this->modelClass();
     }

     protected function modelKey(): string
     {
         return $this->newModel()->getKeyName();
     }

     abstract protected function modelClass() : string;

     protected function schemaHasColumn($column): bool
     {
         return Schema::connection($this->newModel()->getConnectionName())->hasColumn($this->modelClass()::getModel()->getTable(), $column);
     }

     protected function modelExists(): bool
     {
 
        if (empty(array_intersect($this->uniqueFields(), array_keys($this->row)))) {
            return false;
        }

 
         return $this->query()->exists();
     }

     protected function query(): mixed
     {
        $query = $this->modelClass()::query()
            ->where($this->modelKey(), $this->row[$this->modelKey()] ?? null);
       
       foreach ($this->uniqueFields() as $field) {
           if ($field === $this->modelKey()) {
               continue;
           }

           if(!$this->schemaHasColumn($field)) {
               continue;
           }

           $query->orWhere($field, $this->row[$field] ?? null);
       }

       $query->limit(1);

        return $query;
     }

     private function attributes(): array
     {
         if (!$this->modelExists()) {
             return $this->mappedAttributes();
         }
 
         $model = $this->query()->first();
 
         return $model->fill($this->mappedAttributes())->toArray();
     }

     protected function mappedAttributes()
     {
         $row = ImportFieldMapper::row($this->row);
 
         $attributes = [];
 
         foreach ($this->mappedImportFields as $key => $value) {
             if ($row->isMappableValue($value)) {
                 $attributes[$key] = $row->value($value);
             }elseif($row->isMappableValue($this->importMap[$key])) {
                 $attributes[$key] = $row->value($this->importMap[$key]);
             }
         }
 
         return $attributes;
     }
 
}