<?php

namespace App\TransactionFields;

use App\Models\Tenant;
use App\Models\TransactionFieldOverride;
use App\Models\TransactionTemplate;
use App\Models\User;
use Illuminate\Support\Collection;

class TransactionFieldResolver
{
    /**
     * Resolve template fields with tenant, team, and user display overrides.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function resolveForTemplate(TransactionTemplate $template, Tenant $tenant, ?int $teamId = null, ?User $user = null): Collection
    {
        $template->loadMissing('fields.definition');

        $definitionIds = $template->fields
            ->pluck('field_definition_id')
            ->all();

        $overrides = TransactionFieldOverride::query()
            ->forTenant($tenant)
            ->whereIn('field_definition_id', $definitionIds)
            ->where(function ($query) use ($tenant, $teamId, $user): void {
                $query->where(function ($query) use ($tenant): void {
                    $query
                        ->where('scope_type', TransactionFieldOverride::SCOPE_TENANT)
                        ->where('scope_id', $tenant->id);
                });

                if ($teamId !== null) {
                    $query->orWhere(function ($query) use ($teamId): void {
                        $query
                            ->where('scope_type', TransactionFieldOverride::SCOPE_TEAM)
                            ->where('scope_id', $teamId);
                    });
                }

                if ($user !== null) {
                    $query->orWhere(function ($query) use ($user): void {
                        $query
                            ->where('scope_type', TransactionFieldOverride::SCOPE_USER)
                            ->where('scope_id', $user->id);
                    });
                }
            })
            ->get()
            ->sortBy(fn (TransactionFieldOverride $override): int => $this->overrideRank($override->scope_type))
            ->groupBy('field_definition_id');

        return $template->fields
            ->sortBy('sort_order')
            ->values()
            ->map(function ($templateField) use ($overrides): array {
                $definition = $templateField->definition;
                $resolved = [
                    'template_field_id' => $templateField->id,
                    'field_definition_id' => $definition->id,
                    'field_key' => $definition->field_key,
                    'data_type' => $definition->data_type,
                    'value_schema' => $definition->value_schema,
                    'section' => $templateField->section,
                    'label' => $templateField->label ?? $definition->label,
                    'unit' => $templateField->unit ?? $definition->default_unit,
                    'format' => $templateField->format ?? $definition->default_format,
                    'options' => $templateField->options ?? $definition->default_options,
                    'option_labels' => null,
                    'is_required' => $templateField->is_required,
                    'is_visible' => $templateField->is_visible,
                    'sort_order' => $templateField->sort_order,
                    'visibility_rules' => $templateField->visibility_rules,
                    'validation_rules' => $templateField->validation_rules,
                    'calculation_rules' => $templateField->calculation_rules,
                    'date_trigger_rules' => $templateField->date_trigger_rules,
                ];

                foreach ($overrides->get($definition->id, collect()) as $override) {
                    $resolved = $this->applyOverride($resolved, $override);
                }

                return $resolved;
            });
    }

    /**
     * Apply one scoped override to a resolved field array.
     *
     * @param  array<string, mixed>  $resolved
     * @return array<string, mixed>
     */
    private function applyOverride(array $resolved, TransactionFieldOverride $override): array
    {
        foreach (['label', 'unit', 'format', 'option_labels', 'is_required', 'is_visible', 'sort_order'] as $attribute) {
            if ($override->{$attribute} !== null) {
                $resolved[$attribute] = $override->{$attribute};
            }
        }

        return $resolved;
    }

    /**
     * Higher-precedence display overrides run later.
     */
    private function overrideRank(string $scopeType): int
    {
        return match ($scopeType) {
            TransactionFieldOverride::SCOPE_TENANT => 10,
            TransactionFieldOverride::SCOPE_TEAM => 20,
            TransactionFieldOverride::SCOPE_USER => 30,
            default => 0,
        };
    }
}
