<?php

declare(strict_types=1);

namespace Xoops\ModuleTools\Common;

/*
 You may not change or alter any portion of this comment or credits
 of supporting developers from this source code or any supporting source code
 which is considered copyrighted (c) material of the original comment or credit authors.
*/

/**
 * @copyright 2000-2026 XOOPS Project (https://xoops.org)
 * @license   GNU GPL 2.0 or later (https://www.gnu.org/licenses/gpl-2.0.html)
 * @author    XOOPS Development Team
 */

use Xoops\Helpers\Utility\Filesystem;

/**
 * Filesystem convenience methods mixed into {@see SysUtility}.
 *
 * This is now a THIN WRAPPER: every recursive/heavy operation delegates to
 * {@see \Xoops\Helpers\Utility\Filesystem} (`mkdir`, `copy`, `copyDirectory`,
 * `deleteDirectory`, `moveDirectory`); the trait only adds XOOPS-flavoured glue
 * (safe-path guards, the `index.html` drop). It is kept because consumers extend
 * `SysUtility` and call `$utility::createFolder()` / `copyFile()` / `rcopy()`.
 *
 * NEW code should prefer:
 *   - `Xoops\Helpers\Utility\Filesystem` directly for file/dir operations, and
 *   - {@see DirectoryChecker} / {@see FileChecker} for guarded existence/permission checks.
 *
 * @api Stable Common-tier API (Lab\* is experimental, module-local code is private).
 * @deprecated since 1.4.0. Use Xoops\Helpers\Utility\Filesystem for new code; retained through XOOPS 2.8.
 */
trait FilesManagement
{
    /**
     * Function responsible for checking if a directory exists, we can also write in and create an index.html file
     *
     * @param string $folder The full path of the directory to check
     *
     * @throws \RuntimeException
     */
    public static function createFolder($folder): void
    {
        $folder = (string)$folder;
        if (!self::isSafeFilesystemPath($folder)) {
            throw new \RuntimeException(\sprintf('Refusing unsafe directory path: %s', $folder));
        }

        if (!\is_dir($folder)) {
            if (!Filesystem::mkdir($folder, 0755) && !\is_dir($folder)) {
                throw new \RuntimeException(\sprintf('Unable to create the %s directory', $folder));
            }
        }

        $indexFile = rtrim($folder, '/\\') . '/index.html';
        if (!is_file($indexFile)) {
            file_put_contents($indexFile, '<script>history.go(-1);</script>');
        }
    }

    /**
     * @param $file
     * @param $folder
     * @return bool
     */
    public static function copyFile(string $file, string $folder): bool
    {
        if (!self::isSafeFilesystemPath($file) || !self::isSafeFilesystemPath($folder)) {
            return false;
        }

        return Filesystem::copy($file, $folder);
    }

    /**
     * @param $src
     * @param $dst
     */
    public static function recurseCopy($src, $dst): void
    {
        if (!self::isSafeFilesystemPath((string)$src) || !self::isSafeFilesystemPath((string)$dst)) {
            throw new \RuntimeException('Refusing unsafe copy path.');
        }

        if (!\is_dir($src)) {
            throw new \RuntimeException('The directory ' . $src . ' could not be opened.');
        }

        if (!Filesystem::copyDirectory((string)$src, (string)$dst)) {
            throw new \RuntimeException('The directory ' . $dst . ' could not be created.');
        }
    }

    /**
     * Copy a file, or recursively copy a folder and its contents
     * @param string $source Source path
     * @param string $dest   Destination path
     * @return      bool     Returns true on success, false on failure
     * @author      Aidan Lister <aidan@php.net>
     * @version     1.0.1
     * @link        https://aidanlister.com/2004/04/recursively-copying-directories-in-php/
     */
    public static function xcopy($source, $dest): bool
    {
        if (!self::isSafeFilesystemPath((string)$source) || !self::isSafeFilesystemPath((string)$dest)) {
            return false;
        }

        // A link is never recreated: its target is not checked against any base, so a link in a
        // module's testdata/ could point anything (mainfile.php, /etc/passwd) into the upload tree.
        if (\is_link($source)) {
            return false;
        }

        // Simple copy for a file
        if (\is_file($source)) {
            return Filesystem::copy($source, $dest);
        }

        if (@\is_dir($source)) {
            // Deep copy the directory and its contents
            if (!Filesystem::copyDirectory((string)$source, (string)$dest)) {
                throw new \RuntimeException(\sprintf('Directory "%s" was not created', $dest));
            }
        }

        return true;
    }

    /**
     * Remove files and (sub)directories
     *
     * @param string $src source directory to delete
     *
     * @return bool true on success
     * @uses \Xmf\Module\Helper::isUserAdmin()
     *
     * @uses \Xmf\Module\Helper::getHelper()
     */
    public static function deleteDirectory($src): bool
    {
        if (!self::isSafeFilesystemPath((string)$src)) {
            return false;
        }

        // Only continue if user is a 'global' Admin
        $user = self::runtimeUser();
        if (!is_object($user) || !($user instanceof \XoopsUser) || !$user->isAdmin()) {
            return false;
        }

        return Filesystem::deleteDirectory((string)$src);
    }

    /**
     * Recursively remove directory
     *
     * @todo currently won't remove directories with hidden files, should it?
     *
     * @param string $src directory to remove (delete)
     *
     * @return bool true on success
     */
    public static function rrmdir($src): bool
    {
        if (!self::isSafeFilesystemPath((string)$src)) {
            return false;
        }

        // Only continue if user is a 'global' Admin
        $user = self::runtimeUser();
        if (!is_object($user) || !($user instanceof \XoopsUser) || !$user->isAdmin()) {
            return false;
        }

        // If source is not a directory stop processing
        if (!\is_dir($src)) {
            return false;
        }

        return Filesystem::deleteDirectory((string)$src); // remove the directory & return results
    }

    /**
     * Recursively move files from one directory to another
     *
     * @param string $src  - Source of files being moved
     * @param string $dest - Destination of files being moved
     *
     * @return bool true on success
     */
    public static function rmove($src, $dest): bool
    {
        if (!self::isSafeFilesystemPath((string)$src) || !self::isSafeFilesystemPath((string)$dest)) {
            return false;
        }

        // Only continue if user is a 'global' Admin
        $user = self::runtimeUser();
        if (!is_object($user) || !($user instanceof \XoopsUser) || !$user->isAdmin()) {
            return false;
        }

        // If source is not a directory stop processing
        if (!\is_dir($src)) {
            return false;
        }

        // If the destination directory does not exist and could not be created stop processing
        if (!\is_dir($dest) && !Filesystem::mkdir($dest, 0755) && !\is_dir($dest)) {
            return false;
        }

        return Filesystem::moveDirectory((string)$src, (string)$dest); // move contents & remove the source directory
    }

    /**
     * Recursively copy directories and files from one directory to another
     *
     * @param string $src  - Source of files being moved
     * @param string $dest - Destination of files being moved
     *
     * @return bool true on success
     * @uses \Xmf\Module\Helper::isUserAdmin()
     *
     * @uses \Xmf\Module\Helper::getHelper()
     */
    public static function rcopy($src, $dest): bool
    {
        if (!self::isSafeFilesystemPath((string)$src) || !self::isSafeFilesystemPath((string)$dest)) {
            return false;
        }

        // Only continue if user is a 'global' Admin
        $user = self::runtimeUser();
        if (!is_object($user) || !($user instanceof \XoopsUser) || !$user->isAdmin()) {
            return false;
        }

        // If source is not a directory stop processing
        if (!\is_dir($src)) {
            return false;
        }

        // If the destination directory does not exist and could not be created stop processing
        if (!\is_dir($dest) && !Filesystem::mkdir($dest, 0755) && !\is_dir($dest)) {
            return false;
        }

        return Filesystem::copyDirectory((string)$src, (string)$dest);
    }

    private static function isSafeFilesystemPath(string $path): bool
    {
        return '' !== $path
            && !str_contains($path, "\0")
            && !str_contains($path, '://')
            && !str_contains($path, '..');
    }

    /** @legacy-global-accessor */
    private static function runtimeUser(): mixed
    {
        return $GLOBALS['xoopsUser'] ?? null;
    }
}
