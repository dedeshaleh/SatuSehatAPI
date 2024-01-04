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
                $dataArr = array('ValReturn' => $dataCek, 'base_url' => $q->base_url, 'NoOrganisasi' => $q->NoOrganisasi);
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
                echo json_encode($dataArr);
            } else {
                // Handle non-200 status code
                echo "HTTP Error: $httpCode\n";
                echo "Response: $response\n";
            }
        }

        curl_close($ch);

    }


    public function EncounterKunjunganAwal($ID=null) 
    {
        date_default_timezone_set("Asia/Jakarta");
        $dataToken = $this->GetToken("Post Kunjungan Awal");
        $json = file_get_contents("php://input"); // json string
        $object = json_decode($json); // php object
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
                echo json_encode($dataArr);
            } else {
                // Handle non-200 status code
                echo "HTTP Error: $httpCode\n";
                echo "Response: $response\n";
            }
        }

        curl_close($ch);

    }

    public function EncounterUpdateInprogres($ID=null) 
    {
        date_default_timezone_set("Asia/Jakarta");
        $dataToken = $this->GetToken("Post Kunjungan InProgres");
        $json = file_get_contents("php://input"); // json string
        $object = json_decode($json); // php object
        $NoRegistrasi = $object->NoRegistrasi;
        $IdEncounter = $object->IdEncounter;
        $NikPasien = $object->NikPasien;
        $NamaPasien = $object->NamaPasien;
        $NikDokter = $object->NikDokter;
        $NamaDokter = $object->NamaDokter;
        $IdLocation = $object->IdLocation;
        $NamaLokasi = $object->NamaLokasi;
        $DateInprogres = $object->DateInprogres;
        $DateAwalCounter = $object->DateAwalCounter;
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
                echo json_encode($dataArr);
            } else {
                // Handle non-200 status code
                echo "HTTP Error: $httpCode\n";
                echo "Response: $response\n";
            }
        }

        curl_close($ch);

    }

    public function ConditionPrimary($ID=null) 
    {
        date_default_timezone_set("Asia/Jakarta");
        $dataToken = $this->GetToken("Post Kunjungan InProgres");
        $json = file_get_contents("php://input"); // json string
        $object = json_decode($json); // php object
        $EncounterInprogres = $object->EncounterInprogres;
        $NikPasien = $object->NikPasien;
        $NamaPasien = $object->NamaPasien;
        $KodeICD10 = $object->KodeICD10;
        $NamaICD10 = $object->NamaICD10;
        $DateRecord = $object->DateRecord;
        $NoRegistrasi = "TEST-01";
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
                echo json_encode($dataArr);
            } else {
                // Handle non-200 status code
                echo "HTTP Error: $httpCode\n";
                echo "Response: $response\n";
            }
        }

        curl_close($ch);

    }

    public function ConditionSecondary($ID=null) 
    {
        date_default_timezone_set("Asia/Jakarta");
        $dataToken = $this->GetToken("Post Kunjungan InProgres");
        $json = file_get_contents("php://input"); // json string
        $object = json_decode($json); // php object
        $EncounterInprogres = $object->EncounterInprogres;
        $NikPasien = $object->NikPasien;
        $NamaPasien = $object->NamaPasien;
        $KodeICD10 = $object->KodeICD10;
        $NamaICD10 = $object->NamaICD10;
        $DateRecord = $object->DateRecord;
        $TextEncounter = $object->TextEncounter;
        $NoRegistrasi = "TEST-01";
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
                echo json_encode($dataArr);
            } else {
                // Handle non-200 status code
                echo "HTTP Error: $httpCode\n";
                echo "Response: $response\n";
            }
        }

        curl_close($ch);

    }

    public function EncounterUpdateFinish($ID=null) 
    {
        date_default_timezone_set("Asia/Jakarta");
        $dataToken = $this->GetToken("Post Kunjungan InProgres");
        $json = file_get_contents("php://input"); // json string
        $object = json_decode($json); // php object
        $NoRegistrasi = $object->NoRegistrasi;
        $IdEncounter = $object->IdEncounter;
        $NikPasien = $object->NikPasien;
        $NamaPasien = $object->NamaPasien;
        $NikDokter = $object->NikDokter;
        $NamaDokter = $object->NamaDokter;
        $IdLocation = $object->IdLocation;
        $NamaLokasi = $object->NamaLokasi;
        $DateAwal = $object->DateAwal;
        $DateInprogres = $object->DateInprogres;
        $DateAkhir = $object->DateAkhir;
        $IdKondisiPrimary = $object->IdKondisiPrimary;
        $DeskripsiKondisiPrimary = $object->DeskripsiKondisiPrimary;
        $IdKondisiSecondary = $object->IdKondisiSecondary;
        $DeskripsiKondisiSecondary = $object->DeskripsiKondisiSecondary;
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
