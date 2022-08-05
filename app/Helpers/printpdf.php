<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Http;
use GuzzleHttp\Client;
use Response;

class printpdf
{
	public $baseUrl;
	public $currentPayload = false;
	
	function __construct() {
		$this->baseUrl = "https://api.pdfmonkey.io/api/v1/documents/";
	}

	private function getBearerToken() {
		$token = "YZsssKa8rfMNHm6Gy2c8"; 
	//	$token = "HXC-K3sK3Pcp7sC3LXgJ";
		return $token;
	}

	public function generateDocument($type, $idDocument = false, $years = 1) 
	{	
		$token = $this->getBearerToken();
		$body = $this->getBody($type, $idDocument, $years);
		
		$response = Http::withHeaders([
		'Authorization' => 'Bearer '.$token,
		'Content-Type' => 'application/json'
		])->post($this->baseUrl, $body);

	 	$idDocument = $response['document']['id'];
	 
	  	return $idDocument;

		
	}
	
	public function getDocumentData($documentId) {
		$token = $this->getBearerToken();
		$getDocument = Http::withHeaders([
		'Authorization' => 'Bearer '.$token,
		'Content-Type' => 'application/json'
		])->get($this->baseUrl.$documentId);

			$fileContent = file_get_contents($getDocument['document']['download_url']);

			$fileName = time().'.pdf';

			file_put_contents(base_path() . '/public/crdocument/'.$fileName, $fileContent);
		 
	 		$file = base_path().'/public/crdocument/'.$fileName;
	 
	     	$headers = array(
              'Content-Type: application/pdf',
            );
	 
			return Response::download($file, $fileName, $headers);
	}
	
	private function getTemplateId($type) {
		switch ($type) {
			case "bilancio":
				return "7CEDF658-EB45-450D-9280-EF277C85E6CC";
				break;
			case "crAndamentale":
				return "A187E905-99F6-40C2-9ABD-EE4D88E5F29A";
				break;
			case "allerta":
				return "AFB142A4-B910-4E48-AFF7-0BCE3D6E292F";  // template monkey test 2B18B647-7186-4AA0-A1A3-C67BEE465469
				break;
		}
	}
	
 
	private function getBody($type, $idBilancio, $years) {
		$templateId = $this->getTemplateId($type);
		$payload = $this->getPayload($type, $idBilancio, $years);

		return [
			  "document" => [
				"document_template_id" => $templateId,
				 "status" => "pending",
				"payload" => $payload,
				"meta" => [
				  "clientId" => "ABC1234-DE",
				  "_filename" => $type."_".time().".pdf"
				]
			  ]
		];
	}
	
	private function getPayload($type, $idBilancio, $years) {
		
		if(!$this->currentPayload) {
			return "No Payload";
		} 
	
		return $this->currentPayload;
	}

}