@inject('model', '\App\Domains\Auth\Models\User')

@extends('backend.layouts.app')

@section('title', __('Create User'))

@section('content')

<script>

function toCurrency(obj){
    if(obj.value.includes('.')){
        let x = parseInt(obj.value.replace('.',''), 10)
        if(!isNaN(x)){
        obj.value = x.toLocaleString('it-IT')
        }
    }
    else{
        let x = parseInt(obj.value, 10)
        if(!isNaN(x)){
        obj.value = x.toLocaleString('it-IT')
        }
    }
}

</script>

    <x-forms.post :action="route('admin.analysis.bilanci.store')">
        <x-backend.card>
            <x-slot name="header">
            </x-slot>

            <x-slot name="headerActions">
            </x-slot>

            <x-slot name="body">
                <div x-data="{userType : '{{ $model::TYPE_USER }}'}">
                    <input type="hidden" name="formaGiuridica" value="{{$formaGiuridica}}">
                    <input type="text"  class="form-control d-none"   name="currYear" value="{{ $currentYear }}"  required />
                    <input type="text"  class="form-control d-none"   name="prevYear" value="{{ $prevYear }}"  required />
                    @if(!isset($alerts))

                    <div class="alert alert-danger" role="alert">
                        Prima di inoltrare il bilancio verificare che tutti i valori necessari siano riportati all’interno della maschera sottostante.
                      </div>
                      @endif
                      @if(isset($alerts))

                      <div class="alert alert-danger" role="alert">
                        Attenzione!!! Ricontrollare le seguenti voci (puoi comunque scegliere di caricare l'XBRL):
                        <br>
                        <small>Clicca sulla voce di bilancio per navigare fino al campo da correggere</small>
                        <br>
                        @foreach($alerts as $singleVoice=>$singleAlert)
                        <span style="background-color: white; color:red; border-radius: 70px; padding: 2px 4.5px 2px 4.5px; margin: 1px 2px 1px 2px"  onclick="scroller('{{$singleAlert}}')" >{{$singleVoice}}</span> <br>
                        @endforeach
                      </div>
                      @endif
                    <div class="card card-collapsed  overflow-hidden">
                        <div class="card-header border-bottom-0">
                            <h3 class="card-title">Maschera inserimento valori mancanti</h3>
                            <div class="card-options">
                                <a href="#" class="card-options-collapse mr-2" data-toggle="card-collapse"><i class="fe fe-chevron-up"></i></a>
                                <a href="#" class="card-options-remove" data-toggle="card-remove"><i class="fe fe-x"></i></a>
                            </div>
                        </div>
                        <div class="card-body">

                            <div class="form-group row">
                                <div class="col-md-8 text-center round-label-left"></div>
                                <div class="col-md-2 text-center round-label-left">
                                    {{ str_replace(' ', ' / ',$currentYear) }}
                                </div>
                                <div class="col-md-2 text-center round-label-right">
                                    {{ str_replace(' ', ' / ',$prevYear) }}
                                </div>
                            </div>
                            <?php
                            foreach($mascheraOrdinata as $index=>$fieldName){
                                if(isset($extNames[$fieldName])){ ?>
                                <div class="form-group row">
                                <div class="col-md-8 data-label">
                                    <h3>{{ $fieldName }}</h3>
                                </div>
                                <div class="col-md-2 ">
                                    <input type="text" onchange="toCurrency(this)" class="form-control"  name="{{ $extNames[$fieldName].' curr' }}" value="0"  required />
                                </div>
                                <div class="col-md-2 ">
                                    <input type="text" onchange="toCurrency(this)" class="form-control"  name="{{ $extNames[$fieldName].' prev' }}" value="0"  required />
                                </div>
                                </div>
                                <?php
                                }
                            }
                            ?>
                        </div>
                    </div>



                    <hr>
                    <h1 class="text-center">Dati Anagrafici</h1>
                    <hr>

                    @foreach ($jsonData['anagrafic'] as $data => $value)
                        @if(isset($value) && $value != "")
                        <div class="form-group row">
                            <div class="col-md-6 data-label anagrafic-label">
                                <h3>
                                <?php
                                $arr = preg_split('/(?=[A-Z])/', $data);
                                $arr = array_unique($arr);

                                $string = "";

                                foreach ($arr as $dat=>$val){
                                    $string.=$val.' ';
                                }
                                echo $string;
                                ?>
                                </h3>
                            </div>
                            <div class="col-md-6">
                                <input style="background-color: #fff; text-align: center" type="text"  class="form-control anagrafic-label" readonly name="{{ $data.' anag' }}" value="{{ $value }}"  required />
                            </div>
                        </div>
                        @endif
                    @endforeach


                    <div class="form-group row">
                        <div class="col-md-6 data-label anagrafic-label">
                            <h3>Tipo Azienda</h3>
                        </div>
                        <div class="col-md-6">
                            <input style="background-color: #fff; text-align: center" type="text"  class="form-control anagrafic-label" readonly name="tipo_azienda" value="{{ $tipo_azienda }}"  required />
                        </div>
                    </div>

                    <hr>
                    <h1 class="text-center">Voci di Bilancio</h1>
                    <hr>

                    <div class="form-group row">
                        <div class="col-md-8 text-center round-label-left"></div>
                        <div class="col-md-2 text-center round-label-left">
                            {{ str_replace(' ', ' / ',$currentYear) }}
                        </div>
                        <div class="col-md-2 text-center round-label-right">
                            {{ str_replace(' ', ' / ',$prevYear) }}
                        </div>

                    </div>

                @foreach($gradi as $header1=>$header2)
                        <div class="form-group row">

                            @if(isset($support3[$vociExt[$header1]]) || $header1 == 'Conto economico')
                                @if($header1 == 'Conto economico')
                                @else
							    <div class="col-md-8 data-label">
                                <h1 id="{{$vociExt[$header1]}}">{{ $header1 }}</h1>
                            </div>
                                    <div class="col-md-2 ">
                                        <input type="text"  onchange="toCurrency(this)" class="form-control"  name="{{ $vociExt[$header1].' curr' }}" value="{{number_format($support3[$vociExt[$header1]]['current'],0,',','.')}}"  required />
                                    </div>
                                    <div class="col-md-2 ">
                                        <input type="text"  onchange="toCurrency(this)" class="form-control"  name="{{ $vociExt[$header1].' prev' }}" value="{{number_format($support3[$vociExt[$header1]]['prev'],0,',','.') }}"  required />
                                    </div>
                                @endif

                            @endif
                            @foreach($header2 as $h2=>$header3)

                                @if(isset($support3[$vociExt[$h2]]))
							                 <div class="col-md-8 data-label">
                                    <h2 id="{{$vociExt[$h2]}}" >{{ $h2 }}</h2>
                                </div>
                                    <div class="col-md-2 ">
                                        <input type="text"  onchange="toCurrency(this)" class="form-control"  name="{{ $vociExt[$h2].' curr' }}" value="{{ number_format($support3[$vociExt[$h2]]['current'],0,',','.')}}"  required />
                                    </div>
                                    <div class="col-md-2 ">
                                        <input type="text"  onchange="toCurrency(this)" class="form-control"  name="{{ $vociExt[$h2].' prev' }}" value="{{ number_format($support3[$vociExt[$h2]]['prev'],0,',','.') }}"  required />
                                    </div>

                                @endif
                                @foreach($header3 as $h3=>$header4)

                                    @if(isset($support3[$vociExt[$h3]]))
							                     <div class="col-md-8 data-label">
                                        <h3 id="{{$vociExt[$h3]}}" >{{ $h3 }}</h3>
                                    </div>
                                        <div class="col-md-2 ">
                                            <input type="text"  onchange="toCurrency(this)" class="form-control"  name="{{ $vociExt[$h3].' curr' }}" value="{{ number_format($support3[$vociExt[$h3]]['current'],0,',','.')}}"  required />
                                        </div>
                                        <div class="col-md-2 ">
                                            <input type="text"  onchange="toCurrency(this)" class="form-control"  name="{{ $vociExt[$h3].' prev' }}" value="{{ number_format($support3[$vociExt[$h3]]['prev'],0,',','.') }}"  required />
                                        </div>

                                    @endif
                                    @foreach($header4 as $h4=>$header5)

                                        @if(isset($support3[$vociExt[$h4]]))
							           <div class="col-md-8 data-label">
                                            <h4 id="{{$vociExt[$h4]}}" >{{ $h4 }}</h4>
                                        </div>
                                            <div class="col-md-2 ">
                                                <input type="text"  onchange="toCurrency(this)" class="form-control"  name="{{ $vociExt[$h4].' curr' }}" value="{{ number_format($support3[$vociExt[$h4]]['current'],0,',','.')}}"  required />
                                            </div>
                                            <div class="col-md-2 ">
                                                <input type="text"  onchange="toCurrency(this)" class="form-control"  name="{{ $vociExt[$h4].' prev' }}" value="{{ number_format($support3[$vociExt[$h4]]['prev'],0,',','.') }}"  required />
                                            </div>

                                        @endif
                                        @foreach($header5 as $h5=>$h6)

                                            @if(isset($support3[$vociExt[$h5]]))
							       <div class="col-md-8 data-label">
                                                <h6 id="{{$vociExt[$h5]}}">{{ $h5 }}</h6>
                                            </div>
                                                <div class="col-md-2 ">
                                                    <input type="text"  onchange="toCurrency(this)" class="form-control"  name="{{ $vociExt[$h5].' curr' }}" value="{{ number_format($support3[$vociExt[$h5]]['current'],0,',','.')}}"  required />
                                                </div>
                                                <div class="col-md-2 ">
                                                    <input type="text"  onchange="toCurrency(this)"   class="form-control"  name="{{ $vociExt[$h5].' prev' }}" value="{{ number_format($support3[$vociExt[$h5]]['prev'],0,',','.') }}"  required />
                                                </div>

                                            @endif
                                        @endforeach
                                    @endforeach
                                @endforeach
                            @endforeach
                        </div>
                    @endforeach
                    @if(isset($alerts))
                        <input type="hidden" name="ignoreAlert" value="1">
                    @endif
                </div>
            </x-slot>

            <x-slot name="footer">
                <button class="btn btn-sm btn-primary float-right" type="submit">Importa Bilancio</button>
            </x-slot>
        </x-backend.card>
    </x-forms.post>

<script>

    function scroller(voiceName){
        const element = document.querySelector('#'+voiceName)
        const topPos = element.getBoundingClientRect().top + window.pageYOffset - 80

        window.scrollTo({
        top: topPos,
        behavior: 'smooth'
        })
    }

</script>

@endsection
