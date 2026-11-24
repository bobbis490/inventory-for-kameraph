<?php
/**
 * KameraPH Helper Functions
 */

if (!function_exists('base_url')) {
    function base_url($path = '') {
        // Detect base directory dynamically
        $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        $docRoot = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? '');
        
        // Find KameraPH in URL path
        $base = '/KameraPH';
        if (preg_match('#^(.*?/KameraPH)#i', $scriptName, $matches)) {
            $base = $matches[1];
        }

        $path = ltrim($path, '/');
        return rtrim($base, '/') . ($path !== '' ? '/' . $path : '');
    }
}

if (!function_exists('url')) {
    function url($path = '') {
        return base_url($path);
    }
}

if (!function_exists('asset_url')) {
    function asset_url($path = '') {
        return base_url('assets/' . ltrim($path, '/'));
    }
}

if (!function_exists('image_url')) {
    function image_url($imagePath) {
        $imagePath = trim((string)$imagePath);
        if ($imagePath === '') {
            return 'data:image/svg+xml;charset=UTF-8,%3Csvg%20width%3D%22400%22%20height%3D%22300%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20viewBox%3D%220%200%20400%20300%22%3E%3Crect%20width%3D%22400%22%20height%3D%22300%22%20fill%3D%22%23f1f5f9%22%2F%3E%3Cg%20transform%3D%22translate(170%2C%20100)%22%20fill%3D%22none%22%20stroke%3D%22%2394a3b8%22%20stroke-width%3D%222%22%3E%3Cpath%20d%3D%22M58%2048a4%204%200%200%201-4%204H6a4%204%200%200%201-4-4V20a4%204%200%200%201%204-4h8l5-7h18l5%207h8a4%204%200%200%201%204%204z%22%2F%3E%3Ccircle%20cx%3D%2230%22%20cy%3D%2233%22%20r%3D%2211%22%2F%3E%3C%2Fg%3E%3Ctext%20x%3D%22200%22%20y%3D%22185%22%20text-anchor%3D%22middle%22%20fill%3D%22%2394a3b8%22%20font-family%3D%22sans-serif%22%20font-size%3D%2213%22%20font-weight%3D%22600%22%3ENo%20Photo%20Available%3C%2Ftext%3E%3C%2Fsvg%3E';
        }

        // If it is already a full web URL (http:// or https://)
        if (preg_match('#^https?://#i', $imagePath)) {
            return $imagePath;
        }

        // Normalize Windows backslashes
        $cleanPath = str_replace('\\', '/', $imagePath);

        // If user pasted a local file path like C:/xampp/htdocs/KameraPH/uploads/file.jpg or /xampp/...
        if (preg_match('#(?:htdocs/)?KameraPH/(.+)$#i', $cleanPath, $matches)) {
            $cleanPath = $matches[1];
        } elseif (preg_match('#[a-zA-Z]:/.+/(uploads/.+)$#i', $cleanPath, $matches)) {
            $cleanPath = $matches[1];
        }

        // Strip any leading ../ or ./
        $cleanPath = preg_replace('#^(\.\./|\./)+#', '', $cleanPath);

        // If it starts with uploads/
        if (strpos($cleanPath, 'uploads/') === 0) {
            return base_url($cleanPath);
        }

        // If it's a bare filename (like "sony-a6400.jfif")
        return base_url('uploads/' . ltrim($cleanPath, '/'));
    }
}

if (!function_exists('sanitize')) {
    function sanitize($data) {
        return htmlspecialchars((string)$data, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('flash')) {
    function flash($type, $message) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['flash'] = [
            'type' => $type, // 'success', 'danger', 'warning', 'info'
            'message' => $message
        ];
    }
}

if (!function_exists('get_flash')) {
    function get_flash() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (isset($_SESSION['flash'])) {
            $flash = $_SESSION['flash'];
            unset($_SESSION['flash']);
            return $flash;
        }
        return null;
    }
}

if (!function_exists('render_flash')) {
    function render_flash() {
        $flash = get_flash();
        if ($flash) {
            $type = htmlspecialchars($flash['type']);
            $message = htmlspecialchars($flash['message']);
            $icon = 'bi-info-circle';
            if ($type === 'success') $icon = 'bi-check-circle-fill';
            if ($type === 'danger') $icon = 'bi-exclamation-triangle-fill';
            if ($type === 'warning') $icon = 'bi-exclamation-circle-fill';

            return "<div class=\"alert alert-{$type} alert-dismissible fade show shadow-sm d-flex align-items-center\" role=\"alert\">
                <i class=\"bi {$icon} me-2 fs-5\"></i>
                <div class=\"flex-grow-1\">{$message}</div>
                <button type=\"button\" class=\"btn-close\" data-bs-dismiss=\"alert\" aria-label=\"Close\"></button>
            </div>";
        }
        return '';
    }
}

if (!function_exists('status_badge')) {
    function status_badge($status) {
        $statusStr = trim((string)$status);
        $cls = 'bg-secondary text-white';

        switch (strtolower($statusStr)) {
            case 'active':
            case 'live':
                $cls = 'badge-status-live';
                break;
            case 'verified':
            case 'completed':
                $cls = 'badge-status-verified';
                break;
            case 'pending':
            case 'pending verification':
            case 'draft':
            case 'reserved':
                $cls = 'badge-status-pending';
                break;
            case 'inactive':
            case 'rejected':
            case 'cancelled':
            case 'expired':
            case 'suspended':
                $cls = 'bg-danger text-white';
                break;
            case 'sold':
                $cls = 'bg-dark text-white';
                break;
        }

        return '<span class="badge ' . $cls . '">' . sanitize($statusStr) . '</span>';
    }
}

if (!function_exists('condition_badge')) {
    function condition_badge($condition) {
        $cond = trim((string)$condition);
        $cls = 'bg-secondary text-white';
        switch (strtolower($cond)) {
            case 'brand new':
                $cls = 'bg-primary text-white';
                break;
            case 'mint':
                $cls = 'badge-condition-mint';
                break;
            case 'excellent':
                $cls = 'badge-condition-excellent';
                break;
            case 'good':
                $cls = 'badge-condition-good';
                break;
            case 'fair':
                $cls = 'bg-secondary text-white';
                break;
            case 'with defects':
                $cls = 'badge-status-pending';
                break;
        }
        return '<span class="badge ' . $cls . '">' . sanitize($cond) . '</span>';
    }
}

if (!function_exists('render_stars')) {
    function render_stars($rating) {
        $rating = (int)$rating;
        $html = '<span class="text-warning rating-stars" title="' . $rating . '/5">';
        for ($i = 1; $i <= 5; $i++) {
            if ($i <= $rating) {
                $html .= '<i class="bi bi-star-fill"></i> ';
            } else {
                $html .= '<i class="bi bi-star text-muted"></i> ';
            }
        }
        $html .= '</span>';
        return $html;
    }
}

if (!function_exists('format_price')) {
    function format_price($price) {
        if ($price === null || $price === '') {
            return '—';
        }
        return '₱' . number_format((float)$price, 2);
    }
}

if (!function_exists('redirect')) {
    function redirect($path) {
        $url = (preg_match('#^https?://#i', $path)) ? $path : base_url($path);
        header("Location: " . $url);
        exit();
    }
}

if (!function_exists('current_user')) {
    function current_user() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return $_SESSION['user'] ?? null;
    }
}

if (!function_exists('is_logged_in')) {
    function is_logged_in() {
        return current_user() !== null;
    }
}

if (!function_exists('is_admin')) {
    function is_admin() {
        $u = current_user();
        return $u && (!empty($u['is_admin']) || ($u['role'] ?? '') === 'admin');
    }
}

if (!function_exists('require_auth')) {
    function require_auth($role = null) {
        if (!is_logged_in()) {
            flash('warning', 'Please log in with a system account to continue.');
            $return = $_SERVER['REQUEST_URI'] ?? '';
            redirect('login.php?redirect=' . urlencode($return));
        }
        if ($role === 'admin' && !is_admin()) {
            flash('danger', 'Access denied. Administrator privileges required.');
            redirect('index.php');
        }
    }
}

if (!function_exists('get_current_seller_id')) {
    function get_current_seller_id() {
        $u = current_user();
        if (!$u) return 0;
        if (!empty($u['SellerID'])) return (int)$u['SellerID'];
        if (!empty($u['UserID'])) {
            global $conn;
            if ($conn) {
                $uid = (int)$u['UserID'];
                $chk = $conn->query("SELECT SellerID FROM seller_profile WHERE UserID = {$uid} LIMIT 1");
                if ($chk && $row = $chk->fetch_assoc()) {
                    $sid = (int)$row['SellerID'];
                    $_SESSION['user']['SellerID'] = $sid;
                    return $sid;
                }
            }
        }
        return 0;
    }
}

if (!function_exists('can_edit_listing')) {
    function can_edit_listing($listing) {
        if (is_admin()) return true;
        $u = current_user();
        if (!$u) return false;
        $listingSellerId = is_array($listing) ? (int)($listing['SellerID'] ?? 0) : (int)$listing;
        if ($listingSellerId <= 0) return false;

        // 1. Direct session match
        $sessSellerId = (int)($u['SellerID'] ?? 0);
        if ($sessSellerId > 0 && $sessSellerId === $listingSellerId) {
            return true;
        }

        // 2. Resolved seller ID match
        $sellerId = get_current_seller_id();
        if ($sellerId > 0 && $sellerId === $listingSellerId) {
            return true;
        }

        // 3. Database ownership check by UserID
        $uid = (int)($u['UserID'] ?? 0);
        if ($uid > 0) {
            global $conn;
            if ($conn) {
                $chk = $conn->query("SELECT SellerID FROM seller_profile WHERE UserID = {$uid} AND SellerID = {$listingSellerId} LIMIT 1");
                if ($chk && $chk->num_rows > 0) {
                    $_SESSION['user']['SellerID'] = $listingSellerId;
                    return true;
                }
            }
            try {
                $pdo = Database::connect();
                $st = $pdo->prepare("SELECT SellerID FROM seller_profile WHERE UserID = ? AND SellerID = ? LIMIT 1");
                $st->execute([$uid, $listingSellerId]);
                if ($st->fetch()) {
                    $_SESSION['user']['SellerID'] = $listingSellerId;
                    return true;
                }
            } catch (Exception $e) {}
        }

        return false;
    }
}

// Load Warehouse Inventory Helper Functions
require_once __DIR__ . '/warehouse_functions.php';
