<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * CodeIgniter-HMVC
 *
 * @package    CodeIgniter-HMVC
 * @author     N3Cr0N (N3Cr0N@list.ru)
 * @copyright  2019 N3Cr0N
 * @license    https://opensource.org/licenses/MIT  MIT License
 * @link       <URI> (description)
 * @version    GIT: $Id$
 * @since      Version 0.0.1
 * @filesource
 *
 */

class BackApi extends MY_Controller
{
    //
    public $CI;

    /**
     * An array of variables to be passed through to the
     * view, layout,....
     */
    protected $data = array();

    /**
     * [__construct description]
     *
     * @method __construct
     */
    public function __construct()
    {
        // To inherit directly the attributes of the parent class.
        parent::__construct();
    }

    /**
     * [index description]
     *
     * @method index
     *
     * @return [type] [description]
     */
    public function index()
    {
        // Example
        //$this->load->view('backend/dashboard');
    }
    public function GetToken()
    {
        $q = $this->db->query("SELECT * FROM SatuSehat.Environment WHERE Branch = 'BHI' AND Status = 'DEV' ")->row();

        $data = array(
            "client_id" => $q->client_id,
            "client_secret" => $q->client_secret
        );
        
        // Create a new cURL resource
        $ch = curl_init();
        
        // Build the form data
        $formData = http_build_query($data);
        
        // Set cURL options
        $url = $q->auth_url.'/accesstoken?grant_type=client_credentials';
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $formData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/x-www-form-urlencoded'
        ));
        
        // Execute cURL session and capture the response
        $response = curl_exec($ch);
        
        // Check for cURL errors
        if(curl_errno($ch)) {
            echo 'Error: ' . curl_error($ch);
        } else {
            // Decode the response JSON
            $dataCek = json_decode($response);
        
            // Check the status in the response
            if ($dataCek->status == "401" || $dataCek->status == "403") {
                echo $response;
                return $response;
            } else {
                // Build the response in the desired format
                $dataArr = array('ValReturn' => $dataCek, 'base_url' => $q->base_url);
                // echo json_encode($dataArr);
                return $dataArr;
            }
        }
        
        // Close cURL session
        curl_close($ch);

        $dataToken = array(
            'NoTrx' => uniqid(true).date("Y_m_d"),
            'access_token' => $dataCek->access_token,
            'client_id' => $dataCek->client_id,
            'expires_in' => $dataCek->expires_in,
            'CreateDate' => date("Y-m-d H:i:s")
        );

        $this->db->insert('SatuSehat.Log_Token', $dataToken);
        
    }
    public function GetPasien($NIK) 
    {
        date_default_timezone_set("Asia/Jakarta");
        $dataToken = $this->GetToken();
    
        $data = array(
            "Param" => null,
        );

		$json = json_encode($data, JSON_PRETTY_PRINT);
        $dataUrl = $dataToken['base_url'];
        $dataAccess = $dataToken['ValReturn']->access_token;
        		
        $url = "$dataUrl/Patient?identifier=https://fhir.kemkes.go.id/id/nik|$NIK";
        // var_dump($url);
		$ch = curl_init($url);
		
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
		curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Content-Type: application/json',
            'Authorization: Bearer '.$dataAccess
		));
		
		$response = curl_exec($ch);
        var_dump($response);
		if(curl_errno($ch)) {
			// echo 'Error: ' . curl_error($ch);
            echo $response;
		} else {
            
            $dataCek = json_decode($response);
            $dataArr = array('ValReturn' => $dataCek, 'access_token' => $dataAccess);
            echo json_encode($dataArr);

		}
		curl_close($ch);
    }

    public function GetPasien2($NIK) 
    {
        date_default_timezone_set("Asia/Jakarta");
        $dataToken = $this->GetToken();

        $data = array(
            "Param" => null,
        );

        $json = json_encode($data, JSON_PRETTY_PRINT);
        $dataUrl = $dataToken['base_url'];
        $dataAccess = $dataToken['ValReturn']->access_token;

        $url = "$dataUrl/Patient?identifier=https://fhir.kemkes.go.id/id/nik|$NIK";
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Authorization: Bearer ' . $dataAccess
        ));

        $response = curl_exec($ch);

        // Check for cURL errors
        if (curl_errno($ch)) {
            echo 'Error: ' . curl_error($ch);
        } else {
            // Check HTTP status code
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ($httpCode == 200) {
                // Decode and handle the response
                $dataCek = json_decode($response);
                $dataArr = array('ValReturn' => $dataCek, 'access_token' => $dataAccess);
                echo json_encode($dataArr);
            } else {
                // Handle non-200 status code
                echo "HTTP Error: $httpCode\n";
                echo "Response: $response\n";
            }
        }

        curl_close($ch);

    }
}
