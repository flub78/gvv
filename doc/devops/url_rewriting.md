# URL rewriting

To avoid the /index/php in the URLs.

1. First, create or modify the .htaccess file in your root directory:

```
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php/$1 [L]
```

2. Then modify your application/config/config.php file:
* Find the line with $config['index_page']
* Change it from:

```
$config['index_page'] = 'index.php';
```

to:

```
$config['index_page'] = '';
```

Additional steps to ensure it works:

Make sure mod_rewrite is enabled on your Apache server
Your Apache configuration should allow .htaccess overrides (AllowOverride All)

```
sudo a2enmod rewrite
sudo systemctl restart apache2
apache2ctl -M | grep rewrite
```

## And to remove the double slash

Remove it from the asset_helper.php

```
if (!function_exists('controller_url')) {
    function controller_url($nom) {
        // return site_url() . $nom;
        return site_url() . '/' . $nom;
    }
}
```

## Deployment

It is definitively a good idea, but I should be cautious because the modifications in the asset_helper.php could break the site.

