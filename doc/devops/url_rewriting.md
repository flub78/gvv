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

