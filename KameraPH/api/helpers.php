<?php
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Test-User-Id");
if (($_SERVER["REQUEST_METHOD"] ?? "") === "OPTIONS") {
    http_response_code(204);
    exit;
}
function respond(int $code, $data): never {
    http_response_code($code);
    echo json_encode($data);
    exit;
}
function jsonBody(): array {
    $data = json_decode(file_get_contents("php://input"), true);
    return is_array($data) ? $data : [];
}
function getBearerToken(): ?string {
    $hdr = $_SERVER["HTTP_AUTHORIZATION"] ?? $_SERVER["REDIRECT_HTTP_AUTHORIZATION"] ?? null;
    if (!$hdr && function_exists("apache_request_headers")) {
        $headers = apache_request_headers();
        $hdr = $headers["Authorization"] ?? $headers["authorization"] ?? null;
    }
    if ($hdr && preg_match('/^Bearer\s+(\S+)$/i', trim($hdr), $m)) {
        return $m[1];
    }
    return null;
}
function currentUser(): ?array {
    // 1. Cross-module session token (Authorization: Bearer <token>)
    $token = getBearerToken();
    if ($token !== null && $token !== "") {
        require_once __DIR__ . '/../config/Database.php';
        $db = Database::connect();
        $stmt = $db->prepare(
            "SELECT UserID FROM verification_token
             WHERE (TokenHash = :raw OR TokenHash = SHA2(:hashed, 256))
               AND ExpiresAt > NOW()
             ORDER BY TokenID DESC LIMIT 1"
        );
        $stmt->execute([":raw" => $token, ":hashed" => $token]);
        $uid = $stmt->fetchColumn();
        if ($uid !== false) {
            return ["UserID" => (int)$uid];
        }
        return null;
    }

    // 2. Standalone dev/demo fallback (X-Test-User-Id header from config.js)
    $id = $_SERVER["HTTP_X_TEST_USER_ID"] ?? null;
    return $id ? ["UserID" => (int)$id] : null;
}
function requireUser(): array {
    $user = currentUser();
    if (!$user) {
        respond(401, ["error" => "Please log in"]);
    }
    return $user;
}
