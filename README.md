# Local Legends Orlando

A warm, community-first digital publication for the people, businesses, creators, nonprofits, and ministries shaping Central Florida. The site is built with PHP, MySQL, HTML, CSS, and Vanilla JavaScript for straightforward IONOS-compatible deployment.

## What is included

- Public home, stories, story detail, categories, tags, search, About, Contact, and feature application pages.
- A protected admin area for article publishing, categories, tags, submissions, interviews, and media uploads.
- A direct-link-only `/feature-interview/` multi-step interview that can be converted into an article draft from the admin area.
- Article metadata, canonical URLs, Open Graph/Twitter basics, Schema.org Article data, XML sitemap, and robots controls.

## Requirements

- PHP 8.1+ with PDO MySQL and Fileinfo enabled.
- MySQL 8+ or a compatible MariaDB version.
- Apache with `mod_rewrite` enabled. The `uploads/` directory must be writable by the PHP process.

## Production setup

1. Create a MySQL database and import [`database/schema.sql`](database/schema.sql).
2. Configure the hosting environment variables: `SITE_URL`, `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`, and `ADMIN_EMAIL`.
   - `SITE_URL` must be the full public URL without a trailing slash, for example `https://locallegendsorlando.com`.
3. Create the first admin account from the command line:

   ```bash
   php database/create_admin.php "Editor Name" editor@example.com "use-a-long-unique-password"
   ```

4. Give `uploads/` write permission for PHP, while keeping the included `uploads/.htaccess` file in place.
5. Upload the project files to the domain document root. Keep the root `.htaccess` file in place for clean story, category, and tag URLs.
6. Sign in at `/admin/login.php`, create categories/tags, upload media, and publish the first story.

## Editorial workflow

1. Use **Media** to upload a featured image and provide useful alt text.
2. Use **Categories & tags** to define the story’s organization.
3. Create an article, assign its image/categories/tags, add the SEO title/description, then publish.
4. Review public “Get Featured” submissions under **Submissions**.
5. Share `/feature-interview/` privately with invited businesses. Review completed interviews in **Interviews** and select **Create article draft**.

## Local checks

```bash
for file in $(find . -name '*.php' -print); do php -l "$file" || exit 1; done
git diff --check
php tests/run.php
```

## Security notes

- No public registration exists; admin accounts are created through the CLI utility.
- Admin and public state-changing forms use CSRF tokens.
- Uploads are limited to accepted image MIME types and blocked from PHP execution by `uploads/.htaccess`.
- Use HTTPS in production; secure cookies are automatically enabled on HTTPS requests.
