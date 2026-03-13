<?php
/**
 * Role-Based Access Control (RBAC) System
 * Include this file in admin pages to check permissions
 */

function get_user_role() {
    if (!isset($_SESSION['admin_id'])) {
        return null;
    }
    
    $sql = "SELECT u.role_id, r.name, r.permissions 
            FROM admin_users u 
            LEFT JOIN roles r ON u.role_id = r.id 
            WHERE u.id = ?";
    
    return dbFetchOne($sql, [$_SESSION['admin_id']]);
}

function has_permission($permission) {
    $role = get_user_role();
    
    if (!$role) {
        return false;
    }
    
    // Owner has all permissions
    if ($role['name'] === 'owner') {
        return true;
    }
    
    // Check permissions JSON
    $permissions = json_decode($role['permissions'], true);
    
    if (isset($permissions['all']) && $permissions['all']) {
        return true;
    }
    
    return isset($permissions[$permission]) && $permissions[$permission];
}

function require_permission($permission) {
    if (!has_permission($permission)) {
        setFlashMessage('error', 'You do not have permission to access this page');
        redirect('index.php');
    }
}

function get_user_permissions() {
    $role = get_user_role();
    if (!$role) {
        return [];
    }
    return json_decode($role['permissions'], true) ?? [];
}

function is_owner() {
    $role = get_user_role();
    return $role && $role['name'] === 'owner';
}

function is_manager() {
    $role = get_user_role();
    return $role && $role['name'] === 'manager';
}

function is_chef() {
    $role = get_user_role();
    return $role && $role['name'] === 'head_chef';
}

function is_waitstaff() {
    $role = get_user_role();
    return $role && $role['name'] === 'waitstaff';
}

function is_cashier() {
    $role = get_user_role();
    return $role && $role['name'] === 'cashier';
}
?>
