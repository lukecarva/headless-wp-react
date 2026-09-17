# Translations

Translation files for the `headless-portfolio` text domain live here.

Generate the `.pot` template with WP-CLI (from the plugin directory):

```bash
wp i18n make-pot . languages/headless-portfolio.pot --domain=headless-portfolio
```

Then add locale files (e.g. `headless-portfolio-pt_BR.po` / `.mo`). WordPress
loads them automatically via the `Domain Path: /languages` header and
`load_plugin_textdomain()` (see `includes/I18n.php`).
