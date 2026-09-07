## New version release instructions.

1. Add changelog for new version into `README.md`
2. Clear the local cache: `php artisan optimize:clear`
3. Update version number in `version.txt`
4. Run `npm run build:ssr`
5. Remove the `installed` file from the storage public.
6. Clear the logs, sessions, testings, etc from storage.
7. Make version zip without unnecessary files and folders.
