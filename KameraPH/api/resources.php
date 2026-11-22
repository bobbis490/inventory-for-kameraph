<?php
/**
 * Resource definitions - one entry per table.
 *
 * Field options:
 *   type      int | float | bool | string | enum | datetime | year
 *   required  must be supplied on create
 *   null      value may be NULL
 *   default   value (or Closure) used on create when the field is omitted
 *   values    allowed values for enum
 *   min/max   numeric range (string: max length)     gt = exclusive minimum
 *   ref       [table, pk]  foreign key checked before writing
 *   readonly  never accepted from the client (set by hooks)
 *
 * Resource options:
 *   joins / select   extra SQL for list + show (base table alias is `t`)
 *   search           columns searched by ?q=
 *   sort_map         extra sortable names => SQL expression
 *   range            query-param => [column, operator]
 *   validate         fn(array $merged, ?array $existing): array  (field => error)
 *   before_save      fn(array &$changes, array $merged, ?array $existing): void
 *   pre_write        fn(mysqli $db, array $merged, ?int $id): void  (inside the transaction)
 *   preprocess       fn(array &$input, ?array $existing): void      (before validation)
 *   detail           fn(array $row): array   extra data for GET /{resource}/{id}
 *   transform        fn(array $row): array   final shaping of every output row
 */

$now = fn() => date('Y-m-d H:i:s');

return [

    // ------------------------------------------------------------------ brand
    'brand' => [
        'table'  => 'brand',
        'pk'     => 'BrandID',
        'search' => ['t.BrandName'],
        'fields' => [
            'BrandName' => ['type' => 'string', 'required' => true, 'max' => 100],
            'Status'    => ['type' => 'enum', 'values' => ['Active', 'Inactive'], 'default' => 'Active'],
        ],
    ],

    // --------------------------------------------------------------- category
    'category' => [
        'table'  => 'category',
        'pk'     => 'CategoryID',
        'select' => 'p.CategoryName AS ParentCategoryName',
        'joins'  => 'LEFT JOIN category p ON t.ParentCategoryID = p.CategoryID',
        'search' => ['t.CategoryName', 't.Description'],
        'sort_map' => ['ParentCategoryName' => 'p.CategoryName'],
        'fields' => [
            'ParentCategoryID' => ['type' => 'int', 'null' => true, 'min' => 1, 'ref' => ['category', 'CategoryID']],
            'CategoryName'     => ['type' => 'string', 'required' => true, 'max' => 100],
            'Description'      => ['type' => 'string', 'null' => true, 'max' => 65535],
            'Status'           => ['type' => 'enum', 'values' => ['Active', 'Inactive'], 'default' => 'Active'],
        ],
        'validate' => function (array $m, ?array $old, ?int $id = null): array {
            if ($id !== null && isset($m['ParentCategoryID']) && (int)$m['ParentCategoryID'] === $id) {
                return ['ParentCategoryID' => 'A category cannot be its own parent.'];
            }
            return [];
        },
    ],

    // --------------------------------------------------------- spec_definition
    'spec_definition' => [
        'table'  => 'spec_definition',
        'pk'     => 'SpecDefID',
        'select' => 'c.CategoryName',
        'joins'  => 'LEFT JOIN category c ON t.CategoryID = c.CategoryID',
        'search' => ['t.SpecName', 'c.CategoryName'],
        'sort_map' => ['CategoryName' => 'c.CategoryName'],
        'fields' => [
            'CategoryID'   => ['type' => 'int', 'required' => true, 'min' => 1, 'ref' => ['category', 'CategoryID']],
            'SpecName'     => ['type' => 'string', 'required' => true, 'max' => 100],
            'DataType'     => ['type' => 'enum', 'values' => ['Text', 'Number', 'Decimal', 'Boolean'], 'default' => 'Text'],
            'Unit'         => ['type' => 'string', 'null' => true, 'max' => 30],
            'IsRequired'   => ['type' => 'bool', 'default' => 0],
            'IsFilterable' => ['type' => 'bool', 'default' => 1],
        ],
    ],

    // ----------------------------------------------------------- product_model
    'product_model' => [
        'table'  => 'product_model',
        'pk'     => 'ModelID',
        'select' => 'b.BrandName, c.CategoryName',
        'joins'  => 'LEFT JOIN brand b ON t.BrandID = b.BrandID LEFT JOIN category c ON t.CategoryID = c.CategoryID',
        'search' => ['t.ModelName', 'b.BrandName', 'c.CategoryName'],
        'sort_map' => ['BrandName' => 'b.BrandName', 'CategoryName' => 'c.CategoryName'],
        'fields' => [
            'BrandID'     => ['type' => 'int', 'required' => true, 'min' => 1, 'ref' => ['brand', 'BrandID']],
            'CategoryID'  => ['type' => 'int', 'required' => true, 'min' => 1, 'ref' => ['category', 'CategoryID']],
            'ModelName'   => ['type' => 'string', 'required' => true, 'max' => 150],
            'ReleaseYear' => ['type' => 'year', 'null' => true],
            'Status'      => ['type' => 'enum', 'values' => ['Active', 'Inactive'], 'default' => 'Active'],
        ],
        'detail' => function (array $row): array {
            $r = rows('SELECT COUNT(*) AS ReviewCount, AVG(RatingValue) AS AvgRating FROM product_review WHERE ModelID = ?', 'i', [$row['ModelID']])[0];
            return [
                'ReviewCount' => (int)$r['ReviewCount'],
                'AvgRating'   => $r['AvgRating'] !== null ? round((float)$r['AvgRating'], 2) : null,
            ];
        },
    ],

    // --------------------------------------------------------- product_listing
    'product_listing' => [
        'table'  => 'product_listing',
        'pk'     => 'ListingID',
        'select' => 'pm.ModelName, b.BrandName, c.CategoryName, '
                  . '(SELECT li.ImageURL FROM listing_image li WHERE li.ListingID = t.ListingID '
                  . ' ORDER BY li.IsPrimary DESC, li.DisplayOrder ASC LIMIT 1) AS PrimaryImage',
        'joins'  => 'LEFT JOIN product_model pm ON t.ModelID = pm.ModelID '
                  . 'LEFT JOIN brand b ON pm.BrandID = b.BrandID '
                  . 'LEFT JOIN category c ON pm.CategoryID = c.CategoryID',
        'search' => ['t.Title', 't.Description', 'pm.ModelName', 'b.BrandName'],
        'sort_map' => [
            'ModelName' => 'pm.ModelName', 'BrandName' => 'b.BrandName', 'CategoryName' => 'c.CategoryName',
        ],
        'range' => [
            'min_price' => ['t.Price', '>='],
            'max_price' => ['t.Price', '<='],
            'brand_id'  => ['pm.BrandID', '='],
            'category_id' => ['pm.CategoryID', '='],
        ],
        'fields' => [
            'SellerID'         => ['type' => 'int', 'required' => true, 'min' => 1],
            'ModelID'          => ['type' => 'int', 'required' => true, 'min' => 1, 'ref' => ['product_model', 'ModelID']],
            'Title'            => ['type' => 'string', 'required' => true, 'max' => 255],
            'Description'      => ['type' => 'string', 'null' => true, 'max' => 65535],
            'Price'            => ['type' => 'float', 'required' => true, 'gt' => 0],
            'OriginalPrice'    => ['type' => 'float', 'null' => true, 'min' => 0],
            'Condition'        => ['type' => 'enum', 'values' => ['Brand New', 'Mint', 'Excellent', 'Good', 'Fair', 'With defects'], 'default' => 'Excellent'],
            'Quantity'         => ['type' => 'int', 'min' => 0, 'default' => 1],
            'WeightGrams'      => ['type' => 'int', 'min' => 0, 'default' => 0],
            'LengthCm'         => ['type' => 'float', 'min' => 0, 'default' => 0],
            'WidthCm'          => ['type' => 'float', 'min' => 0, 'default' => 0],
            'HeightCm'         => ['type' => 'float', 'min' => 0, 'default' => 0],
            'InsuredValue'     => ['type' => 'float', 'min' => 0, 'default' => 0],
            'Status'           => ['type' => 'enum', 'values' => ['Draft', 'Pending Verification', 'Rejected', 'Live', 'Reserved', 'Sold', 'Suspended'], 'default' => 'Draft'],
            'SuspensionReason' => ['type' => 'string', 'null' => true, 'max' => 255],
        ],
        'detail' => function (array $row): array {
            $id = (int)$row['ListingID'];
            return [
                'images' => rows('SELECT * FROM listing_image WHERE ListingID = ? ORDER BY IsPrimary DESC, DisplayOrder ASC', 'i', [$id]),
                'specifications' => rows(
                    'SELECT ls.*, sd.SpecName, sd.DataType, sd.Unit FROM listing_specification ls
                     INNER JOIN spec_definition sd ON ls.SpecDefID = sd.SpecDefID
                     WHERE ls.ListingID = ? ORDER BY sd.SpecName ASC', 'i', [$id]),
                'verifications' => rows('SELECT * FROM product_verification WHERE ListingID = ? ORDER BY VerificationID DESC', 'i', [$id]),
                'reservations'  => rows('SELECT * FROM listing_reservation WHERE ListingID = ? ORDER BY ReservationID DESC', 'i', [$id]),
            ];
        },
        'transform' => function (array $row): array {
            if (array_key_exists('PrimaryImage', $row)) {
                $row['PrimaryImageLink'] = $row['PrimaryImage'] !== null ? image_link($row['PrimaryImage']) : null;
            }
            return $row;
        },
    ],

    // ----------------------------------------------------------- listing_image
    'listing_image' => [
        'table'  => 'listing_image',
        'pk'     => 'ImageID',
        'sort_map' => [],
        'fields' => [
            'ListingID'    => ['type' => 'int', 'required' => true, 'min' => 1, 'ref' => ['product_listing', 'ListingID']],
            'ImageURL'     => ['type' => 'string', 'required' => true, 'max' => 500],
            'DisplayOrder' => ['type' => 'int', 'min' => 0, 'default' => 1],
            'IsPrimary'    => ['type' => 'bool', 'default' => 0],
        ],
        // Accepts a multipart file (field "image_file") OR an ImageURL / path / bare filename in uploads/
        'preprocess' => function (array &$input, ?array $existing): void {
            if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] !== UPLOAD_ERR_NO_FILE) {
                $f = $_FILES['image_file'];
                if ($f['error'] !== UPLOAD_ERR_OK) {
                    fail(422, 'upload_failed', 'Image upload failed (PHP upload error ' . $f['error'] . ').');
                }
                $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
                if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'jfif', 'gif'], true)) {
                    fail(422, 'validation_failed', 'Invalid image type.', ['image_file' => 'Allowed: JPG, JPEG, PNG, WEBP, JFIF, GIF.']);
                }
                if ($f['size'] > 8 * 1024 * 1024) {
                    fail(422, 'validation_failed', 'Image too large.', ['image_file' => 'Uploaded file exceeds the 8MB limit.']);
                }
                $dir = dirname(__DIR__) . '/uploads/';
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }
                $listingId = (int)($input['ListingID'] ?? ($existing['ListingID'] ?? 0));
                $name = 'listing_' . $listingId . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
                if (!move_uploaded_file($f['tmp_name'], $dir . $name)) {
                    fail(500, 'upload_failed', 'Failed to save the uploaded image.');
                }
                $input['ImageURL'] = 'uploads/' . $name;
            } elseif (isset($input['ImageURL']) && is_string($input['ImageURL']) && trim($input['ImageURL']) !== '') {
                $input['ImageURL'] = normalize_image_url($input['ImageURL']);
            }
        },
        'validate' => function (array $m, ?array $old, ?int $id = null): array {
            if (isset($m['ImageURL']) && preg_match('#(^|/)\.\.(/|$)#', (string)$m['ImageURL'])) {
                return ['ImageURL' => 'Path traversal is not allowed.'];
            }
            return [];
        },
        // Only one primary image per listing (same rule as the web form)
        'pre_write' => function (mysqli $db, array $m, ?int $id): void {
            if (!empty($m['IsPrimary'])) {
                q('UPDATE listing_image SET IsPrimary = 0 WHERE ListingID = ? AND ImageID != ?', 'ii', [(int)$m['ListingID'], (int)$id])->close();
            }
        },
        'transform' => function (array $row): array {
            if (isset($row['ImageURL'])) {
                $row['ImageLink'] = image_link($row['ImageURL']);
            }
            return $row;
        },
    ],

    // ----------------------------------------------------- listing_specification
    'listing_specification' => [
        'table'  => 'listing_specification',
        'pk'     => 'ListingSpecID',
        'select' => 'sd.SpecName, sd.DataType, sd.Unit',
        'joins'  => 'LEFT JOIN spec_definition sd ON t.SpecDefID = sd.SpecDefID',
        'search' => ['t.SpecValue', 'sd.SpecName'],
        'sort_map' => ['SpecName' => 'sd.SpecName'],
        'fields' => [
            'ListingID' => ['type' => 'int', 'required' => true, 'min' => 1, 'ref' => ['product_listing', 'ListingID']],
            'SpecDefID' => ['type' => 'int', 'required' => true, 'min' => 1, 'ref' => ['spec_definition', 'SpecDefID']],
            'SpecValue' => ['type' => 'string', 'required' => true, 'max' => 255],
        ],
        'validate' => function (array $m, ?array $old, ?int $id = null): array {
            $dup = rows(
                'SELECT ListingSpecID FROM listing_specification WHERE ListingID = ? AND SpecDefID = ? AND ListingSpecID != ?',
                'iii', [(int)$m['ListingID'], (int)$m['SpecDefID'], (int)$id]
            );
            if ($dup) {
                fail(409, 'conflict', 'This specification is already defined for this listing. Update the existing value instead.',
                    ['existing_id' => (int)$dup[0]['ListingSpecID']]);
            }
            return [];
        },
    ],

    // ------------------------------------------------------- listing_reservation
    'listing_reservation' => [
        'table'  => 'listing_reservation',
        'pk'     => 'ReservationID',
        'select' => 'pl.Title AS ListingTitle',
        'joins'  => 'LEFT JOIN product_listing pl ON t.ListingID = pl.ListingID',
        'sort_map' => ['ListingTitle' => 'pl.Title'],
        'fields' => [
            'ListingID'  => ['type' => 'int', 'required' => true, 'min' => 1, 'ref' => ['product_listing', 'ListingID']],
            'OrderID'    => ['type' => 'int', 'required' => true, 'min' => 1],
            'Quantity'   => ['type' => 'int', 'min' => 1, 'default' => 1],
            'ReservedAt' => ['type' => 'datetime', 'default' => $now],
            'ExpiresAt'  => ['type' => 'datetime', 'default' => fn(array $d) => date('Y-m-d H:i:s', strtotime(($d['ReservedAt'] ?? 'now') . ' +24 hours'))],
            'Status'     => ['type' => 'enum', 'values' => ['Reserved', 'Completed', 'Cancelled', 'Expired'], 'default' => 'Reserved'],
        ],
        'validate' => function (array $m, ?array $old, ?int $id = null): array {
            if (strtotime((string)$m['ExpiresAt']) <= strtotime((string)$m['ReservedAt'])) {
                return ['ExpiresAt' => 'Expiration date must be later than the reservation start date.'];
            }
            return [];
        },
    ],

    // ------------------------------------------------------ product_verification
    'product_verification' => [
        'table'  => 'product_verification',
        'pk'     => 'VerificationID',
        'select' => 'pl.Title AS ListingTitle',
        'joins'  => 'LEFT JOIN product_listing pl ON t.ListingID = pl.ListingID',
        'search' => ['t.VerificationType', 't.Remarks', 'pl.Title'],
        'sort_map' => ['ListingTitle' => 'pl.Title'],
        'fields' => [
            'ListingID'          => ['type' => 'int', 'required' => true, 'min' => 1, 'ref' => ['product_listing', 'ListingID']],
            'VerificationType'   => ['type' => 'string', 'required' => true, 'max' => 150, 'default' => 'Physical Authenticity & Serial Check'],
            'VerificationStatus' => ['type' => 'enum', 'values' => ['Pending', 'Verified', 'Rejected'], 'default' => 'Pending'],
            'VerifiedByAdminID'  => ['type' => 'int', 'required' => true, 'min' => 1],
            'VerifiedAt'         => ['type' => 'datetime', 'null' => true, 'readonly' => true],
            'Remarks'            => ['type' => 'string', 'null' => true, 'max' => 65535],
        ],
        // VerifiedAt is managed by the server, exactly like the web form
        'before_save' => function (array &$changes, array $m, ?array $old): void {
            $status = $m['VerificationStatus'];
            if ($old === null) {
                $changes['VerifiedAt'] = ($status === 'Verified') ? date('Y-m-d H:i:s') : null;
            } elseif ($status === 'Verified') {
                if ($old['VerificationStatus'] !== 'Verified' || empty($old['VerifiedAt'])) {
                    $changes['VerifiedAt'] = date('Y-m-d H:i:s');
                }
            } elseif ($status === 'Pending') {
                $changes['VerifiedAt'] = null;
            }
        },
    ],

    // ---------------------------------------------------------- product_review
    'product_review' => [
        'table'  => 'product_review',
        'pk'     => 'ReviewID',
        'select' => 'pm.ModelName',
        'joins'  => 'LEFT JOIN product_model pm ON t.ModelID = pm.ModelID',
        'search' => ['t.ReviewText', 'pm.ModelName'],
        'sort_map' => ['ModelName' => 'pm.ModelName'],
        'range'  => ['min_rating' => ['t.RatingValue', '>=']],
        'fields' => [
            'OrderItemID'    => ['type' => 'int', 'required' => true, 'min' => 1],
            'ModelID'        => ['type' => 'int', 'required' => true, 'min' => 1, 'ref' => ['product_model', 'ModelID']],
            'ReviewerUserID' => ['type' => 'int', 'required' => true, 'min' => 1],
            'RatingValue'    => ['type' => 'int', 'required' => true, 'min' => 1, 'max' => 5],
            'ReviewText'     => ['type' => 'string', 'required' => true, 'max' => 65535],
            'CreatedAt'      => ['type' => 'datetime', 'readonly' => true],
        ],
        'before_save' => function (array &$changes, array $m, ?array $old): void {
            if ($old === null) {
                $changes['CreatedAt'] = date('Y-m-d H:i:s');
            }
        },
    ],
];
