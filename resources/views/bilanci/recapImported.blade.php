@inject('model', '\App\Domains\Auth\Models\User')

@extends('backend.layouts.app')

@section('title', __('Create User'))

@section('content')

<?php
//    dd($gradi);
$currentYear = $jsonData['currentYear'];
$prevYear = $jsonData['prevYear'];
$support1 = $jsonData['current'];
$support2 = $jsonData['prev'];
$support3 = array();
$support5 = isset($jsonData['singleCurrent']) ? $jsonData['singleCurrent'] : null;
$support6 = isset($jsonData['singlePrev']) ? $jsonData['singlePrev'] : null;
ksort($support1);
ksort($support2);

foreach ($support1 as $sup1 => $val1) {
    foreach ($support2 as $sup2 => $val2) {
        if ($sup1 == $sup2) {
            $support3[$sup1]['current'] = $val1;
            $support3[$sup1]['prev'] = $val2;
        }
    }
}

if (isset($support5)) {
    foreach ($support5 as $data => $val) {
        $support3[$data]['current'] = $val;
    }
}

if (isset($support6)) {
    foreach ($support6 as $data => $val) {
        $support3[$data]['prev'] = $val;
    }
}
?>


<x-forms.post :action="route('admin.analysis.bilanci.store')">
    <x-backend.card>




        <x-slot name="body">
            <div x-data="{userType : '{{ $model::TYPE_USER }}'}">

                <input type="text" class="form-control d-none" name="currYear" value="{{ $currentYear }}" required />
                <input type="text" class="form-control d-none" name="prevYear" value="{{ $prevYear }}" required />


                <h1><b>Anagrafica Aziendale</b></h1>
                @foreach ($jsonData['anagrafic'] as $data => $value)
                @if(isset($value) && $value != "")
                <div class="row ">
                    <div class="col data-label anagrafic-label">
                        <?php
                        $data = str_replace('DatiAnagrafici', '', $data);
                        $arr = preg_split('/(?=[A-Z])/', $data);
                        $arr = array_unique($arr);

                        $string = "";

                        foreach ($arr as $dat => $val) {
                            $string .= $val . ' ';
                        }
                        echo $string;
                        ?>
                    </div>
                    <div class="col">

                       @if ( $value == 'false' )
                           No
                        @elseif($value == 'true' )
                            Si
                        @else
                            {{ $value}}
                        @endif
                        <input style="background-color: #fff; text-align: center" type="text" readonly class="form-control anagrafic-label d-none" readonly name="{{ $data.' anag' }}" value="{{ $value }}" required />
                    </div>
                </div>
                @endif
                @endforeach



                <div class="form-group row">
                    <div class="col data-label anagrafic-label">
                        Tipo Azienda
                    </div>
                    <div class="col">
                        {{ $tipo_azienda }}
                        <input style="background-color: #fff; text-align: center" type="text" readonly class="form-control anagrafic-label d-none" readonly name="tipo_azienda" value="{{ $tipo_azienda }}" required />
                    </div>
                </div>
<hr>




                <div class="form-group row ">
                    <div class="col-8 round-label-left">
                        <h1><b>Dati di Bilancio</b></h1>
                    </div>
                    <div class="col-2 text-center round-label-left">
                      <?php
                        $currentYear = explode(' ', $currentYear);
$currentYear = substr($currentYear[0], 0, 4);?>
                          <h2 class="yearHeading">   <b>  {{ str_replace(' ', ' / ',$currentYear) }}</b></h2>
                    </div>
                    <div class="col-2 text-center round-label-right">
                        <?php
                        $prevYear = explode(' ', $prevYear);
                        $prevYear = substr($prevYear[0], 0, 4);?>
                        <h2 class="yearHeading"><b>  {{ str_replace(' ', ' / ',$prevYear) }}</b></h2>
                    </div>

                </div>


                @foreach($gradi as $header1=>$header2)
                        <div class="form-group row datibilancio">
                            <div class="col-8 data-label text-truncate bg-grey">
                                <h1><b>- {{ $header1 }}</b></h1>
                            </div>

                            @if(isset($support3[$vociExt[$header1]]) || $header1 == 'Conto economico')
                            @if($header1 == 'Conto economico')
                                    <div class="col-2 bg-grey"></div>
                                    <div class="col-2 bg-grey"></div>
                            @else
                            <div class="col-2 headingNumberOne bg-grey">
                                {{number_format($support3[$vociExt[$header1]]['current'],0,',','.')}} €
                                <input type="text" class="form-control d-none" name="{{ $vociExt[$header1].' curr' }}" value="{{number_format($support3[$vociExt[$header1]]['current'],0,',','.')}}" required />
                            </div>
                            <div class="col-2 headingNumberOne bg-grey ">
                                {{ number_format($support3[$vociExt[$header1]]['prev'],0,',','.') }} €
                                <input type="text" class="form-control d-none" name="{{ $vociExt[$header1].' prev' }}" value="{{ number_format($support3[$vociExt[$header1]]['prev'],0,',','.') }}" required />
                            </div>
                            @endif
                            @else
                            <div class="col-2 headingNumberOne">
                                0 €
                                <input type="text" class="form-contro d-nonel" name="{{ $vociExt[$header1].' curr' }}" value="0" required />
                            </div>
                            <div class="col-2 headingNumberOne">
                                0} €
                                <input type="text" class="form-control d-none" name="{{ $vociExt[$header1].' prev' }}" value="0" required />
                            </div>
                            @endif
                                    @foreach($header2 as $h2=>$header3)
                                    <div class="col-8 data-label text-truncate">
                                        <h2 style="padding-left:25px;">- {{ $h2 }}</h2>
                                    </div>
                                    @if(isset($support3[$vociExt[$h2]]))
                                    <div class="col-2 headingNumberTwo">
                                        {{number_format($support3[$vociExt[$h2]]['current'],0,',','.')}} €
                                        <input type="text" class="form-control d-none" name="{{ $vociExt[$h2].' curr' }}" value="{{number_format($support3[$vociExt[$h2]]['current'],0,',','.')}}" required />
                                    </div>
                                    <div class="col-2 headingNumberTwo">
                                        {{ number_format($support3[$vociExt[$h2]]['prev'],0,',','.') }} €
                                        <input type="text" class="form-control d-none" name="{{ $vociExt[$h2].' prev' }}" value="{{ number_format($support3[$vociExt[$h2]]['prev'],0,',','.') }}" required />
                                    </div>
                                    @else
                                    <div class="col-2 headingNumberTwo">
                                        0 €
                                        <input type="text" class="form-control d-none" name="{{ $vociExt[$h2].' curr' }}" value="0" required />
                                    </div>
                                    <div class="col-2 headingNumberTwo">
                                        0 €
                                        <input type="text" class="form-control d-none" name="{{ $vociExt[$h2].' prev' }}" value="0" required />
                                    </div>
                                    @endif
                                    @foreach($header3 as $h3=>$header4)
                                    <div class="col-8 data-label text-truncate">
                                        <h3 style="padding-left:50px;">- {{ $h3 }}</h3>
                                    </div>
                                    @if(isset($support3[$vociExt[$h3]]))
                                    <div class="col-2 headingNumberThree">
                                        {{number_format($support3[$vociExt[$h3]]['current'],0,',','.')}} €
                                        <input type="text" class="form-control d-none" name="{{ $vociExt[$h3].' curr' }}" value="{{number_format($support3[$vociExt[$h3]]['current'],0,',','.')}}" required />
                                    </div>
                                    <div class="col-2 headingNumberThree">
                                        {{ number_format($support3[$vociExt[$h3]]['prev'],0,',','.') }} €
                                        <input type="text" class="form-control d-none" name="{{ $vociExt[$h3].' prev' }}" value="{{ number_format($support3[$vociExt[$h3]]['prev'],0,',','.') }}" required />
                                    </div>
                                    @else
                                    <div class="col-2 headingNumberThree">
                                        0 €
                                        <input type="text" class="form-control d-none" name="{{ $vociExt[$h3].' curr' }}" value="0" required />
                                    </div>
                                    <div class="col-2 headingNumberThree">
                                        0 €
                                        <input type="text" class="form-control d-none" name="{{ $vociExt[$h3].' prev' }}" value="0" required />
                                    </div>
                                    @endif
                                    @foreach($header4 as $h4=>$header5)
                                    <div class="col-8 data-label text-truncate">
                                        <h4 style="padding-left:75px;">- {{ $h4 }}</h4>
                                    </div>
                                    @if(isset($support3[$vociExt[$h4]]))
                                    <div class="col-2 headingNumberFour">
                                        {{number_format($support3[$vociExt[$h4]]['current'],0,',','.')}} €
                                        <input type="text" class="form-control d-none"  name="{{ $vociExt[$h4].' curr' }}" value="{{number_format($support3[$vociExt[$h4]]['current'],0,',','.')}}" required />
                                    </div>
                                    <div class="col-2 headingNumberFour">
                                        {{ number_format($support3[$vociExt[$h4]]['prev'],0,',','.') }} €
                                        <input type="text" class="form-control d-none" name="{{ $vociExt[$h4].' prev' }}" value="{{ number_format($support3[$vociExt[$h4]]['prev'],0,',','.') }}" required />
                                    </div>
                                    @else
                                    <div class="col-2 headingNumberFour">
                                        0 €
                                        <input type="text" class="form-control d-none" name="{{ $vociExt[$h4].' curr' }}" value="0" required />
                                    </div>
                                    <div class="col-2 headingNumberFour">
                                        0 €
                                        <input type="text" class="form-control d-none" name="{{ $vociExt[$h4].' prev' }}" value="0" required />
                                    </div>
                                    @endif
                                    @foreach($header5 as $h5=>$h6)
                                    <div class="col-8 data-label text-truncate">
                                        <h6 style="padding-left:100px;">- {{ $h5 }}</h6>
                                    </div>
                                    @if(isset($support3[$vociExt[$h5]]))
                                    <div class="col-2 headingNumberFive">
                                        {{number_format($support3[$vociExt[$h5]]['current'],0,',','.')}} €
                                        <input type="text" class="form-control d-none" name="{{ $vociExt[$h5].' curr' }}" value="{{number_format($support3[$vociExt[$h5]]['current'],0,',','.')}}" required />
                                    </div>
                                    <div class="col-2 headingNumberFive">
                                        {{ number_format($support3[$vociExt[$h5]]['prev'],0,',','.') }} €
                                        <input type="text" class="form-control d-none" name="{{ $vociExt[$h5].' prev' }}" value="{{ number_format($support3[$vociExt[$h5]]['prev'],0,',','.') }}" required />
                                    </div>
                                    @else
                                    <div class="col-2 headingNumberFive">
                                        0 €
                                        <input type="text" class="form-control d-none" name="{{ $vociExt[$h5].' curr' }}" value="0" required />
                                    </div>
                                    <div class="col-2 headingNumberFive">
                                        0 €
                                        <input type="text" class="form-control d-none" name="{{ $vociExt[$h5].' prev' }}" value="0" required />
                                    </div>
                                    @endif

                                    @endforeach
                                    @endforeach
                            @endforeach
                    @endforeach
                </div>
                @endforeach

            </div>
        </x-slot>


    </x-backend.card>
</x-forms.post>

<style>
    .col-* {
        background: black;
    }
    .datibilancio > [class^='col-'] { /* class name starts with col-lg */
        background: #edf8ff;
        margin-bottom:5px;
    }

    .datibilancio > [class*='headingNumber'] { /* class name starts with col-lg */
      padding-top:5px;
    }

    .datibilancio .headingNumberOne {
        text-align: center;
        font-size: 1.5rem;
        font-weight: bold;
        padding-top:10px;
    }

    .datibilancio .headingNumberTwo {
        text-align: center;
        font-size: 1.4rem;
        font-weight: 600;
    }

    .datibilancio .headingNumberThree {
        text-align: center;
        font-size: 1.4rem;
        font-weight: 400;
    }

    .datibilancio .headingNumberFour {
        text-align: center;
        font-size: 1.5rem;
        font-weight: 400;
    }

    .datibilancio .headingNumberFive {
        text-align: center;
        font-size: 1.2rem;
        font-weight: 400;
    }
    h6 {
        font-size:1.2rem;
        margin-bottom:0;
    }
    .yearHeading {
        color: #b30c0c;
        background: #f9f9f9;
        font-family: 'Roboto';
    }
    .datibilancio .bg-grey {
        background: #3c4c64;

        color: white;
    }
</style>
@endsection
