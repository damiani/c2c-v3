<?php

namespace App\Authorization;

use App\Models\TenantMembership;
use Illuminate\Support\Arr;

class TenantPermissionRegistry
{
    /**
     * Get the built-in role definitions every tenant can use.
     *
     * @return array<string, array{name: string, description: string, permissions: list<string>}>
     */
    public static function systemRoles(): array
    {
        return [
            TenantMembership::ROLE_OWNER => [
                'name' => 'Owner',
                'description' => 'Full tenant administration, including tenant deletion.',
                'permissions' => [TenantPermission::All],
            ],
            TenantMembership::ROLE_ADMIN => [
                'name' => 'Admin',
                'description' => 'Tenant administration without destructive tenant deletion.',
                'permissions' => [
                    TenantPermission::TenantView,
                    TenantPermission::TenantUpdate,
                    TenantPermission::TenantManageMembers,
                    TenantPermission::TenantManageRoles,
                    TenantPermission::TenantManageBranding,
                    TenantPermission::TenantManageIntegrations,
                    TenantPermission::TenantViewReports,
                    TenantPermission::TransactionsViewAll,
                    TenantPermission::DocumentsUpload,
                    TenantPermission::DocumentsReview,
                    TenantPermission::ComplianceManage,
                ],
            ],
            TenantMembership::ROLE_BROKER_ADMIN => [
                'name' => 'Broker/Admin',
                'description' => 'Brokerage-level oversight, reports, and transaction visibility.',
                'permissions' => [
                    TenantPermission::TenantView,
                    TenantPermission::TenantViewReports,
                    TenantPermission::TransactionsViewTeam,
                    TenantPermission::TransactionsViewAll,
                    TenantPermission::DocumentsUpload,
                    TenantPermission::DocumentsReview,
                    TenantPermission::ComplianceManage,
                ],
            ],
            TenantMembership::ROLE_COORDINATOR => [
                'name' => 'Coordinator/Assistant',
                'description' => 'Transaction support, document review, and deadline coordination.',
                'permissions' => [
                    TenantPermission::TenantView,
                    TenantPermission::TransactionsViewTeam,
                    TenantPermission::DocumentsUpload,
                    TenantPermission::DocumentsReview,
                ],
            ],
            TenantMembership::ROLE_BACK_OFFICE => [
                'name' => 'Back Office',
                'description' => 'Compliance and document review workflows across tenant transactions.',
                'permissions' => [
                    TenantPermission::TenantView,
                    TenantPermission::TenantViewReports,
                    TenantPermission::TransactionsViewAll,
                    TenantPermission::DocumentsReview,
                    TenantPermission::ComplianceManage,
                ],
            ],
            TenantMembership::ROLE_AGENT => [
                'name' => 'Agent',
                'description' => 'Standard agent access to owned transactions and document uploads.',
                'permissions' => [
                    TenantPermission::TenantView,
                    TenantPermission::TransactionsViewOwn,
                    TenantPermission::DocumentsUpload,
                ],
            ],
            TenantMembership::ROLE_MEMBER => [
                'name' => 'Member',
                'description' => 'Default retail/unaffiliated member access.',
                'permissions' => [
                    TenantPermission::TenantView,
                    TenantPermission::TransactionsViewOwn,
                    TenantPermission::DocumentsUpload,
                ],
            ],
        ];
    }

    /**
     * Get a built-in role definition by slug.
     *
     * @return array{name: string, description: string, permissions: list<string>}|null
     */
    public static function systemRole(string $role): ?array
    {
        return self::systemRoles()[$role] ?? null;
    }

    /**
     * Get every built-in role slug.
     *
     * @return list<string>
     */
    public static function systemRoleSlugs(): array
    {
        return array_keys(self::systemRoles());
    }

    /**
     * Get fallback permissions for a built-in role slug.
     *
     * @return list<string>
     */
    public static function permissionsForRole(string $role): array
    {
        return self::systemRole($role)['permissions'] ?? [];
    }

    /**
     * Determine whether a permission list grants an ability.
     *
     * @param  list<string>|array<string, mixed>|null  $permissions
     */
    public static function allows(?array $permissions, string $permission): bool
    {
        $normalized = self::normalizePermissions($permissions);

        return in_array(TenantPermission::All, $normalized, true)
            || in_array($permission, $normalized, true);
    }

    /**
     * Normalize stored permissions into a list of allowed permission strings.
     *
     * @param  list<string>|array<string, mixed>|null  $permissions
     * @return list<string>
     */
    public static function normalizePermissions(?array $permissions): array
    {
        if ($permissions === null) {
            return [];
        }

        $normalized = [];

        foreach ($permissions as $key => $value) {
            if (is_string($value)) {
                $normalized[] = $value;

                continue;
            }

            if (is_string($key) && $value === true) {
                $normalized[] = $key;
            }
        }

        return array_values(array_unique(Arr::where($normalized, fn (string $permission): bool => $permission !== '')));
    }
}
