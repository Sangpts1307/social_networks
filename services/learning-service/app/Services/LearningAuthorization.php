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

        return $actor;
    }
}
