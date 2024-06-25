<?php

use Illuminate\Support\Facades\Storage;
use Spatie\SimpleExcel\SimpleExcelReader;

use function Livewire\Volt\{computed, mount, protect, state, usesFileUploads};

usesFileUploads();

state(['importFieldMap' => []]);

state(['uploadedCsv' => null]);

state(['mappedImportFields' => []]);

state(['shouldCleanupStorage' => true]);

state(['storageDisk' => 'local']);

state(['storagePath' => fn () => config('field-mapper.path')]);

state(['maxFiles' => config('field-mapper.max_files')]);

state(['maxFilesize' => config('field-mapper.max_filesize')]);

state(['fileName' => null]);

mount(function (array $importFieldMap = [], bool $shouldCleanupStorage = true) {
	$this->importFieldMap = $importFieldMap;
	$this->importFields = $importFieldMap;
	$this->shouldCleanupStorage = $shouldCleanupStorage;
});

$updatedUploadedCsv = function () {
    if(count($this->importFields) > 0) {
		foreach($this->importFields as $field) {
			if(in_array($field, array_keys($this->importFieldMap))) {
				$this->mappedImportFields[$field] = $field;
			}elseif(in_array((string) str()->of($field)->lower()->snake(), array_keys($this->importFieldMap))) {
				$this->mappedImportFields[(string) str()->of($field)->lower()->snake()] = (string) str()->of($field)->lower()->snake();
			}
		}
	}

	$this->dispatch('import-fields-mapper-updated-uploaded-file', $this->fileName);
	$this->dispatch('import-fields-mapper-updated-mapped-import-fields', $this->mappedImportFields);
};

$updatedMappedImportFields = function () {
	$this->dispatch('import-mapper-updated-mapped-import-fields', $this->mappedImportFields);
};

$cleanupStorage = protect(function () {
	$files = Storage::disk($this->storageDisk)->files($this->storagePath);

	if (count($files) > $this->maxFiles) {
		$files = collect($files)->sortByDesc(function ($file) {
			return Storage::disk($this->storageDisk)->lastModified($file);
		})->values()->all();

		$filesToDelete = array_slice($files, $this->maxFiles);

		Storage::disk($this->storageDisk)->delete($filesToDelete);
	}
});

$importFields = computed(function () {
	if ($this->uploadedCsv) {
		if (! Storage::disk($this->storageDisk)->exists($this->storagePath)) {
			Storage::disk($this->storageDisk)->makeDirectory($this->storagePath);
		}

		if($this->shouldCleanupStorage) {
			$this->cleanupStorage($this->maxFiles);
		}

		$ulid = (string) str()->ulid();

		$this->fileName = $ulid . '.csv';

		$this->uploadedCsv->storeAs($this->storagePath, $ulid . '.csv', $this->storageDisk);
		$this->uploadedCsvPath = Storage::disk($this->storageDisk)->path($this->storagePath . '/' . $this->fileName);

		$csv = SimpleExcelReader::create(Storage::disk($this->storageDisk)->path($this->storagePath . '/' . $this->fileName))
			->getRows()
			->toArray();

		$firstRow = $csv[0];

		$keys = collect($firstRow)->mapWithKeys(function ($value, $key) {
			return [(string) str()->of($key)->lower()->snake() => $key];
		})->toArray();
		
		$sorted = array_merge(array_flip(array_keys($this->importFieldMap)), $keys);

		$invalid = function ($value) use ($keys){
			return !array_key_exists($value, $keys);
		};

		foreach($sorted as $key => $value) {
			if($invalid($key)) {
				$sorted[$value] = '';
				$sorted[$key] = '';
			}
		}

		return $sorted;
	}

    return $this->importFieldMap;
});
?>

<div class="grid grid-cols-6 gap-6">
    <div class="col-span-6 sm:col-span-2">
        <x-field-mapper::label for="name" value="{{ __('Upload Csv') }}" />
        <x-field-mapper::input id="name" type="file" class="block w-full mt-1" wire:model.defer="uploadedCsv" />

        <x-field-mapper::input-error for="uploaded_csv" class="mt-2" />

        <x-field-mapper::label for="mapper" class="mt-1" value="{{ __('Map Fields') }}" />

        <div class="grid grid-cols-2 gap-2 mt-1">
            <!-- field mapper -->
            @foreach($importFieldMap as $key => $mappableField)
                <x-field-mapper::input readonly type="text" class="block w-full mt-1" value="{{ $mappableField }}" />
                <x-field-mapper::select class="block w-full mt-1" :disabled="is_null($this->uploadedCsv)" wire:model.live="mappedImportFields.{{ $key }}">
                    <option></option>
                    @foreach($this->importFields as $fk => $field)
                        <option value="{{ $fk }}">{{ $field }}</option>
                    @endforeach
				</x-field-mapper::select>
            @endforeach
        </div>
    </div>
</div>