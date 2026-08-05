<?php

use App\Actions\Documents\BuildDocumentStoragePath;
use App\Models\Document;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('documents use a private local disk by default for development', function () {
    expect(config('documents.storage.disk'))->toBe('documents')
        ->and(config('documents.storage.path_prefix'))->toBe('documents')
        ->and(config('documents.storage.visibility'))->toBe('private')
        ->and(config('filesystems.disks.documents.driver'))->toBe('local')
        ->and(config('filesystems.disks.documents.root'))->toBe(storage_path('app/private'))
        ->and(config('filesystems.disks.documents.visibility'))->toBe('private')
        ->and(config('filesystems.disks.documents.throw'))->toBeTrue();
});

test('s3 disk keeps s3 compatible endpoint assumptions configurable', function () {
    expect(config('filesystems.disks.s3.driver'))->toBe('s3')
        ->and(config('filesystems.disks.s3.endpoint'))->toBe(env('AWS_ENDPOINT'))
        ->and(config('filesystems.disks.s3.use_path_style_endpoint'))->toBe(env('AWS_USE_PATH_STYLE_ENDPOINT', false));
});

test('document storage paths are tenant scoped and do not trust uploaded filenames', function () {
    $tenant = Tenant::factory()->create();

    config(['documents.storage.path_prefix' => 'document-binaries']);

    $path = app(BuildDocumentStoragePath::class)
        ->handle($tenant, '..\\closing-package.pdf', 'signed-packages/package-1');

    expect($path)->toBe("document-binaries/tenants/{$tenant->id}/documents/signed-packages/package-1/closing-package.pdf");
});

test('document factory records configured storage disk and tenant scoped path', function () {
    config([
        'documents.storage.disk' => 's3',
        'documents.storage.path_prefix' => 'c2c-documents',
    ]);

    $document = Document::factory()->create();

    expect($document->storage_disk)->toBe('s3')
        ->and($document->storage_path)->toStartWith("c2c-documents/tenants/{$document->tenant_id}/documents/")
        ->and($document->storage_path)->toEndWith('/'.$document->original_filename);
});

test('configured document disk can be faked for upload tests', function () {
    Storage::fake(config('documents.storage.disk'));

    $document = Document::factory()->create();

    Storage::disk($document->storage_disk)->put($document->storage_path, 'contract bytes');

    Storage::disk($document->storage_disk)->assertExists($document->storage_path);
});
