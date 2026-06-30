<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureFeaturePermission
{
    private array $featureMap = [
        'manage_users' => ['admin'],
        'manage_import' => ['admin', 'associe'],
        'export_data' => ['admin', 'associe', 'chef'],
        'view_audit' => ['admin', 'associe'],
        'manage_schedules' => ['admin'],
    ];

    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $role = strtolower((string) session('user_role', ''));
        $allowedRoles = $this->featureMap[$feature] ?? ['admin'];

        if (! in_array($role, $allowedRoles, true)) {
            abort(403, 'Permission insuffisante.');
        }

        return $next($request);
    }
}
