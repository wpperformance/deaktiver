## Deaktiver

WordPress plugin to disable default features you do not need (RSS, comments, jQuery, posts/blog, etc.).

### Settings

You can use the plugin options page, or a theme config file for a more accurate setup that avoids a database call:

`{theme}/config/deaktiver.php`

Example:

```php
<?php

return [
    'emoji' => true,
    'embed' => true,
    'feed' => true,
    'xmlrpc' => true,
    'jquery' => true,
    'jquery-migrate' => true,
    'version' => true,
    'powered-by' => true,
    'wlwmanifest' => true,
    'rsd_link' => true,
    'short_link' => true,
    'rest_link' => true,
    'comments' => true,
    'posts' => true,
    'rest_user' => true,
    'login_url' => true,
    'login_lang_selector' => true,
];
```

`true` means the feature is **disabled**.

---

## Changelog

### 1.5.1

- **Posts / blog**: fix CPT save on `post.php` when `posts` is disabled — no longer 403 custom post types. Classic editor POSTs to `post.php` without a query string; resolve `post_type` from GET, then POST, then `get_post_type()` of the edited ID. Native posts, categories and tags stay blocked.

### 1.5.0

- **Posts / blog**: new `posts` option to disable the native blog surface
  - Admin: hide Posts menu, block post/category/tag screens, remove Quick Draft and “New Post” admin bar item
  - Front: 301 redirect to home for single posts, category/tag archives, post-only author/date archives, post feeds, and post-only search
  - Queries: exclude `post` from front search and secondary queries (Query Loop, etc.)
  - REST: remove `/wp/v2/posts`, `/wp/v2/categories`, `/wp/v2/tags` and related sub-routes
  - Widgets: unregister Recent Posts, Categories, Archives, Calendar, Tag Cloud
- **Bootstrap**: main plugin file renamed from `index.php` to `deaktiver.php`
- **Admin app**: Vite upgraded to v8 (`rolldownOptions`, `import.meta.dirname`), dynamic public base path for Bedrock (`/app/plugins/...`) and classic (`/wp-content/plugins/...`), Biome config updated for 2.x
