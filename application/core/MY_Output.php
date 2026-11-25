<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * Injecte le script de protection CSRF (assets/javascript/csrf.js) au début
 * du <head> de chaque page HTML produite par l'application.
 *
 * Passer par la sortie plutôt que par chaque vue couvre toutes les pages,
 * y compris celles qui n'utilisent pas l'en-tête commun et les pages futures.
 * Les réponses qui ne sont pas des pages HTML complètes (PDF, JSON, fragments
 * AJAX sans <head>) sont laissées telles quelles.
 */
class MY_Output extends CI_Output
{
    function _display($output = '')
    {
        if ($output == '') {
            $output = $this->final_output;
        }

        if (config_item('csrf_protection') === TRUE && $this->is_html_response()) {
            $output = $this->inject_csrf_script($output);
        }

        parent::_display($output);
    }

    /**
     * @param string $output
     * @return string
     */
    protected function inject_csrf_script($output)
    {
        if (!is_string($output) || strpos($output, 'csrf.js') !== FALSE) {
            return $output;
        }
        if (!preg_match('/<head\b[^>]*>/i', $output, $match, PREG_OFFSET_CAPTURE)) {
            return $output;
        }

        $file = FCPATH . 'assets/javascript/csrf.js';
        $version = file_exists($file) ? filemtime($file) : 0;
        $tag = '<script src="' . rtrim(config_item('base_url'), '/') . '/assets/javascript/csrf.js?v=' . $version . '"></script>';

        $position = $match[0][1] + strlen($match[0][0]);
        return substr($output, 0, $position) . "\n" . $tag . substr($output, $position);
    }

    /**
     * La réponse est-elle une page HTML (type de contenu HTML ou non précisé) ?
     *
     * @return bool
     */
    protected function is_html_response()
    {
        foreach ($this->headers as $header) {
            if (stripos($header[0], 'Content-Type:') === 0) {
                return stripos($header[0], 'text/html') !== FALSE;
            }
        }
        return TRUE;
    }
}

/* End of file MY_Output.php */
/* Location: ./application/core/MY_Output.php */
