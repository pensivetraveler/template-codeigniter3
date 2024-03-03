<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class MY_Controller extends CI_Controller
{
    function __construct()
    {
        parent::__construct();

        date_default_timezone_set('Asia/Seoul');

        $this->load->library('Authorization_Token');

        $this->encryption->initialize(
            array(
                'cipher' => 'aes-256',
                'mode' => 'ctr',
            )
        );
    }

}