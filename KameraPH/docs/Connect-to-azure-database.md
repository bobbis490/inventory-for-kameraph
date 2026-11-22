# KameraPH — Universal AI Prompt for All 10 Groups

> **Instructions for Group Members:**
> Copy everything inside the prompt section below and paste it directly into your AI coding assistant (Claude, ChatGPT, Cursor, or Antigravity) inside your module's project workspace.

---

You are helping our group update our KameraPH module to connect to our class's unified database (`kph-database`) and cross-module authentication system while still working standalone on local XAMPP.

Please apply the following 5 updates to our module right now without breaking any of our existing classes, endpoints, or pages:

### 1. Standardized `config/config.php` and `config/Database.php`
Update `<our_module_folder>/config/config.php` so it connects to our shared Azure MySQL database (`kph-database`), with commented local XAMPP fallback values and all 10 module API base URLs:

```php
<?php
return [
    // Unified Azure MySQL Database (shared by all 10 modules)
    "host"    => "kamaera-ph-mysql.mysql.database.azure.com",
    "port"    => 3306,
    "dbname"  => "kph-database",
    "user"    => "kph_app",
    "pass"    => "KphApp_2026!",
    "charset" => "utf8mb4",

    // Local XAMPP fallback (uncomment only if testing offline without internet):
    // "host" => "localhost", "dbname" => "kph-database", "user" => "root", "pass" => "",

    "modules" => [
        "accounts"    => "http://localhost/kph_user_account_identity/api",
        "sellers"     => "http://localhost/kph_seller_verification/api",
        "catalog"     => "http://localhost/kph_catalog_specification/api",
        "engagement"  => "http://localhost/kph_marketing_engagement/api",
        "shopping"    => "http://localhost/kph_shopping_promotions/api",
        "orders"      => "http://localhost/kph_order_payment/api",
        "fulfillment" => "http://localhost/kph_fulfillment_logistics/api",
        "shipping"    => "http://localhost/kph_shipping_delivery/api",
        "support"     => "http://localhost/kph_post_purchase_support/api",
        "admin"       => "http://localhost/kph_administration_analytics/api",
    ],
];
```

Update `<our_module_folder>/config/Database.php` so PDO connects cleanly to both Azure MySQL (with SSL support) and local XAMPP:

```php
<?php
class Database {
    private static ?PDO $pdo = null;

    public static function connect(): PDO {
        if (self::$pdo === null) {
            $c = require __DIR__ . '/config.php';
            $port = $c['port'] ?? 3306;
            $charset = $c['charset'] ?? 'utf8mb4';
            $dsn = "mysql:host={$c['host']};port={$port};dbname={$c['dbname']};charset={$charset}";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_TIMEOUT            => 10,
            ];
            if (!empty($c['ssl_ca']) && file_exists($c['ssl_ca'])) {
                $options[PDO::MYSQL_ATTR_SSL_CA] = $c['ssl_ca'];
            } elseif (defined('PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT')) {
                $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
            }
            self::$pdo = new PDO($dsn, $c['user'], $c['pass'], $options);
        }
        return self::$pdo;
    }
}
```

### 2. Protect Database Credentials From GitHub (`.gitignore`)
Create or update `.gitignore` in the repository root so `config.php` is never pushed to GitHub:
```gitignore
**/config/config.php
config/config.php
.env
*.env
**/node_modules/
```
If `config.php` is already tracked in Git, run `git update-index --skip-worktree <our_module_folder>/config/config.php`.

### 3. Dual-Mode Authentication in `api/helpers.php`
Update `<our_module_folder>/api/helpers.php` so `currentUser()` checks:
1. First, `Authorization: Bearer <token>` against the shared `verification_token` table in `kph-database` (issued by Module 1 on login).
2. Second, falls back to `X-Test-User-Id` so we can still test and demo our module standalone on local XAMPP.

Use this exact `api/helpers.php`:
```php
<?php
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Test-User-Id");
if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
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
```

### 4. Cross-Module Token Handoff in `web/js/api.js`
Update `<our_module_folder>/web/js/api.js` so it captures `?token=` from cross-module links into `localStorage` (`kph-auth-token`) and sends `Authorization: Bearer <token>` alongside `X-Test-User-Id`:

```javascript
(function () {
  try {
    const params = new URLSearchParams(location.search);
    const urlToken = params.get("token");
    if (urlToken) {
      localStorage.setItem("kph-auth-token", urlToken);
    }
  } catch (e) { /* ignore storage errors */ }
})();

async function request(method, path, body) {
  let res;
  const headers = { "Content-Type": "application/json" };
  try {
    const token = localStorage.getItem("kph-auth-token");
    if (token) headers["Authorization"] = "Bearer " + token;
  } catch (e) { /* ignore storage errors */ }
  if (typeof KPH !== "undefined" && KPH.testUserId) {
    headers["X-Test-User-Id"] = KPH.testUserId;
  }
  try {
    res = await fetch(KPH.api + path, {
      method: method,
      headers: headers,
      body: body === undefined ? undefined : JSON.stringify(body)
    });
  } catch (e) {
    throw new Error("Cannot reach the server. Check that Apache is running and the address in config.js is correct.");
  }
  let data = null;
  try { data = await res.json(); } catch (e) { /* the answer was not JSON */ }
  if (!res.ok) {
    throw new Error((data && data.error) || "Something went wrong (status " + res.status + ").");
  }
  return data;
}

function apiGet(path) { return request("GET", path); }
function apiSend(method, path, body) { return request(method, path, body); }

function escapeHtml(text) {
  const d = document.createElement("div");
  d.textContent = text == null ? "" : String(text);
  return d.innerHTML;
}

function formatPeso(amount) {
  const n = Number(amount);
  if (Number.isNaN(n)) return "";
  return "₱" + n.toLocaleString("en-PH", { minimumFractionDigits: 0, maximumFractionDigits: 2 });
}
```

### 5. Foreign-Key Handling & Group 1 Specific Rule
- Because `kph-database` has real foreign keys across all 69 tables (with pre-seeded test IDs: `user` `1..10`, `admin_user` `1`, `seller_profile` `1..5`, `category` `1..10`, `product_listing` `1..15`, `orders` `1001, 5001, 9001, 9002`), check our backend classes and API files:
  - In `api/*.php`, ensure `PDOException` is caught and returns `500 {"error": "Server error"}` (never leaking raw SQL messages via a `RuntimeException` catch block).
  - Where our backend classes insert foreign key IDs (like `UserID`, `ListingID`, `CategoryID`, `SellerID`, `OrderID`), catch `PDOException` with `$e->getCode() === '23000'` and throw `new InvalidArgumentException("Referenced record does not exist.")` so the API returns a clean `400`.
- **ONLY IF our module is Module 1 (`kph_user_account_identity`):**
  - On `POST /api/auth.php?action=login` (and `social-login`), generate `$rawToken = bin2hex(random_bytes(32))`, insert `Purpose = 'session'`, `TokenHash = SHA2(:rawToken, 256)`, `ExpiresAt = DATE_ADD(NOW(), INTERVAL 7 DAY)` into `verification_token`, return `"token" => $rawToken` in the login JSON response, and have our login page save `localStorage.setItem("kph-auth-token", res.token)`.

After applying these updates, test the database connection with PHP CLI (`php -r "require 'config/Database.php'; Database::connect(); echo 'Connected OK';"`), run our existing backend/API tests, and report the results.
