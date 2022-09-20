<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Illuminate\Http\Request;
use App\Models\Bilanci;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Financial\Bilanci\Controllers\BilanciController;

class BilanciTest extends TestCase
{
    use DatabaseTransactions;

    public function setUp(): void
    {
        parent::setUp();
        DB::beginTransaction();
    }

    public function test_recap_balance_01()
    {
        Storage::fake('local');

        $filename = public_path('bilanci\X0547180.xbrl');

        $file = new UploadedFile($filename, 'X0547180.xbrl', 'application/xbrl', null, true);

        $parameters = [
            'tipo_azienda' => 'Ingegneria civile, Costruzioni specializzate',
            'forma_giuridica' => 'Società per azione SPA',
            'account_id' => 1,
            'base64' => $file,
        ];

        $response = $this->json('post', 'api/recapBilancio', $parameters);

        $response->assertStatus(200);

        $voicesIfEquals = $this->assertEquals('SRT GROUP SRL', $response['jsonData']['anagrafic']['DatiAnagraficiDenominazione']);

        $anagrafic = json_encode($response['jsonData']['anagrafic']);
        $vociExt = json_encode($response['vociExt']);

        $data = [
            'anagrafic' => $anagrafic,
            'vociExt' => $vociExt
        ];

        return $data;
    }



    public function test_store_balance()
    {
        $recap = $this->test_recap_balance_01();

        $vociExt = $recap['vociExt'];
        $anagrafic = $recap['anagrafic'];

        $extVoices = [];

        foreach (json_decode($vociExt) as $key => $singleVoice) {
            $voicesCurr = $singleVoice . '_curr';
            $voicesPrev = $singleVoice . '_prev';
            $extVoices[$voicesCurr] = 123;
            $extVoices[$voicesPrev] = 1234;
            foreach (json_decode($anagrafic) as $key => $value) {
                $extVoices[$key] = $value;
            }
        }

        $extVoices['formaGiuridica'] = "Società in nome collettivo SNC";
        $extVoices['tipo_azienda'] = "Fornitura acqua, reti fognarie rifiuti, Trasmissone Energia e Gas";
        $extVoices['years'] = "2017-2018";

        $response = $this->json('post', 'api/importBilancio', $extVoices);

        $response->assertStatus(200);

        $data = $this->assertDatabaseHas('bilanci', ['forma_giuridica' => $extVoices['formaGiuridica'], 'tipo_azienda' => $extVoices['tipo_azienda'], 'year' => $extVoices['years']]);

        // $this->setBilancioId($response['idBilancio']);
        // DB::table('bilanci')->delete($response['idBilancio']);
        // return $response['idBilancio'];
    }

    

    public function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    // public function test_show_balance() 
    // {
    //     $response = $this->json('get', 'api/getBilancio/29');

    //     $response->assertStatus(200);
    // }



    /**
     * A basic feature test example.
     *
     * @return void
     */
    // public function test_index_balance()
    // {

    //     $response = $this->json('get', 'api/getAllBilanci');

    //     $response->assertStatus(200);
    // }

    // public function test_index_structure_json_balance()
    // {
    //     $this->json('get', 'api/getAllBilanci')
    //         ->assertStatus(Response::HTTP_OK)
    //         ->assertJsonStructure(
    //             [
    //                 "error",
    //                 "data" => [
    //                     "current_page",
    //                     "data" => [
    //                         [
    //                             "id",
    //                             "name",
    //                             "serialized_data",
    //                             "json_data",
    //                             "account_id",
    //                             "company_id",
    //                             "year",
    //                             "tipologia",
    //                             "created_at",
    //                             "updated_at",
    //                             "json_data_prev",
    //                             "json_data_anag",
    //                             "current_year",
    //                             "prev_year",
    //                             "tipo_azienda",
    //                             "forma_giuridica",
    //                             "provvisorio",
    //                             "predefinito",
    //                             "company_name",
    //                             "annoFormatted",
    //                             "account"
    //                         ]
    //                     ],
    //                 ]
    //             ]
    //         );
    // }

}
