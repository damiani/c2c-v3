<?php

namespace App\Actions\Documents;

use App\Models\Tenant;
use Illuminate\Support\Str;

class BuildDocumentStoragePath
{
    /**
     * Build a tenant-scoped object-storage path for a canonical document binary.
     */
    public function handle(Tenant|int $tenant, string $filename, ?string $directory = null): string
    {
        $tenantId = $tenant instanceof Tenant ? $tenant->getKey() : $tenant;
        $safeFilename = basename(str_replace('\\', '/', $filename));
        $pathPrefix = trim((string) config('documents.storage.path_prefix', 'documents'), '/');
        $directory = trim($directory ?: (string) Str::uuid(), '/');

        return collect([
            $pathPrefix ?: null,
            'tenants',
            $tenantId,
            'documents',
            $directory,
            $safeFilename,
        ])
            ->filter(fn (mixed $segment): bool => filled($segment))
            ->implode('/');
    }
}
