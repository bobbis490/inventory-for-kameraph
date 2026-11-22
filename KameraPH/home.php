<?php
/**
 * KameraPH Marketplace Homepage
 * Place in the project root (C:\xampp\htdocs\KameraPH\home.php) and open /KameraPH/home.php
 * Works with your existing header/footer/navbar, style.css and helper functions.
 * It reads your tables defensively (SHOW COLUMNS), so it adapts to your column names.
 */
require_once __DIR__ . '/config/database.php';

$pageTitle  = 'Marketplace';
$activeMenu = 'home';

/* ---------- schema helpers ---------- */
function table_cols(mysqli $conn, string $table): array {
    try {
        $res = $conn->query("SHOW COLUMNS FROM `" . $conn->real_escape_string($table) . "`");
        $out = [];
        while ($r = $res->fetch_assoc()) $out[strtolower($r['Field'])] = $r['Field'];
        return $out;
    } catch (Throwable $e) { return []; }
}
function pick(array $cols, array $candidates): ?string {
    foreach ($candidates as $c) if (isset($cols[strtolower($c)])) return $cols[strtolower($c)];
    return null;
}

$plCols = table_cols($conn, 'product_listing');
$pmCols = table_cols($conn, 'product_model');
$brCols = table_cols($conn, 'brand');
$caCols = table_cols($conn, 'category');
$pvCols = table_cols($conn, 'product_verification');

$colStatus    = pick($plCols, ['Status', 'ListingStatus']);
$colCondition = pick($plCols, ['ConditionType', 'Condition', 'ItemCondition', 'ConditionStatus', 'ProductCondition']);
$colSeller    = pick($plCols, ['SellerName', 'Seller', 'SellerID', 'UserID', 'OwnerID']);
$colSellerIsName = $colSeller && in_array(strtolower($colSeller), ['sellername', 'seller']);
$colLocation  = pick($plCols, ['Location', 'City', 'SellerLocation']);
$colCreated   = pick($plCols, ['CreatedAt', 'DateListed', 'ListedAt', 'DateCreated']);
$colDesc      = pick($plCols, ['Description']);
$colModelFK   = pick($plCols, ['ModelID']);

$pmName = pick($pmCols, ['ModelName', 'Name']);
$pmCat  = pick($pmCols, ['CategoryID']);
$pmBr   = pick($pmCols, ['BrandID']);
$brName = pick($brCols, ['BrandName', 'Name']);
$caName = pick($caCols, ['CategoryName', 'Name']);

$joinModel    = $colModelFK && $pmName && pick($pmCols, ['ModelID']);
$joinBrand    = $joinModel && $pmBr && $brName && pick($brCols, ['BrandID']);
$joinCategory = $joinModel && $pmCat && $caName && pick($caCols, ['CategoryID']);

$pvListing = pick($pvCols, ['ListingID']);
$pvStatus  = pick($pvCols, ['Status', 'VerificationStatus']);

/* ---------- build query ---------- */
$select = ["pl.*"];
$from   = "product_listing pl";
if ($joinModel)    { $from .= " LEFT JOIN product_model pm ON pl.`$colModelFK` = pm.ModelID"; $select[] = "pm.`$pmName` AS _model"; }
if ($joinBrand)    { $from .= " LEFT JOIN brand b ON pm.`$pmBr` = b.BrandID"; $select[] = "b.`$brName` AS _brand"; }
if ($joinCategory) { $from .= " LEFT JOIN category c ON pm.`$pmCat` = c.CategoryID"; $select[] = "c.`$caName` AS _category"; }
$select[] = "(SELECT li.ImageURL FROM listing_image li WHERE li.ListingID = pl.ListingID
              ORDER BY li.IsPrimary DESC, li.DisplayOrder ASC LIMIT 1) AS _image";
if ($pvListing && $pvStatus) {
    $select[] = "EXISTS(SELECT 1 FROM product_verification pv WHERE pv.`$pvListing` = pl.ListingID
                 AND LOWER(pv.`$pvStatus`) = 'verified') AS _verified";
}

$where = []; $types = ''; $params = [];
if ($colStatus) $where[] = "LOWER(pl.`$colStatus`) IN ('active','live')";

$q = trim($_GET['q'] ?? '');
if ($q !== '') {
    $like = "%{$q}%";
    $parts = ["pl.Title LIKE ?"]; $types .= 's'; $params[] = $like;
    if ($colDesc)    { $parts[] = "pl.`$colDesc` LIKE ?";  $types .= 's'; $params[] = $like; }
    if ($joinModel)  { $parts[] = "pm.`$pmName` LIKE ?";   $types .= 's'; $params[] = $like; }
    if ($joinBrand)  { $parts[] = "b.`$brName` LIKE ?";    $types .= 's'; $params[] = $like; }
    $where[] = '(' . implode(' OR ', $parts) . ')';
}
$catFilter = (int)($_GET['category'] ?? 0);
if ($catFilter > 0 && $joinCategory) { $where[] = "pm.`$pmCat` = ?"; $types .= 'i'; $params[] = $catFilter; }

$condFilter = trim($_GET['condition'] ?? '');
if ($condFilter !== '' && $colCondition) { $where[] = "pl.`$colCondition` = ?"; $types .= 's'; $params[] = $condFilter; }

$minPrice = ($_GET['min'] ?? '') !== '' ? (float)$_GET['min'] : null;
$maxPrice = ($_GET['max'] ?? '') !== '' ? (float)$_GET['max'] : null;
if ($minPrice !== null) { $where[] = "pl.Price >= ?"; $types .= 'd'; $params[] = $minPrice; }
if ($maxPrice !== null) { $where[] = "pl.Price <= ?"; $types .= 'd'; $params[] = $maxPrice; }

$sortOptions = [
    'newest'     => ['Newest first',        $colCreated ? "pl.`$colCreated` DESC" : "pl.ListingID DESC"],
    'price_asc'  => ['Price: low to high',  "pl.Price ASC"],
    'price_desc' => ['Price: high to low',  "pl.Price DESC"],
];
$sort = isset($sortOptions[$_GET['sort'] ?? '']) ? $_GET['sort'] : 'newest';

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

/* pagination */
$perPage = 12;
$page    = max(1, (int)($_GET['page'] ?? 1));

$countStmt = $conn->prepare("SELECT COUNT(*) AS n FROM $from $whereSql");
if ($params) $countStmt->bind_param($types, ...$params);
$countStmt->execute();
$total = (int)$countStmt->get_result()->fetch_assoc()['n'];
$countStmt->close();
$pages  = max(1, (int)ceil($total / $perPage));
$page   = min($page, $pages);
$offset = ($page - 1) * $perPage;

$sql  = "SELECT " . implode(', ', $select) . " FROM $from $whereSql ORDER BY {$sortOptions[$sort][1]} LIMIT $perPage OFFSET $offset";
$stmt = $conn->prepare($sql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$listings = $stmt->get_result();

/* filter dropdown data */
$categories = $joinCategory ? $conn->query("SELECT CategoryID, `$caName` AS Name FROM category ORDER BY `$caName`") : null;
$conditions = [];
if ($colCondition) {
    $cr = $conn->query("SELECT DISTINCT `$colCondition` AS c FROM product_listing WHERE `$colCondition` IS NOT NULL AND `$colCondition` <> '' ORDER BY c");
    while ($r = $cr->fetch_assoc()) $conditions[] = $r['c'];
}

/* marketplace stats */
$sellerCount = null;
if ($colSeller) {
    $sr = $conn->query("SELECT COUNT(DISTINCT `$colSeller`) AS n FROM product_listing" . ($colStatus ? " WHERE LOWER(`$colStatus`) IN ('active','live')" : ''));
    $sellerCount = (int)$sr->fetch_assoc()['n'];
}

function qs(array $override = []): string {
    $p = array_merge($_GET, $override);
    foreach ($p as $k => $v) if ($v === '' || $v === null) unset($p[$k]);
    return '?' . http_build_query($p);
}

require_once __DIR__ . '/includes/header.php';
?>

<style>
    .market-hero { padding: 1.5rem 0 1.75rem; }
    .market-hero h1 { font-size: clamp(1.6rem, 3.4vw, 2.4rem); font-weight: 800; letter-spacing: -0.5px; margin-bottom: .4rem; }
    .market-hero p  { color: #94a3b8; margin-bottom: 1.25rem; max-width: 52ch; }
    .market-search .form-control { border: 0; padding: .85rem 1.1rem; font-size: 1rem; }
    .market-search .btn { background: var(--brand-orange); border: 0; color: #fff; font-weight: 600; padding: .85rem 1.6rem; }
    .market-search .btn:hover { background: var(--brand-orange-dark); }

    .filter-bar { background: #111c2e; border: 1px solid #1e2b42; border-radius: 12px; padding: .85rem 1rem; }
    .filter-bar .form-select, .filter-bar .form-control { background-color: #0b1320; border-color: #263754; color: #e2e8f0; font-size: .875rem; }
    .filter-bar .form-control::placeholder { color: #64748b; }
    .filter-bar label { color: #94a3b8; font-size: .75rem; margin-bottom: .2rem; }

    .product-card { display: flex; flex-direction: column; height: 100%; background: #fff; border-radius: 14px; overflow: hidden;
                    color: #334155; text-decoration: none; box-shadow: 0 4px 20px rgba(0,0,0,.18); transition: box-shadow .15s ease; }
    .product-card:hover { color: #334155; box-shadow: 0 0 0 2px var(--brand-orange), 0 8px 26px rgba(0,0,0,.3); }
    .product-img-wrap { position: relative; aspect-ratio: 4 / 3; background: #f1f5f9; }
    .product-img-wrap img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .product-img-wrap img.is-placeholder { object-fit: contain; padding: 22%; opacity: .7; }
    .product-flag { position: absolute; top: .6rem; left: .6rem; background: #16a34a; color: #fff; font-size: .72rem; font-weight: 600;
                    padding: 3px 9px; border-radius: 6px; }
    .product-body { padding: .95rem 1rem 1rem; display: flex; flex-direction: column; flex: 1; }
    .product-brand { font-size: .78rem; color: #64748b; font-weight: 600; margin-bottom: .15rem; }
    .product-title { font-size: .98rem; font-weight: 700; color: #0f172a; line-height: 1.3; margin-bottom: .5rem;
                     display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; min-height: 2.6em; }
    .product-price { font-size: 1.3rem; font-weight: 800; color: var(--brand-orange); margin-bottom: .6rem; }
    .seller-row { margin-top: auto; padding-top: .7rem; border-top: 1px solid #f1f5f9; display: flex; align-items: center; gap: .55rem; font-size: .8rem; }
    .seller-avatar { width: 28px; height: 28px; border-radius: 50%; background: #0b1320; color: #fff; font-weight: 700; font-size: .75rem;
                     display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .seller-meta { min-width: 0; line-height: 1.2; }
    .seller-meta span { display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .seller-name { font-weight: 600; color: #0f172a; }
    .seller-loc  { color: #64748b; font-size: .74rem; }

    .market-empty { background: #111c2e; border: 1px dashed #263754; border-radius: 14px; padding: 3rem 1rem; text-align: center; color: #94a3b8; }
    .market-empty i { font-size: 2.5rem; color: #475569; }

    .pagination .page-link { background: #111c2e; border-color: #1e2b42; color: #cbd5e1; }
    .pagination .page-item.active .page-link { background: var(--brand-orange); border-color: var(--brand-orange); color: #fff; }
    .pagination .page-item.disabled .page-link { background: #0b1320; color: #475569; }
</style>

<section class="market-hero">
    <h1>Camera gear from sellers across the Philippines</h1>
    <p>Browse cameras, lenses and accessories listed by other photographers. Open a listing to see photos, specs and the seller.</p>

    <form method="GET" class="market-search">
        <div class="input-group input-group-lg shadow">
            <input type="text" name="q" class="form-control" placeholder="Search by camera, lens or brand, e.g. Sony A6400" value="<?php echo sanitize($q); ?>">
            <button class="btn" type="submit"><i class="bi bi-search me-1"></i> Search</button>
        </div>
    </form>

    <div class="d-flex flex-wrap gap-4 mt-3 small" style="color:#94a3b8;">
        <span><strong class="text-white"><?php echo number_format($total); ?></strong> listing<?php echo $total === 1 ? '' : 's'; ?> available</span>
        <?php if ($sellerCount !== null): ?>
            <span><strong class="text-white"><?php echo number_format($sellerCount); ?></strong> seller<?php echo $sellerCount === 1 ? '' : 's'; ?></span>
        <?php endif; ?>
    </div>
</section>

<form method="GET" class="filter-bar mb-4">
    <?php if ($q !== ''): ?><input type="hidden" name="q" value="<?php echo sanitize($q); ?>"><?php endif; ?>
    <div class="row g-2 align-items-end">
        <?php if ($categories): ?>
        <div class="col-6 col-md-3 col-lg-2">
            <label for="f-category">Category</label>
            <select id="f-category" name="category" class="form-select form-select-sm">
                <option value="">All categories</option>
                <?php while ($c = $categories->fetch_assoc()): ?>
                    <option value="<?php echo $c['CategoryID']; ?>" <?php echo $catFilter == $c['CategoryID'] ? 'selected' : ''; ?>><?php echo sanitize($c['Name']); ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <?php endif; ?>
        <?php if ($conditions): ?>
        <div class="col-6 col-md-3 col-lg-2">
            <label for="f-condition">Condition</label>
            <select id="f-condition" name="condition" class="form-select form-select-sm">
                <option value="">Any condition</option>
                <?php foreach ($conditions as $c): ?>
                    <option value="<?php echo sanitize($c); ?>" <?php echo $condFilter === $c ? 'selected' : ''; ?>><?php echo sanitize($c); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>
        <div class="col-6 col-md-3 col-lg-2">
            <label for="f-min">Min price (₱)</label>
            <input id="f-min" type="number" min="0" step="100" name="min" class="form-control form-control-sm" placeholder="0" value="<?php echo $minPrice !== null ? sanitize($minPrice) : ''; ?>">
        </div>
        <div class="col-6 col-md-3 col-lg-2">
            <label for="f-max">Max price (₱)</label>
            <input id="f-max" type="number" min="0" step="100" name="max" class="form-control form-control-sm" placeholder="Any" value="<?php echo $maxPrice !== null ? sanitize($maxPrice) : ''; ?>">
        </div>
        <div class="col-6 col-md-3 col-lg-2">
            <label for="f-sort">Sort by</label>
            <select id="f-sort" name="sort" class="form-select form-select-sm">
                <?php foreach ($sortOptions as $key => $opt): ?>
                    <option value="<?php echo $key; ?>" <?php echo $sort === $key ? 'selected' : ''; ?>><?php echo $opt[0]; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-md-3 col-lg-2 d-flex gap-2">
            <button type="submit" class="btn btn-sm btn-light fw-semibold flex-grow-1">Apply</button>
            <?php if ($_GET): ?><a href="home.php" class="btn btn-sm btn-outline-secondary" title="Clear all filters"><i class="bi bi-x-lg"></i></a><?php endif; ?>
        </div>
    </div>
</form>

<?php if ($listings && $listings->num_rows > 0): ?>
    <div class="row g-3 g-lg-4">
        <?php while ($row = $listings->fetch_assoc()):
            $hasImage = !empty($row['_image']);
            $sellerRaw = $colSeller ? ($row[$colSeller] ?? '') : '';
            $sellerLabel = $sellerRaw === '' ? 'KameraPH seller' : ($colSellerIsName ? $sellerRaw : 'Seller #' . $sellerRaw);
            $initial = strtoupper(mb_substr($colSellerIsName && $sellerRaw !== '' ? $sellerRaw : 'S', 0, 1));
            $location = $colLocation ? trim((string)($row[$colLocation] ?? '')) : '';
            $brand = $row['_brand'] ?? ($row['_category'] ?? '');
        ?>
        <div class="col-6 col-md-4 col-xl-3">
            <a class="product-card" href="<?php echo base_url('product_listing/view.php?id=' . (int)$row['ListingID']); ?>">
                <div class="product-img-wrap">
                    <img src="<?php echo image_url($row['_image'] ?? ''); ?>" alt="<?php echo sanitize($row['Title']); ?>" loading="lazy" class="<?php echo $hasImage ? '' : 'is-placeholder'; ?>">
                    <?php if (!empty($row['_verified'])): ?><span class="product-flag"><i class="bi bi-patch-check-fill me-1"></i>Verified</span><?php endif; ?>
                </div>
                <div class="product-body">
                    <?php if ($brand !== ''): ?><div class="product-brand"><?php echo sanitize($brand); ?></div><?php endif; ?>
                    <div class="product-title"><?php echo sanitize($row['Title']); ?></div>
                    <div class="product-price"><?php echo format_price($row['Price'] ?? null); ?></div>
                    <?php if ($colCondition && !empty($row[$colCondition])): ?>
                        <div class="mb-2"><?php echo condition_badge($row[$colCondition]); ?></div>
                    <?php endif; ?>
                    <div class="seller-row">
                        <div class="seller-avatar"><?php echo sanitize($initial); ?></div>
                        <div class="seller-meta">
                            <span class="seller-name"><?php echo sanitize($sellerLabel); ?></span>
                            <?php if ($location !== ''): ?><span class="seller-loc"><i class="bi bi-geo-alt"></i> <?php echo sanitize($location); ?></span><?php endif; ?>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <?php endwhile; ?>
    </div>

    <?php if ($pages > 1): ?>
    <nav class="mt-4" aria-label="Listing pages">
        <ul class="pagination justify-content-center mb-0">
            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>"><a class="page-link" href="<?php echo qs(['page' => $page - 1]); ?>">Previous</a></li>
            <?php for ($i = max(1, $page - 2); $i <= min($pages, $page + 2); $i++): ?>
                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>"><a class="page-link" href="<?php echo qs(['page' => $i]); ?>"><?php echo $i; ?></a></li>
            <?php endfor; ?>
            <li class="page-item <?php echo $page >= $pages ? 'disabled' : ''; ?>"><a class="page-link" href="<?php echo qs(['page' => $page + 1]); ?>">Next</a></li>
        </ul>
    </nav>
    <?php endif; ?>
<?php else: ?>
    <div class="market-empty">
        <i class="bi bi-camera"></i>
        <h5 class="text-white mt-2">No listings match your search</h5>
        <p class="mb-3">Try a different keyword, or clear the filters to see everything on sale.</p>
        <a href="home.php" class="btn btn-sm btn-light fw-semibold">Clear filters</a>
        <a href="<?php echo base_url('product_listing/add.php'); ?>" class="btn btn-sm btn-primary ms-1">Sell your gear</a>
    </div>
<?php endif; ?>

<?php
$stmt->close();
require_once __DIR__ . '/includes/footer.php';
?>
