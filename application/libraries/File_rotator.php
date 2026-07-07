<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * File_rotator Library
 *
 * Rotates a PDF or image file in place by +/-90 degrees.
 * Extracted from Archived_documents::rotate() (Lot 9 — soumission par
 * téléchargement) so the logic can be shared with forms_admin.
 *
 * Tool used:
 *   - PDF   : qpdf (rotates page 1 only, matches previous behaviour)
 *   - image : ImageMagick convert
 */
class File_rotator
{
    /**
     * Rotate $absolute_path in place.
     *
     * @param string $absolute_path Absolute, already-validated path to the file
     * @param string $mime          Mime type of the file (application/pdf or image/*)
     * @param string $direction     'cw' or 'ccw'
     * @return array ['success' => bool, 'error_code' => string|null, 'tool' => string|null, 'detail' => string|null]
     *   error_code one of: 'invalid_direction', 'tool_missing', 'rotate_failed', 'not_supported'
     */
    public function rotate($absolute_path, $mime, $direction)
    {
        if (!in_array($direction, array('cw', 'ccw'), true)) {
            return array('success' => false, 'error_code' => 'invalid_direction', 'tool' => null, 'detail' => null);
        }

        if (!file_exists($absolute_path)) {
            return array('success' => false, 'error_code' => 'not_supported', 'tool' => null, 'detail' => null);
        }

        // The tmp file must live in the same directory as the target: rename() fails
        // with EXDEV across filesystems (e.g. sys_get_temp_dir() vs an encrypted home
        // mount), which previously went unnoticed because rename()'s return value
        // wasn't checked.
        $tmp_base = dirname($absolute_path) . '/.gvv_rotate_' . uniqid();

        if ($mime === 'application/pdf') {
            return $this->rotate_pdf($absolute_path, $direction, $tmp_base);
        }

        if (strpos($mime, 'image/') === 0) {
            return $this->rotate_image($absolute_path, $direction, $tmp_base);
        }

        return array('success' => false, 'error_code' => 'not_supported', 'tool' => null, 'detail' => null);
    }

    private function rotate_pdf($absolute_path, $direction, $tmp_base)
    {
        $qpdf = trim((string) @shell_exec('which qpdf 2>/dev/null'));
        if (!$qpdf || !file_exists($qpdf)) {
            return array('success' => false, 'error_code' => 'tool_missing', 'tool' => 'qpdf', 'detail' => null);
        }

        $angle = ($direction === 'cw') ? '+90' : '-90';
        $tmp_file = $tmp_base . '.pdf';
        $cmd = escapeshellcmd($qpdf) . ' --rotate=' . $angle . ':1-z '
             . escapeshellarg($absolute_path) . ' ' . escapeshellarg($tmp_file) . ' 2>&1';
        $output = array();
        $return_code = 0;
        exec($cmd, $output, $return_code);

        if ($return_code !== 0 || !file_exists($tmp_file)) {
            @unlink($tmp_file);
            return array('success' => false, 'error_code' => 'rotate_failed', 'tool' => 'qpdf', 'detail' => implode(' ', $output));
        }

        if (!@rename($tmp_file, $absolute_path)) {
            @unlink($tmp_file);
            return array('success' => false, 'error_code' => 'rotate_failed', 'tool' => 'qpdf', 'detail' => 'Impossible de remplacer le fichier original (permissions ?)');
        }
        return array('success' => true, 'error_code' => null, 'tool' => 'qpdf', 'detail' => null);
    }

    private function rotate_image($absolute_path, $direction, $tmp_base)
    {
        $convert = trim((string) @shell_exec('which convert 2>/dev/null'));
        if (!$convert || !file_exists($convert)) {
            return array('success' => false, 'error_code' => 'tool_missing', 'tool' => 'convert', 'detail' => null);
        }

        $degrees = ($direction === 'cw') ? '90' : '-90';
        $ext = strtolower(pathinfo($absolute_path, PATHINFO_EXTENSION));
        $tmp_file = $tmp_base . '.' . $ext;
        $cmd = escapeshellcmd($convert) . ' -rotate ' . escapeshellarg($degrees) . ' '
             . escapeshellarg($absolute_path) . ' ' . escapeshellarg($tmp_file) . ' 2>&1';
        $output = array();
        $return_code = 0;
        exec($cmd, $output, $return_code);

        if ($return_code !== 0 || !file_exists($tmp_file)) {
            @unlink($tmp_file);
            return array('success' => false, 'error_code' => 'rotate_failed', 'tool' => 'convert', 'detail' => implode(' ', $output));
        }

        if (!@rename($tmp_file, $absolute_path)) {
            @unlink($tmp_file);
            return array('success' => false, 'error_code' => 'rotate_failed', 'tool' => 'convert', 'detail' => 'Impossible de remplacer le fichier original (permissions ?)');
        }
        return array('success' => true, 'error_code' => null, 'tool' => 'convert', 'detail' => null);
    }
}
