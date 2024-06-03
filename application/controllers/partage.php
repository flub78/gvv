<?php

/**
 *    GVV Gestion vol à voile
 *    Copyright (C) 2011  Philippe Boissel & Frédéric Peignot
 *
 *    This program is free software: you can redistribute it and/or modify
 *    it under the terms of the GNU General Public License as published by
 *    the Free Software Foundation, either version 3 of the License, or
 *    (at your option) any later version.
 *
 *    This program is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU General Public License for more details.
 *
 *    You should have received a copy of the GNU General Public License
 *    along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * File: Partage.php
 * Partage controller ce controleur gére les fichiers partagés
 *
 */
class Partage extends CI_Controller {
    protected $upload_dir = './uploads/';

    /**
     * Constructor
     */
    function __construct() {
        parent::__construct();

        $this->load->helper('file');
        $this->load->helper('date');
    }

    /**
     * Affiche le liste des fichiers communs
     */
    public function upload($dir, $error = '') {
        $data = array ();
        if ($dir == "ca") {
            $data ['title'] = "Fichiers partagés par les membres du CA";
        } else {
            $data ['title'] = "Fichiers partagés";
        }

        $upload_dir = $this->upload_dir . "/$dir";

        $file_list = get_dir_file_info($upload_dir);

        $list = array ();
        foreach ( $file_list as $name => $info ) {
            $url = base_url() . $info ['relative_path'] . '/' . urlencode($info ['name']);
            $values = array (
                    'Id' => $name,
                    'Nom' => anchor($url, $name),
                    'Taille' => $info ['size'],
                    'Date' => unix_to_human($info ['date'])
            );
            $list [] = $values;
        }

        $data ['error'] = $error;
        $data ['count'] = count($list);
        $data ['premier'] = 0;
        $data ['list'] = $list;
        $data ['controller'] = 'partage';
        $data ['title_row'] = array (
                'Nom',
                'Taille',
                'Date'
        );
        $data ['col_list'] = array (
                'Nom',
                'Taille',
                'Date'
        );
        $data ['primary_key'] = "Id";
        $data ['dir'] = $dir;
        load_last_view('simpleDirectoryView', $data);
    }

    /**
     * Charge un fichier partagé
     */
    public function do_upload($dir) {
        $config ['upload_path'] = "./uploads/$dir";
        $config ['allowed_types'] = 'doc|xls|pdf|sql|gif|jpg|png|zip';
        $config ['max_size'] = '100';

        $this->load->library('upload', $config);

        if (! $this->upload->do_upload()) {
            $error = $this->upload->display_errors();
        } else {
            $error = "";
        }
        $this->upload($dir, $error);
    }

    /**
     * Supprime un fichier partagé
     */
    public function delete($dir, $file) {
        $path = "./uploads/$dir/$file";
        if (! unlink($path)) {
            $error = "Erreur en supprimant $file";
        } else {
            $error = '';
        }
        $this->upload($dir, $error);
    }
}