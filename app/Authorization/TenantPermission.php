<?php

namespace App\Authorization;

class TenantPermission
{
    public const string All = '*';

    public const string TenantView = 'tenant.view';

    public const string TenantUpdate = 'tenant.update';

    public const string TenantDelete = 'tenant.delete';

    public const string TenantManageMembers = 'tenant.manage_members';

    public const string TenantManageRoles = 'tenant.manage_roles';

    public const string TenantManageBranding = 'tenant.manage_branding';

    public const string TenantManageIntegrations = 'tenant.manage_integrations';

    public const string TenantViewReports = 'tenant.view_reports';

    public const string TransactionsViewOwn = 'transactions.view_own';

    public const string TransactionsViewTeam = 'transactions.view_team';

    public const string TransactionsViewAll = 'transactions.view_all';

    public const string DocumentsUpload = 'documents.upload';

    public const string DocumentsReview = 'documents.review';

    public const string ComplianceManage = 'compliance.manage';

    /**
     * Get every supported tenant permission.
     *
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::TenantView,
            self::TenantUpdate,
            self::TenantDelete,
            self::TenantManageMembers,
            self::TenantManageRoles,
            self::TenantManageBranding,
            self::TenantManageIntegrations,
            self::TenantViewReports,
            self::TransactionsViewOwn,
            self::TransactionsViewTeam,
            self::TransactionsViewAll,
            self::DocumentsUpload,
            self::DocumentsReview,
            self::ComplianceManage,
        ];
    }
}
