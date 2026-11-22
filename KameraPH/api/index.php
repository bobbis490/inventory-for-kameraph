<?php
/**
 * KameraPH REST API - front controller
 *
 *   GET    /api/{resource}          list (filters, ?q=, ?sort=, ?page=, ?limit=)
 *   GET    /api/{resource}/{id}     show
 *   POST   /api/{resource}          create
 *   PUT    /api/{resource}/{id}     update (partial - send only the fields to change)
 *   PATCH  /api/{resource}/{id}     update (same as PUT)
 *   DELETE /api/{resource}/{id}     delete
 *
 * See README.md for the full reference.
 */

declare(strict_types=1);

$CONFIG = require __DIR__ . '/config.php';
$RESOURCES = require __DIR__ . '/resources.php';

date_default_timezone_set($CONFIG['timezone']);
ini_set('display_errors', '0');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

/* ------------------------------------------------------------------ output */

function respond(int $status, mixed $payload = null): never
{
    http_response_code($status);
    if ($payload !== null) {
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION | JSON_PARTIAL_OUTPUT_ON_ERROR);
    }
    exit;
}

function fail(int $status, string $code, string $message, array $details = []): never
{
    $err = ['code' => $code, 'message' => $message];
    if ($details) {
        $err['details'] = $details;
    }
    respond($status, ['error' => $err]);
}

class Invalid extends Exception {}

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Access-Control-Allow-Origin: ' . $CONFIG['cors_origin']);
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-API-Key, Authorization, X-HTTP-Method-Override');

set_exception_handler(function (Throwable $e) use ($CONFIG) {
    if ($e instanceof mysqli_sql_exception) {
        switch ((int)$e->getCode()) {
            case 1062: fail(409, 'conflict', 'A record with the same unique value already exists.');
            case 1451: fail(409, 'in_use', 'Cannot delete this record because other records still reference it. Remove or reassign them first.');
            case 1452: fail(422, 'invalid_reference', 'A referenced record does not exist.');
            case 1048:
            case 1264:
            case 1265:
            case 1292:
            case 1366:
            case 1406: fail(422, 'validation_failed', 'A value was rejected by the database.', $CONFIG['debug'] ? ['db' => $e->getMessage()] : []);
            case 1045:
            case 1049:
            case 2002: fail(503, 'database_unavailable', 'Could not connect to the database.', $CONFIG['debug'] ? ['db' => $e->getMessage()] : []);
        }
    }
    error_log('[KameraPH API] ' . $e);
    fail(500, 'server_error', 'Unexpected server error.', $CONFIG['debug'] ? ['exception' => $e->getMessage()] : []);
});

/* ---------------------------------------------------------------- database */

function db(): mysqli
{
    global $CONFIG;
    static $conn = null;
    if ($conn === null) {
        $conn = mysqli_init();
        $conn->options(MYSQLI_OPT_INT_AND_FLOAT_NATIVE, 1);
        $conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 4);

        $flags = 0;
        if ($CONFIG['db_host'] !== 'localhost' && $CONFIG['db_host'] !== '127.0.0.1') {
            if (defined('MYSQLI_CLIENT_SSL')) {
                $flags = MYSQLI_CLIENT_SSL;
            }
        }

        try {
            $conn->real_connect($CONFIG['db_host'], $CONFIG['db_user'], $CONFIG['db_pass'], $CONFIG['db_name'], $CONFIG['db_port'], null, $flags);
            $conn->set_charset('utf8mb4');
        } catch (Throwable $e) {
            // Local fallback if Azure unreachable or offline
            $conn = mysqli_init();
            $conn->options(MYSQLI_OPT_INT_AND_FLOAT_NATIVE, 1);
            try {
                $conn->real_connect('localhost', 'root', '', 'kph-database', 3306);
            } catch (Throwable $e2) {
                $conn->real_connect('localhost', 'root', '', 'inventory', 3306);
            }
            $conn->set_charset('utf8mb4');
        }
    }
    return $conn;
}

function bind_types(array $params): string
{
    $t = '';
    foreach ($params as $p) {
        $t .= is_int($p) ? 'i' : (is_float($p) ? 'd' : 's');
    }
    return $t;
}

/** Prepare + execute. $types may be '' to auto-detect. */
function q(string $sql, string $types = '', array $params = []): mysqli_stmt
{
    $stmt = db()->prepare($sql);
    if ($params) {
        $stmt->bind_param($types !== '' ? $types : bind_types($params), ...$params);
    }
    $stmt->execute();
    return $stmt;
}

function rows(string $sql, string $types = '', array $params = []): array
{
    $stmt = q($sql, $types, $params);
    $out = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $out;
}

/* ----------------------------------------------------------------- helpers */

function site_base(): string
{
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
    $path = rtrim(str_replace('\\', '/', dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '/api/index.php'))), '/');
    return ($https ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $path;
}

function image_link(string $url): string
{
    return preg_match('#^https?://#i', $url) ? $url : site_base() . '/' . ltrim($url, '/');
}

/** Same normalisation the web form applies to pasted paths / filenames. */
function normalize_image_url(string $input): string
{
    $p = str_replace('\\', '/', trim($input));
    if (preg_match('#^https?://#i', $p)) {
        return $p;
    }
    if (preg_match('#(?:htdocs/)?KameraPH/(.+)$#i', $p, $m)) {
        $p = $m[1];
    } elseif (preg_match('#[a-zA-Z]:/.+/(uploads/.+)$#i', $p, $m)) {
        $p = $m[1];
    }
    $p = preg_replace('#^(\.\./|\./)+#', '', $p);
    return strpos($p, 'uploads/') === 0 ? $p : 'uploads/' . ltrim($p, '/');
}

/* -------------------------------------------------------------- validation */

function coerce(string $name, array $f, mixed $v): mixed
{
    if ($v === null || (is_string($v) && trim($v) === '')) {
        if (!empty($f['null'])) {
            return null;
        }
        throw new Invalid('cannot be empty.');
    }
    if (is_array($v) || is_object($v)) {
        throw new Invalid('must be a single value.');
    }

    switch ($f['type']) {
        case 'int':
        case 'year':
            if (is_int($v)) {
                $n = $v;
            } elseif (is_float($v) && floor($v) === $v) {
                $n = (int)$v;
            } elseif (is_string($v) && preg_match('/^-?\d+$/', trim($v))) {
                $n = (int)trim($v);
            } else {
                throw new Invalid('must be a whole number.');
            }
            if ($f['type'] === 'year') {
                $f['min'] = 1900;
                $f['max'] = 2100;
            }
            return range_check($n, $f);

        case 'float':
            if (!is_int($v) && !is_float($v) && !(is_string($v) && is_numeric(trim($v)))) {
                throw new Invalid('must be a number.');
            }
            return range_check((float)$v, $f);

        case 'bool':
            $b = is_bool($v) ? $v : filter_var($v, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($b === null) {
                throw new Invalid('must be true or false.');
            }
            return $b ? 1 : 0;

        case 'enum':
            foreach ($f['values'] as $allowed) {
                if (is_string($v) && strcasecmp(trim($v), $allowed) === 0) {
                    return $allowed;
                }
            }
            throw new Invalid('must be one of: ' . implode(', ', $f['values']) . '.');

        case 'datetime':
            $ts = is_string($v) ? strtotime($v) : false;
            if ($ts === false) {
                throw new Invalid('must be a valid date/time (e.g. 2026-11-22 14:30:00).');
            }
            return date('Y-m-d H:i:s', $ts);

        default: // string
            if (is_bool($v)) {
                throw new Invalid('must be text.');
            }
            $s = trim((string)$v);
            if (isset($f['max']) && mb_strlen($s) > $f['max']) {
                throw new Invalid('must be at most ' . $f['max'] . ' characters.');
            }
            return $s;
    }
}

function range_check(int|float $n, array $f): int|float
{
    if (isset($f['gt']) && $n <= $f['gt']) {
        throw new Invalid('must be greater than ' . $f['gt'] . '.');
    }
    if (isset($f['min']) && $n < $f['min']) {
        throw new Invalid('must be at least ' . $f['min'] . '.');
    }
    if (isset($f['max']) && $n > $f['max']) {
        throw new Invalid('must be at most ' . $f['max'] . '.');
    }
    return $n;
}

function check_refs(array $res, array $values, array &$errors): void
{
    foreach ($res['fields'] as $name => $f) {
        if (!isset($f['ref']) || !isset($values[$name]) || isset($errors[$name])) {
            continue;
        }
        [$table, $pk] = $f['ref'];
        if (!rows("SELECT 1 FROM `$table` WHERE `$pk` = ? LIMIT 1", 'i', [$values[$name]])) {
            $errors[$name] = "references a $table record that does not exist.";
        }
    }
}

function field_type(string $t): string
{
    return in_array($t, ['int', 'year', 'bool'], true) ? 'i' : ($t === 'float' ? 'd' : 's');
}

/** Cast DB values to proper JSON types. */
function shape_row(array $res, array $row): array
{
    foreach ($row as $k => $v) {
        if ($v === null) {
            continue;
        }
        if ($k === $res['pk']) {
            $row[$k] = (int)$v;
        } elseif (isset($res['fields'][$k])) {
            $t = $res['fields'][$k]['type'];
            if ($t === 'int' || $t === 'year') {
                $row[$k] = (int)$v;
            } elseif ($t === 'float') {
                $row[$k] = (float)$v;
            } elseif ($t === 'bool') {
                $row[$k] = (bool)(int)$v;
            }
        }
    }
    if (isset($res['transform'])) {
        $row = ($res['transform'])($row);
    }
    return $row;
}

/* ------------------------------------------------------------------- query */

function base_select(array $res): string
{
    $extra = isset($res['select']) ? ', ' . $res['select'] : '';
    return "SELECT t.*{$extra} FROM `{$res['table']}` t " . ($res['joins'] ?? '');
}

function find_one(array $res, int $id): ?array
{
    $r = rows(base_select($res) . " WHERE t.`{$res['pk']}` = ? LIMIT 1", 'i', [$id]);
    return $r ? shape_row($res, $r[0]) : null;
}

function find_raw(array $res, int $id): ?array
{
    $r = rows("SELECT * FROM `{$res['table']}` WHERE `{$res['pk']}` = ? LIMIT 1", 'i', [$id]);
    return $r[0] ?? null;
}

function handle_list(array $res): never
{
    global $CONFIG;
    $where = [];
    $types = '';
    $params = [];

    // exact-match filters on any field:  ?Status=Live,Reserved   ?ParentCategoryID=null
    $filterable = $res['fields'] + [$res['pk'] => ['type' => 'int']];
    foreach ($filterable as $name => $f) {
        if (!isset($_GET[$name]) || is_array($_GET[$name]) || $f['type'] === 'string' || !empty($f['readonly'])) {
            continue;
        }
        $raw = trim((string)$_GET[$name]);
        if (strtolower($raw) === 'null') {
            $where[] = "t.`$name` IS NULL";
            continue;
        }
        $vals = [];
        foreach (explode(',', $raw) as $piece) {
            try {
                $vals[] = coerce($name, ['null' => false] + $f, $piece);
            } catch (Invalid $e) {
                fail(400, 'bad_filter', "Filter '$name' " . $e->getMessage());
            }
        }
        $where[] = "t.`$name` IN (" . implode(',', array_fill(0, count($vals), '?')) . ')';
        foreach ($vals as $v) {
            $types .= field_type($f['type']);
            $params[] = $v;
        }
    }

    foreach (($res['range'] ?? []) as $param => [$col, $op]) {
        if (isset($_GET[$param]) && $_GET[$param] !== '' && !is_array($_GET[$param])) {
            if (!is_numeric($_GET[$param])) {
                fail(400, 'bad_filter', "Filter '$param' must be a number.");
            }
            $where[] = "$col $op ?";
            $types .= 'd';
            $params[] = (float)$_GET[$param];
        }
    }

    $search = trim((string)($_GET['q'] ?? ''));
    if ($search !== '' && !empty($res['search'])) {
        $or = [];
        foreach ($res['search'] as $col) {
            $or[] = "$col LIKE ?";
            $types .= 's';
            $params[] = '%' . $search . '%';
        }
        $where[] = '(' . implode(' OR ', $or) . ')';
    }
    $whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

    // sorting: ?sort=-Price,Title
    $map =[$res['pk'] => 't.`' . $res['pk'] . '`'];
    foreach ($res['fields'] as $name => $_) {
        $map[$name] = "t.`$name`";
    }
    $map += $res['sort_map'] ?? [];
    $order = [];
    foreach (array_filter(explode(',', (string)($_GET['sort'] ?? ''))) as $s) {
        $dir = $s[0] === '-' ? 'DESC' : 'ASC';
        $col = ltrim($s, '-+ ');
        if (!isset($map[$col])) {
            fail(400, 'bad_sort', "Cannot sort by '$col'.", ['allowed' => array_keys($map)]);
        }
        $order[] = $map[$col] . ' ' . $dir;
    }
    if (!$order) {
        $order[] = 't.`' . $res['pk'] . '` DESC';
    }

    $limit = (int)($_GET['limit'] ?? $CONFIG['default_limit']);
    $limit = max(1, min($limit, $CONFIG['max_limit']));
    $page = max(1, (int)($_GET['page'] ?? 1));
    $offset = ($page - 1) * $limit;

    $total = (int)rows("SELECT COUNT(*) AS n FROM `{$res['table']}` t " . ($res['joins'] ?? '') . $whereSql, $types, $params)[0]['n'];
    $data = rows(
        base_select($res) . $whereSql . ' ORDER BY ' . implode(', ', $order) . ' LIMIT ? OFFSET ?',
        $types . 'ii',
        array_merge($params, [$limit, $offset])
    );

    respond(200, [
        'data' => array_map(fn($r) => shape_row($res, $r), $data),
        'meta' => ['page' => $page, 'limit' => $limit, 'total' => $total, 'pages' => (int)ceil($total / $limit)],
    ]);
}

/* ------------------------------------------------------------------- write */

function parse_body(): array
{
    $ct = strtolower($_SERVER['CONTENT_TYPE'] ?? '');
    $raw = file_get_contents('php://input');

    if (str_contains($ct, 'application/json')) {
        if (trim((string)$raw) === '') {
            return [];
        }
        $data = json_decode($raw, true);
        if (!is_array($data) || array_is_list($data) && $data !== []) {
            fail(400, 'invalid_json', 'Request body must be a JSON object.');
        }
        return $data;
    }
    if ($_POST) {
        return $_POST;
    }
    if (str_contains($ct, 'application/x-www-form-urlencoded') && $raw) {
        parse_str($raw, $out);
        return $out;
    }
    return [];
}

function handle_write(array $res, ?int $id): never
{
    $isUpdate = $id !== null;
    $input = parse_body();
    $existing = null;

    if ($isUpdate) {
        $existing = find_raw($res, $id);
        if (!$existing) {
            fail(404, 'not_found', 'Record not found.');
        }
    }
    if (isset($res['preprocess'])) {
        ($res['preprocess'])($input, $existing);
    }

    $errors = [];
    $changes = [];

    foreach ($res['fields'] as $name => $f) {
        if (!empty($f['readonly'])) {
            continue;
        }
        if (array_key_exists($name, $input)) {
            try {
                $changes[$name] = coerce($name, $f, $input[$name]);
            } catch (Invalid $e) {
                $errors[$name] = $e->getMessage();
            }
        } elseif (!$isUpdate) {
            if (array_key_exists('default', $f)) {
                $changes[$name] = $f['default'] instanceof Closure ? ($f['default'])($changes) : $f['default'];
            } elseif (!empty($f['required'])) {
                $errors[$name] = 'is required.';
            }
        }
    }

    if ($isUpdate && !$errors && !$changes) {
        fail(400, 'no_changes', 'No updatable fields were supplied.', ['updatable' => array_keys(array_filter($res['fields'], fn($f) => empty($f['readonly'])))]);
    }

    $merged = $isUpdate ? $changes + $existing : $changes;
    if (!$errors) {
        check_refs($res, $changes, $errors);
    }
    if (!$errors && isset($res['validate'])) {
        $errors = ($res['validate'])($merged, $existing, $id);
    }
    if ($errors) {
        fail(422, 'validation_failed', 'The submitted data is invalid.', $errors);
    }

    if (isset($res['before_save'])) {
        ($res['before_save'])($changes, $merged, $existing);
    }

    $db = db();
    $db->begin_transaction();
    try {
        if (isset($res['pre_write'])) {
            ($res['pre_write'])($db, $merged, $id);
        }

        $cols = array_keys($changes);
        $types = '';
        $values = [];
        foreach ($cols as $c) {
            $types .= field_type($res['fields'][$c]['type']);
            $values[] = $changes[$c];
        }
        $quoted = array_map(fn($c) => "`$c`", $cols);

        if ($isUpdate) {
            $set = implode(', ', array_map(fn($c) => "$c = ?", $quoted));
            q("UPDATE `{$res['table']}` SET $set WHERE `{$res['pk']}` = ?", $types . 'i', array_merge($values, [$id]))->close();
            $newId = $id;
        } else {
            $stmt = q(
                "INSERT INTO `{$res['table']}` (" . implode(', ', $quoted) . ') VALUES (' . implode(', ', array_fill(0, count($cols), '?')) . ')',
                $types,
                $values
            );
            $newId = (int)$stmt->insert_id;
            $stmt->close();
        }
        $db->commit();
    } catch (Throwable $e) {
        $db->rollback();
        throw $e;
    }

    $row = find_one($res, $newId);
    if (!$isUpdate) {
        header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?') . '/' . $newId);
    }
    respond($isUpdate ? 200 : 201, ['data' => $row]);
}

function handle_delete(array $res, int $id): never
{
    if (!find_raw($res, $id)) {
        fail(404, 'not_found', 'Record not found.');
    }
    q("DELETE FROM `{$res['table']}` WHERE `{$res['pk']}` = ?", 'i', [$id])->close();
    respond(204);
}

/* -------------------------------------------------------------------- auth */

function authorize(string $method): void
{
    global $CONFIG;
    $key = (string)$CONFIG['api_key'];
    if ($key === '') {
        return;
    }
    $isRead = in_array($method, ['GET', 'HEAD'], true);
    if ($isRead && !$CONFIG['key_required_for_reads']) {
        return;
    }
    $given = $_SERVER['HTTP_X_API_KEY'] ?? '';
    if ($given === '') {
        $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
        if (preg_match('/^Bearer\s+(.+)$/i', $auth, $m)) {
            $given = trim($m[1]);
        }
    }
    if (!hash_equals($key, $given)) {
        header('WWW-Authenticate: Bearer realm="KameraPH API"');
        fail(401, 'unauthorized', 'A valid API key is required (X-API-Key header).');
    }
}

/* ------------------------------------------------------------------ router */

$method = strtoupper($_SERVER['REQUEST_METHOD']);
if ($method === 'OPTIONS') {
    respond(204);
}
if ($method === 'POST') {
    $override = strtoupper($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'] ?? ($_GET['_method'] ?? ''));
    if (in_array($override, ['PUT', 'PATCH', 'DELETE'], true)) {
        $method = $override;
    }
}

$route = $_GET['route'] ?? ltrim($_SERVER['PATH_INFO'] ?? '', '/');
$parts = array_values(array_filter(explode('/', trim((string)$route, '/')), 'strlen'));

authorize($method);

if (!$parts || $parts[0] === 'index.php') {
    $list = [];
    foreach ($RESOURCES as $name => $r) {
        $list[$name] = [
            'primary_key' => $r['pk'],
            'fields' => array_keys(array_filter($r['fields'], fn($f) => empty($f['readonly']))),
        ];
    }
    respond(200, ['name' => 'KameraPH Inventory API', 'version' => '1.0', 'resources' => $list]);
}
if ($parts[0] === 'health') {
    db();
    respond(200, ['status' => 'ok', 'time' => date('c')]);
}

$resName = $parts[0];
if (!isset($RESOURCES[$resName])) {
    fail(404, 'unknown_resource', "Unknown resource '$resName'.", ['available' => array_keys($RESOURCES)]);
}
$res = $RESOURCES[$resName];

if (count($parts) > 2) {
    fail(404, 'not_found', 'No such endpoint.');
}
$id = null;
if (isset($parts[1])) {
    if (!ctype_digit($parts[1]) || (int)$parts[1] < 1) {
        fail(400, 'invalid_id', 'The record ID must be a positive integer.');
    }
    $id = (int)$parts[1];
}

switch ($method) {
    case 'GET':
    case 'HEAD':
        if ($id === null) {
            handle_list($res);
        }
        $row = find_one($res, $id);
        if (!$row) {
            fail(404, 'not_found', 'Record not found.');
        }
        if (isset($res['detail'])) {
            $row = array_merge($row, ($res['detail'])($row));
            if (isset($row['images'])) {
                $row['images'] = array_map(fn($i) => shape_row($RESOURCES['listing_image'], $i), $row['images']);
            }
        }
        respond(200, ['data' => $row]);

    case 'POST':
        if ($id !== null) {
            fail(405, 'method_not_allowed', 'POST is only valid on the collection. Use PUT/PATCH to update.');
        }
        handle_write($res, null);

    case 'PUT':
    case 'PATCH':
        if ($id === null) {
            fail(405, 'method_not_allowed', 'Include the record ID in the URL.');
        }
        handle_write($res, $id);

    case 'DELETE':
        if ($id === null) {
            fail(405, 'method_not_allowed', 'Include the record ID in the URL.');
        }
        handle_delete($res, $id);

    default:
        header('Allow: GET, POST, PUT, PATCH, DELETE, OPTIONS');
        fail(405, 'method_not_allowed', "Method $method is not supported.");
}
