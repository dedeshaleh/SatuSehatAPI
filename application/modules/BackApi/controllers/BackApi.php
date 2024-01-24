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
        
            // Check the status in the response
            if ($httpCode == 200) {
                $dataToken = array(
                    'NoTrx' => uniqid(true).date("Y_m_d"),
                    'access_token' => $dataCek->access_token,
                    'client_id' => $dataCek->client_id,
                    'expires_in' => $dataCek->expires_in,
                    'CreateDate' => date("Y-m-d H:i:s"),
                    'TokenAkses' => $AksesToken
                );
        
                $this->db->insert('SatuSehat.Log_Token', $dataToken);
                // Build the response in the desired format
                $dataArr = array('ValReturn' => $dataCek, 'base_url' => $q->base_url, 'NoOrganisasi' => $q->NoOrganisasi, "DataToken" => $dataToken);
                // echo json_encode($dataArr);
                return $dataArr;
               
            } else {
                // echo $response;
                echo "HTTP Error: $httpCode\n";
                echo "Response: $response\n";
                return $response;
            }
        }
        
        // Close cURL session
        curl_close($ch);
    }

    public function GetPasienByNIk() 
    {
        date_default_timezone_set("Asia/Jakarta");

        $json = file_get_contents("php://input"); // json string
        $object = json_decode($json); // php object

        $NIK = $object->NIK;
        $Token = $object->Token;

        $q2 = $this->db->query("SELECT * FROM EMR.SatuSehat.Log_Token WHERE access_token = '$Token' ")->row();

        $GetDate = $q2->CreateDate;
        $NewDate = date("Y-m-d H:i:s");

        $startTimeStamp = strtotime($GetDate);
        $currentTimeStamp = strtotime($NewDate);
        $timeDifferenceInSeconds = $currentTimeStamp - $startTimeStamp;

        if ($timeDifferenceInSeconds > $q2->expires_in ) {
            $data = array(
                "status" => 400,
                "message" => "Credentials Failed"
            );
            echo json_encode($data);
            die;
        }

        $dataToken = $this->GetToken("Get Pasien By NIK");

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
                $dataArr = array('Nik' => $NIK, 'url'=> $url, 'ValReturn' => $dataCek, 'access_token' => $dataAccess);
                $IdCek = $dataCek->entry[0]->resource;
                $NamaPasien = $dataCek->entry[0]->resource->name[0]->text;
                $this->db->query("UPDATE DB_Master_Fix.dbo.Pasien SET ID_Satu_Sehat = '$IdCek->id', Nama_Pasien_SatuSehat = '$NamaPasien' WHERE ID_No = '$NIK'");
                echo json_encode($dataArr);
            } else {
                // Handle non-200 status code
                echo "HTTP Error: $httpCode\n";
                echo "Response: $response\n";
            }
        }

        curl_close($ch);

    }

    public function GetPasienByName() 
    {
        date_default_timezone_set("Asia/Jakarta");
        $json = file_get_contents("php://input"); // json string
        $object = json_decode($json); // php object

        $name = $object->name;
        $dob = $object->dob;
        $gender = $object->gender;
        $Token = $object->Token;

        $q2 = $this->db->query("SELECT * FROM EMR.SatuSehat.Log_Token WHERE access_token = '$Token' ")->row();

        $GetDate = $q2->CreateDate;
        $NewDate = date("Y-m-d H:i:s");

        $startTimeStamp = strtotime($GetDate);
        $currentTimeStamp = strtotime($NewDate);
        $timeDifferenceInSeconds = $currentTimeStamp - $startTimeStamp;

        if ($timeDifferenceInSeconds > $q2->expires_in ) {
            $data = array(
                "status" => 400,
                "message" => "Credentials Failed"
            );
            echo json_encode($data);
            die;
        }


        $dataToken = $this->GetToken("Get Pasien By Name");

        $data = array(
            "Param" => null,
        );

        $json = json_encode($data, JSON_PRETTY_PRINT);
        $dataUrl = $dataToken['base_url'];
        $dataAccess = $dataToken['ValReturn']->access_token;

        $name = urlencode($name);

        $url = "$dataUrl/Patient?name=$name&birthdate=$dob&gender=$gender";

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

    public function GetDokterNIK() 
    {
        date_default_timezone_set("Asia/Jakarta");

        $json = file_get_contents("php://input"); // json string
        $object = json_decode($json); // php object
        $NIK = $object->NIK;
        $Token = $object->Token;

        $q2 = $this->db->query("SELECT * FROM EMR.SatuSehat.Log_Token WHERE access_token = '$Token' ")->row();

        $GetDate = $q2->CreateDate;
        $NewDate = date("Y-m-d H:i:s");

        $startTimeStamp = strtotime($GetDate);
        $currentTimeStamp = strtotime($NewDate);
        $timeDifferenceInSeconds = $currentTimeStamp - $startTimeStamp;

        if ($timeDifferenceInSeconds > $q2->expires_in ) {
            $data = array(
                "status" => 400,
                "message" => "Credentials Failed"
            );
            echo json_encode($data);
            die;
        }

        $dataToken = $this->GetToken("Get Dokter BY NIK");

        $data = array(
            "Param" => null,
        );

        $json = json_encode($data, JSON_PRETTY_PRINT);
        $dataUrl = $dataToken['base_url'];
        $dataAccess = $dataToken['ValReturn']->access_token;

        $url = "$dataUrl/Practitioner?identifier=https://fhir.kemkes.go.id/id/nik|$NIK";
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
                // $IdDokter = $dataCek['entry'];
                $IdCek = $dataCek->entry[0]->resource;
                $NamaDokter = $dataCek->entry[0]->resource->name[0]->text;
                $this->db->query("UPDATE DB_Master_Fix.dbo.Dokter SET ID_Satu_Sehat = '$IdCek->id', Nama_Dr_SatuSehat = '$NamaDokter' WHERE No_KTP = '$NIK'");
                echo json_encode($dataArr);
            } else {
                // Handle non-200 status code
                echo "HTTP Error: $httpCode\n";
                echo "Response: $response\n";
            }
        }

        curl_close($ch);

    }
    
    public function GetDokterByName() 
    {
        date_default_timezone_set("Asia/Jakarta");

        $json = file_get_contents("php://input"); // json string
        $object = json_decode($json); // php object

        $name = $object->name;
        $dob = $object->dob;
        $gender = $object->gender;
        $Token = $object->Token;

        $q2 = $this->db->query("SELECT * FROM EMR.SatuSehat.Log_Token WHERE access_token = '$Token' ")->row();

        $GetDate = $q2->CreateDate;
        $NewDate = date("Y-m-d H:i:s");

        $startTimeStamp = strtotime($GetDate);
        $currentTimeStamp = strtotime($NewDate);
        $timeDifferenceInSeconds = $currentTimeStamp - $startTimeStamp;

        if ($timeDifferenceInSeconds > $q2->expires_in ) {
            $data = array(
                "status" => 400,
                "message" => "Credentials Failed"
            );
            echo json_encode($data);
            die;
        }
        $dataToken = $this->GetToken("GET Dokter By Name");

        $data = array(
            "Param" => null,
        );

        $json = json_encode($data, JSON_PRETTY_PRINT);
        $dataUrl = $dataToken['base_url'];
        $dataAccess = $dataToken['ValReturn']->access_token;
        $name = urlencode($name);

        $url = "$dataUrl/Practitioner?name=$name&birthdate=$dob&gender=$gender";
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

    public function GetOrganization() 
    {
        date_default_timezone_set("Asia/Jakarta");
        $json = file_get_contents("php://input"); // json string
        $object = json_decode($json); // php object

        $Kode = $object->Kode;
        $Token = $object->Token;

        $q2 = $this->db->query("SELECT * FROM EMR.SatuSehat.Log_Token WHERE access_token = '$Token' ")->row();

        $GetDate = $q2->CreateDate;
        $NewDate = date("Y-m-d H:i:s");

        $startTimeStamp = strtotime($GetDate);
        $currentTimeStamp = strtotime($NewDate);
        $timeDifferenceInSeconds = $currentTimeStamp - $startTimeStamp;

        if ($timeDifferenceInSeconds > $q2->expires_in ) {
            $data = array(
                "status" => 400,
                "message" => "Credentials Failed"
            );
            echo json_encode($data);
            die;
        }

        $dataToken = $this->GetToken("GET Organization");

        $data = array(
            "Param" => null,
        );

        $json = json_encode($data, JSON_PRETTY_PRINT);
        $dataUrl = $dataToken['base_url'];
        $dataAccess = $dataToken['ValReturn']->access_token;

        $url = "$dataUrl/Organization/$Kode";
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

    public function GetLokasi() 
    {
        date_default_timezone_set("Asia/Jakarta");

        $json = file_get_contents("php://input"); // json string
        $object = json_decode($json); // php object

        $OrgID = $object->Kode;
        $KodeRuangan = $object->KodeRuangan;
        $Token = $object->Token;

        $q2 = $this->db->query("SELECT * FROM EMR.SatuSehat.Log_Token WHERE access_token = '$Token' ")->row();

        $GetDate = $q2->CreateDate;
        $NewDate = date("Y-m-d H:i:s");

        $startTimeStamp = strtotime($GetDate);
        $currentTimeStamp = strtotime($NewDate);
        $timeDifferenceInSeconds = $currentTimeStamp - $startTimeStamp;

        if ($timeDifferenceInSeconds > $q2->expires_in ) {
            $data = array(
                "status" => 400,
                "message" => "Credentials Failed"
            );
            echo json_encode($data);
            die;
        }

        $dataToken = $this->GetToken("GET Lokasi");

        $data = array(
            "Param" => null,
        );

        $json = json_encode($data, JSON_PRETTY_PRINT);
        $dataUrl = $dataToken['base_url'];
        $dataAccess = $dataToken['ValReturn']->access_token;

        $url = "$dataUrl/Location?identifier=http://sys-ids.kemkes.go.id/location/$OrgID|$KodeRuangan";
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

    public function POSTLokasi() 
    {
        date_default_timezone_set("Asia/Jakarta");

        $json = file_get_contents("php://input"); // json string
        $object = json_decode($json); // php object

        $KodePoli = $object->KodePoli;
        $Token = $object->Token;

        $q2 = $this->db->query("SELECT * FROM EMR.SatuSehat.Log_Token WHERE access_token = '$Token' ")->row();

        $GetDate = $q2->CreateDate;
        $NewDate = date("Y-m-d H:i:s");

        $startTimeStamp = strtotime($GetDate);
        $currentTimeStamp = strtotime($NewDate);
        $timeDifferenceInSeconds = $currentTimeStamp - $startTimeStamp;

        if ($timeDifferenceInSeconds > $q2->expires_in ) {
            $data = array(
                "status" => 400,
                "message" => "Credentials Failed"
            );
            echo json_encode($data);
            die;
        }


        $dataToken = $this->GetToken("Post Lokasi");
        $q = $this->db->query("	SELECT * FROM DB_Master_Fix.dbo.Line_of_service WHERE Poly_Type = '$KodePoli'")->row();
        $KodeRuangan = TRIM($q->Poly_Type);
        $NamaRuangan = TRIM($q->Description);
        $DeskripsiRuangan = TRIM($q->Description);
        $NoOrganisasi = $dataToken['NoOrganisasi'];

        $data = '{
            "resourceType": "Location",
            "identifier": [
                {
                    "system": "http://sys-ids.kemkes.go.id/location/'.$NoOrganisasi.'",
                    "value": "'.$KodeRuangan.'"
                }
            ],
            "status": "active",
            "name": "'.$NamaRuangan.'",
            "description": "'.$DeskripsiRuangan.'",
            "mode": "instance",
            "telecom": [
                {
                    "system": "phone",
                    "value": "(021) 29309999",
                    "use": "work"
                },
                {
                    "system": "fax",
                    "value": "(021) 29309999",
                    "use": "work"
                },
                {
                    "system": "email",
                    "value": "satusehat@bethsaidahospitals.com"
                },
                {
                    "system": "url",
                    "value": "https://www.bethsaidahospitals.com/",
                    "use": "work"
                }
            ],
            "address": {
                "use": "work",
                "line": [
                    "Jalan Boulevard Raya Gading Serpong Kav. 29 Gading Serpong, Curug Sangereng, Kec. Klp. Dua, Kabupaten Tangerang, Banten"
                ],
                "city": "Tangerang",
                "postalCode": "15810",
                "country": "ID",
                "extension": [
                    {
                        "url": "https://fhir.kemkes.go.id/r4/StructureDefinition/administrativeCode",
                        "extension": [
                            {
                                "url": "province",
                                "valueCode": "36"
                            },
                            {
                                "url": "city",
                                "valueCode": "3603"
                            },
                            {
                                "url": "district",
                                "valueCode": "360317"
                            },
                            {
                                "url": "village",
                                "valueCode": "3603172002"
                            },
                            {
                                "url": "rt",
                                "valueCode": "00"
                            },
                            {
                                "url": "rw",
                                "valueCode": "00"
                            }
                        ]
                    }
                ]
            },
            "physicalType": {
                "coding": [
                    {
                        "system": "http://terminology.hl7.org/CodeSystem/location-physical-type",
                        "code": "ro",
                        "display": "Room"
                    }
                ]
            },
            "position": {
                "longitude": -6.254739179934109,
                "latitude": 106.62263995968453,
                "altitude": 0
            },
            "managingOrganization": {
                "reference": "Organization/'.$NoOrganisasi.'"
            }
        }';

        // $json = json_encode($data, JSON_PRETTY_PRINT);
        $dataUrl = $dataToken['base_url'];
        $dataAccess = $dataToken['ValReturn']->access_token;

        $url = "$dataUrl/Location";
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
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
            if ($httpCode == 201 || $httpCode == 200) {
                // Decode and handle the response
                $dataCek = json_decode($response);
                $dataArr = array('ValReturn' => $dataCek, 'access_token' => $dataAccess, 'Id' => $dataCek->id);
                $this->db->query("UPDATE DB_Master_Fix.dbo.Line_of_service SET ID_Satu_sehat = '$dataCek->id' WHERE Poly_Type = '$KodeRuangan'");
                $this->db->query("UPDATE EMR.SatuSehat.Log_Token SET Payload = '$data', Deskripsi = '$response' WHERE access_token = '$dataAccess'");
                echo json_encode($dataArr);
            } else {
                $cek = $this->GetLokasiUpdate($NoOrganisasi, $KodeRuangan);

                if ($cek == 0) {
                    echo "HTTP Error: $httpCode\n";
                    echo "Response: $cek->Response\n";
                    echo "Response: $response\n";
                }else{
                    echo json_encode($cek);
                    $Resp = json_encode($cek['Response']);
                    $idSatu = $cek['id'];
                    // echo $Resp;
                    $this->db->query("UPDATE DB_Master_Fix.dbo.Line_of_service SET ID_Satu_sehat = '$idSatu' WHERE Poly_Type = '$KodeRuangan'");
                    $this->db->query("UPDATE EMR.SatuSehat.Log_Token SET Payload = '$data', Deskripsi = '$Resp' WHERE access_token = '$dataAccess'");
                }
                // Handle non-200 status code
                // echo "HTTP Error: $httpCode\n";
                // echo "Response: $response\n";
            }
        }

        curl_close($ch);

    }

    public function GetLokasiUpdate($OrgID, $KodeRuangan) 
    {
        date_default_timezone_set("Asia/Jakarta");
        $dataToken = $this->GetToken("GET Lokasi");

        $data = array(
            "Param" => null,
        );

        $json = json_encode($data, JSON_PRETTY_PRINT);
        $dataUrl = $dataToken['base_url'];
        $dataAccess = $dataToken['ValReturn']->access_token;

        $url = "$dataUrl/Location?identifier=http://sys-ids.kemkes.go.id/location/$OrgID|$KodeRuangan";
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
            $dataCek = json_decode($response, true);
            if ($httpCode == 200) {
            // Decode and handle the response
            $dataArr = array('status' => 1, 'Response' => $dataCek, 'access_token' => $dataAccess, 'id' => $dataCek['entry'][0]['resource']['id']);
            // echo json_encode($dataArr);
            return $dataArr;
            } else {
                // Handle non-200 status code
                $dataArr = array('status' => 0, 'Response' => $response, 'access_token' => $dataAccess, 'id' => $dataCek['entry'][0]['resource']['id']);
                return $dataArr;
                // echo "HTTP Error: $httpCode\n";
                // echo "Response: $response\n";
            }
        }

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
        $object = $this->db->query("SELECT * FROM EMR.SatuSehat.vw_SatuSehat WHERE NoRegistrasi = '$NoRegis'")->row();
            $NoRegistrasi = $object->NoRegistrasi;
            $NikPasien = $object->NikPasien;
            $NamaPasien = $object->NamaPasien;
            $NikDokter = $object->NikDokter;
            $NamaDokter = $object->NamaDokter;
            $IdLocation = $object->IdLocation;
            $NamaLokasi = $object->NamaLokasi;
            $DateAwal = $object->DateAwal;
            $DateAkhir = $object->DateAkhir;

        $GetDate = $q2->CreateDate;
        $NewDate = date("Y-m-d H:i:s");

        $startTimeStamp = strtotime($GetDate);
        $currentTimeStamp = strtotime($NewDate);
        $timeDifferenceInSeconds = $currentTimeStamp - $startTimeStamp;

        if ($timeDifferenceInSeconds > $q2->expires_in ) {
            $data = array(
                "status" => 400,
                "message" => "Credentials Failed"
            );
            echo json_encode($data);
            die;
        }

        $qCekTempId = $this->db->query("SELECT * FROM EMR.SatuSehat.Temp_ID WHERE NoRegis = '$NoRegis' AND Status = 'Encounter - Awal' ")->row();

        if ($qCekTempId == null) {
            $dataToken = $this->GetToken("Post Kunjungan Awal");
            $NoOrganisasi = $dataToken['NoOrganisasi'];
            

            $data = '{
                "resourceType": "Encounter",
                "identifier": [
                    {
                        "system": "http://sys-ids.kemkes.go.id/encounter/'.$NoOrganisasi.'",
                        "value": "'.$NoRegistrasi.'"
                    }
                ],
                "status": "arrived",
                "class": {
                    "system": "http://terminology.hl7.org/CodeSystem/v3-ActCode",
                    "code": "AMB",
                    "display": "ambulatory"
                },
                "subject": {
                    "reference": "Patient/'.$NikPasien.'",
                    "display": "'.$NamaPasien.'"
                },
                "participant": [
                    {
                        "type": [
                            {
                                "coding": [
                                    {
                                        "system": "http://terminology.hl7.org/CodeSystem/v3-ParticipationType",
                                        "code": "ATND",
                                        "display": "attender"
                                    }
                                ]
                            }
                        ],
                        "individual": {
                            "reference": "Practitioner/'.$NikDokter.'",
                            "display": "'.$NamaDokter.'"
                        }
                    }
                ],
                "period": {
                    "start": "'.$DateAwal.'"
                },
                "location": [
                    {
                        "location": {
                            "reference": "Location/'.$IdLocation.'",
                            "display": "'.$NamaLokasi.'"
                        }
                    }
                ],
                "statusHistory": [
                    {
                        "status": "arrived",
                        "period": {
                            "start": "'.$DateAwal.'",
                            "end": "'.$DateAkhir.'"
                        }
                    }
                ],
                "serviceProvider": {
                    "reference": "Organization/'.$NoOrganisasi.'"
                }
            }';

            
            // $json = json_encode($data, JSON_PRETTY_PRINT);
            $dataUrl = $dataToken['base_url'];
            $dataAccess = $dataToken['ValReturn']->access_token;

            $url = "$dataUrl/Encounter";
            $ch = curl_init($url);

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
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
                if ($httpCode == 201 || $httpCode == 200) {
                    // Decode and handle the response
                    $dataCek = json_decode($response);
                    $dataArr = array('ValReturn' => $dataCek, 'access_token' => $dataAccess);
                    $TokenContinue = array(
                        'NoTrx' => uniqid(true).date("Y_m_d"),
                        'access_token' => $dataAccess,
                        'client_id' => $dataToken["DataToken"]['client_id'],
                        'expires_in' => $dataToken["DataToken"]['expires_in'],
                        'CreateDate' => date("Y-m-d H:i:s"),
                        'Deskripsi' => $response,
                        'Payload' => $data,
                        'TokenAkses' => "Post Kunjungan Awal"
                    );
            
                    $this->db->insert('SatuSehat.Log_Token', $TokenContinue);
                    $TempId = array(
                        "NoTrx" => uniqid(true).date("_Y-m-d"),
                        "NoRegis" => $NoRegistrasi,
                        "ID_SatuSehat" => $dataCek->id,
                        "Status" => "Encounter - Awal",
                        "CreateDate" => date("Y-m-d H:i:s"),
                        "Token" => $dataAccess
                    );
                    $this->db->insert("EMR.SatuSehat.Temp_ID", $TempId);
                    $this->EncounterUpdateInprogres($object, $dataCek->id);
                    echo json_encode($dataArr);
                } else {
                    // Handle non-200 status code
                    $dataArr = array('Status' => 0, 'payload' => $data, 'access_token' => $dataAccess, 'httpError' => $httpCode, "Posisi" => "1");
                    echo json_encode($dataArr);
                    // echo "HTTP Error: Post Kunjungan Awal $httpCode\n";
                    // echo "Response: $response\n";
                    // echo "Payload: $data\n";
                    die();
                }
            }

            curl_close($ch);
        }else{
            $this->EncounterUpdateInprogres($object, $qCekTempId->ID_SatuSehat);
            
        }

        

    }

    public function EncounterUpdateInprogres($object, $IdEncounter) 
    {
        date_default_timezone_set("Asia/Jakarta");
        $NoRegistrasi = $object->NoRegistrasi;
        $qCekTempId = $this->db->query("SELECT * FROM EMR.SatuSehat.Temp_ID WHERE NoRegis = '$NoRegistrasi' AND Status = 'Encounter - InProgress' ")->row();

        if ($qCekTempId == null) {
            $dataToken = $this->GetToken("Post Kunjungan InProgres");
            $NoRegistrasi = $object->NoRegistrasi;
            $IdEncounter = $IdEncounter;
            $NikPasien = $object->NikPasien;
            $NamaPasien = $object->NamaPasien;
            $NikDokter = $object->NikDokter;
            $NamaDokter = $object->NamaDokter;
            $IdLocation = $object->IdLocation;
            $NamaLokasi = $object->NamaLokasi;
            $DateInprogres = $object->DateInprogres;
            $DateAwalCounter = $object->DateAwal;
            $NoOrganisasi = $dataToken['NoOrganisasi'];

            $data = '{
                "resourceType": "Encounter",
                "id": "'.$IdEncounter.'",
                "identifier": [
                {
                    "system": "http://sys-ids.kemkes.go.id/encounter/'.$NoOrganisasi.'",
                    "value": "'.$NoRegistrasi.'"
                }
                ],
                "status": "in-progress",
                "class": {
                "system": "http://terminology.hl7.org/CodeSystem/v3-ActCode",
                "code": "AMB",
                "display": "ambulatory"
                },
                "subject": {
                "reference": "Patient/'.$NikPasien.'",
                "display": "'.$NamaPasien.'"
                },
                "participant": [
                {
                    "type": [
                    {
                        "coding": [
                        {
                            "system": "http://terminology.hl7.org/CodeSystem/v3-ParticipationType",
                            "code": "ATND",
                            "display": "attender"
                        }
                        ]
                    }
                    ],
                    "individual": {
                    "reference": "Practitioner/'.$NikDokter.'",
                    "display": "'.$NamaDokter.'"
                    }
                }
                ],
                "period": {
                "start": "'.$DateInprogres.'"
                },
                "location": [
                {
                    "location": {
                    "reference": "Location/'.$IdLocation.'",
                    "display": "'.$NamaLokasi.'"
                    }
                }
                ],
                "statusHistory": [
                {
                    "status": "arrived",
                    "period": {
                    "start": "'.$DateAwalCounter.'",
                    "end": "'.$DateInprogres.'"
                    }
                },
                {
                    "status": "in-progress",
                    "period": {
                    "start": "'.$DateInprogres.'"
                    }
                }
                ],
                "serviceProvider": {
                "reference":"Organization/'.$NoOrganisasi.'"
                }
            }
            ';

            // $json = json_encode($data, JSON_PRETTY_PRINT);
            // echo $data;
            // die();
            $dataUrl = $dataToken['base_url'];
            $dataAccess = $dataToken['ValReturn']->access_token;

            $url = "$dataUrl/Encounter";
            $ch = curl_init($url);

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
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
                if ($httpCode == 201 || $httpCode == 200) {
                    // Decode and handle the response
                    $dataCek = json_decode($response);
                    $dataArr = array('ValReturn' => $dataCek, 'access_token' => $dataAccess);
                    $TokenContinue = array(
                        'NoTrx' => uniqid(true).date("Y_m_d"),
                        'access_token' => $dataAccess,
                        'client_id' => $dataToken["DataToken"]['client_id'],
                        'expires_in' => $dataToken["DataToken"]['expires_in'],
                        'CreateDate' => date("Y-m-d H:i:s"),
                        'Deskripsi' => $response,
                        'Payload' => $data,
                        'TokenAkses' => "Post Kunjungan Inprogress"
                    );
            
                    $this->db->insert('SatuSehat.Log_Token', $TokenContinue);
                    $TempId = array(
                        "NoTrx" => uniqid(true).date("_Y-m-d"),
                        "NoRegis" => $NoRegistrasi,
                        "ID_SatuSehat" => $dataCek->id,
                        "Status" => "Encounter - InProgress",
                        "CreateDate" => date("Y-m-d H:i:s"),
                        "Token" => $dataAccess
                    );
                    $this->db->insert("EMR.SatuSehat.Temp_ID", $TempId);
                    $this->ConditionPrimary($object, $dataCek->id, $IdEncounter);

                    // $this->db->query("UPDATE EMR.SatuSehat.Log_Token SET Payload = '$data', Deskripsi = '$response' WHERE access_token = '$dataAccess'");
                    echo json_encode($dataArr);
                } else {
                    // Handle non-200 status code
                    $dataArr = array('Status' => 0, 'payload' => $data, 'access_token' => $dataAccess, 'httpError' => $httpCode, "Posisi" => "2");
                    echo json_encode($dataArr);
                    // echo "HTTP Error: Post Kunjungan InProgres $httpCode\n";
                    // echo "Response: $response\n";
                    // echo "Payload: $data\n";
                    die();
                }
            }

            curl_close($ch);
        } else {
            $this->ConditionPrimary($object, $qCekTempId->ID_SatuSehat, $IdEncounter);
        }
        

        

    }

    public function ConditionPrimary($object, $IdInprogres, $IdEncounter) 
    {
        date_default_timezone_set("Asia/Jakarta");
        $NoRegistrasi = $object->NoRegistrasi;
        $qCekTempId = $this->db->query("SELECT * FROM EMR.SatuSehat.Temp_ID WHERE NoRegis = '$NoRegistrasi' AND Status = 'Condition - Primary' ")->row();

        if ($qCekTempId == null ) {
            $dataToken = $this->GetToken("Post Condition Primary");
            $EncounterInprogres = $IdInprogres;
            $NikPasien = $object->NikPasien;
            $NamaPasien = $object->NamaPasien;
            $KodeICD10 = $object->KodeICD10Primary;
            $NamaICD10 = $object->NamaICD10Primary;
            $DateRecord = $object->DateRecordPrimary;
            $NoRegistrasi = $object->NoRegistrasi;
            $NoOrganisasi = $dataToken['NoOrganisasi'];
            $TglPendek = date('Y-m-d', strtotime($DateRecord));

            $data = '{
                "resourceType": "Condition",
                "identifier": [
                    {
                        "system": "http://sys-ids.kemkes.go.id/encounter/'.$NoOrganisasi.'",
                        "value": "'.$NoRegistrasi.'"
                    }
                ],
                "clinicalStatus": {
                "coding": [
                    {
                        "system": "http://terminology.hl7.org/CodeSystem/condition-clinical",
                        "code": "active",
                        "display": "Active"
                    }
                ]
                },
                "category": [
                {
                    "coding": [
                        {
                            "system": "http://terminology.hl7.org/CodeSystem/condition-category",
                            "code": "encounter-diagnosis",
                            "display": "Encounter Diagnosis"
                        }
                    ]
                }
                ],
                "code": {
                "coding": [
                    {
                        "system": "http://hl7.org/fhir/sid/icd-10",
                        "code": "'.$KodeICD10.'",
                        "display": "'.$NamaICD10.'"
                    }
                ]
                },
                "subject": {
                "reference": "Patient/'.$NikPasien.'",
                "display": "'.$NamaPasien.'"
                },
                "encounter": {
                "reference": "Encounter/'.$EncounterInprogres.'"
                },
                "onsetDateTime": "'.$DateRecord.'",
                "recordedDate" : "'.$DateRecord.'"
            }
            ';

            // $json = json_encode($data, JSON_PRETTY_PRINT);
            // echo $data;
            // die();
            $dataUrl = $dataToken['base_url'];
            $dataAccess = $dataToken['ValReturn']->access_token;

            $url = "$dataUrl/Condition";
            $ch = curl_init($url);

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
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
                if ($httpCode == 201 || $httpCode == 200) {
                    // Decode and handle the response
                    $dataCek = json_decode($response);
                    $dataArr = array('ValReturn' => $dataCek, 'access_token' => $dataAccess);
                    $TokenContinue = array(
                        'NoTrx' => uniqid(true).date("Y_m_d"),
                        'access_token' => $dataAccess,
                        'client_id' => $dataToken["DataToken"]['client_id'],
                        'expires_in' => $dataToken["DataToken"]['expires_in'],
                        'CreateDate' => date("Y-m-d H:i:s"),
                        'Deskripsi' => $response,
                        'Payload' => $data,
                        'TokenAkses' => "Post Condition Pertama"
                    );
            
                    $this->db->insert('SatuSehat.Log_Token', $TokenContinue);
                    $TempId = array(
                        "NoTrx" => uniqid(true).date("_Y-m-d"),
                        "NoRegis" => $NoRegistrasi,
                        "ID_SatuSehat" => $dataCek->id,
                        "Status" => "Condition - Primary",
                        "CreateDate" => date("Y-m-d H:i:s"),
                        "Token" => $dataAccess
                    );
                    $this->db->insert("EMR.SatuSehat.Temp_ID", $TempId);
                    $this->ConditionSecondary($object, $IdInprogres, $dataCek->id, $IdEncounter);
                    // $this->db->query("UPDATE EMR.SatuSehat.Log_Token SET Payload = '$data', Deskripsi = '$response' WHERE access_token = '$dataAccess'");
                    echo json_encode($dataArr);
                } else {
                    // Handle non-200 status code
                    $dataArr = array('Status' => 0, 'payload' => $data, 'access_token' => $dataAccess, 'httpError' => $httpCode, "Posisi" => "3");
                    echo json_encode($dataArr);
                    // echo "HTTP Error: Post Condition Primary $httpCode\n";
                    // echo "Response: $response\n";
                    // echo "Payload: $data\n";
                    die();
                }
            }

            curl_close($ch);
        } else {
            $this->ConditionSecondary($object, $IdInprogres, $qCekTempId->ID_SatuSehat, $IdEncounter);
            
        }
        

        

    }

    public function ConditionSecondary($object, $IdInprogres, $IdKondisiPrimary, $IdEncounter) 
    {
        date_default_timezone_set("Asia/Jakarta");
        $NoRegistrasi = $object->NoRegistrasi;
        $qCekTempId = $this->db->query("SELECT * FROM EMR.SatuSehat.Temp_ID WHERE NoRegis = '$NoRegistrasi' AND Status = 'Conditon - Secondary' ")->row();

        if ($qCekTempId == null) {
            $dataToken = $this->GetToken("Post Condition Secondary");
            $EncounterInprogres = $IdInprogres;
            $NikPasien = $object->NikPasien;
            $NamaPasien = $object->NamaPasien;
            $KodeICD10 = $object->KodeICD10Secondary;
            $NamaICD10 = $object->NamaICD10Secondary;
            $DateRecord = $object->DateRecordSecondary;
            $TextEncounter = $object->TextEncounter;
            $NoRegistrasi = $object->NoRegistrasi;
            $NoOrganisasi = $dataToken['NoOrganisasi'];
            $TglPendek = date('Y-m-d', strtotime($DateRecord));

            $data = '{
                "resourceType": "Condition",
                "identifier": [
                    {
                        "system": "http://sys-ids.kemkes.go.id/encounter/'.$NoOrganisasi.'",
                        "value": "'.$NoRegistrasi.'"
                    }
                ],
                "clinicalStatus": {
                    "coding": [
                        {
                            "system": "http://terminology.hl7.org/CodeSystem/condition-clinical",
                            "code": "active",
                            "display": "Active"
                        }
                    ]
                },
                "category": [
                    {
                        "coding": [
                            {
                                "system": "http://terminology.hl7.org/CodeSystem/condition-category",
                                "code": "encounter-diagnosis",
                                "display": "Encounter Diagnosis"
                            }
                        ]
                    }
                ],
                "code": {
                    "coding": [
                        {
                            "system": "http://hl7.org/fhir/sid/icd-10",
                            "code": "'.$KodeICD10.'",
                            "display": "'.$NamaICD10.'"
                        }
                    ]
                },
                "subject": {
                    "reference": "Patient/'.$NikPasien.'",
                    "display": "'.$NamaPasien.'"
                },
                "encounter": {
                    "reference": "Encounter/'.$EncounterInprogres.'",
                    "display": "'.$TextEncounter.'"
                },
                "onsetDateTime": "'.$DateRecord.'",
                "recordedDate": "'.$DateRecord.'"
            }
            ';

            // $json = json_encode($data, JSON_PRETTY_PRINT);
            // echo $data;
            // die();
            $dataUrl = $dataToken['base_url'];
            $dataAccess = $dataToken['ValReturn']->access_token;

            $url = "$dataUrl/Condition";
            $ch = curl_init($url);

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
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
                if ($httpCode == 201 || $httpCode == 200) {
                    // Decode and handle the response
                    $dataCek = json_decode($response);
                    $dataArr = array('ValReturn' => $dataCek, 'access_token' => $dataAccess);
                    $TokenContinue = array(
                        'NoTrx' => uniqid(true).date("Y_m_d"),
                        'access_token' => $dataToken["DataToken"]->access_token,
                        'client_id' => $dataToken["DataToken"]->client_id,
                        'expires_in' => $dataToken["DataToken"]->expires_in,
                        'CreateDate' => date("Y-m-d H:i:s"),
                        'Deskripsi' => $response,
                        'Payload' => $data,
                        'TokenAkses' => $dataAccess
                    );
            
                    $this->db->insert('SatuSehat.Log_Token', $TokenContinue);
                    $TempId = array(
                        "NoTrx" => uniqid(true).date("_Y-m-d"),
                        "NoRegis" => $NoRegistrasi,
                        "ID_SatuSehat" => $dataCek->id,
                        "Status" => "Conditon - Secondary",
                        "CreateDate" => date("Y-m-d H:i:s"),
                        "Token" => $dataAccess
                    );
                    $this->db->insert("EMR.SatuSehat.Temp_ID", $TempId);
                    $this->EncounterUpdateFinish($object, $IdInprogres, $IdKondisiPrimary, $dataCek->id, $IdEncounter);
                    // $this->db->query("UPDATE EMR.SatuSehat.Log_Token SET Payload = '$data', Deskripsi = '$response' WHERE access_token = '$dataAccess'");
                    echo json_encode($dataArr);
                } else {
                    // Handle non-200 status code
                    $dataArr = array('Status' => 0, 'payload' => $data, 'access_token' => $dataAccess, 'httpError' => $httpCode, "Posisi" => "4");
                    echo json_encode($dataArr);

                    // echo "HTTP Error: Post Condition Secondary $httpCode\n";
                    // echo "Response: $response\n";
                    // echo "Payload: $data\n";
                    die();
                }
            }

            curl_close($ch);
        } else {
            $this->EncounterUpdateFinish($object, $IdInprogres, $IdKondisiPrimary, $qCekTempId->ID_SatuSehat, $IdEncounter);
        }
        

        

    }

    public function EncounterUpdateFinish($object, $IdInprogres, $IdKondisiPrimary, $IdKondisiSecondary, $IdEncounter) 
    {
        date_default_timezone_set("Asia/Jakarta");
        $NoRegistrasi = $object->NoRegistrasi;
        $qCekTempId = $this->db->query("SELECT * FROM EMR.SatuSehat.Temp_ID WHERE NoRegis = '$NoRegistrasi' AND Status = 'Encounter - Finish' ")->row();
        if ($qCekTempId == null) {
            $dataToken = $this->GetToken("Post Kunjungan Finish");
            $NoRegistrasi = $object->NoRegistrasi;
            $NikPasien = $object->NikPasien;
            $NamaPasien = $object->NamaPasien;
            $NikDokter = $object->NikDokter;
            $NamaDokter = $object->NamaDokter;
            $IdLocation = $object->IdLocation;
            $NamaLokasi = $object->NamaLokasi;
            $DateAwal = $object->DateAwal;
            $DateInprogres = $object->DateInprogres;
            $DateAkhir = $object->DateAkhir;
            // $IdKondisiPrimary = $object->IdKondisiPrimary;
            $DeskripsiKondisiPrimary = $object->NamaICD10Primary;
            // $IdKondisiSecondary = $object->IdKondisiSecondary;
            $DeskripsiKondisiSecondary = $object->NamaICD10Secondary;
            $NoOrganisasi = $dataToken['NoOrganisasi'];

            $data = '{
                "resourceType": "Encounter",
                "id": "'.$IdInprogres.'",
                "identifier": [
                    {
                        "system": "http://sys-ids.kemkes.go.id/encounter/'.$NoOrganisasi.'",
                        "value": "'.$NoRegistrasi.'"
                    }
                ],
                "status": "finished",
                "class": {
                    "system": "http://terminology.hl7.org/CodeSystem/v3-ActCode",
                    "code": "AMB",
                    "display": "ambulatory"
                },
                "subject": {
                    "reference": "Patient/'.$NikPasien.'",
                    "display": "'.$NamaPasien.'"
                },
                "participant": [
                    {
                        "type": [
                            {
                                "coding": [
                                    {
                                        "system": "http://terminology.hl7.org/CodeSystem/v3-ParticipationType",
                                        "code": "ATND",
                                        "display": "attender"
                                    }
                                ]
                            }
                        ],
                        "individual": {
                            "reference": "Practitioner/'.$NikDokter.'",
                            "display": "'.$NamaDokter.'"
                        }
                    }
                ],
                "period": {
                    "start": "'.$DateAwal.'",
                    "end": "'.$DateAkhir.'"
                },
                "location": [
                    {
                        "location": {
                            "reference": "Location/'.$IdLocation.'",
                            "display": "'.$NamaLokasi.'"
                        }
                    }
                ],
                "diagnosis": [
                    {
                        "condition": {
                            "reference": "Condition/'.$IdKondisiPrimary.'",
                            "display": "'.$DeskripsiKondisiPrimary.'"
                        },
                        "use": {
                            "coding": [
                                {
                                    "system": "http://terminology.hl7.org/CodeSystem/diagnosis-role",
                                    "code": "DD",
                                    "display": "Discharge diagnosis"
                                }
                            ]
                        },
                        "rank": 1
                    },
                    {
                        "condition": {
                            "reference": "Condition/'.$IdKondisiSecondary.'",
                            "display": "'.$DeskripsiKondisiSecondary.'"
                        },
                        "use": {
                            "coding": [
                                {
                                    "system": "http://terminology.hl7.org/CodeSystem/diagnosis-role",
                                    "code": "DD",
                                    "display": "Discharge diagnosis"
                                }
                            ]
                        },
                        "rank": 2
                    }
                ],
                "statusHistory": [
                    {
                        "status": "arrived",
                        "period": {
                            "start": "'.$DateAwal.'",
                            "end": "'.$DateInprogres.'"
                        }
                    },
                    {
                        "status": "in-progress",
                        "period": {
                            "start": "'.$DateInprogres.'",
                            "end": "'.$DateAkhir.'"
                        }
                    },
                    {
                        "status": "finished",
                        "period": {
                            "start": "'.$DateAkhir.'",
                            "end": "'.$DateAkhir.'"
                        }
                    }
                ],
                "serviceProvider": {
                    "reference": "Organization/'.$NoOrganisasi.'"
                }
            }
            ';

            // $json = json_encode($data, JSON_PRETTY_PRINT);
            // echo $data;
            // die();
            $dataUrl = $dataToken['base_url'];
            $dataAccess = $dataToken['ValReturn']->access_token;

            $url = "$dataUrl/Encounter";
            $ch = curl_init($url);

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
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
                if ($httpCode == 201 || $httpCode == 200) {
                    // Decode and handle the response
                    $dataCek = json_decode($response);
                    $TokenContinue = array(
                        'NoTrx' => uniqid(true).date("Y_m_d"),
                        'access_token' => $dataToken["DataToken"]->access_token,
                        'client_id' => $dataToken["DataToken"]->client_id,
                        'expires_in' => $dataToken["DataToken"]->expires_in,
                        'CreateDate' => date("Y-m-d H:i:s"),
                        'Deskripsi' => $response,
                        'Payload' => $data,
                        'TokenAkses' => $dataAccess
                    );
            
                    $this->db->insert('SatuSehat.Log_Token', $TokenContinue);
                    $TempId = array(
                        "NoTrx" => uniqid(true).date("_Y-m-d"),
                        "NoRegis" => $NoRegistrasi,
                        "ID_SatuSehat" => $dataCek->id,
                        "Status" => "Encounter - Finish",
                        "CreateDate" => date("Y-m-d H:i:s"),
                        "Token" => $dataAccess
                    );
                    $this->db->insert("EMR.SatuSehat.Temp_ID", $TempId);
                    $dataArr = array('Status' => 1, 'ValReturn' => $dataCek, 'access_token' => $dataAccess);
                    echo json_encode($dataArr);
                } else {
                    // Handle non-200 status code
                    $dataArr = array('Status' => 0, 'payload' => $data, 'access_token' => $dataAccess, 'httpError' => $httpCode, "Posisi" => "5");
                    echo json_encode($dataArr);
                    // echo "HTTP Error: Post Kunjungan Finish $httpCode\n";
                    // echo "Response: $response\n";
                    // echo "Payload: $data\n";
                    die();
                }
            }

            curl_close($ch);
        } else {
            $data = array(
                "IdEncounter" => $IdEncounter,
                "IdInProgress" => $IdInprogres,
                "IdConditionPrim" => $IdKondisiPrimary,
                "IdConditionSecond" => $IdKondisiSecondary,
                "IdFinish" => $qCekTempId->ID_SatuSehat
            );
            $dataArr = array('Status' => 1, 'ID' => $data);
            echo json_encode($dataArr);
        }
        
        

    }

    public function GetTokenKFACode($AksesToken)
    {
        
        $q = $this->db->query("SELECT * FROM SatuSehat.Environment WHERE Branch = 'BHI' AND Status = 'PROD' ")->row();
        $data = array(
            "client_id" => $q->client_id,
            "client_secret" => $q->client_secret
        );
    
        // Create a new cURL resource
        $ch = curl_init();
        
        // Build the form data
        $formData = http_build_query($data);
        
        // Set cURL options
        $url = 'https://api-satusehat.kemkes.go.id/oauth2/v1/accesstoken?grant_type=client_credentials';
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
        
            // Check the status in the response
            if ($httpCode == 200) {
                $dataToken = array(
                    'NoTrx' => uniqid(true).date("Y_m_d"),
                    'access_token' => $dataCek->access_token,
                    'client_id' => $dataCek->client_id,
                    'expires_in' => $dataCek->expires_in,
                    'CreateDate' => date("Y-m-d H:i:s"),
                    'TokenAkses' => $AksesToken
                );
        
                $this->db->insert('SatuSehat.Log_Token', $dataToken);
                // Build the response in the desired format
                $dataArr = array('ValReturn' => $dataCek, "DataToken" => $dataToken);
                // echo json_encode($dataArr);
                return $dataArr;
               
            } else {
                // echo $response;
                echo "HTTP Error: $httpCode\n";
                echo "Response: $response\n";
                return $response;
            }
        }
        
        // Close cURL session
        curl_close($ch);
    }

    public function GetKFACode($page,$Size,$product_type)
    {
        date_default_timezone_set("Asia/Jakarta");
        
        // $q = $this->db->query("SELECT * FROM SatuSehat.Environment WHERE Branch = 'BHI' AND Status = 'DEV' ")->row();
        $dataToken = $this->GetTokenKFACode("Akses KFA Untuk Insert Database Page : $page ");
        // Create a new cURL resource
        $ch = curl_init();
     
        $dataAccess = $dataToken['ValReturn']->access_token;

        // Build the form data
        // $formData = http_build_query($data);
        
        // Set cURL options
        $url = "https://api-satusehat.kemkes.go.id/kfa-v2/products/all?page=$page&size=$Size&product_type=$product_type";
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
        // curl_setopt($ch, CURLOPT_POSTFIELDS, $formData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Authorization: Bearer ' . $dataAccess,
            "Accept: application/json"
        ));
        
        // Execute cURL session and capture the response
        $response = curl_exec($ch);
        
        // Check for cURL errors
        if(curl_errno($ch)) {
            return 'Error: ' . curl_error($ch);
        } else {
            // Decode the response JSON
            $dataCek = json_decode($response);

            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
            // Check the status in the response
            if ($httpCode == 200) {
                // echo json_encode($response);
               $dataHasil = $dataCek->items->data;
               $dataFix = array();
               $dataIngredients = array();
               $retUpdate = array();
               foreach ($dataHasil as $va) {
                $q = $this->db->query("SELECT * FROM EMR.SatuSehat.MasterKFA WHERE kfa_code = '$va->kfa_code'")->num_rows();
                if ($q <= 0) {
                    if ($va->kfa_code == null) {
                        
                    }else{
                        $dataFix[] = array(
                            "kfa_code" => strval($va->kfa_code),
                            "name" => strval($va->name),
                            "farmalkes_type_code" => strval($va->farmalkes_type->code),
                            "farmalkes_type_name" => strval($va->farmalkes_type->name),
                            "farmalkes_type_group" => strval($va->farmalkes_type->group),
                            "manufacturer" => strval($va->manufacturer),
                            "fix_price" => strval($va->fix_price),
                            "net_weight_uom_name" => strval($va->net_weight_uom_name),
                            "volume_uom_name" => strval($va->volume_uom_name),
                            "uom" => strval($va->uom->name),
                            "dose_per_unit" => strval($va->dose_per_unit),
                            "dosage_form_code" => strval($va->dosage_form->code),
                            "dosage_form_name" => strval($va->dosage_form->name),
                            "product_template_code" => strval($va->product_template->kfa_code), 
                            "product_template_name" => strval($va->product_template->name),
                            "product_template_display_name" => strval($va->product_template->display_name),
                            "page" => $page,
                            "size" => $Size,
                            "update_date" => date("Y-m-d H:i:s")
                        );       
                    }
                                 
                }else{
                    $dataFix2 = array(
                        "name" => strval($va->name),
                        "farmalkes_type_code" => strval($va->farmalkes_type->code),
                        "farmalkes_type_name" => strval($va->farmalkes_type->name),
                        "farmalkes_type_group" => strval($va->farmalkes_type->group),
                        "manufacturer" => strval($va->manufacturer),
                        "fix_price" => strval($va->fix_price),
                        "net_weight_uom_name" => strval($va->net_weight_uom_name),
                        "volume_uom_name" => strval($va->volume_uom_name),
                        "uom" => strval($va->uom->name),
                        "dose_per_unit" => strval($va->dose_per_unit),
                        "dosage_form_code" => strval($va->dosage_form->code),
                        "dosage_form_name" => strval($va->dosage_form->name),
                        "product_template_code" => strval($va->product_template->kfa_code), 
                        "product_template_name" => strval($va->product_template->name),
                        "product_template_display_name" => strval($va->product_template->display_name),
                        "page" => $page,
                        "size" => $Size,
                        "update_date" => date("Y-m-d H:i:s")
                    );
                    $retUpdate[] = "Berhasil Update";
                    $this->M_BackApi->UpdateData($va->kfa_code, $dataFix2);
                }
                $product_template_kode = $va->product_template->kfa_code;
                foreach ($va->active_ingredients as $vac) {
                    $zat_aktif = str_replace("'", "", $vac->zat_aktif);
                    $kekuatan_zat_aktif = str_replace("'", "", $vac->kekuatan_zat_aktif);
                    $q2 = $this->db->query("SELECT * FROM EMR.SatuSehat.MasterKFA_Ingredient WHERE kfa_code = '$va->kfa_code' AND active_ingredients_code ='$vac->kfa_code' 
                    AND active_ingredients_zat_aktif = '$zat_aktif' AND product_template_code = '$product_template_kode'")->num_rows();
                    if ($q2 > 0) {
                     $this->db->query("UPDATE EMR.SatuSehat.MasterKFA_Ingredient SET active_ingredients_code = '$vac->kfa_code', kfa_code = '$va->kfa_code', 
                     active_ingredients_zat_aktif = '$zat_aktif', active_ingredients_kekuatan_zat_aktif = '$kekuatan_zat_aktif' WHERE kfa_code = '$va->kfa_code' 
                     AND active_ingredients_code ='$vac->kfa_code' AND active_ingredients_zat_aktif = '$zat_aktif' AND product_template_code = '$product_template_kode' ");
                    }else{
                        if ($zat_aktif == '' || $zat_aktif == null) {
                            
                        } else {
                            $dataIngredients = array(
                                "kfa_code" => strval($va->kfa_code),
                                "product_template_code" => strval($va->product_template->kfa_code),
                                "active_ingredients_code" => strval($vac->kfa_code),
                                "active_ingredients_zat_aktif" => str_replace("'", "", strval($vac->zat_aktif)),
                                "active_ingredients_kekuatan_zat_aktif" => $kekuatan_zat_aktif
                            );    
                            $this->db->insert('EMR.SatuSehat.MasterKFA_Ingredient', $dataIngredients);
                        }
                    }
                   }
               }

               
                if (count($dataFix) > 0) {
                $this->db->insert_batch("EMR.SatuSehat.MasterKFA", $dataFix);
                $hasil = $this->db->affected_rows(); 
                $data = array(
                    "status" => 200,
                    "message" => "Data Berhasil Di Insert $hasil",
                    "data" => $dataFix
                );
                
                // echo json_encode($data);
                return $data;
            }else{
                if (count($retUpdate) > 1) {
                    $data = array(
                        "status" => 200,
                        "message" => "Data Updated",
                        "data" => $retUpdate
                    );
                }else{
                    $data = array(
                        "status" => 200,
                        "message" => "Tidak Ada Data Insert",
                        "data" => $dataFix
                    );
                }
               
                // echo json_encode($data);
                return $data;
               }
               
            } else {
                $data = array(
                    "status" => 400,
                    "message" => "Error",
                    "Http_error" => $httpCode,
                    "Token" => $dataAccess,
                    "page" => $page
                );
               return $data;
                // echo $response;
                // echo "HTTP Error: $httpCode\n";
                // echo "Response: $response\n";
                // echo "Token: $dataAccess\n";
                // echo "Page: $page\n";
                // return $response;
            }
        }
        // Close cURL session
        curl_close($ch);

        
    }

    function BatchHit() {
        set_time_limit((60*60)*2);
        // $q = $this->db->query("SELECT * FROM SatuSehat.Environment WHERE Branch = 'BHI' AND Status = 'DEV' ")->row();
        $json = file_get_contents("php://input"); // json string
        $object = json_decode($json); // php object

        $page = $object->page;
        $size = $object->size;
        $product_type = $object->product_type;
        $Token = $object->Token;

        $q2 = $this->db->query("SELECT * FROM EMR.SatuSehat.Log_Token WHERE access_token = '$Token' ")->row();

        $GetDate = $q2->CreateDate;
        $NewDate = date("Y-m-d H:i:s");

        $startTimeStamp = strtotime($GetDate);
        $currentTimeStamp = strtotime($NewDate);
        $timeDifferenceInSeconds = $currentTimeStamp - $startTimeStamp;

        if ($timeDifferenceInSeconds > $q2->expires_in ) {
            $data = array(
                "status" => 400,
                "message" => "Credentials Failed"
            );
            echo json_encode($data);
            die;
        }

        $dataToken = $this->GetTokenKFACode("Akses KFA Untuk Insert Database");
        // Create a new cURL resource
        $ch = curl_init();
     
        $dataAccess = $dataToken['ValReturn']->access_token;

        // Build the form data
        // $formData = http_build_query($data);
        
        // Set cURL options
        $url = "https://api-satusehat.kemkes.go.id/kfa-v2/products/all?page=$page&size=$size&product_type=$product_type";
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
        // curl_setopt($ch, CURLOPT_POSTFIELDS, $formData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Authorization: Bearer ' . $dataAccess,
            "Accept: application/json"
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
            $Hasil = array();
            $HasilCek = array();
            // Check the status in the response
            if ($httpCode == 200) {
                // echo json_encode($response);
                $dataHasil = $dataCek->items->data;
                $Totaldata = $dataCek->total;
                $HasilBagi = ceil($Totaldata/$size)+1;
                for ($i=ceil($page); $i < $HasilBagi; $i++) {
                    $qc = $this->db->query("SELECT * FROM EMR.SatuSehat.MasterKFA WHERE page = '$i' ORDER BY update_date ASC")->row(); 
                    if (strtotime("now") > strtotime($qc->update_date)) {
                        $Hasil["HasilKirim"] = $this->GetKFACode($i,$size,$product_type);
                        $Hasil["Page"] = $i;
                        $HasilCek[] = $Hasil;
                    }else{
                        $Hasil["HasilKirim"] = $qc;
                        $Hasil["Page"] = $i;
                        $HasilCek[] = $Hasil;
                    }
                }
                $data = array(
                    "Hasil" => $HasilCek,
                );
                echo json_encode($data);
                
                // Close cURL session
               
            } else {
                $data = array(
                    "status" => 400,
                    "message" => "Error",
                    "Http_error" => $httpCode,
                    "Token" => $dataAccess,
                    "page" => $page
                );
                echo json_encode($data);
                // echo $response;
                // echo "HTTP Error: $httpCode\n";
                // echo "Response: $response\n";
                // echo "Token: $dataAccess\n";
                // echo "Page: $page\n";
                // return $response;
            }
        }

        curl_close($ch);

        
    }

    public function EncounterKunjunganAwal() 
    {
        date_default_timezone_set("Asia/Jakarta");
        $json = file_get_contents("php://input"); // json string
        $object = json_decode($json); // php object

        $NoRegis = $object->NoRegis;
        $Token = $object->Token;

        $NoRegis = $this->input->post("NoRegis");
        $Token = $this->input->post("Token");

        $q2 = $this->db->query("SELECT * FROM EMR.SatuSehat.Log_Token WHERE access_token = '$Token' ")->row();
        $object = $this->db->query("SELECT * FROM EMR.SatuSehat.vw_SatuSehat WHERE NoRegistrasi = '$NoRegis'")->row();
            $NoRegistrasi = $object->NoRegistrasi;
            $NikPasien = $object->NikPasien;
            $NamaPasien = $object->NamaPasien;
            $NikDokter = $object->NikDokter;
            $NamaDokter = $object->NamaDokter;
            $IdLocation = $object->IdLocation;
            $NamaLokasi = $object->NamaLokasi;
            $DateAwal = $object->DateAwal;
            $DateAkhir = $object->DateAkhir;

        $GetDate = $q2->CreateDate;
        $NewDate = date("Y-m-d H:i:s");

        $startTimeStamp = strtotime($GetDate);
        $currentTimeStamp = strtotime($NewDate);
        $timeDifferenceInSeconds = $currentTimeStamp - $startTimeStamp;

        if ($timeDifferenceInSeconds > $q2->expires_in ) {
            $data = array(
                "status" => 400,
                "message" => "Credentials Failed"
            );
            echo json_encode($data);
            die;
        }

        $qCekTempId = $this->db->query("SELECT * FROM EMR.SatuSehat.Temp_ID WHERE NoRegis = '$NoRegis' AND Status = 'Encounter - Awal' ")->row();

        if ($qCekTempId == null) {
            $dataToken = $this->GetToken("Post Kunjungan Awal");
            $NoOrganisasi = $dataToken['NoOrganisasi'];
            

            $data = '{
                "resourceType": "Encounter",
                "identifier": [
                    {
                        "system": "http://sys-ids.kemkes.go.id/encounter/'.$NoOrganisasi.'",
                        "value": "'.$NoRegistrasi.'"
                    }
                ],
                "status": "arrived",
                "class": {
                    "system": "http://terminology.hl7.org/CodeSystem/v3-ActCode",
                    "code": "AMB",
                    "display": "ambulatory"
                },
                "subject": {
                    "reference": "Patient/'.$NikPasien.'",
                    "display": "'.$NamaPasien.'"
                },
                "participant": [
                    {
                        "type": [
                            {
                                "coding": [
                                    {
                                        "system": "http://terminology.hl7.org/CodeSystem/v3-ParticipationType",
                                        "code": "ATND",
                                        "display": "attender"
                                    }
                                ]
                            }
                        ],
                        "individual": {
                            "reference": "Practitioner/'.$NikDokter.'",
                            "display": "'.$NamaDokter.'"
                        }
                    }
                ],
                "period": {
                    "start": "'.$DateAwal.'"
                },
                "location": [
                    {
                        "location": {
                            "reference": "Location/'.$IdLocation.'",
                            "display": "'.$NamaLokasi.'"
                        }
                    }
                ],
                "statusHistory": [
                    {
                        "status": "arrived",
                        "period": {
                            "start": "'.$DateAwal.'",
                            "end": "'.$DateAkhir.'"
                        }
                    }
                ],
                "serviceProvider": {
                    "reference": "Organization/'.$NoOrganisasi.'"
                }
            }';

            
            // $json = json_encode($data, JSON_PRETTY_PRINT);
            $dataUrl = $dataToken['base_url'];
            $dataAccess = $dataToken['ValReturn']->access_token;

            $url = "$dataUrl/Encounter";
            $ch = curl_init($url);

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
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
                if ($httpCode == 201 || $httpCode == 200) {
                    // Decode and handle the response
                    $dataCek = json_decode($response);
                    $TokenContinue = array(
                        'NoTrx' => uniqid(true).date("Y_m_d"),
                        'access_token' => $dataAccess,
                        'client_id' => $dataToken["DataToken"]['client_id'],
                        'expires_in' => $dataToken["DataToken"]['expires_in'],
                        'CreateDate' => date("Y-m-d H:i:s"),
                        'Deskripsi' => $response,
                        'Payload' => $data,
                        'TokenAkses' => "Post Kunjungan Awal"
                    );
            
                    $this->db->insert('SatuSehat.Log_Token', $TokenContinue);
                    $TempId = array(
                        "NoTrx" => uniqid(true).date("_Y-m-d"),
                        "NoRegis" => $NoRegistrasi,
                        "ID_SatuSehat" => $dataCek->id,
                        "Status" => "Encounter - Awal",
                        "CreateDate" => date("Y-m-d H:i:s"),
                        "Token" => $dataAccess
                    );
                    $this->db->insert("EMR.SatuSehat.Temp_ID", $TempId);
                    $dataArr = array('status' => 1, 'response' => 200, 'ValReturn' => $dataCek, 'access_token' => $dataAccess, 'status_hit' => "Encounter - Awal");
                    echo json_encode($dataArr);
                } else {
                    // Handle non-200 status code
                    $dataArr = array('Status' => 0, 'payload' => $data, 'access_token' => $dataAccess, 'httpError' => $httpCode, "Posisi" => "1");
                    echo json_encode($dataArr);
                    // echo "HTTP Error: Post Kunjungan Awal $httpCode\n";
                    // echo "Response: $response\n";
                    // echo "Payload: $data\n";
                    die();
                }
            }

            curl_close($ch);
        }else{
            $this->EncounterUpdateInprogres($object, $qCekTempId->ID_SatuSehat);
            
        }

        

    }

    public function EncounterUpdateInprogres_Single() 
    {
        date_default_timezone_set("Asia/Jakarta");
        $json = file_get_contents("php://input"); // json string
        $object = json_decode($json); // php object

        $NoRegis = $object->NoRegis;
        $Token = $object->Token;

        $q2 = $this->db->query("SELECT * FROM EMR.SatuSehat.Log_Token WHERE access_token = '$Token' ")->row();
        $object = $this->db->query("SELECT * FROM EMR.SatuSehat.vw_SatuSehat WHERE NoRegistrasi = '$NoRegistrasi'")->row();
            $NoRegistrasi = $object->NoRegistrasi;
            $NikPasien = $object->NikPasien;
            $NamaPasien = $object->NamaPasien;
            $NikDokter = $object->NikDokter;
            $NamaDokter = $object->NamaDokter;
            $IdLocation = $object->IdLocation;
            $NamaLokasi = $object->NamaLokasi;
            $DateAwal = $object->DateAwal;
            $DateAkhir = $object->DateAkhir;

        $GetDate = $q2->CreateDate;
        $NewDate = date("Y-m-d H:i:s");

        $startTimeStamp = strtotime($GetDate);
        $currentTimeStamp = strtotime($NewDate);
        $timeDifferenceInSeconds = $currentTimeStamp - $startTimeStamp;

        if ($timeDifferenceInSeconds > $q2->expires_in ) {
            $data = array(
                "status" => 400,
                "message" => "Credentials Failed"
            );
            echo json_encode($data);
            die;
        }
        $CekEncounter = $this->db->query("SELECT * FROM EMR.SatuSehat.Temp_ID WHERE NoRegis = '$NoRegistrasi' AND Status = 'Encounter - Awal' ")->row();
        $qCekTempId = $this->db->query("SELECT * FROM EMR.SatuSehat.Temp_ID WHERE NoRegis = '$NoRegistrasi' AND Status = 'Encounter - InProgress' ")->row();

        $IdEncounter = $CekEncounter->ID_SatuSehat;

        if ($qCekTempId == null) {
            $dataToken = $this->GetToken("Post Kunjungan InProgres");
            $NoRegistrasi = $object->NoRegistrasi;
            $IdEncounter = $IdEncounter;
            $NikPasien = $object->NikPasien;
            $NamaPasien = $object->NamaPasien;
            $NikDokter = $object->NikDokter;
            $NamaDokter = $object->NamaDokter;
            $IdLocation = $object->IdLocation;
            $NamaLokasi = $object->NamaLokasi;
            $DateInprogres = $object->DateInprogres;
            $DateAwalCounter = $object->DateAwal;
            $NoOrganisasi = $dataToken['NoOrganisasi'];

            $data = '{
                "resourceType": "Encounter",
                "id": "'.$IdEncounter.'",
                "identifier": [
                {
                    "system": "http://sys-ids.kemkes.go.id/encounter/'.$NoOrganisasi.'",
                    "value": "'.$NoRegistrasi.'"
                }
                ],
                "status": "in-progress",
                "class": {
                "system": "http://terminology.hl7.org/CodeSystem/v3-ActCode",
                "code": "AMB",
                "display": "ambulatory"
                },
                "subject": {
                "reference": "Patient/'.$NikPasien.'",
                "display": "'.$NamaPasien.'"
                },
                "participant": [
                {
                    "type": [
                    {
                        "coding": [
                        {
                            "system": "http://terminology.hl7.org/CodeSystem/v3-ParticipationType",
                            "code": "ATND",
                            "display": "attender"
                        }
                        ]
                    }
                    ],
                    "individual": {
                    "reference": "Practitioner/'.$NikDokter.'",
                    "display": "'.$NamaDokter.'"
                    }
                }
                ],
                "period": {
                "start": "'.$DateInprogres.'"
                },
                "location": [
                {
                    "location": {
                    "reference": "Location/'.$IdLocation.'",
                    "display": "'.$NamaLokasi.'"
                    }
                }
                ],
                "statusHistory": [
                {
                    "status": "arrived",
                    "period": {
                    "start": "'.$DateAwalCounter.'",
                    "end": "'.$DateInprogres.'"
                    }
                },
                {
                    "status": "in-progress",
                    "period": {
                    "start": "'.$DateInprogres.'"
                    }
                }
                ],
                "serviceProvider": {
                "reference":"Organization/'.$NoOrganisasi.'"
                }
            }
            ';

            // $json = json_encode($data, JSON_PRETTY_PRINT);
            // echo $data;
            // die();
            $dataUrl = $dataToken['base_url'];
            $dataAccess = $dataToken['ValReturn']->access_token;

            $url = "$dataUrl/Encounter";
            $ch = curl_init($url);

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
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
                if ($httpCode == 201 || $httpCode == 200) {
                    // Decode and handle the response
                    $dataCek = json_decode($response);
                    $TokenContinue = array(
                        'NoTrx' => uniqid(true).date("Y_m_d"),
                        'access_token' => $dataAccess,
                        'client_id' => $dataToken["DataToken"]['client_id'],
                        'expires_in' => $dataToken["DataToken"]['expires_in'],
                        'CreateDate' => date("Y-m-d H:i:s"),
                        'Deskripsi' => $response,
                        'Payload' => $data,
                        'TokenAkses' => "Post Kunjungan Inprogress"
                    );
            
                    $this->db->insert('SatuSehat.Log_Token', $TokenContinue);
                    $TempId = array(
                        "NoTrx" => uniqid(true).date("_Y-m-d"),
                        "NoRegis" => $NoRegistrasi,
                        "ID_SatuSehat" => $dataCek->id,
                        "Status" => "Encounter - InProgress",
                        "CreateDate" => date("Y-m-d H:i:s"),
                        "Token" => $dataAccess
                    );
                    $this->db->insert("EMR.SatuSehat.Temp_ID", $TempId);

                    $dataArr = array('status' => 1, 'response' => 200, 'ValReturn' => $dataCek, 'access_token' => $dataAccess, 'status_hit' => "Encounter - InProgress");
                    echo json_encode($dataArr);
                } else {
                    // Handle non-200 status code
                    $dataArr = array('Status' => 0, 'response' => 400, 'payload' => $data, 'access_token' => $dataAccess, 'httpError' => $httpCode, "Posisi" => "2");
                    echo json_encode($dataArr);
                    // echo "HTTP Error: Post Kunjungan InProgres $httpCode\n";
                    // echo "Response: $response\n";
                    // echo "Payload: $data\n";
                    die();
                }
            }

            curl_close($ch);
        } else{
            $dataArr = array('status' => 1, 'response' => 200, 'status_hit' => "Encounter - InProgress", "ID" => $qCekTempId->ID_SatuSehat);
            echo json_encode($dataArr);
        }

    }

    public function ConditionPrimary_Single() 
    {
        date_default_timezone_set("Asia/Jakarta");
        $json = file_get_contents("php://input"); // json string
        $object = json_decode($json); // php object

        $NoRegistrasi = $object->NoRegis;
        $Token = $object->Token;
        
        $q2 = $this->db->query("SELECT * FROM EMR.SatuSehat.Log_Token WHERE access_token = '$Token' ")->row();
        $object = $this->db->query("SELECT * FROM EMR.SatuSehat.vw_SatuSehat WHERE NoRegistrasi = '$NoRegistrasi'")->row();
            $NoRegistrasi = $object->NoRegistrasi;
            $NikPasien = $object->NikPasien;
            $NamaPasien = $object->NamaPasien;
            $NikDokter = $object->NikDokter;
            $NamaDokter = $object->NamaDokter;
            $IdLocation = $object->IdLocation;
            $NamaLokasi = $object->NamaLokasi;
            $DateAwal = $object->DateAwal;
            $DateAkhir = $object->DateAkhir;

        $GetDate = $q2->CreateDate;
        $NewDate = date("Y-m-d H:i:s");

        $startTimeStamp = strtotime($GetDate);
        $currentTimeStamp = strtotime($NewDate);
        $timeDifferenceInSeconds = $currentTimeStamp - $startTimeStamp;

        if ($timeDifferenceInSeconds > $q2->expires_in ) {
            $data = array(
                "status" => 400,
                "message" => "Credentials Failed"
            );
            echo json_encode($data);
            die;
        }

        $CekInprogress = $this->db->query("SELECT * FROM EMR.SatuSehat.Temp_ID WHERE NoRegis = '$NoRegistrasi' AND Status = 'Encounter - InProgress' ")->row();
        $qCekTempId = $this->db->query("SELECT * FROM EMR.SatuSehat.Temp_ID WHERE NoRegis = '$NoRegistrasi' AND Status = 'Condition - Primary' ")->row();

        $IdInprogres = $CekInprogress->ID_SatuSehat;

        if ($qCekTempId == null ) {
            $dataToken = $this->GetToken("Post Condition Primary");
            $EncounterInprogres = $IdInprogres;
            $NikPasien = $object->NikPasien;
            $NamaPasien = $object->NamaPasien;
            $KodeICD10 = $object->KodeICD10Primary;
            $NamaICD10 = $object->NamaICD10Primary;
            $DateRecord = $object->DateRecordPrimary;
            $NoRegistrasi = $object->NoRegistrasi;
            $NoOrganisasi = $dataToken['NoOrganisasi'];
            $TglPendek = date('Y-m-d', strtotime($DateRecord));

            $data = '{
                "resourceType": "Condition",
                "identifier": [
                    {
                        "system": "http://sys-ids.kemkes.go.id/encounter/'.$NoOrganisasi.'",
                        "value": "'.$NoRegistrasi.'"
                    }
                ],
                "clinicalStatus": {
                "coding": [
                    {
                        "system": "http://terminology.hl7.org/CodeSystem/condition-clinical",
                        "code": "active",
                        "display": "Active"
                    }
                ]
                },
                "category": [
                {
                    "coding": [
                        {
                            "system": "http://terminology.hl7.org/CodeSystem/condition-category",
                            "code": "encounter-diagnosis",
                            "display": "Encounter Diagnosis"
                        }
                    ]
                }
                ],
                "code": {
                "coding": [
                    {
                        "system": "http://hl7.org/fhir/sid/icd-10",
                        "code": "'.$KodeICD10.'",
                        "display": "'.$NamaICD10.'"
                    }
                ]
                },
                "subject": {
                "reference": "Patient/'.$NikPasien.'",
                "display": "'.$NamaPasien.'"
                },
                "encounter": {
                "reference": "Encounter/'.$EncounterInprogres.'"
                },
                "onsetDateTime": "'.$DateRecord.'",
                "recordedDate" : "'.$DateRecord.'"
            }
            ';

            // $json = json_encode($data, JSON_PRETTY_PRINT);
            // echo $data;
            // die();
            $dataUrl = $dataToken['base_url'];
            $dataAccess = $dataToken['ValReturn']->access_token;

            $url = "$dataUrl/Condition";
            $ch = curl_init($url);

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
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
                if ($httpCode == 201 || $httpCode == 200) {
                    // Decode and handle the response
                    $dataCek = json_decode($response);
                    $TokenContinue = array(
                        'NoTrx' => uniqid(true).date("Y_m_d"),
                        'access_token' => $dataAccess,
                        'client_id' => $dataToken["DataToken"]['client_id'],
                        'expires_in' => $dataToken["DataToken"]['expires_in'],
                        'CreateDate' => date("Y-m-d H:i:s"),
                        'Deskripsi' => $response,
                        'Payload' => $data,
                        'TokenAkses' => "Post Condition Pertama"
                    );
            
                    $this->db->insert('SatuSehat.Log_Token', $TokenContinue);
                    $TempId = array(
                        "NoTrx" => uniqid(true).date("_Y-m-d"),
                        "NoRegis" => $NoRegistrasi,
                        "ID_SatuSehat" => $dataCek->id,
                        "Status" => "Condition - Primary",
                        "CreateDate" => date("Y-m-d H:i:s"),
                        "Token" => $dataAccess
                    );
                    $this->db->insert("EMR.SatuSehat.Temp_ID", $TempId);
                    $dataArr = array('status' => 1, 'response' => 200, 'ValReturn' => $dataCek, 'access_token' => $dataAccess, 'status_hit' => "Condition - Primary");
                    echo json_encode($dataArr);
                } else {
                    // Handle non-200 status code
                    $dataArr = array('status' => 0, 'response' => 400, 'payload' => $data, 'access_token' => $dataAccess, 'httpError' => $httpCode, "Posisi" => "3");
                    echo json_encode($dataArr);
                    // echo "HTTP Error: Post Condition Primary $httpCode\n";
                    // echo "Response: $response\n";
                    // echo "Payload: $data\n";
                    die();
                }
            }

            curl_close($ch);
        } else {
            $dataArr = array('status' => 1, 'response' => 200, 'status_hit' => "Condition - Primary", "ID" => $qCekTempId->ID_SatuSehat);
            echo json_encode($dataArr);
            
        }
    }

    public function ConditionSecondary_Single() 
    {
        date_default_timezone_set("Asia/Jakarta");
        $json = file_get_contents("php://input"); // json string
        $object = json_decode($json); // php object

        $NoRegistrasi = $object->NoRegis;
        $Token = $object->Token;

        $q2 = $this->db->query("SELECT * FROM EMR.SatuSehat.Log_Token WHERE access_token = '$Token' ")->row();
        $object = $this->db->query("SELECT * FROM EMR.SatuSehat.vw_SatuSehat WHERE NoRegistrasi = '$NoRegistrasi'")->row();
            $NoRegistrasi = $object->NoRegistrasi;
            $NikPasien = $object->NikPasien;
            $NamaPasien = $object->NamaPasien;
            $NikDokter = $object->NikDokter;
            $NamaDokter = $object->NamaDokter;
            $IdLocation = $object->IdLocation;
            $NamaLokasi = $object->NamaLokasi;
            $DateAwal = $object->DateAwal;
            $DateAkhir = $object->DateAkhir;

        $GetDate = $q2->CreateDate;
        $NewDate = date("Y-m-d H:i:s");

        $startTimeStamp = strtotime($GetDate);
        $currentTimeStamp = strtotime($NewDate);
        $timeDifferenceInSeconds = $currentTimeStamp - $startTimeStamp;

        if ($timeDifferenceInSeconds > $q2->expires_in ) {
            $data = array(
                "status" => 400,
                "message" => "Credentials Failed"
            );
            echo json_encode($data);
            die;
        }
        $CekInprogress = $this->db->query("SELECT * FROM EMR.SatuSehat.Temp_ID WHERE NoRegis = '$NoRegistrasi' AND Status = 'Encounter - InProgress' ")->row();
        $qCekTempId = $this->db->query("SELECT * FROM EMR.SatuSehat.Temp_ID WHERE NoRegis = '$NoRegistrasi' AND Status = 'Conditon - Secondary' ")->row();
        $IdInprogres = $CekInprogress->ID_SatuSehat;

        if ($qCekTempId == null) {
            $dataToken = $this->GetToken("Post Condition Secondary");
            $EncounterInprogres = $IdInprogres;
            $NikPasien = $object->NikPasien;
            $NamaPasien = $object->NamaPasien;
            $KodeICD10 = $object->KodeICD10Secondary;
            $NamaICD10 = $object->NamaICD10Secondary;
            $DateRecord = $object->DateRecordSecondary;
            $TextEncounter = $object->TextEncounter;
            $NoRegistrasi = $object->NoRegistrasi;
            $NoOrganisasi = $dataToken['NoOrganisasi'];
            $TglPendek = date('Y-m-d', strtotime($DateRecord));

            $data = '{
                "resourceType": "Condition",
                "identifier": [
                    {
                        "system": "http://sys-ids.kemkes.go.id/encounter/'.$NoOrganisasi.'",
                        "value": "'.$NoRegistrasi.'"
                    }
                ],
                "clinicalStatus": {
                    "coding": [
                        {
                            "system": "http://terminology.hl7.org/CodeSystem/condition-clinical",
                            "code": "active",
                            "display": "Active"
                        }
                    ]
                },
                "category": [
                    {
                        "coding": [
                            {
                                "system": "http://terminology.hl7.org/CodeSystem/condition-category",
                                "code": "encounter-diagnosis",
                                "display": "Encounter Diagnosis"
                            }
                        ]
                    }
                ],
                "code": {
                    "coding": [
                        {
                            "system": "http://hl7.org/fhir/sid/icd-10",
                            "code": "'.$KodeICD10.'",
                            "display": "'.$NamaICD10.'"
                        }
                    ]
                },
                "subject": {
                    "reference": "Patient/'.$NikPasien.'",
                    "display": "'.$NamaPasien.'"
                },
                "encounter": {
                    "reference": "Encounter/'.$EncounterInprogres.'",
                    "display": "'.$TextEncounter.'"
                },
                "onsetDateTime": "'.$DateRecord.'",
                "recordedDate": "'.$DateRecord.'"
            }
            ';

            // $json = json_encode($data, JSON_PRETTY_PRINT);
            // echo $data;
            // die();
            $dataUrl = $dataToken['base_url'];
            $dataAccess = $dataToken['ValReturn']->access_token;

            $url = "$dataUrl/Condition";
            $ch = curl_init($url);

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
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
                if ($httpCode == 201 || $httpCode == 200) {
                    // Decode and handle the response
                    $dataCek = json_decode($response);
                    $TokenContinue = array(
                        'NoTrx' => uniqid(true).date("Y_m_d"),
                        'access_token' => $dataToken["DataToken"]->access_token,
                        'client_id' => $dataToken["DataToken"]->client_id,
                        'expires_in' => $dataToken["DataToken"]->expires_in,
                        'CreateDate' => date("Y-m-d H:i:s"),
                        'Deskripsi' => $response,
                        'Payload' => $data,
                        'TokenAkses' => $dataAccess
                    );
            
                    $this->db->insert('SatuSehat.Log_Token', $TokenContinue);
                    $TempId = array(
                        "NoTrx" => uniqid(true).date("_Y-m-d"),
                        "NoRegis" => $NoRegistrasi,
                        "ID_SatuSehat" => $dataCek->id,
                        "Status" => "Conditon - Secondary",
                        "CreateDate" => date("Y-m-d H:i:s"),
                        "Token" => $dataAccess
                    );
                    $this->db->insert("EMR.SatuSehat.Temp_ID", $TempId);
                    $dataArr = array('status' => 1, 'response' => 200, 'ValReturn' => $dataCek, 'access_token' => $dataAccess, 'status_hit' => "Condition - Secondary");
                    echo json_encode($dataArr);
                } else {
                    // Handle non-200 status code
                    $dataArr = array('Status' => 0, 'response' => 400, 'payload' => $data, 'access_token' => $dataAccess, 'httpError' => $httpCode, "Posisi" => "4");
                    echo json_encode($dataArr);

                    // echo "HTTP Error: Post Condition Secondary $httpCode\n";
                    // echo "Response: $response\n";
                    // echo "Payload: $data\n";
                    die();
                }
            }

            curl_close($ch);
        } else {
            $dataArr = array('status' => 1, 'response' => 200, 'status_hit' => "Condition - Secondary", "ID" => $qCekTempId->ID_SatuSehat);
            echo json_encode($dataArr);
        }
    }

    public function EncounterUpdateFinish_Single() 
    {
        date_default_timezone_set("Asia/Jakarta");
        
        $json = file_get_contents("php://input"); // json string
        $object = json_decode($json); // php object

        $NoRegistrasi = $object->NoRegis;
        $Token = $object->Token;


        $q2 = $this->db->query("SELECT * FROM EMR.SatuSehat.Log_Token WHERE access_token = '$Token' ")->row();
        $object = $this->db->query("SELECT * FROM EMR.SatuSehat.vw_SatuSehat WHERE NoRegistrasi = '$NoRegistrasi'")->row();
            $NoRegistrasi = $object->NoRegistrasi;
            $NikPasien = $object->NikPasien;
            $NamaPasien = $object->NamaPasien;
            $NikDokter = $object->NikDokter;
            $NamaDokter = $object->NamaDokter;
            $IdLocation = $object->IdLocation;
            $NamaLokasi = $object->NamaLokasi;
            $DateAwal = $object->DateAwal;
            $DateAkhir = $object->DateAkhir;

        $GetDate = $q2->CreateDate;
        $NewDate = date("Y-m-d H:i:s");

        $startTimeStamp = strtotime($GetDate);
        $currentTimeStamp = strtotime($NewDate);
        $timeDifferenceInSeconds = $currentTimeStamp - $startTimeStamp;

        if ($timeDifferenceInSeconds > $q2->expires_in ) {
            $data = array(
                "status" => 400,
                "message" => "Credentials Failed"
            );
            echo json_encode($data);
            die;
        }
        $CekAwal = $this->db->query("SELECT * FROM EMR.SatuSehat.Temp_ID WHERE NoRegis = '$NoRegistrasi' AND Status = 'Encounter - Awal' ")->row();
        $CekInprogress = $this->db->query("SELECT * FROM EMR.SatuSehat.Temp_ID WHERE NoRegis = '$NoRegistrasi' AND Status = 'Encounter - InProgress' ")->row();
        $CekPrimary = $this->db->query("SELECT * FROM EMR.SatuSehat.Temp_ID WHERE NoRegis = '$NoRegistrasi' AND Status = 'Condition - Primary' ")->row();
        $CekSecondary = $this->db->query("SELECT * FROM EMR.SatuSehat.Temp_ID WHERE NoRegis = '$NoRegistrasi' AND Status = 'Conditon - Secondary' ")->row();
        $qCekTempId = $this->db->query("SELECT * FROM EMR.SatuSehat.Temp_ID WHERE NoRegis = '$NoRegistrasi' AND Status = 'Encounter - Finish' ")->row();
        $IdInprogres = $CekInprogress->ID_SatuSehat;
        $IdKondisiPrimary = $CekPrimary->ID_SatuSehat;
        $IdKondisiSecondary = $CekSecondary->ID_SatuSehat;
        $IdEncounter = $CekAwal->ID_SatuSehat;
        
        if ($qCekTempId == null) {
            $dataToken = $this->GetToken("Post Kunjungan Finish");
            $NoRegistrasi = $object->NoRegistrasi;
            $NikPasien = $object->NikPasien;
            $NamaPasien = $object->NamaPasien;
            $NikDokter = $object->NikDokter;
            $NamaDokter = $object->NamaDokter;
            $IdLocation = $object->IdLocation;
            $NamaLokasi = $object->NamaLokasi;
            $DateAwal = $object->DateAwal;
            $DateInprogres = $object->DateInprogres;
            $DateAkhir = $object->DateAkhir;
            // $IdKondisiPrimary = $object->IdKondisiPrimary;
            $DeskripsiKondisiPrimary = $object->NamaICD10Primary;
            // $IdKondisiSecondary = $object->IdKondisiSecondary;
            $DeskripsiKondisiSecondary = $object->NamaICD10Secondary;
            $NoOrganisasi = $dataToken['NoOrganisasi'];

            $data = '{
                "resourceType": "Encounter",
                "id": "'.$IdInprogres.'",
                "identifier": [
                    {
                        "system": "http://sys-ids.kemkes.go.id/encounter/'.$NoOrganisasi.'",
                        "value": "'.$NoRegistrasi.'"
                    }
                ],
                "status": "finished",
                "class": {
                    "system": "http://terminology.hl7.org/CodeSystem/v3-ActCode",
                    "code": "AMB",
                    "display": "ambulatory"
                },
                "subject": {
                    "reference": "Patient/'.$NikPasien.'",
                    "display": "'.$NamaPasien.'"
                },
                "participant": [
                    {
                        "type": [
                            {
                                "coding": [
                                    {
                                        "system": "http://terminology.hl7.org/CodeSystem/v3-ParticipationType",
                                        "code": "ATND",
                                        "display": "attender"
                                    }
                                ]
                            }
                        ],
                        "individual": {
                            "reference": "Practitioner/'.$NikDokter.'",
                            "display": "'.$NamaDokter.'"
                        }
                    }
                ],
                "period": {
                    "start": "'.$DateAwal.'",
                    "end": "'.$DateAkhir.'"
                },
                "location": [
                    {
                        "location": {
                            "reference": "Location/'.$IdLocation.'",
                            "display": "'.$NamaLokasi.'"
                        }
                    }
                ],
                "diagnosis": [
                    {
                        "condition": {
                            "reference": "Condition/'.$IdKondisiPrimary.'",
                            "display": "'.$DeskripsiKondisiPrimary.'"
                        },
                        "use": {
                            "coding": [
                                {
                                    "system": "http://terminology.hl7.org/CodeSystem/diagnosis-role",
                                    "code": "DD",
                                    "display": "Discharge diagnosis"
                                }
                            ]
                        },
                        "rank": 1
                    },
                    {
                        "condition": {
                            "reference": "Condition/'.$IdKondisiSecondary.'",
                            "display": "'.$DeskripsiKondisiSecondary.'"
                        },
                        "use": {
                            "coding": [
                                {
                                    "system": "http://terminology.hl7.org/CodeSystem/diagnosis-role",
                                    "code": "DD",
                                    "display": "Discharge diagnosis"
                                }
                            ]
                        },
                        "rank": 2
                    }
                ],
                "statusHistory": [
                    {
                        "status": "arrived",
                        "period": {
                            "start": "'.$DateAwal.'",
                            "end": "'.$DateInprogres.'"
                        }
                    },
                    {
                        "status": "in-progress",
                        "period": {
                            "start": "'.$DateInprogres.'",
                            "end": "'.$DateAkhir.'"
                        }
                    },
                    {
                        "status": "finished",
                        "period": {
                            "start": "'.$DateAkhir.'",
                            "end": "'.$DateAkhir.'"
                        }
                    }
                ],
                "serviceProvider": {
                    "reference": "Organization/'.$NoOrganisasi.'"
                }
            }
            ';

            // $json = json_encode($data, JSON_PRETTY_PRINT);
            // echo $data;
            // die();
            $dataUrl = $dataToken['base_url'];
            $dataAccess = $dataToken['ValReturn']->access_token;

            $url = "$dataUrl/Encounter";
            $ch = curl_init($url);

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
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
                if ($httpCode == 201 || $httpCode == 200) {
                    // Decode and handle the response
                    $dataCek = json_decode($response);
                    $TokenContinue = array(
                        'NoTrx' => uniqid(true).date("Y_m_d"),
                        'access_token' => $dataToken["DataToken"]->access_token,
                        'client_id' => $dataToken["DataToken"]->client_id,
                        'expires_in' => $dataToken["DataToken"]->expires_in,
                        'CreateDate' => date("Y-m-d H:i:s"),
                        'Deskripsi' => $response,
                        'Payload' => $data,
                        'TokenAkses' => $dataAccess
                    );
            
                    $this->db->insert('SatuSehat.Log_Token', $TokenContinue);
                    $TempId = array(
                        "NoTrx" => uniqid(true).date("_Y-m-d"),
                        "NoRegis" => $NoRegistrasi,
                        "ID_SatuSehat" => $dataCek->id,
                        "Status" => "Encounter - Finish",
                        "CreateDate" => date("Y-m-d H:i:s"),
                        "Token" => $dataAccess
                    );
                    $this->db->insert("EMR.SatuSehat.Temp_ID", $TempId);
                    $dataArr = array('status' => 1, 'response' => 200, 'ValReturn' => $dataCek, 'access_token' => $dataAccess, 'status_hit' => "Encounter - Finish");
                    echo json_encode($dataArr);
                } else {
                    // Handle non-200 status code
                    $dataArr = array('Status' => 0, 'response' => 400, 'payload' => $data, 'access_token' => $dataAccess, 'httpError' => $httpCode, "Posisi" => "5");
                    echo json_encode($dataArr);
                    // echo "HTTP Error: Post Kunjungan Finish $httpCode\n";
                    // echo "Response: $response\n";
                    // echo "Payload: $data\n";
                    die();
                }
            }

            curl_close($ch);
        } else {
            $data = array(
                "IdEncounter" => $IdEncounter,
                "IdInProgress" => $IdInprogres,
                "IdConditionPrim" => $IdKondisiPrimary,
                "IdConditionSecond" => $IdKondisiSecondary,
                "IdFinish" => $qCekTempId->ID_SatuSehat
            );
            $dataArr = array('Status' => 1, 'ID' => $data);
            echo json_encode($dataArr);
        }
        
        

    }


}
