# volt-csv-import-field-mapper

Component to allow user to map fields for csv imports

## Tailwind Purge
```
'./vendor/inmanturbo/volt-csv-import-field-mapper/resources/views/**/*.blade.php',
```

## Usage

```php
use Illuminate\Support\Facades\Storage;
use Spatie\SimpleExcel\SimpleExcelReader;
use Inmanturbo\ImportFieldMapper\ImportFieldMapper;
use App\Models\Contact;

use function Livewire\Volt\{on, state};

state([
    'importMap' => [
        'display_name' => 'Display Name',
        'email' => 'Email',
    ],
    'uploadDialog' => false,
]);

on([
    'import-field-mapper-updated-uploaded-file' => fn($uploadedCsvFile) => $this->uploadedCsvFile = $uploadedCsvFile,
    'import-field-mapper-updated-mapped-import-fields' => fn($mappedImportFields) => $this->mappedImportFields = $mappedImportFields,
]);


$importCsv = function () {
   $reader = SimpleExcelReader::create(Storage::path(config('import-field-mapper.path'). '/' . $this->uploadedCsvFile));

    $reader->getRows()->each(function ($row) {
        $row = ImportFieldMapper::row($row);

        $contact = new Contact;

        // map fields from mappedImportFields
        foreach ($this->mappedImportFields as $key => $value) {
  
            if ($row->isMappableValue($row[$value])) {
                $contact->$key = $row->value($value);
            }elseif($row->isMappableValue($this->importMap[$key])) {
                $contact->$key = $row->value($this->importMap[$key]);
            }
        }

        $contact->save();
    });

    $this->uploadedCsvFile = null;
    $this->uploadDialog = false;
    $this->banner($reader->getRows()->count() . ' Contacts imported successfully');
};

<div>

    <x-button wire:click="$set('uploadDialog', true)" type="file" class="justify-center w-full space-x-1 sm:w-40">
        {{_('Upload Csv')}}
    </x-button>

    <x-dialog-modal wire:model="uploadDialog" maxWidth="full">
        <x-slot name="title">
            {{ __('Import Contacts from CSV file') }}
        </x-slot>

        <x-slot name="content">
            @livewire('import-field-mapper', ['importFieldMap' => $this->importMap,])
        </x-slot>
        
        <x-slot name="footer">
            <x-secondary-button wire:click="$set('uploadDialog', false)" wire:loading.attr="disabled">
                {{ __('Cancel') }}
            </x-secondary-button>

            <x-button class="ml-3" wire:click="importCsv" wire:loading.attr="disabled">
                <div wire:loading>
                    <svg class="w-4 mr-2 animate-spin" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 28 28" fill="none" stroke-width="3"><circle cx="14" cy="14" r="12" stroke="currentColor" class="opacity-25"></circle><path d="M26 14c0-6.627-5.373-12-12-12" stroke-linecap="round" stroke="currentColor" class="opacity-75"></path></svg>
                </div>
                {{ __('Save') }}
            </x-button>
        </x-slot>
    </x-dialog-modal>
</div>

```
