# KameraPH Inventory API

JSON REST API over the same MySQL database (`inventory`) as the web app.
Base URL (XAMPP): `http://localhost/KameraPH/api/`

No Composer or extra setup. Needs Apache `mod_rewrite` (on by default in XAMPP).
If rewrites are off, use `http://localhost/KameraPH/api/index.php/brand/1` instead.

## Resources

| Resource | Primary key | Notes |
|---|---|---|
| `brand` | BrandID | |
| `category` | CategoryID | self-parenting blocked |
| `spec_definition` | SpecDefID | |
| `product_model` | ModelID | `GET /{id}` adds `ReviewCount`, `AvgRating` |
| `product_listing` | ListingID | `GET /{id}` adds `images`, `specifications`, `verifications`, `reservations` |
| `listing_image` | ImageID | file upload or path/URL; one primary image per listing |
| `listing_specification` | ListingSpecID | one value per (listing, spec); duplicate → 409 |
| `listing_reservation` | ReservationID | `ExpiresAt` must be after `ReservedAt`; defaults to now / +24h |
| `product_verification` | VerificationID | `VerifiedAt` is set by the server |
| `product_review` | ReviewID | rating 1–5; `CreatedAt` set by the server |

`GET /api/` lists every resource with its writable fields.

## Endpoints

```
GET     /api/{resource}        list
GET     /api/{resource}/{id}   one record
POST    /api/{resource}        create   -> 201 + Location header
PUT     /api/{resource}/{id}   update   -> 200   (partial: send only changed fields)
PATCH   /api/{resource}/{id}   same as PUT
DELETE  /api/{resource}/{id}   delete   -> 204
GET     /api/health            DB connectivity check
```

Bodies are JSON (`Content-Type: application/json`) or form data. Field names are the
column names (`BrandName`, `Price`, ...). Unknown fields are ignored, so you can GET a
record, edit it and PUT it straight back. Enum values are case-insensitive.

### List options

| Param | Example | Meaning |
|---|---|---|
| any field | `?Status=Live,Reserved` | exact match, comma = OR; `?ParentCategoryID=null` for NULL |
| `q` | `?q=a6400` | text search (title/description/model/brand etc.) |
| `sort` | `?sort=-Price,Title` | `-` = descending |
| `page`, `limit` | `?page=2&limit=50` | default 25, max 100 |
| `min_price`, `max_price`, `brand_id`, `category_id` | | `product_listing` only |
| `min_rating` | | `product_review` only |

Response: `{ "data": [...], "meta": { "page", "limit", "total", "pages" } }`

### Examples

```bash
# create a listing
curl -X POST http://localhost/KameraPH/api/product_listing \
  -H "Content-Type: application/json" -H "X-API-Key: YOUR_KEY" \
  -d '{"SellerID":1,"ModelID":1,"Title":"Sony A6400 body","Price":32000,"Condition":"Mint","Status":"Live"}'

# live listings under 40k, newest price first
curl "http://localhost/KameraPH/api/product_listing?Status=Live&max_price=40000&sort=-Price"

# upload an image (multipart)
curl -X POST http://localhost/KameraPH/api/listing_image \
  -H "X-API-Key: YOUR_KEY" -F ListingID=1 -F IsPrimary=true -F image_file=@photo.jpg

# approve a verification
curl -X PATCH http://localhost/KameraPH/api/product_verification/1 \
  -H "Content-Type: application/json" -H "X-API-Key: YOUR_KEY" -d '{"VerificationStatus":"Verified"}'
```

```js
const res = await fetch('/KameraPH/api/product_listing?Status=Live');
const { data, meta } = await res.json();
```

Multipart bodies can't be sent with PUT in PHP. To change an image with a file, send
`POST /api/listing_image/{id}` with header `X-HTTP-Method-Override: PUT` (or `?_method=PUT`).

## Errors

Always JSON: `{ "error": { "code", "message", "details"? } }`

| Status | code | When |
|---|---|---|
| 400 | `invalid_json`, `invalid_id`, `bad_filter`, `bad_sort`, `no_changes` | malformed request |
| 401 | `unauthorized` | missing/wrong API key |
| 404 | `not_found`, `unknown_resource` | |
| 409 | `conflict`, `in_use` | duplicate value / record still referenced by others |
| 422 | `validation_failed`, `invalid_reference` | per-field messages in `details` |
| 503 | `database_unavailable` | MySQL not running / wrong credentials |

## Security

Edit `api/config.php` (or set env vars):

- `api_key` / `KAMERAPH_API_KEY`: when set, all writes require `X-API-Key: <key>` (or
  `Authorization: Bearer <key>`). Set `key_required_for_reads` to protect GETs too.
  **Set a key before exposing this beyond localhost.**
- `cors_origin`: change `*` to your frontend's URL in production.
- DB credentials default to the XAMPP ones used by `config/database.php`
  (`root`, empty password, db `inventory`).
- `.htaccess` blocks direct web access to `config.php` and `resources.php`.
- Set `KAMERAPH_DEBUG=1` only while developing (adds raw error text to 500 responses).

## Adding or changing a field

Everything is declared in `resources.php`. Add a line to the table's `fields` array
(type, required, default, min/max, ref) and it becomes writable, validated and filterable.
