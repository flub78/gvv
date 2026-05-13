# URL rewriting on Apache

This page explains both supported configurations:

1. Without URL rewriting: URLs contain `index.php`.
2. With URL rewriting: clean URLs without `index.php`.

No application code change is required between these two modes. Only Apache and `application/config/config.php` are adjusted.

## Case 1: without URL rewriting

Use this mode if you do not want to enable Apache rewrite rules.

In `application/config/config.php`:

```php
$config['base_url'] = 'https://your-domain.example/';
$config['index_page'] = 'index.php';
```

Resulting URLs look like:

```text
https://your-domain.example/index.php/welcome
```

This mode works without `mod_rewrite` and without `.htaccess` rewrite rules.

## Case 2: with URL rewriting (clean URLs)

Use this mode if you want URLs without `index.php`.

### Prerequisites: how to check before enabling rewrite

Before changing configuration, validate these prerequisites.

1. Apache is installed and running:

```bash
apache2ctl -v
sudo systemctl status apache2 --no-pager
```

Expected:
- `apache2ctl -v` returns an Apache version.
- service status is `active (running)`.

2. You can manage Apache modules (Debian/Ubuntu):

```bash
which a2enmod
```

Expected: a path like `/usr/sbin/a2enmod`.

3. Your site is served by Apache and the vhost points to GVV root:

```bash
apache2ctl -S
```

Expected:
- your domain appears in `ServerName`/`ServerAlias`.
- `DocumentRoot` matches the GVV web root (directory containing `index.php`).

4. Apache allows `.htaccess` overrides for your GVV directory:

Check your vhost config and confirm:

```apache
<Directory /path/to/gvv>
    AllowOverride All
</Directory>
```

If `AllowOverride None` is set, rewrite rules in `.htaccess` will be ignored.

5. The app already works without rewrite:

Open:

```text
https://your-domain.example/index.php/welcome
```

Expected: page loads successfully before you switch to clean URLs.

6. Optional quick check that `mod_rewrite` is not already enabled:

```bash
apache2ctl -M | grep rewrite
```

Expected:
- if enabled: `rewrite_module` appears.
- if not enabled: no output (you will enable it in Step 1 below).

### Step 1: enable Apache rewrite module

On Debian/Ubuntu:

```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
apache2ctl -M | grep rewrite
```

Expected output contains `rewrite_module`.

### Step 2: allow `.htaccess` overrides in Apache vhost

Edit your virtual host file (example: `/etc/apache2/sites-available/000-default.conf`):

```apache
<VirtualHost *:80>
    ServerName your-domain.example
    DocumentRoot /var/www/gvv

    <Directory /var/www/gvv>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

Then reload Apache:

```bash
sudo systemctl reload apache2
```

`AllowOverride All` is required so Apache applies rewrite rules from `.htaccess`.

### Step 3: create/update `.htaccess` in GVV web root

In the GVV root directory (same level as `index.php`), use:

```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php/$1 [L]
```

### Step 4: update CodeIgniter config

In `application/config/config.php`:

```php
$config['base_url'] = 'https://your-domain.example/';
$config['index_page'] = '';
```

Resulting URLs look like:

```text
https://your-domain.example/welcome
```

### Step 5: verify

1. Open `https://your-domain.example/welcome`.
2. Open `https://your-domain.example/index.php/welcome` (it should still work).
3. Check there is no `404 Not Found` and no redirect loop.

## Troubleshooting

If clean URLs do not work:

1. Check Apache module:

```bash
apache2ctl -M | grep rewrite
```

2. Check vhost allows overrides (`AllowOverride All`) for the GVV web root.
3. Check `.htaccess` is in the same directory as `index.php`.
4. Check `application/config/config.php` uses:
   - `$config['base_url']` with trailing slash
   - `$config['index_page'] = ''` for rewrite mode
5. Reload Apache after each config change:

```bash
sudo systemctl reload apache2
```

## Important note

Do not modify helper code to switch between rewrite and non-rewrite deployments.

The URL mode must be controlled by Apache configuration and `config.php`, not by changing application source code.

## Domain-to-domain redirection with `.htaccess`

Use this when you want all traffic from one domain (source) to be redirected to another domain (target).

Example:
- source: `old-domain.example`
- target: `new-domain.example`

### Prerequisites: checks before enabling the redirect

1. Both domains resolve in DNS:

```bash
dig +short old-domain.example
dig +short new-domain.example
```

2. Apache serves the source domain and you can identify its vhost:

```bash
apache2ctl -S
```

3. `.htaccess` is allowed on the source vhost:

```apache
<Directory /path/to/source-web-root>
    AllowOverride All
</Directory>
```

4. Rewrite module is enabled:

```bash
apache2ctl -M | grep rewrite
```

Expected output contains `rewrite_module`.

5. TLS/SSL is valid for domains that redirect on HTTPS.

6. Existing `.htaccess` is backed up before changes.

### `.htaccess` rules to add (source domain)

In the source web root (same directory as the source domain `index.php`), add:

```apache
RewriteEngine On

# Keep Let's Encrypt HTTP challenge reachable (optional but recommended)
RewriteCond %{REQUEST_URI} !^/\.well-known/acme-challenge/

# Redirect source domain to target domain and keep path + query string
RewriteCond %{HTTP_HOST} ^(www\.)?old-domain\.example$ [NC]
RewriteRule ^ https://new-domain.example%{REQUEST_URI} [R=301,L,NE,QSA]
```

What this does:
- `R=301`: permanent redirect (SEO/browser cache friendly).
- `%{REQUEST_URI}`: keeps `/path/subpath`.
- `QSA`: preserves query string.
- `NE`: avoids unwanted URL escaping.

If you want a temporary redirect during testing, use `R=302` instead of `R=301`.

### Verification after deployment

1. Config syntax:

```bash
sudo apache2ctl configtest
```

2. Reload Apache:

```bash
sudo systemctl reload apache2
```

3. Check HTTP response:

```bash
curl -I http://old-domain.example/some/path?x=1
curl -I https://old-domain.example/some/path?x=1
```

Expected:
- status `301` (or `302` if configured)
- `Location: https://new-domain.example/some/path?x=1`

4. Browser check:
- open a few URLs on the source domain
- confirm no redirect loop
- confirm destination pages load correctly

### Can we comment rules to disable the redirect?

Yes. In `.htaccess`, prefix the redirect lines with `#`.

Example (redirect disabled):

```apache
RewriteEngine On

# RewriteCond %{REQUEST_URI} !^/\.well-known/acme-challenge/
# RewriteCond %{HTTP_HOST} ^(www\.)?old-domain\.example$ [NC]
# RewriteRule ^ https://new-domain.example%{REQUEST_URI} [R=301,L,NE,QSA]
```

After commenting/uncommenting rules:

```bash
sudo systemctl reload apache2
```

Note: if you tested with `301`, browsers may cache aggressively. Use a private window or clear cache when validating changes.

