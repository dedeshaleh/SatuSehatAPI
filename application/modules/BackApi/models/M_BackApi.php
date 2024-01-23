<?php 
defined('BASEPATH') OR exit('No direct script access allowed');

class M_BackApi extends CI_Model {


    function __construct()
    {
         parent::__construct();
    }

    function UpdateData($kfa_code, $dataFix) {

        $this->db->where("kfa_code", $kfa_code);
        $this->db->update("EMR.SatuSehat.MasterKFA", $dataFix);
        // return $this->db->get();
        
    }

}

/* End of file Mod_login.php */
