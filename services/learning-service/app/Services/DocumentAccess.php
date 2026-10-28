<?php

namespace App\Services;

use Illuminate\Database\Query\Builder;

class DocumentAccess
{
    public function scope(Builder $query, array $actor, string $permission, string $level = 'view'): Builder
    {
        $grants = array_filter($actor['authorization']['grants'], fn ($g) => in_array($permission, $g['permissions'] ?? [], true)
            && (empty($g['expires_at']) || strtotime($g['expires_at']) > now()->timestamp));
        $query->where('documents.organization_id', $actor['organization_id']);
        $query->where(function (Builder $q) use ($actor, $grants): void {
            $q->whereRaw('1 = 0');
            foreach ($grants as $grant) {
                if ($grant['scope_type'] === 'organization') {
                    $q->orWhereRaw('1 = 1');
                } elseif ($grant['scope_type'] === 'self') {
                    $q->orWhere('documents.owner_user_id', $actor['user_id']);
                } elseif ($grant['scope_type'] === 'org_unit') {
                    $q->orWhereIn('documents.org_unit_id', $grant['org_unit_ids'] ?? []);
                }
            }
        });

        // An explicit allow list replaces the default organization audience, even when all rules expire.
        $query->where(function (Builder $q) use ($actor, $level, $grants): void {
            $q->where(function (Builder $default): void {
                $default->whereNotExists(fn ($r) => $r->selectRaw('1')->from('document_access_rules as acl_default')
                    ->whereColumn('acl_default.document_id', 'documents.id')->where('acl_default.effect', 'allow'));
            })->orWhereExists(fn ($r) => $this->matchingRule($r, $actor, $level, 'allow', $grants));
        });
        $query->whereNotExists(fn ($r) => $this->matchingRule($r, $actor, $level, 'deny', array_filter($actor['authorization']['grants'], fn ($g) => empty($g['expires_at']) || strtotime($g['expires_at']) > now()->timestamp)));

        return $query;
    }

    private function matchingRule(Builder $query, array $actor, string $level, string $effect, array $grants): Builder
    {
        $levels = match ($level) {
            'manage' => ['manage'],
            'download' => ['download', 'manage'],
            default => ['view', 'download', 'manage'],
        };
        if ($effect === 'deny') {
            $levels = $level === 'download' ? ['view', 'download', 'manage'] : ($level === 'view' ? ['view', 'manage'] : ['manage']);
        }

        return $query->selectRaw('1')->from('document_access_rules as acl')
            ->whereColumn('acl.document_id', 'documents.id')->where('acl.effect', $effect)
            ->whereIn('acl.access_level', $levels)
            ->where(fn ($q) => $q->whereNull('acl.starts_at')->orWhere('acl.starts_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('acl.ends_at')->orWhere('acl.ends_at', '>', now()))
            ->where(function (Builder $q) use ($actor, $grants): void {
                $q->where('acl.principal_type', 'organization')
                    ->orWhere(fn ($p) => $p->where('acl.principal_type', 'user')->where('acl.principal_id', $actor['user_id']))
                    ->orWhere(fn ($p) => $p->whereIn('acl.principal_type', ['department', 'org_unit'])->whereIn('acl.principal_id', $actor['authorization']['org_unit_ids']));
                // Role ACL must match the SAME grant's scope as the requested action.
                foreach ($grants as $grant) {
                    $q->orWhere(function (Builder $role) use ($grant, $actor): void {
                        $role->where('acl.principal_type', 'role')->where('acl.principal_id', $grant['role_id']);
                        if ($grant['scope_type'] === 'org_unit') {
                            $role->whereIn('documents.org_unit_id', $grant['org_unit_ids'] ?? []);
                        } elseif ($grant['scope_type'] === 'self') {
                            $role->where('documents.owner_user_id', $actor['user_id']);
                        } elseif ($grant['scope_type'] !== 'organization') {
                            $role->whereRaw('1 = 0');
                        }
                    });
                }
            });
    }
}
