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

class SatuSehatCase_2 extends MY_Controller
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
        $this->load->model("M_BackApi");
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
    public function GetLogin()
    {
        date_default_timezone_set("Asia/Jakarta");
        $json = file_get_contents("php://input"); // json string
        $object = json_decode($json); // php object

        $Branch = $object->Branch;
        $client_id = $object->client_id;
        $status = $object->status;
        $client_secret = $object->client_secret;

        $q = $this->db->query("SELECT * FROM SatuSehat.Environment WHERE Branch = '$Branch' AND Status = '$status' AND client_id = '$client_id' AND client_secret = '$client_secret' ")->row();
        if ($q !== null) {
            // Menghasilkan byte acak
            $randomBytes = random_bytes(32);

            // Mengonversi byte ke dalam format base64
            $base64Code = str_replace(['+', '/', '='], ['', '', ''], base64_encode($randomBytes));

            $GenToken = $base64Code;

            $dataToken = array(
                'NoTrx' => uniqid(true).date("Y_m_d"),
                'access_token' => $GenToken,
                'client_id' => $client_id,
                'expires_in' => 3600,
                'CreateDate' => date("Y-m-d H:i:s"),
                'TokenAkses' => "Get Credential"
            );
    
            $this->db->insert('SatuSehat.Log_Token', $dataToken);

            $data = array(
                "status" => 200,
                "message" => "Success Access Credentials",
                "Token" => $GenToken
            );
        }else{
            $data = array(
                "status" => 400,
                "message" => "Invalid Credentials"
            );
        }
        
        echo json_encode($data);

    }

    public function GetToken($AksesToken)
    {
        $EnvData = $this->config->item('EnvData');
        $q = $this->db->query("SELECT * FROM SatuSehat.Environment WHERE Branch = 'BHI' AND Status = '$EnvData' ")->row();

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

            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $dataToken = array(
                'NoTrx' => uniqid(true).date("Y_m_d"),
                'access_token' => $dataCek->access_token,
                'client_id' => $dataCek->client_id,
                'expires_in' => $dataCek->expires_in,
                'CreateDate' => date("Y-m-d H:i:s"),
                'Deskripsi' => json_encode($dataCek, TRUE),
                'payload' => json_encode($data, TRUE),
                'TokenAkses' => $AksesToken
            );
    
            $this->db->insert('SatuSehat.Log_Token', $dataToken);
            // Check the status in the response
            if ($httpCode == 200) {
                
                // Build the response in the desired format
                $dataArr = array('status' => 1, 'ValReturn' => $dataCek, 'base_url' => $q->base_url, 'NoOrganisasi' => $q->NoOrganisasi, "DataToken" => $dataToken);
                // echo json_encode($dataArr);
                return $dataArr;
               
            } else {
                $dataArr = array('status' => 0, 'ValReturn' => $dataCek, 'base_url' => $q->base_url, 'NoOrganisasi' => $q->NoOrganisasi, "DataToken" => $dataToken);
                return $dataArr;
                // echo $response;
                // echo "HTTP Error: $httpCode\n";
                // echo "Response: $response\n";
                // return $response;
            }
        }
        
        // Close cURL session
        curl_close($ch);
    }

    public function BatchSend() 
    {
        date_default_timezone_set("Asia/Jakarta");

        $json = file_get_contents("php://input"); // json string
        $object = json_decode($json); // php object

        $NoRegis = $object->NoRegis;
        $Token = $object->Token;

        $q2 = $this->db->query("SELECT * FROM EMR.SatuSehat.Log_Token WHERE access_token = '$Token' ")->row();
        $DataPasien = $this->db->query("SELECT * FROM EMR.SatuSehat.vw_SatuSehatCase_2 WHERE NoRegistrasi = '$NoRegis'")->row();

        $GetDate = $q2->CreateDate;
        $NewDate = date("Y-m-d H:i:s");

        $startTimeStamp = strtotime($GetDate);
        $currentTimeStamp = strtotime($NewDate);
        $timeDifferenceInSeconds = $currentTimeStamp - $startTimeStamp;

        if ($timeDifferenceInSeconds > $q2->expires_in ) {
            $data = array(
                "status" => 400,
                "message" => "Time Out's"
            );
            echo json_encode($data);
            die;
        }

        $dataToken = $this->GetToken("Batch Send Case 2");
        if ($dataToken['status'] == 0) {
            $dataArr = array('ValReturn' => $dataToken);
            echo json_encode($dataArr);
            die();
        };
        $NoOrganisasi = $dataToken['NoOrganisasi'];
        $dataUrl = $dataToken['base_url'];
        $dataAccess = $dataToken['ValReturn']->access_token;
        $client_id = $dataToken["DataToken"]['client_id'];
        $expires_in = $dataToken["DataToken"]['expires_in'];
        $ID_Encounter = $DataPasien->ID_Encounter;
        $Pasien = $DataPasien->Pasien;
        $Dokter = $DataPasien->Dokter;
        $TanggalEfektif = $DataPasien->TanggalEfektif;
        $TanggalIsue = $DataPasien->TanggalIsue;
        $ValueNadi = $DataPasien->ValueNadi;
        $genArray = array(
            "NoOrganisasi" => $NoOrganisasi,
            "dataUrl" => $dataUrl,
            "dataAccess" => $dataAccess,
            "client_id" => $client_id,
            "expires_in" => $expires_in,
            "DataPasien" => $DataPasien
        );

        $DataDetail = (object) $genArray;

        $qCekTempId = $this->db->query("SELECT * FROM EMR.SatuSehat.Temp_ID WHERE NoRegis = '$NoRegis' AND Status = 'Observation - Nadi' ")->row();

        if ($qCekTempId == null) {
            $data = '{
                "identifier":[
                    {
                        "system": "http://sys-ids.kemkes.go.id/observation/'.$NoOrganisasi.'",
                        "value": "'.$NoRegis.'"
                    }
                ],
                "resourceType": "Observation",
                "status": "final",
                "category": [
                    {
                        "coding": [
                            {
                                "system": "http://terminology.hl7.org/CodeSystem/observation-category",
                                "code": "vital-signs",
                                "display": "Vital Signs"
                            }
                        ]
                    }
                ],
                "code": {
                    "coding": [
                        {
                            "system": "http://loinc.org",
                            "code": "8867-4",
                            "display": "Heart rate"
                        }
                    ]
                },
                "subject": {
                    "reference": "Patient/'.$Pasien.'"
                },
                "performer": [
                    {
                        "reference": "Practitioner/'.$Dokter.'"
                    }
                ],
                "encounter": {
                    "reference": "Encounter/'.$ID_Encounter.'"
                },
                "effectiveDateTime": "'.$TanggalEfektif.'",
                "issued": "'.$TanggalIsue.'",
                "valueQuantity": {
                    "value": '.$ValueNadi.',
                    "unit": "beats/minute",
                    "system": "http://unitsofmeasure.org",
                    "code": "/min"
                }
            }';

            // $json = json_encode($data, JSON_PRETTY_PRINT);

            $url = "$dataUrl/Observation";
            $ch = curl_init($url);

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Authorization: Bearer ' . $dataAccess
            ));

            $response = curl_exec($ch);
            $dataCek = json_decode($response);
            $TokenContinue = array(
                'NoTrx' => uniqid(true).date("Y_m_d"),
                'access_token' => $dataAccess,
                'client_id' => $dataToken["DataToken"]['client_id'],
                'expires_in' => $dataToken["DataToken"]['expires_in'],
                'CreateDate' => date("Y-m-d H:i:s"),
                'Deskripsi' => json_encode($dataCek, TRUE),
                'Payload' => $data,
                'TokenAkses' => "Post Observation - Nadi"
            );
    
            $this->db->insert('SatuSehat.Log_Token', $TokenContinue);
            // Check for cURL errors
            if (curl_errno($ch)) {

                $dataArr = array('Status' => 0, 'payload' => $data, 'access_token' => $dataAccess, 'httpError' => 400, $ch, "Posisi" => "1", "response" => json_decode($response));
                echo json_encode($dataArr);

            } else {
                // Check HTTP status code
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                if ($httpCode == 201 || $httpCode == 200) {
                    // Decode and handle the response
                    $dataArr = array('ValReturn' => $dataCek, 'access_token' => $dataAccess);
                    
                    $TempId = array(
                        "NoTrx" => uniqid(true).date("_Y-m-d"),
                        "NoRegis" => $NoRegis,
                        "ID_SatuSehat" => $dataCek->id,
                        "Status" => "Observation - Nadi",
                        "CreateDate" => date("Y-m-d H:i:s"),
                        "Token" => $dataAccess
                    );
                    $this->db->insert("EMR.SatuSehat.Temp_ID", $TempId);
                    $DataResp = array(
                        "ID_Nadi" => $dataCek->id
                    );
                    $this->Case_2($DataDetail, $DataResp);
                    // echo json_encode($dataArr);
                } else {
                    // Handle non-200 status code
                    $hasil = json_decode($data);
                    $dataArr = array('Status' => 0, 'payload' => $hasil, 'access_token' => $dataAccess, 'httpError' => $httpCode, "Posisi" => "1", "response" => json_decode($response));
                    echo json_encode($dataArr);
                    die();
                }
            }

            curl_close($ch);
        }else{
            // $dataArr = array('Status' => 0, 'message' => "Data Sudah Ada", "ID"=> $qCekTempId->ID_SatuSehat, "Posisi" => "1");
            // echo json_encode($dataArr);
            $DataResp = array(
                "ID_Nadi" => $qCekTempId->ID_SatuSehat
            );
            $this->Case_2($DataDetail, $DataResp);
            
        }
    }

    function Case_2($DataDetail, $DataID)
    {
        // var_dump($DataDetail);
        
        $NoRegis = $DataDetail->DataPasien->NoRegistrasi;
        $Pasien = $DataDetail->DataPasien->Pasien;
        $Dokter = $DataDetail->DataPasien->Dokter;
        $ValuePernapasan = $DataDetail->DataPasien->ValuePernapasan;
        $TanggalEfektif = $DataDetail->DataPasien->TanggalEfektif;
        $TanggalIsue = $DataDetail->DataPasien->TanggalIsue;
        $ID_Encounter = $DataDetail->DataPasien->ID_Encounter;
        $NoOrganisasi = $DataDetail->NoOrganisasi;
        $dataAccess = $DataDetail->dataAccess;
        $dataUrl = $DataDetail->dataUrl;
        $client_id = $DataDetail->client_id;
        $expires_in = $DataDetail->expires_in;
        $qCekTempId = $this->db->query("SELECT * FROM EMR.SatuSehat.Temp_ID WHERE NoRegis = '$NoRegis' AND Status = 'Observation - Pernapasan' ")->row();

        if ($qCekTempId == null) {
            $data = '{
                "identifier":[
                    {
                        "system": "http://sys-ids.kemkes.go.id/observation/'.$NoOrganisasi.'",
                        "value": "'.$NoRegis.'"
                    }
                ],
                "resourceType": "Observation",
                "status": "final",
                "category": [
                    {
                        "coding": [
                            {
                                "system": "http://terminology.hl7.org/CodeSystem/observation-category",
                                "code": "vital-signs",
                                "display": "Vital Signs"
                            }
                        ]
                    }
                ],
                "code": {
                    "coding": [
                        {
                            "system": "http://loinc.org",
                            "code": "9279-1",
                            "display": "Respiratory rate"
                        }
                    ]
                },
                "subject": {
                    "reference": "Patient/'.$Pasien.'"
                },
                "performer": [
                    {
                        "reference": "Practitioner/'.$Dokter.'"
                    }
                ],
                "encounter": {
                    "reference": "Encounter/'.$ID_Encounter.'"
                },
                "effectiveDateTime": "'.$TanggalEfektif.'",
                "issued": "'.$TanggalIsue.'",
                "valueQuantity": {
                    "value": '.$ValuePernapasan.',
                    "unit": "breaths/minute",
                    "system": "http://unitsofmeasure.org",
                    "code": "/min"
                }
            }';

            // $json = json_encode($data, JSON_PRETTY_PRINT);

            $url = "$dataUrl/Observation";
            $ch = curl_init($url);

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Authorization: Bearer ' . $dataAccess
            ));

            $response = curl_exec($ch);
            $dataCek = json_decode($response);
            $TokenContinue = array(
                'NoTrx' => uniqid(true).date("Y_m_d"),
                'access_token' => $dataAccess,
                'client_id' => $client_id,
                'expires_in' => $expires_in,
                'CreateDate' => date("Y-m-d H:i:s"),
                'Deskripsi' => json_encode($dataCek, TRUE),
                'Payload' => $data,
                'TokenAkses' => "Post Observation - Pernapasan"
            );
    
            $this->db->insert('SatuSehat.Log_Token', $TokenContinue);
            // Check for cURL errors
            if (curl_errno($ch)) {

                $dataArr = array('Status' => 0, 'payload' => $data, 'access_token' => $dataAccess, 'httpError' => 400, $ch, "Posisi" => "2", "response" => json_decode($response));
                echo json_encode($dataArr);

            } else {
                // Check HTTP status code
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                if ($httpCode == 201 || $httpCode == 200) {
                    // Decode and handle the response
                    $dataArr = array();
                    
                    $TempId = array(
                        "NoTrx" => uniqid(true).date("_Y-m-d"),
                        "NoRegis" => $NoRegis,
                        "ID_SatuSehat" => $dataCek->id,
                        "Status" => "Observation - Pernapasan",
                        "CreateDate" => date("Y-m-d H:i:s"),
                        "Token" => $dataAccess
                    );
                    $this->db->insert("EMR.SatuSehat.Temp_ID", $TempId);
                    $DataResp = array(
                        "ID_Nadi" => $DataID["ID_Nadi"],
                        "ID_Pernapasan" => $dataCek->id
                    );
                    $this->Case_3($DataDetail, $DataResp);
                    // $dataArr = array('Status' => 1, 'ValReturn' => $dataCek, 'access_token' => $dataAccess, 'payload' => json_decode($data), 'access_token' => $dataAccess, 'httpError' => $httpCode, "Posisi" => "2", "response" => json_decode($response));
                    // echo json_encode($dataArr);
                } else {
                    // Handle non-200 status code
                    $hasil = json_decode($data);
                    $dataArr = array('Status' => 0, 'payload' => $hasil, 'access_token' => $dataAccess, 'httpError' => $httpCode, "Posisi" => "2", "response" => json_decode($response));
                    echo json_encode($dataArr);
                }
            }

            curl_close($ch);
        }else{
            // $dataArr = array('Status' => 0, 'message' => "Data Sudah Ada", "ID"=> $qCekTempId->ID_SatuSehat, "Posisi" => "2");
            $DataResp = array(
                "ID_Nadi" => $DataID["ID_Nadi"],
                "ID_Pernapasan" => $qCekTempId->ID_SatuSehat
            );
            $this->Case_3($DataDetail, $DataResp);
            
        }
    }

    function Case_3($DataDetail, $DataID)
    {
        $NoRegis = $DataDetail->DataPasien->NoRegistrasi;
        $Pasien = $DataDetail->DataPasien->Pasien;
        $Dokter = $DataDetail->DataPasien->Dokter;
        $ValueSistolik = $DataDetail->DataPasien->ValueSistolik;
        $TanggalEfektif = $DataDetail->DataPasien->TanggalEfektif;
        $TanggalIsue = $DataDetail->DataPasien->TanggalIsue;
        $ID_Encounter = $DataDetail->DataPasien->ID_Encounter;
        $NoOrganisasi = $DataDetail->NoOrganisasi;
        $dataAccess = $DataDetail->dataAccess;
        $dataUrl = $DataDetail->dataUrl;
        $client_id = $DataDetail->client_id;
        $expires_in = $DataDetail->expires_in;
        $qCekTempId = $this->db->query("SELECT * FROM EMR.SatuSehat.Temp_ID WHERE NoRegis = '$NoRegis' AND Status = 'Observation - Sistolik' ")->row();

        if ($qCekTempId == null) {
            $data = '{
                "identifier":[
                    {
                        "system": "http://sys-ids.kemkes.go.id/observation/'.$NoOrganisasi.'",
                        "value": "'.$NoRegis.'"
                    }
                ],
                "resourceType": "Observation",
                "status": "final",
                "category": [
                    {
                        "coding": [
                            {
                                "system": "http://terminology.hl7.org/CodeSystem/observation-category",
                                "code": "vital-signs",
                                "display": "Vital Signs"
                            }
                        ]
                    }
                ],
                "code": {
                    "coding": [
                        {
                            "system": "http://loinc.org",
                            "code": "8480-6",
                            "display": "Systolic blood pressure"
                        }
                    ]
                },
                "subject": {
                    "reference": "Patient/'.$Pasien.'"
                },
                "performer": [
                    {
                        "reference": "Practitioner/'.$Dokter.'"
                    }
                ],
                "encounter": {
                    "reference": "Encounter/'.$ID_Encounter.'"
                },
                "effectiveDateTime": "'.$TanggalEfektif.'",
                "issued": "'.$TanggalIsue.'",
                "bodySite": {
                    "coding": [
                        {
                            "system": "http://snomed.info/sct",
                            "code": "368209003",
                            "display": "Right arm"
                        }
                    ]
                },
                "valueQuantity": {
                    "value": '.$ValueSistolik.',
                    "unit": "mm[Hg]",
                    "system": "http://unitsofmeasure.org",
                    "code": "mm[Hg]"
                }
            }';

            // $json = json_encode($data, JSON_PRETTY_PRINT);

            $url = "$dataUrl/Observation";
            $ch = curl_init($url);

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Authorization: Bearer ' . $dataAccess
            ));

            $response = curl_exec($ch);
            $dataCek = json_decode($response);
            $TokenContinue = array(
                'NoTrx' => uniqid(true).date("Y_m_d"),
                'access_token' => $dataAccess,
                'client_id' => $client_id,
                'expires_in' => $expires_in,
                'CreateDate' => date("Y-m-d H:i:s"),
                'Deskripsi' => json_encode($dataCek, TRUE),
                'Payload' => $data,
                'TokenAkses' => "Post Observation - Sistolik"
            );
    
            $this->db->insert('SatuSehat.Log_Token', $TokenContinue);
            // Check for cURL errors
            if (curl_errno($ch)) {

                $dataArr = array('Status' => 0, 'payload' => $data, 'access_token' => $dataAccess, 'httpError' => 400, $ch, "Posisi" => "3", "response" => json_decode($response));
                echo json_encode($dataArr);

            } else {
                // Check HTTP status code
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                if ($httpCode == 201 || $httpCode == 200) {
                    // Decode and handle the response
                    $dataArr = array();
                    
                    $TempId = array(
                        "NoTrx" => uniqid(true).date("_Y-m-d"),
                        "NoRegis" => $NoRegis,
                        "ID_SatuSehat" => $dataCek->id,
                        "Status" => "Observation - Sistolik",
                        "CreateDate" => date("Y-m-d H:i:s"),
                        "Token" => $dataAccess
                    );
                    $this->db->insert("EMR.SatuSehat.Temp_ID", $TempId);
                    $DataResp = array(
                        "ID_Nadi" => $DataID["ID_Nadi"],
                        "ID_Pernapasan" => $DataID["ID_Pernapasan"],
                        "ID_Sistolik" => $dataCek->id
                    );
                    $this->Case_4($DataDetail, $DataResp);
                    // $dataArr = array('Status' => 1, 'ValReturn' => $dataCek, 'access_token' => $dataAccess, 'payload' => json_decode($data), 'access_token' => $dataAccess, 'httpError' => $httpCode, "Posisi" => "2", "response" => json_decode($response));
                    // echo json_encode($dataArr);
                } else {
                    // Handle non-200 status code
                    $hasil = json_decode($data);
                    $dataArr = array('Status' => 0, 'payload' => $hasil, 'access_token' => $dataAccess, 'httpError' => $httpCode, "Posisi" => "3", "response" => json_decode($response));
                    echo json_encode($dataArr);
                }
            }

            curl_close($ch);
        }else{
            // $dataArr = array('Status' => 0, 'message' => "Data Sudah Ada", "ID"=> $qCekTempId->ID_SatuSehat, "Posisi" => "2");
            $DataResp = array(
                "ID_Nadi" => $DataID["ID_Nadi"],
                "ID_Pernapasan" => $DataID["ID_Pernapasan"],
                "ID_Sistolik" => $qCekTempId->ID_SatuSehat
            );
            $this->Case_4($DataDetail, $DataResp);
            
        }
    }

    function Case_4($DataDetail, $DataID)
    {
        $NoRegis = $DataDetail->DataPasien->NoRegistrasi;
        $Pasien = $DataDetail->DataPasien->Pasien;
        $Dokter = $DataDetail->DataPasien->Dokter;
        $ValueDiastolik = $DataDetail->DataPasien->ValueDiastolik;
        $TanggalEfektif = $DataDetail->DataPasien->TanggalEfektif;
        $TanggalIsue = $DataDetail->DataPasien->TanggalIsue;
        $ID_Encounter = $DataDetail->DataPasien->ID_Encounter;
        $NoOrganisasi = $DataDetail->NoOrganisasi;
        $dataAccess = $DataDetail->dataAccess;
        $dataUrl = $DataDetail->dataUrl;
        $client_id = $DataDetail->client_id;
        $expires_in = $DataDetail->expires_in;
        $qCekTempId = $this->db->query("SELECT * FROM EMR.SatuSehat.Temp_ID WHERE NoRegis = '$NoRegis' AND Status = 'Observation - Diastolik' ")->row();

        if ($qCekTempId == null) {
            $data = '{
                "identifier":[
                    {
                        "system": "http://sys-ids.kemkes.go.id/observation/'.$NoOrganisasi.'",
                        "value": "'.$NoRegis.'"
                    }
                ],
                "resourceType": "Observation",
                "status": "final",
                "category": [
                    {
                        "coding": [
                            {
                                "system": "http://terminology.hl7.org/CodeSystem/observation-category",
                                "code": "vital-signs",
                                "display": "Vital Signs"
                            }
                        ]
                    }
                ],
                "code": {
                    "coding": [
                        {
                            "system": "http://loinc.org",
                            "code": "8462-4",
                            "display": "Diastolic blood pressure"
                        }
                    ]
                },
                "subject": {
                    "reference": "Patient/'.$Pasien.'"
                },
                "performer": [
                    {
                        "reference": "Practitioner/'.$Dokter.'"
                    }
                ],
                "encounter": {
                    "reference": "Encounter/'.$ID_Encounter.'"
                },
                "effectiveDateTime": "'.$TanggalEfektif.'",
                "issued": "'.$TanggalIsue.'",
                "bodySite": {
                    "coding": [
                        {
                            "system": "http://snomed.info/sct",
                            "code": "368209003",
                            "display": "Right arm"
                        }
                    ]
                },
                "valueQuantity": {
                    "value": '.$ValueDiastolik.',
                    "unit": "mm[Hg]",
                    "system": "http://unitsofmeasure.org",
                    "code": "mm[Hg]"
                }
            }';

            // $json = json_encode($data, JSON_PRETTY_PRINT);

            $url = "$dataUrl/Observation";
            $ch = curl_init($url);

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Authorization: Bearer ' . $dataAccess
            ));

            $response = curl_exec($ch);
            $dataCek = json_decode($response);
            $TokenContinue = array(
                'NoTrx' => uniqid(true).date("Y_m_d"),
                'access_token' => $dataAccess,
                'client_id' => $client_id,
                'expires_in' => $expires_in,
                'CreateDate' => date("Y-m-d H:i:s"),
                'Deskripsi' => json_encode($dataCek, TRUE),
                'Payload' => $data,
                'TokenAkses' => "Post Observation - Diastolik"
            );
    
            $this->db->insert('SatuSehat.Log_Token', $TokenContinue);
            // Check for cURL errors
            if (curl_errno($ch)) {

                $dataArr = array('Status' => 0, 'payload' => $data, 'access_token' => $dataAccess, 'httpError' => 400, $ch, "Posisi" => "4", "response" => json_decode($response));
                echo json_encode($dataArr);

            } else {
                // Check HTTP status code
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                if ($httpCode == 201 || $httpCode == 200) {
                    // Decode and handle the response
                    $dataArr = array();
                    
                    $TempId = array(
                        "NoTrx" => uniqid(true).date("_Y-m-d"),
                        "NoRegis" => $NoRegis,
                        "ID_SatuSehat" => $dataCek->id,
                        "Status" => "Observation - Diastolik",
                        "CreateDate" => date("Y-m-d H:i:s"),
                        "Token" => $dataAccess
                    );
                    $this->db->insert("EMR.SatuSehat.Temp_ID", $TempId);
                    $DataResp = array(
                        "ID_Nadi" => $DataID["ID_Nadi"],
                        "ID_Pernapasan" => $DataID["ID_Pernapasan"],
                        "ID_Sistolik" => $DataID["ID_Sistolik"],
                        "ID_Diastolik" => $dataCek->id,
                    );
                    $this->Case_5($DataDetail, $DataResp);
                    // $dataArr = array('Status' => 1, 'ValReturn' => $dataCek, 'access_token' => $dataAccess, 'payload' => json_decode($data), 'access_token' => $dataAccess, 'httpError' => $httpCode, "Posisi" => "2", "response" => json_decode($response));
                    // echo json_encode($dataArr);
                } else {
                    // Handle non-200 status code
                    $hasil = json_decode($data);
                    $dataArr = array('Status' => 0, 'payload' => $hasil, 'access_token' => $dataAccess, 'httpError' => $httpCode, "Posisi" => "4", "response" => json_decode($response));
                    echo json_encode($dataArr);
                }
            }

            curl_close($ch);
        }else{
            // $dataArr = array('Status' => 0, 'message' => "Data Sudah Ada", "ID"=> $qCekTempId->ID_SatuSehat, "Posisi" => "2");
            $DataResp = array(
                "ID_Nadi" => $DataID["ID_Nadi"],
                "ID_Pernapasan" => $DataID["ID_Pernapasan"],
                "ID_Sistolik" => $DataID["ID_Sistolik"],
                "ID_Diastolik" => $qCekTempId->ID_SatuSehat
            );
            $this->Case_5($DataDetail, $DataResp);
            
        }
    }
    function Case_5($DataDetail, $DataID)
    {
        $NoRegis = $DataDetail->DataPasien->NoRegistrasi;
        $Pasien = $DataDetail->DataPasien->Pasien;
        $Dokter = $DataDetail->DataPasien->Dokter;
        $ValueSuhu = $DataDetail->DataPasien->ValueSuhu;
        $TanggalEfektif = $DataDetail->DataPasien->TanggalEfektif;
        $TanggalIsue = $DataDetail->DataPasien->TanggalIsue;
        $ID_Encounter = $DataDetail->DataPasien->ID_Encounter;
        $NoOrganisasi = $DataDetail->NoOrganisasi;
        $dataAccess = $DataDetail->dataAccess;
        $dataUrl = $DataDetail->dataUrl;
        $client_id = $DataDetail->client_id;
        $expires_in = $DataDetail->expires_in;
        $qCekTempId = $this->db->query("SELECT * FROM EMR.SatuSehat.Temp_ID WHERE NoRegis = '$NoRegis' AND Status = 'Observation - Suhu' ")->row();

        if ($qCekTempId == null) {
            $data = '{
                "identifier":[
                    {
                        "system": "http://sys-ids.kemkes.go.id/observation/'.$NoOrganisasi.'",
                        "value": "'.$NoRegis.'"
                    }
                ],
                "resourceType": "Observation",
                "status": "final",
                "category": [
                    {
                        "coding": [
                            {
                                "system": "http://terminology.hl7.org/CodeSystem/observation-category",
                                "code": "vital-signs",
                                "display": "Vital Signs"
                            }
                        ]
                    }
                ],
                "code": {
                    "coding": [
                        {
                            "system": "http://loinc.org",
                            "code": "8310-5",
                            "display": "Body temperature"
                        }
                    ]
                },
                "subject": {
                    "reference": "Patient/'.$Pasien.'"
                },
                "performer": [
                    {
                        "reference": "Practitioner/'.$Dokter.'"
                    }
                ],
                "encounter": {
                    "reference": "Encounter/'.$ID_Encounter.'"
                },
                "effectiveDateTime": "'.$TanggalEfektif.'",
                "issued": "'.$TanggalIsue.'",
                "valueQuantity": {
                    "value": '.$ValueSuhu.',
                    "unit": "C",
                    "system": "http://unitsofmeasure.org",
                    "code": "Cel"
                }
            }';

            // $json = json_encode($data, JSON_PRETTY_PRINT);

            $url = "$dataUrl/Observation";
            $ch = curl_init($url);

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Authorization: Bearer ' . $dataAccess
            ));

            $response = curl_exec($ch);
            $dataCek = json_decode($response);
            $TokenContinue = array(
                'NoTrx' => uniqid(true).date("Y_m_d"),
                'access_token' => $dataAccess,
                'client_id' => $client_id,
                'expires_in' => $expires_in,
                'CreateDate' => date("Y-m-d H:i:s"),
                'Deskripsi' => json_encode($dataCek, TRUE),
                'Payload' => $data,
                'TokenAkses' => "Post Observation - Suhu"
            );
    
            $this->db->insert('SatuSehat.Log_Token', $TokenContinue);
            // Check for cURL errors
            if (curl_errno($ch)) {

                $dataArr = array('Status' => 0, 'payload' => $data, 'access_token' => $dataAccess, 'httpError' => 400, $ch, "Posisi" => "5", "response" => json_decode($response));
                echo json_encode($dataArr);

            } else {
                // Check HTTP status code
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                if ($httpCode == 201 || $httpCode == 200) {
                    // Decode and handle the response
                    $dataArr = array();
                    
                    $TempId = array(
                        "NoTrx" => uniqid(true).date("_Y-m-d"),
                        "NoRegis" => $NoRegis,
                        "ID_SatuSehat" => $dataCek->id,
                        "Status" => "Observation - Suhu",
                        "CreateDate" => date("Y-m-d H:i:s"),
                        "Token" => $dataAccess
                    );
                    $this->db->insert("EMR.SatuSehat.Temp_ID", $TempId);
                    $DataResp = array(
                        "ID_Nadi" => $DataID["ID_Nadi"],
                        "ID_Pernapasan" => $DataID["ID_Pernapasan"],
                        "ID_Sistolik" => $DataID["ID_Sistolik"],
                        "ID_Diastolik" => $DataID["ID_Diastolik"],
                        "ID_Suhu" => $dataCek->id,
                    );
                    $this->Case_6($DataDetail, $DataResp);
                    // $dataArr = array('Status' => 1, 'ValReturn' => $dataCek, 'access_token' => $dataAccess, 'payload' => json_decode($data), 'access_token' => $dataAccess, 'httpError' => $httpCode, "Posisi" => "2", "response" => json_decode($response));
                    // echo json_encode($dataArr);
                } else {
                    // Handle non-200 status code
                    $hasil = json_decode($data);
                    $dataArr = array('Status' => 0, 'payload' => $hasil, 'access_token' => $dataAccess, 'httpError' => $httpCode, "Posisi" => "5", "response" => json_decode($response));
                    echo json_encode($dataArr);
                }
            }

            curl_close($ch);
        }else{
            // $dataArr = array('Status' => 0, 'message' => "Data Sudah Ada", "ID"=> $qCekTempId->ID_SatuSehat, "Posisi" => "2");
            $DataResp = array(
                "ID_Nadi" => $DataID["ID_Nadi"],
                "ID_Pernapasan" => $DataID["ID_Pernapasan"],
                "ID_Sistolik" => $DataID["ID_Sistolik"],
                "ID_Diastolik" => $DataID["ID_Diastolik"],
                "ID_Suhu" => $qCekTempId->ID_SatuSehat
            );
            $this->Case_6($DataDetail, $DataResp);
            
        }
    }

    function Case_6($DataDetail, $DataID)
    {
        $NoRegis = $DataDetail->DataPasien->NoRegistrasi;
        $Pasien = $DataDetail->DataPasien->Pasien;
        $Dokter = $DataDetail->DataPasien->Dokter;
        $ID_Encounter = $DataDetail->DataPasien->ID_Encounter;
        $CodingProcedure = $DataDetail->DataPasien->CodingProcedure;
        $TextCounterProcedure = $DataDetail->DataPasien->TextCounterProcedure;
        $CodingIcd10 = $DataDetail->DataPasien->CodingIcd10;
        $CodeSnomed = $DataDetail->DataPasien->CodeSnomed;
        $NoteTindakan = $DataDetail->DataPasien->NoteTindakan;
        $TanggalMulaiProcedure = $DataDetail->DataPasien->TanggalMulaiProcedure;
        $TanggalSelesaiProcedure = $DataDetail->DataPasien->TanggalSelesaiProcedure;
        $NoOrganisasi = $DataDetail->NoOrganisasi;
        $dataAccess = $DataDetail->dataAccess;
        $dataUrl = $DataDetail->dataUrl;
        $client_id = $DataDetail->client_id;
        $expires_in = $DataDetail->expires_in;
        if ($CodingProcedure == null) {
            $DataResp = array(
                "ID_Nadi" => $DataID["ID_Nadi"],
                "ID_Pernapasan" => $DataID["ID_Pernapasan"],
                "ID_Sistolik" => $DataID["ID_Sistolik"],
                "ID_Diastolik" => $DataID["ID_Diastolik"],
                "ID_Suhu" => $DataID["ID_Suhu"],
                "ID_Procedure" => "Tidak Ada"
            );
            $this->Case_7($DataDetail, $DataResp);
        }else{
            $qCekTempId = $this->db->query("SELECT * FROM EMR.SatuSehat.Temp_ID WHERE NoRegis = '$NoRegis' AND Status = 'Procedure - Case2' ")->row();
            if ($qCekTempId == null) {
                $data = '{
                    "identifier":[
                        {
                            "system": "http://sys-ids.kemkes.go.id/procedure/'.$NoOrganisasi.'",
                            "value": "'.$NoRegis.'"
                        }
                    ],
                    "resourceType": "Procedure",
                    "status": "completed",
                    "category": {
                        "coding": [
                            {
                                "system": "http://snomed.info/sct",
                                "code": "103693007",
                                "display": "Diagnostic procedure"
                            }
                        ],
                        "text": "Diagnostic procedure"
                    },
                    "code": {
                        "coding": [
                            {
                                "system": "http://hl7.org/fhir/sid/icd-9-cm",
                                "code": "'.$CodingProcedure.'"
                            }
                        ]
                    },
                    "subject": {
                        "reference": "Patient/'.$Pasien.'"
                    },
                    "encounter": {
                        "reference": "Encounter/'.$ID_Encounter.'",
                        "display": "'.$TextCounterProcedure.'"
                    },
                    "performedPeriod": {
                        "start": "'.$TanggalMulaiProcedure.'",
                        "end": "'.$TanggalSelesaiProcedure.'"
                    },
                    "performer": [
                        {
                            "actor": {
                                "reference": "Practitioner/'.$Dokter.'"
                            }
                        }
                    ],
                    "reasonCode": [
                        {
                            "coding": [
                                {
                                    "system": "http://hl7.org/fhir/sid/icd-10",
                                    "code": "'.$CodingIcd10.'"
                                }
                            ]
                        }
                    ],
                    "bodySite": [
                        {
                            "coding": [
                                {
                                    "system": "http://snomed.info/sct",
                                    "code": "'.$CodeSnomed.'"
                                }
                            ]
                        }
                    ],
                    "note": [
                        {
                            "text": "'.$NoteTindakan.'"
                        }
                    ]
                }';
    
                // $json = json_encode($data, JSON_PRETTY_PRINT);
    
                $url = "$dataUrl/Procedure";
                $ch = curl_init($url);
    
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $dataAccess
                ));
    
                $response = curl_exec($ch);
                $dataCek = json_decode($response);
                $TokenContinue = array(
                    'NoTrx' => uniqid(true).date("Y_m_d"),
                    'access_token' => $dataAccess,
                    'client_id' => $client_id,
                    'expires_in' => $expires_in,
                    'CreateDate' => date("Y-m-d H:i:s"),
                    'Deskripsi' => json_encode($dataCek, TRUE),
                    'Payload' => $data,
                    'TokenAkses' => "Post Procedure - Case2"
                );
        
                $this->db->insert('SatuSehat.Log_Token', $TokenContinue);
                // Check for cURL errors
                if (curl_errno($ch)) {
    
                    $dataArr = array('Status' => 0, 'payload' => $data, 'access_token' => $dataAccess, 'httpError' => 400, $ch, "Posisi" => "5", "response" => json_decode($response));
                    echo json_encode($dataArr);
    
                } else {
                    // Check HTTP status code
                    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    if ($httpCode == 201 || $httpCode == 200) {
                        // Decode and handle the response
                        $dataArr = array();
                        
                        $TempId = array(
                            "NoTrx" => uniqid(true).date("_Y-m-d"),
                            "NoRegis" => $NoRegis,
                            "ID_SatuSehat" => $dataCek->id,
                            "Status" => "Procedure - Case2",
                            "CreateDate" => date("Y-m-d H:i:s"),
                            "Token" => $dataAccess
                        );
                        $this->db->insert("EMR.SatuSehat.Temp_ID", $TempId);
                        $DataResp = array(
                            "ID_Nadi" => $DataID["ID_Nadi"],
                            "ID_Pernapasan" => $DataID["ID_Pernapasan"],
                            "ID_Sistolik" => $DataID["ID_Sistolik"],
                            "ID_Diastolik" => $DataID["ID_Diastolik"],
                            "ID_Suhu" => $DataID["ID_Suhu"],
                            "ID_Procedure" => $dataCek->id,
                        );
                        $this->Case_7($DataDetail, $DataResp);
                        // $dataArr = array('Status' => 1, 'ValReturn' => $dataCek, 'access_token' => $dataAccess, 'payload' => json_decode($data), 'access_token' => $dataAccess, 'httpError' => $httpCode, "Posisi" => "2", "response" => json_decode($response));
                        // echo json_encode($dataArr);
                    } else {
                        // Handle non-200 status code
                        $hasil = json_decode($data);
                        $dataArr = array('Status' => 0, 'payload' => $hasil, 'access_token' => $dataAccess, 'httpError' => $httpCode, "Posisi" => "5", "response" => json_decode($response));
                        echo json_encode($dataArr);
                    }
                }
    
                curl_close($ch);
            }else{
                // $dataArr = array('Status' => 0, 'message' => "Data Sudah Ada", "ID"=> $qCekTempId->ID_SatuSehat, "Posisi" => "2");
                $DataResp = array(
                    "ID_Nadi" => $DataID["ID_Nadi"],
                    "ID_Pernapasan" => $DataID["ID_Pernapasan"],
                    "ID_Sistolik" => $DataID["ID_Sistolik"],
                    "ID_Diastolik" => $DataID["ID_Diastolik"],
                    "ID_Suhu" => $DataID["ID_Suhu"],
                    "ID_Procedure" => $qCekTempId->ID_SatuSehat
                );
                $this->Case_7($DataDetail, $DataResp);
                
            }
        }
        
    }

    function Case_7($DataDetail, $DataID)
    {
        $NoRegis = $DataDetail->DataPasien->NoRegistrasi;
        $Pasien = $DataDetail->DataPasien->Pasien;
        $Dokter = $DataDetail->DataPasien->Dokter;
        $ID_Encounter = $DataDetail->DataPasien->ID_Encounter;
        $LOINC_Code = $DataDetail->DataPasien->LOINC_Code;
        $TextResume = $DataDetail->DataPasien->TextResume;
        $TanggalComposite = $DataDetail->DataPasien->TanggalComposite;
        $NoOrganisasi = $DataDetail->NoOrganisasi;
        $dataAccess = $DataDetail->dataAccess;
        $dataUrl = $DataDetail->dataUrl;
        $client_id = $DataDetail->client_id;
        $expires_in = $DataDetail->expires_in;

        if ($LOINC_Code == null) {
            $dataArr = array('Status' => 0, 'message' => "Data Sudah Ada", "Posisi" => "5");
            $DataResp = array(
                "Hasil" => $dataArr,
                "ID_Nadi" => $DataID["ID_Nadi"],
                "ID_Pernapasan" => $DataID["ID_Pernapasan"],
                "ID_Sistolik" => $DataID["ID_Sistolik"],
                "ID_Diastolik" => $DataID["ID_Diastolik"],
                "ID_Suhu" => $DataID["ID_Suhu"],
                "ID_Procedure" => ($DataID["ID_Procedure"] == null) ? 'Tidak Ada' : $DataID["ID_Procedure"],
                "ID_Composite" => "Tidak Ada",
            );
            echo json_encode($DataResp);
            $TokenContinue = array(
                'NoTrx' => uniqid(true).date("Y_m_d"),
                'access_token' => $dataAccess,
                'client_id' => $client_id,
                'expires_in' => $expires_in,
                'CreateDate' => date("Y-m-d H:i:s"),
                'Deskripsi' => json_encode($DataResp, TRUE),
                'Payload' => "Sukses Send",
                'TokenAkses' => "Post Composite - Case2"
            );
    
            $this->db->insert('SatuSehat.Log_Token', $TokenContinue);
        } else {
            $qCekTempId = $this->db->query("SELECT * FROM EMR.SatuSehat.Temp_ID WHERE NoRegis = '$NoRegis' AND Status = 'Composite - Case2' ")->row();
            if ($qCekTempId == null) {
                $data = '{
                    "identifier":[
                        {
                            "system": "http://sys-ids.kemkes.go.id/composition/'.$NoOrganisasi.'",
                            "value": "'.$NoRegis.'"
                        }
                    ],
                    "resourceType": "Composition",
                    "status": "final",
                    "type": {
                        "coding": [
                            {
                                "system": "http://loinc.org",
                                "code": "18842-5",
                                "display": "Discharge summary"
                            }
                        ]
                    },
                    "category": [
                        {
                            "coding": [
                                {
                                    "system": "http://loinc.org",
                                    "code": "LP173421-1",
                                    "display": "Report"
                                }
                            ]
                        }
                    ],
                    "subject": {
                        "reference": "Patient/'.$Pasien.'"
                    },
                    "encounter": {
                        "reference": "Encounter/'.$ID_Encounter.'"
                    },
                    "date": "'.$TanggalComposite.'",
                    "author": [
                        {
                            "reference": "Practitioner/'.$Dokter.'"
                        }
                    ],
                    "title": "Resume Medis Rawat Jalan",
                    "custodian": {
                        "reference": "Organization/'.$NoOrganisasi.'"
                    },
                    "section": [
                        {
                            "code": {
                                "coding": [
                                    {
                                        "system": "http://loinc.org",
                                        "code": "'.$LOINC_Code.'"
                                    }
                                ]
                            },
                            "text": {
                                "status": "additional",
                                "div": "'.$TextResume.'"
                            }
                        }
                    ]
                }';
    
                // $json = json_encode($data, JSON_PRETTY_PRINT);
    
                $url = "$dataUrl/Composition";
                $ch = curl_init($url);
    
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $dataAccess
                ));
    
                $response = curl_exec($ch);
                $dataCek = json_decode($response);
                $TokenContinue = array(
                    'NoTrx' => uniqid(true).date("Y_m_d"),
                    'access_token' => $dataAccess,
                    'client_id' => $client_id,
                    'expires_in' => $expires_in,
                    'CreateDate' => date("Y-m-d H:i:s"),
                    'Deskripsi' => json_encode($dataCek, TRUE),
                    'Payload' => $data,
                    'TokenAkses' => "Post Composite - Case2"
                );
        
                $this->db->insert('SatuSehat.Log_Token', $TokenContinue);
                // Check for cURL errors
                if (curl_errno($ch)) {
    
                    $dataArr = array('Status' => 0, 'payload' => $data, 'access_token' => $dataAccess, 'httpError' => 400, $ch, "Posisi" => "5", "response" => json_decode($response));
                    echo json_encode($dataArr);
    
                } else {
                    // Check HTTP status code
                    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    if ($httpCode == 201 || $httpCode == 200) {
                        // Decode and handle the response
                        $dataArr = array();
                        
                        $TempId = array(
                            "NoTrx" => uniqid(true).date("_Y-m-d"),
                            "NoRegis" => $NoRegis,
                            "ID_SatuSehat" => $dataCek->id,
                            "Status" => "Composite - Case2",
                            "CreateDate" => date("Y-m-d H:i:s"),
                            "Token" => $dataAccess
                        );
                        $this->db->insert("EMR.SatuSehat.Temp_ID", $TempId);
                        $DataResp = array(
                            "ID_Nadi" => $DataID["ID_Nadi"],
                            "ID_Pernapasan" => $DataID["ID_Pernapasan"],
                            "ID_Sistolik" => $DataID["ID_Sistolik"],
                            "ID_Diastolik" => $DataID["ID_Diastolik"],
                            "ID_Suhu" => $DataID["ID_Suhu"],
                            "ID_Procedure" => $DataID["ID_Procedure"],
                            "ID_Composite" => $dataCek->id,
                        );
                        // $dataArr = array('Status' => 1, 'ValReturn' => $dataCek, 'access_token' => $dataAccess, 'payload' => json_decode($data), 'access_token' => $dataAccess, 'httpError' => $httpCode, "Posisi" => "2", "response" => json_decode($response));
                        // echo json_encode($dataArr);
                        echo json_encode($DataResp);            
                    } else {
                        // Handle non-200 status code
                        $hasil = json_decode($data);
                        $dataArr = array('Status' => 0, 'payload' => $hasil, 'access_token' => $dataAccess, 'httpError' => $httpCode, "Posisi" => "5", "response" => json_decode($response));
                        echo json_encode($dataArr);
                    }
                }
    
                curl_close($ch);
            }else{
                $dataArr = array(
                    "ID_Nadi" => $DataID["ID_Nadi"],
                    "ID_Pernapasan" => $DataID["ID_Pernapasan"],
                    "ID_Sistolik" => $DataID["ID_Sistolik"],
                    "ID_Diastolik" => $DataID["ID_Diastolik"],
                    "ID_Suhu" => $DataID["ID_Suhu"],
                    "ID_Composite" => $qCekTempId->ID_SatuSehat
                );
                $DataResp = array(
                    'Status' => 0, 
                    'message' => "Data Sudah Ada", 
                    "Posisi" => "5",
                    "Hasil" => $dataArr
                );
                echo json_encode($DataResp);
                $TokenContinue = array(
                    'NoTrx' => uniqid(true).date("Y_m_d"),
                    'access_token' => $dataAccess,
                    'client_id' => $client_id,
                    'expires_in' => $expires_in,
                    'CreateDate' => date("Y-m-d H:i:s"),
                    'Deskripsi' => json_encode($DataResp, TRUE),
                    'Payload' => "Sukses Send",
                    'TokenAkses' => "Post Composite - Case2"
                );
        
                $this->db->insert('SatuSehat.Log_Token', $TokenContinue);
                
            }
        }
    }
}
