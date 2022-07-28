@inject('model', '\App\Domains\Auth\Models\User')

@extends('backend.layouts.app')

@section('title', __('Create User'))

@section('content')

@php

if(isset($alerts)){
// dd($sessionData, $fields);
$campiAnagrafici = $sessionData['campiAnagrafici'];
$formaGiuridica = $sessionData['formaGiuridica'];
$tipiAziende = $sessionData['tipiAziende'];
$gradi = $sessionData['gradi'];
$vociExt = $sessionData['vociExt'];
$campiAnnoPrecedente = $sessionData['campiAnnoPrecedente'];
// dd($campiAnagrafici, $fields);
}

$voices = array();
if(isset($required)) {
foreach($required as $singleRequiredVoice){
$voices[$singleRequiredVoice['extended_name']] = $singleRequiredVoice['name'];
}
}
// dd($gradi);

@endphp

<x-forms.post :action="route('admin.bilanci.bilancio.storeProvvisorio')">
    <x-backend.card>
        <x-slot name="header">
            @lang('Create User')
        </x-slot>

        <x-slot name="headerActions">
            <x-utils.link class="card-header-action" :href="route('admin.auth.user.index')" :text="__('Cancel')" />
        </x-slot>

        <x-slot name="body">
            <div x-data="{userType : '{{ $model::TYPE_USER }}'}">

                @if(isset($alerts))
                <div class="alert alert-danger" role="alert">
                    Attenzione!!! Ricontrollare le seguenti voci (puoi comunque scegliere di caricare l'XBRL):
                    <br>
                    <small>Clicca sulla voce di bilancio per navigare fino al campo da correggere</small>
                    <br>
                    @foreach($alerts as $singleVoice=>$singleAlert)
                    <span style="background-color: white; color:red; border-radius: 70px; padding: 2px 4.5px 2px 4.5px; margin: 1px 2px 1px 2px" onclick="scroller('{{$singleAlert}}')">{{$singleVoice}}</span> <br>
                    @endforeach
                </div>
                @endif
                <hr>
                <h1 class="text-center">Dati Anagrafici</h1>
                <hr>
                <div class="form-group row">
                    <div class="col-md-6 data-label">
                        Periodo di riferimento
                    </div>
                    <div class="col-md-3 ">
                        <input type="date" class="form-control" required name="period_start" value="@if(isset($fields)){{$fields['period_start']}}@endif" />
                    </div>
                    <div class="col-md-3 ">
                        <input type="date" class="form-control" required name="period_end" value="@if(isset($fields)){{$fields['period_end']}}@endif" />
                    </div>
                </div>
                @foreach ($campiAnagrafici as $data => $value)
                <div class="form-group row">
                    <div class="col-md-6 data-label anagrafic-label">
                        {{$data}}
                    </div>
                    <div class="col-md-6">
                        <input required style="background-color: #fff; text-align: center" type="text" class="form-control anagrafic-label" name="{{ $value.' anag' }}" value="@if(isset($alerts)){{$fields[($value).'_anag']}}@endif" />
                    </div>
                </div>
                @endforeach


                <div class="form-group row">
                    <div class="col-md-6 data-label anagrafic-label">
                        Tipo Azienda
                    </div>
                    <div class="col-md-6">
                        <select class="form-control" id="tipo_azienda" name="tipo_azienda" required="true">
                            <option value="" style="display: none;" disabled @if(!isset($alerts)) selected @endif>Seleziona il tipo di azienda</option>
                            @foreach($tipiAziende as $tipo)
                            <option @if(isset($fields) && $tipo==$fields['tipo_azienda']) selected @endif value="{{$tipo}}">
                                {{$tipo}}
                            </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="form-group row">
                    <div class="col-md-6 data-label anagrafic-label">
                        Forma Giuridica
                    </div>
                    <div class="col-md-6">
                        <select class="form-control" id="forma_giuridica" name="forma_giuridica">
                            <option value="" style="display: none;" disabled @if(!isset($alerts)) selected @endif>Seleziona la forma giuridica</option>
                            @foreach($formaGiuridica as $forma)
                            <option @if(isset($fields) && $forma==$fields['forma_giuridica']) selected @endif value="{{$forma}}">
                                {{$forma}}
                            </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <hr>
                <h1 class="text-center">Voci di Bilancio</h1>
                <hr>



                @foreach($gradi as $header1=>$header2)
                <div class="form-group row">

                    @if($header1 == 'Conto economico')
                    <div id="{{$vociExt[$header1]}}" class="col-md-6 data-label">
                        <h1>{{ $header1 }}</h1>
                    </div>
                    <div class="col-md-6 ">
                    </div>
                    @else

                    @if(isset($voices[$vociExt[$header1]]))
                    <div id="{{$vociExt[$header1]}}" class="col-md-6 data-label">
                        <h1>{{ $header1 }}</h1>
                    </div>
                    <div class="col-md-6 ">
                        <input type="text" class="form-control" required name="{{ $vociExt[$header1].' curr' }}" value="@if(isset($fields)){{$fields[($vociExt[$header1].'_curr')]}} @else 0 @endif" />
                    </div>
                    @endif
                    @endif
                    
                    @foreach($header2 as $h2=>$header3)

                    @if(isset($voices[$vociExt[$h2]]))
                    <div id="{{$vociExt[$h2]}}" class="col-md-6 data-label">
                        <h2>{{ $h2 }}</h2>
                    </div>
                 {{--   <!-- @dd($header2) -->  --}}
                     <div class="col-md-6 ">
                        <input required type="text" class="form-control" name="{{ $vociExt[$h2].' curr' }}" value="@if(isset($fields)){{$fields[($vociExt[$h2].'_curr')]}} @else 0 @endif" />
                    </div>
                    @endif
                    @foreach($header3 as $h3=>$header4)
                    @if(isset($voices[$vociExt[$h3]]))

                    <div id="{{$vociExt[$h3]}}" class="col-md-6 data-label">
                        <h3>{{ $h3 }}</h3>
                    </div>
                    

                    <div class="col-md-6 ">
                        <input required type="text" class="form-control" name="{{ $vociExt[$h3].' curr' }}" value="@if(isset($fields)){{$fields[($vociExt[$h3].'_curr')]}} @else 0 @endif" />
                    </div>
                    @endif
                      @foreach($header4 as $h4=>$header5)
                    @if(isset($voices[$vociExt[$h4]]))

                    <div id="{{$vociExt[$h4]}}" class="col-md-6 data-label">
                        <h4>{{ $h4 }}</h4>
                    </div>

                    <div class="col-md-6">
                        <input required type="text" class="form-control" id="{{ $vociExt[$h4].' curr' }}" name="{{ $vociExt[$h4].' curr' }}" value="@if(isset($fields)){{$fields[($vociExt[$h4].'_curr')]}} @else 0 @endif" />
                    </div>
                    @endif
                   @foreach($header5 as $h5=>$h6)

                    @if(isset($voices[$vociExt[$h5]]))
                    <div id="{{$vociExt[$h5]}}" class="col-md-6 data-label">
                        <h6>{{ $h5 }}</h6>
                    </div>
                    <div class="col-md-6 " >
                        <input required type="text" class="form-control" id="{{ $vociExt[$h5].' curr' }}" name="{{ $vociExt[$h5].' curr' }}" value="@if(isset($fields)){{$fields[($vociExt[$h5].'_curr')]}} @else 0 @endif" />
                    </div>
                    @endif
                    @endforeach 
                         @endforeach  
                    @endforeach
                    @endforeach
                </div>
                @endforeach
                <hr>
                <h1>Voci di bilancio relative all'anno precedente</h1>
                <hr>
                @foreach($campiAnnoPrecedente as $nome=>$nomeEsteso)
                <div class="form-group row">
                    <div class="col-md-6 data-label">
                        <h3>{{$nome}}</h3>
                    </div>

                    <div class="col-md-6 ">
                        <input required type="text" class="form-control" name="{{ $nomeEsteso.' prev' }}" value="@if(isset($fields)){{$fields[($nomeEsteso.'_prev')]}} @else 0 @endif" />
                    </div>
                </div>
                @endforeach
            </div>
            @if(isset($alerts))
            <input type="hidden" name="ignoreAlert" value="1">
            @endif
        </x-slot>

        <x-slot name="footer">
            <button data-toggle="tooltip" title="Una volta inseriti tutti i dati, puoi inviarli per l'analisi cliccando su questo tasto." class="btn btn-sm btn-primary float-right" type="submit">Importa Bilancio</button>
        </x-slot>
    </x-backend.card>
</x-forms.post>

<script>
    function scroller(voiceName) {
        const element = document.querySelector('#' + voiceName)
        console.log(element)
        const topPos = element.getBoundingClientRect().top + window.pageYOffset - 80
        window.scrollTo({
            top: topPos,
            behavior: 'smooth'
        })
    }

    function toCurrency(obj) {
        if (obj.value.includes('.')) {
            let x = parseInt(obj.value.replace('.', ''), 10)
            if (!isNaN(x)) {
                obj.value = x.toLocaleString('it-IT')
            }
        } else {
            let x = parseInt(obj.value, 10)
            if (!isNaN(x)) {
                obj.value = x.toLocaleString('it-IT')
            }
        }
    }

        /*Nasconde voce crediti verso altri */ 
    var div = document.getElementById('ImmobilizzazioniFinanziarieCreditiVersoAltriTotaleCreditiVersoAltri');
div.getElementsByTagName('h6')[0].innerText = '';

        /*Nasconde input voce crediti verso altri */ 
    var div = document.getElementById('ImmobilizzazioniFinanziarieCreditiVersoAltriTotaleCreditiVersoAltri curr');
        div.style.display = "none";

        /*Nasconde voce altre */ 
    var div = document.getElementById('PatrimonioNettoAltreRiserveDistintamenteIndicateVarieAltreRiserve');
div.getElementsByTagName('h4')[0].innerText = '';

        /*Nasconde input voce altre */ 
    var div = document.getElementById('PatrimonioNettoAltreRiserveDistintamenteIndicateVarieAltreRiserve curr');
        div.style.display = "none";

        /*Nasconde voce Crediti entro 12 mesi */ 
        var div = document.getElementById('CreditiEsigibiliEntroEsercizioSuccessivo');
div.getElementsByTagName('h4')[0].innerText = '';

        /*Nasconde input voce Crediti entro 12 mesi */ 
    var div = document.getElementById('CreditiEsigibiliEntroEsercizioSuccessivo curr');
        div.style.display = "none";

        /*Nasconde voce Crediti oltre 12 mesi */ 
    var div = document.getElementById('CreditiEsigibiliOltreEsercizioSuccessivo');
div.getElementsByTagName('h4')[0].innerText = '';

        /*Nasconde input voce Crediti oltre 12 mesi */ 
    var div = document.getElementById('CreditiEsigibiliOltreEsercizioSuccessivo curr');
        div.style.display = "none";
</script>

@endsection