<?php

namespace App\Services;

use Illuminate\Http\Request;

class LearningAuthorization
{
    /** @return array{user_id:string, organization_id:string, permissions:array<int, string>} */
    public function requirePermission(Request $request, string $permission): array
    {
        $actor = $request->attributes->get('learning_actor');
        abort_unless(is_array($actor), 401, 'Unauthenticated.');
        abort_unless(in_array($permission, $actor['permissions'] ?? [], true), 403, 'You do not have permission for this learning operation.');

        $request->attributes->set('learning_permission', $permission);
        // A scoped grant cannot authorize an operation on an unscoped resource.
        if (! str_starts_with($permission, 'learning.documents.')) {
            abort_unless(collect($actor['authorization']['grants'])->contains(fn ($g) => $g['scope_type'] === 'organization' && in_array($permission, $g['permissions'], true)), 403);
        }

        return $actor;
    }
}
