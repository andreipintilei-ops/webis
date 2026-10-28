# Deploying to cPanel

The hosting has PHP and MySQL (MariaDB) but no Node and no Composer, so it
never builds anything: it pulls the **`deploy` branch**, which GitHub makes
from `main` after every push whose tests pass
(`.github/workflows/deploy-branch.yml`): main's files plus `public/build`
and the production `vendor/`. Each deploy is one more commit on that branch,
so a pull is always a fast-forward.

On the server, cPanel's **Git™ Version Control** pulls it and runs
`.cpanel.yml` → `deploy/cpanel.sh`: maintenance mode, migrations,
`storage:link`, Laravel's caches, the queue worker restarted.

What stays on the server, out of git: `.env`, `storage/` (uploads, logs).

## Once, for a new site (test.webis.ro)

1. **SSH key for GitHub** (cPanel › SSH Access › Import Key): a key with no
   passphrase, its public half added to the GitHub repository as a
   read-only _deploy key_; `~/.ssh/config` pointing github.com at it:

    ```
    Host github.com
        HostName github.com
        User git
        IdentityFile ~/.ssh/github_webis
        IdentitiesOnly yes
        StrictHostKeyChecking accept-new
    ```

2. **Database** (cPanel › MySQL Databases): a database, a user with all
   privileges on it. Then the content: the local database exported and
   imported with phpMyAdmin (or an empty one: the first deploy migrates it).

3. **Clone** (cPanel › Git™ Version Control › Create): clone URL
   `git@github.com:andreipintilei-ops/webis.git`, repository path
   `/home/<user>/test.webis.ro` (it must not exist yet, or be empty),
   then _Manage_ › Basic Information › checked-out branch: `deploy`.

4. **The domain** (cPanel › Domains): test.webis.ro with its document root
   at `test.webis.ro/public`; PHP 8.4 for it (MultiPHP Manager or Select PHP
   Version); SSL (SSL/TLS Status › Run AutoSSL).

5. **`.env`** (File Manager, in `/home/<user>/test.webis.ro`): a copy of
   `deploy/env.test.example` named `.env`, the database and a new `APP_KEY`
   filled in.

6. **Uploads**: the local `storage/app/public` uploaded into the same place
   (zipped, then extracted in File Manager).

7. **Cron jobs** (cPanel › Cron Jobs), every minute, with the site's PHP:

    ```
    cd ~/test.webis.ro && /opt/alt/php84/usr/bin/php artisan schedule:run >> /dev/null 2>&1
    cd ~/test.webis.ro && /opt/alt/php84/usr/bin/php artisan queue:work --stop-when-empty --max-time=50 >> /dev/null 2>&1
    ```

    (`/opt/cpanel/ea-php84/root/usr/bin/php` where CloudLinux's isn't there.)

8. **First deploy**: Git™ Version Control › Manage › Pull or Deploy ›
   _Deploy HEAD Commit_.

## Every update

Push to `main` → tests → the `deploy` branch is rebuilt (Actions tab,
_deploy-branch_) → in cPanel: _Update from Remote_, then _Deploy HEAD
Commit_.
