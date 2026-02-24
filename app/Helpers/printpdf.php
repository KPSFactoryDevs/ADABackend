<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Http;
use GuzzleHttp\Client;
use Response;

class printpdf
{
	public $baseUrl;
	public $currentPayload = false;

	function __construct()
	{
		$this->baseUrl = "https://api.pdfmonkey.io/api/v1/documents/";
	}

	private function getBearerToken()
	{
		$token = "HXC-K3sK3Pcp7sC3LXgJ";
		return $token;
	}

	public function generateDocument($type, $idDocument = false, $years = 1)
	{
		$token = $this->getBearerToken();
		$body = $this->getBody($type, $idDocument, $years);

		$response = Http::withHeaders([
			'Authorization' => 'Bearer ' . $token,
			'Content-Type' => 'application/json'
		])->post($this->baseUrl, $body);

		$json = $response->json();
		if (!isset($json['document']) || !isset($json['document']['id'])) {
			throw new \Exception("PDFMonkey generate error: " . $response->body());
		}

		$idDocument = $json['document']['id'];

		return $idDocument;
	}

	public function getDocumentData($documentId)
	{
		$token = $this->getBearerToken();
		$getDocument = Http::withHeaders([
			'Authorization' => 'Bearer ' . $token,
			'Content-Type' => 'application/json'
		])->get($this->baseUrl . $documentId);

		$json = $getDocument->json();
		if (!isset($json['document']) || !isset($json['document']['download_url']) || empty($json['document']['download_url'])) {
			if (isset($json['document']) && $json['document']['status'] == 'pending') {
				// Retry fetching if it's pending. But currently this is synchronous sleep(5) in the controller.
				// For now throw so it's visible.
				throw new \Exception("PDFMonkey get error: Document is pending or URL missing. Response: " . $getDocument->body());
			}
			throw new \Exception("PDFMonkey get error: " . $getDocument->body());
		}

		$fileContent = file_get_contents($json['document']['download_url']);

		$fileName = time() . '.pdf';

		file_put_contents(base_path() . '/public/crdocument/' . $fileName, $fileContent);

		$file = base_path() . '/public/crdocument/' . $fileName;

		$headers = array(
			'Content-Type: application/pdf',
		);

		return Response::download($file, $fileName, $headers);
	}

	private function getTemplateId($type)
	{
		switch ($type) {
			case "bilancio":
				return "4B9C1CE4-B36E-4371-9385-778BB9CEA134";
				break;
			case "crAndamentale":
				return "54A28FE1-8985-4F39-BCA1-2B1B5690BA8C";
				break;
			case "allerta":
				return "AFB142A4-B910-4E48-AFF7-0BCE3D6E292F";
				break;
		}
	}


	private function getBody($type, $idBilancio, $years)
	{
		$templateId = $this->getTemplateId($type);
		$payload = $this->getPayload($type, $idBilancio, $years);

		return [
			"document" => [
				"document_template_id" => $templateId,
				"status" => "pending",
				"payload" => $payload,
				"meta" => [
					"clientId" => "ABC1234-DE",
					"_filename" => $type . "_" . time() . ".pdf"
				]
			]
		];
	}

	private function getPayload($type, $idBilancio, $years)
	{

		if (!$this->currentPayload) {
			return "No Payload";
		}

		return $this->currentPayload;
	}

}