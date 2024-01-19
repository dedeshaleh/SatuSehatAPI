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
    public function GetToken($AksesToken)
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

    public function GetPasienByNIk($NIK) 
    {
        date_default_timezone_set("Asia/Jakarta");
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
                $dataArr = array('ValReturn' => $dataCek, 'access_token' => $dataAccess);
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

    public function GetPasienByName($name, $dob, $gender) 
    {
        date_default_timezone_set("Asia/Jakarta");
        $dataToken = $this->GetToken("Get Pasien By Name");

        $data = array(
            "Param" => null,
        );

        $json = json_encode($data, JSON_PRETTY_PRINT);
        $dataUrl = $dataToken['base_url'];
        $dataAccess = $dataToken['ValReturn']->access_token;

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

    public function GetDokterNIK($NIK) 
    {
        date_default_timezone_set("Asia/Jakarta");
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
    
    public function GetDokterByName($name, $dob, $gender) 
    {
        date_default_timezone_set("Asia/Jakarta");
        $dataToken = $this->GetToken("GET Dokter By Name");

        $data = array(
            "Param" => null,
        );

        $json = json_encode($data, JSON_PRETTY_PRINT);
        $dataUrl = $dataToken['base_url'];
        $dataAccess = $dataToken['ValReturn']->access_token;

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

    public function GetOrganization($Kode) 
    {
        date_default_timezone_set("Asia/Jakarta");
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

    public function GetLokasi($OrgID, $KodeRuangan) 
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

    public function POSTLokasi($KodePoli) 
    {
        date_default_timezone_set("Asia/Jakarta");
        $dataToken = $this->GetToken("Post Lokasi");
        $json = file_get_contents("php://input"); // json string
        $object = json_decode($json); // php object
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
            if ($httpCode == 200) {
                // Decode and handle the response
                $dataCek = json_decode($response, true);
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


    public function EncounterKunjunganAwal($NoRegis=null) 
    {
        date_default_timezone_set("Asia/Jakarta");
        $dataToken = $this->GetToken("Post Kunjungan Awal");
        // $json = file_get_contents("php://input"); // json string
        // $object = json_decode($json); // php object
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
                $this->EncounterUpdateInprogres($object, $dataCek);
                echo json_encode($dataArr);
            } else {
                // Handle non-200 status code
                echo "HTTP Error: Post Kunjungan Awal $httpCode\n";
                echo "Response: $response\n";
                echo "Payload: $data\n";
                die();
            }
        }

        curl_close($ch);

    }

    public function EncounterUpdateInprogres($object, $HasilEncounter) 
    {
        date_default_timezone_set("Asia/Jakarta");
        $dataToken = $this->GetToken("Post Kunjungan InProgres");
        // $json = file_get_contents("php://input"); // json string
        // $object = json_decode($json); // php object
        $NoRegistrasi = $object->NoRegistrasi;
        $IdEncounter = $HasilEncounter->id;
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
                $this->ConditionPrimary($object, $dataCek, $dataCek->id);

                // $this->db->query("UPDATE EMR.SatuSehat.Log_Token SET Payload = '$data', Deskripsi = '$response' WHERE access_token = '$dataAccess'");
                echo json_encode($dataArr);
            } else {
                // Handle non-200 status code
                echo "HTTP Error: Post Kunjungan InProgres $httpCode\n";
                echo "Response: $response\n";
                echo "Payload: $data\n";
            }
        }

        curl_close($ch);

    }

    public function ConditionPrimary($object, $dataCek, $IdInprogres) 
    {
        date_default_timezone_set("Asia/Jakarta");
        $dataToken = $this->GetToken("Post Condition Primary");
        // $json = file_get_contents("php://input"); // json string
        // $object = json_decode($json); // php object
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
                $this->ConditionSecondary($object, $dataCek, $IdInprogres, $dataCek->id);
                // $this->db->query("UPDATE EMR.SatuSehat.Log_Token SET Payload = '$data', Deskripsi = '$response' WHERE access_token = '$dataAccess'");
                echo json_encode($dataArr);
            } else {
                // Handle non-200 status code
                echo "HTTP Error: Post Condition Primary $httpCode\n";
                echo "Response: $response\n";
                echo "Payload: $data\n";
            }
        }

        curl_close($ch);

    }

    public function ConditionSecondary($object, $dataCek, $IdInprogres, $IdKondisiPrimary) 
    {
        date_default_timezone_set("Asia/Jakarta");
        $dataToken = $this->GetToken("Post Condition Secondary");
        // $json = file_get_contents("php://input"); // json string
        // $object = json_decode($json); // php object
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
                $this->EncounterUpdateFinish($object, $dataCek, $IdInprogres, $IdKondisiPrimary, $dataCek->id);
                // $this->db->query("UPDATE EMR.SatuSehat.Log_Token SET Payload = '$data', Deskripsi = '$response' WHERE access_token = '$dataAccess'");
                echo json_encode($dataArr);
            } else {
                // Handle non-200 status code
                echo "HTTP Error: Post Condition Secondary $httpCode\n";
                echo "Response: $response\n";
                echo "Payload: $data\n";
            }
        }

        curl_close($ch);

    }

    public function EncounterUpdateFinish($object, $dataCek, $IdInprogres, $IdKondisiPrimary, $IdKondisiSecondary) 
    {
        date_default_timezone_set("Asia/Jakarta");
        $dataToken = $this->GetToken("Post Kunjungan Finish");
        // $json = file_get_contents("php://input"); // json string
        // $object = json_decode($json); // php object
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
                echo "HTTP Error: Post Kunjungan Finish $httpCode\n";
                echo "Response: $response\n";
                echo "Payload: $data\n";
            }
        }

        curl_close($ch);

    }

    public function GetTokenKFACode($AksesToken)
    {
        // $q = $this->db->query("SELECT * FROM SatuSehat.Environment WHERE Branch = 'BHI' AND Status = 'DEV' ")->row();

        $data = array(
            "client_id" => '5GQYH5TWL2GmFW0JgcoNqftQkncSnwHAP9UGAryTCPHRSAnt',
            "client_secret" => 'GPYt0ailtueWQUxg7ocHGWK0cn7Gz8du0kfDoA7BcoytD248wUKzWa2D3LfLqfTC'
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
                echo json_encode($dataArr);
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
        // $q = $this->db->query("SELECT * FROM SatuSehat.Environment WHERE Branch = 'BHI' AND Status = 'DEV' ")->row();
        $dataToken = $this->GetTokenKFACode("Akses KFA Untuk Insert Database");
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
            echo 'Error: ' . curl_error($ch);
        } else {
            // Decode the response JSON
            // $dataCek = json_decode($response);

            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
            // Check the status in the response
            if ($httpCode == 200) {
                echo json_encode($response);
               
            } else {
                // echo $response;
                echo "HTTP Error: $httpCode\n";
                echo "Response: $response\n";
                echo "Token: $dataAccess\n";
                return $response;
            }
        }
        
        // Close cURL session
        curl_close($ch);

        
    }

}
