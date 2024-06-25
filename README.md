# volt-csv-import-field-mapper

Component to allow user to map fields for csv imports

## Usage

```php
use Spatie\SimpleExcel\SimpleExcelReader;

use function Livewire\Volt\{on, state};

state([
    'importMap' => [
        'display_name' => 'Display Name',
        'email' => 'Email',
    ],
    'uploadDialog' => false,
]);

on([
    'import-fields-mapper-updated-uploaded-file' => fn($uploadedCsvFile) => $this->uploadedCsvFile = $uploadedCsvFile,
    'import-mapper-updated-mapped-import-fields' => fn($mappedImportFields) => $this->mappedImportFields = $mappedImportFields,
]);


$importCsv = function () {

    $reader = SimpleExcelReader::create(storage_path(config('import-field-mapper'). '/' . $this->uploadedCsvFile));

    $reader->getRows()->each(function ($row) {
        $row = collect($row)->mapWithKeys(function ($value, $key) {
            // !important mappedImportField keys will come back lower and snake case
            return [(string) str()->of($key)->lower()->snake() => $value];
        })->toArray();

        $contact = new Contact;

        // map fields from mappedImportFields
        foreach ($this->mappedImportFields as $key => $value) {
            // only save if value is not empty
            if (isset($row[$value]) && $row[$value] != '') {
                $contact->$key = $row[$value];
            }elseif(isset($row[$this->importMap[$key]]) && $row[$this->importMap[$key]] != '') {
                $contact->$key = $row[$this->importMap[$key]];
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
            @livewire('contacts.field-mapper', ['importFieldMap' => $this->importMap,])
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
