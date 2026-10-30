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
use Xoops\ModuleTools\Internal\PathContainment;

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

        if (!self::copyTree((string)$src, (string)$dst)) {
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
        // A linked destination would be written through, on the file branch as on the tree one.
        if (PathContainment::isLink((string)$source) || PathContainment::isLink((string)$dest)) {
            return false;
        }

        // Simple copy for a file
        if (\is_file($source)) {
            return Filesystem::copy($source, $dest);
        }

        // Deep copy the directory and its contents
        return !\is_dir($source) || self::copyTree((string)$source, (string)$dest);
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

        if (!self::isGlobalAdmin()) {
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

        if (!self::isGlobalAdmin()) {
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

        if (!self::isGlobalAdmin()) {
            return false;
        }

        // If source is not a directory stop processing
        if (!\is_dir($src)) {
            return false;
        }

        // move contents & remove the source directory
        return self::copyTree((string)$src, (string)$dest) && Filesystem::deleteDirectory((string)$src);
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

        if (!self::isGlobalAdmin()) {
            return false;
        }

        // If source is not a directory stop processing
        if (!\is_dir($src)) {
            return false;
        }

        return self::copyTree((string)$src, (string)$dest);
    }

    /**
     * Filesystem::copyDirectory() (helpers 1.0) follows nested links and walks a destination
     * that sits inside the source, copying its own output again; every recursive copy here
     * goes through this instead. A link at any depth fails the copy; it is never followed.
     */
    private static function copyTree(string $source, string $dest): bool
    {
        // is_link() resolves a trailing "/" or "/.", which would hide a linked directory.
        $source = (string)\preg_replace('#(?:[/\\\\]\.?)+\z#', '', $source);
        $dest   = (string)\preg_replace('#(?:[/\\\\]\.?)+\z#', '', $dest);
        $root   = '' === $source || '' === $dest ? false : \realpath($source);
        if (false === $root || PathContainment::isLink($source) || PathContainment::isLink($dest)) {
            return false;
        }
        // A failed mkdir (or a file at $dest) leaves $target false, which the refusal below covers.
        $created = !\is_dir($dest) && Filesystem::mkdir($dest, 0755);
        $target  = \is_dir($dest) ? \realpath($dest) : false;
        // Overlap either way is refused: a destination above the source can receive the source
        // itself, which rmove() then deletes.
        if (
            false === $target
            || PathContainment::isInside($target, $root) || PathContainment::isInsideByIdentity($target, $root)
            || PathContainment::isInside($root, $target) || PathContainment::isInsideByIdentity($root, $target)
        ) {
            if ($created) {
                @\rmdir($dest);
            }

            return false;
        }

        return self::copyEntries($source, $dest);
    }

    private static function copyEntries(string $source, string $dest): bool
    {
        $entries = \scandir($source);

        return false !== $entries && \array_all(
            \array_diff($entries, ['.', '..']),
            static fn (string $entry): bool => self::copyEntry($source . '/' . $entry, $dest . '/' . $entry),
        );
    }

    private static function copyEntry(string $from, string $to): bool
    {
        // Refused, not skipped: rmove() deletes the source after a successful copy. A link
        // already in the destination would be written through, so it is refused too.
        if (PathContainment::isLink($from) || PathContainment::isLink($to)) {
            return false;
        }
        if (!\is_dir($from)) {
            return self::copyRegularFile($from, $to);
        }

        return (@\mkdir($to, 0755) || \is_dir($to)) && self::copyEntries($from, $to);
    }

    /**
     * The link check and the open are separate calls, so a writer could swap the entry for a
     * link in between: the opened handle must be the regular file lstat() saw (device + inode).
     * The destination is unlinked and created exclusively ("x" never follows a link), so a link
     * planted there after the check fails the copy instead of truncating its target. A replaced
     * regular file's read/write bits carry over (execute bits, owner and group do not).
     * ponytail: an ancestor directory swapped mid-copy is not covered (PHP has no openat() or
     * O_NOFOLLOW); source trees must not be writable by lower-trust users while they are copied.
     */
    private static function copyRegularFile(string $from, string $to): bool
    {
        $checked = @\lstat($from);
        if (false === $checked || 0o100000 !== ($checked['mode'] & 0o170000) || false === ($in = @\fopen($from, 'rb'))) {
            return false;
        }
        $opened = \fstat($in);
        $out    = false;
        $mode   = null;
        if (false !== $opened && $opened['dev'] === $checked['dev'] && $opened['ino'] === $checked['ino']) {
            $replaced = @\lstat($to); // the entry itself: a link's own mode, never its target's
            if (false !== $replaced) {
                $mode = 0o100000 === ($replaced['mode'] & 0o170000) ? $replaced['mode'] & 0o777 : null;
                @\unlink($to); // removes a link itself, never its target; a failure leaves "x" to refuse
            }
            // A replaced 0600 file must not reappear as 0644. The mode is applied through the umask at
            // creation: a chmod() by path would follow a link planted after fopen().
            // ponytail: umask() is process-wide; under a threaded SAPI a concurrent request can briefly see it.
            $umask = null === $mode ? null : \umask(~$mode & 0o777);
            try {
                $out = @\fopen($to, 'xb');
            } finally {
                if (null !== $umask) {
                    \umask($umask);
                }
            }
        }
        $ok = false !== $out && false !== \stream_copy_to_stream($in, $out);
        if (false !== $out) {
            $ok = \fclose($out) && $ok;
        }
        \fclose($in);

        return $ok;
    }

    private static function isSafeFilesystemPath(string $path): bool
    {
        return '' !== $path
            && !str_contains($path, "\0")
            && !str_contains($path, '://')
            && !str_contains($path, '..');
    }

    /**
     * Recursive delete/move/copy on arbitrary paths is a webmaster action. XoopsUser::isAdmin()
     * without an id tests the CURRENT module, so a module-only admin would pass it.
     */
    private static function isGlobalAdmin(): bool
    {
        $user = self::runtimeUser();
        if (!$user instanceof \XoopsUser || !\defined('XOOPS_GROUP_ADMIN')) {
            return false;
        }
        $groups = $user->getGroups();

        return \is_array($groups) && \in_array((int) \XOOPS_GROUP_ADMIN, \array_map(\intval(...), $groups), true);
    }

    /** @legacy-global-accessor */
    private static function runtimeUser(): mixed
    {
        return $GLOBALS['xoopsUser'] ?? null;
    }
}
