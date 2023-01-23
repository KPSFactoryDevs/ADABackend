<?php

namespace App\Helpers\Bilanci;

use Illuminate\Support\Facades\Http;

class BilanciAIHelper
{

    public function getDataFromNotaIntegrativa($instance) {

        // METTERE IN HELPER
        $jsonData = false;
        $NotaIntro = $instance->getElements()->ElementsByName('IntroduzioneDebiti');
        $debitiTotaliNotaIntegrativa = 0;
        $debitiTotaliNotaIntegrativaHTML = '';
        if(count($NotaIntro->getElements()) > 0) {
            $debitiTotaliNotaIntegrativaHTML = (array_shift($instance->getElements()->ElementsByName('IntroduzioneDebiti')->getElements()['IntroduzioneDebiti'])['value']);
            $debitiTotaliNotaIntegrativa = strip_tags(htmlspecialchars_decode(array_shift($instance->getElements()->ElementsByName('IntroduzioneDebiti')->getElements()['IntroduzioneDebiti'])['value']));

            $response = Http::withHeaders([
                'Authorization' => 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJhdWQiOiIxIiwianRpIjoiZTVkNmZhZDY4ZDhjMjMwNWNiZjlkMTgxODJmY2VmZjNmN2E4MGI0MWFlMWNmMWU2MTA3NzdmMjI0MTc3Mjc2NWM1ODE4NTdhOTVhNzYzZGQiLCJpYXQiOjE2NzE3MjU3ODguNDk4OTA0LCJuYmYiOjE2NzE3MjU3ODguNDk4OTA1LCJleHAiOjE3MDMyNjE3ODguNDY2ODgzLCJzdWIiOiIxIiwic2NvcGVzIjpbXX0.BhOjRh10a-FyN7kpLv85ALu_a46kKCt4l1FM6ey-bBgcr-yNsmYNE_MzESX2xFPYpM5Gmca4Gpi2GgZzfobiBaNcYrdvgpg5bSuStmW-uRRy8n_qjlwbP94ZuMgJ0NV_8uq3X_PSBy6IF4oAiYj0PHArQUr75n2a6RrhJccTHFtwBiE3z018xrWuLt-Pql8ysASeNIG_ol2O2ZRTX7nqo7zSc_6yGm24bnuJjmfLMV8vvWYkVn6IEO-TxS85ZBAwpJhULjD3Djjc58oRpviC5VQIIqBjxrk6dI4xv_q1mteaiAgLLbdHPYxEuho8FFVPB1Gu8wM43KqWI2c-e4a7X5xnT6tZ-GXhH8hOcrPvRkU-gL-mYZeWvhxmC4AAE4Ulx8-hL-sTDSGie2ZKkET6DRs_rAM2zN2znX1Ie2SsWkgA6oc_4-C85kTdG19VkGxsRlmY6xOXF-TqjhaKFfUuVmrJpCQkYhkTO5qCmjcU-XO3OeFR2EPlvphoD5P_DlrTg_ni6W55C1qWcmcmJ2dGa3qbdTm8uX3v2WlLdJe1-purMwwjadOO08cwcKtG-6TMA-TbN7DKMS7yiSS6bnn1zN7ZmrK1-Cycbb-sMW1WRHgIhVQz3JAj6vrxX45iRrgQrXTdX3zpc8-DSpGbniLpavi5AewYqYGJIXFka0HlE6U',
                'Content-Type' => 'application/json'
            ])->post('http://laravelopenai.test/api/playground', [
                'input' => $debitiTotaliNotaIntegrativa
            ]);

            $notaIntegrativaDebitiTotaliETributari = json_decode($response->getBody()->getContents());
            if(isset($notaIntegrativaDebitiTotaliETributari->debiti_tributari, $notaIntegrativaDebitiTotaliETributari->debiti_tributari)) {
                $jsonData['current']['DebitiDebitiTributari'] = (float)$notaIntegrativaDebitiTotaliETributari->debiti_tributari;
                $jsonData['current']['Debiti'] = (float)$notaIntegrativaDebitiTotaliETributari->debiti_totali;
            }

        }



        return $jsonData;
    }
}
