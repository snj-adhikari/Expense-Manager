<?php

namespace App\Http\Middleware;

use App\Role;
use Closure;
use Illuminate\Support\Facades\Gate;

class AuthGates
{
    public function handle($request, Closure $next)
    {
        $user = \Auth::user();

        if (!app()->runningInConsole() && $user) {
            $roles            = Role::with('permissions')->get();
            $permissionsArray = [];

            foreach ($roles as $role) {
                foreach ($role->permissions as $permissions) {
                    $permissionsArray[$permissions->title][] = $role->id;
                }
            }

            foreach ($permissionsArray as $title => $roles) {
                Gate::define($title, function (\App\User $user , $post = null) use ($roles, $title ) {
                    $current_roles = $user->roles->pluck('id')->toArray();
                    $count_in_permission = count(array_intersect($current_roles, $roles)) > 0;
                    if( $title === 'expense_delete' || $title === 'expense_edit' || $title === 'income_delete' || $title === 'income_edit') :
                        $is_admin = in_array( 1, $current_roles );
                        if( $post && !$is_admin ) {
                            return $post->created_by_id === $user->id && $count_in_permission;
                        } else {
                            return $count_in_permission;
                        }
                        
                    else : 
                        return $count_in_permission;
                    endif;
                });
            }
        }

        return $next($request);
    }
}
