<?php
/**
 * Authentication + access-control helpers.
 * Requires config.php and functions.php to already be loaded.
 */

function current_user() {
    return $_SESSION['user'] ?? null;
}

function is_logged_in() {
    return current_user() !== null;
}

function is_admin() {
    $u = current_user();
    return $u && $u['role'] === 'admin';
}

/** Call at the top of any page that needs a logged-in user. */
function require_login() {
    if (!is_logged_in()) {
        redirect('/login.php');
    }
}

/** Call at the top of any admin-only page. */
function require_admin() {
    require_login();
    if (!is_admin()) {
        flash('danger', 'You do not have permission to view that page.');
        redirect('/index.php');
    }
}

/**
 * Can the current user manage (add payments/enroll) this subject?
 * Admin can manage every subject; a teacher can only manage their own.
 */
function can_manage_subject($subject) {
    $u = current_user();
    if (!$u) return false;
    return $u['role'] === 'admin' || $u['subject'] === $subject;
}
