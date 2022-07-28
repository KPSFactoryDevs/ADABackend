@inject('model', '\App\Domains\Auth\Models\User')

@extends('backend.layouts.app')

@section('title', __('Create User'))

@section('content')



<?php

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
            break;
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


<x-forms.post :action="route('admin.accounts.account.storebilancio')">
    <x-backend.card>
        <x-slot name="header">
            @lang('Create User')
        </x-slot>

        <x-slot name="headerActions">
            <x-utils.link class="card-header-action" :href="route('admin.auth.user.index')" :text="__('Cancel')" />
        </x-slot>

        <x-slot name="body">
            <div x-data="{userType : '{{ $model::TYPE_USER }}'}">


                <input type="text" class="form-control d-none" readonly name="currYear" value="{{ $currentYear }}" required />
                <input type="text" class="form-control d-none" readonly name="prevYear" value="{{ $prevYear }}" required />

                <div class="form-group row">
                    <div class="col-md-8 text-center round-label-left"> Voci di Bilancio</div>
                    <div class="col-md-2 text-center round-label-left">
                        {{ str_replace(' ', ' / ',$currentYear) }}
                    </div>
                    <div class="col-md-2 text-center round-label-right">
                        {{ str_replace(' ', ' / ',$prevYear) }}
                    </div>

                </div>

                @foreach ($jsonData['anagrafic'] as $data => $value)
                @if(isset($value) && $value != "")
                <div class="form-group row">
                    <div class="col-md-6 data-label anagrafic-label">
                        <?php
                        $arr = preg_split('/(?=[A-Z])/', $data);
                        $arr = array_unique($arr);

                        $string = "";

                        foreach ($arr as $dat => $val) {
                            $string .= $val . ' ';
                        }
                        echo $string;
                        ?>
                    </div>
                    <div class="col-md-6">
                        <input style="background-color: #fff; text-align: center" type="text" readonly class="form-control anagrafic-label" readonly name="{{ $data.' anag' }}" value="{{ $value }}" required />
                    </div>
                </div>
                @endif
                @endforeach

                <div class="form-group row">
                    <div class="col-md-6 data-label anagrafic-label">
                        Tipo Azienda
                    </div>
                    <div class="col-md-6">
                        <input style="background-color: #fff; text-align: center" type="text" readonly class="form-control anagrafic-label" readonly name="tipo_azienda" value="{{ $tipo_azienda }}" required />
                    </div>
                </div>

                @foreach($gradi as $header1=>$header2)
                <div class="form-group row">
                    <div class="col-md-8 data-label">
                        <h1>{{ $header1 }}</h1>
                    </div>
                    @if(isset($support3[$vociExt[$header1]]) || $header1 == 'Conto economico')
                    @if($header1 == 'Conto economico')
                    @else
                    <div class="col-md-2 ">
                        <input type="text" readonly class="form-control" name="{{ $vociExt[$header1].' curr' }}" value="{{ number_format($support3[$vociExt[$header1]]['current'],0,'.',',')}}" required /><span>€</span>
                    </div>
                    <div class="col-md-2 ">
                        <input type="text" readonly class="form-control" name="{{ $vociExt[$header1].' prev' }}" value="{{ number_format($support3[$vociExt[$header1]]['prev'],0,'.',',') }}" required /><span>€</span>
                    </div>
                    @endif
                    @else
                    <div class="col-md-2 ">
                        <input type="text" readonly class="form-control" name="{{ $vociExt[$header1].' curr' }}" value="0" required /><span>€</span>
                    </div>
                    <div class="col-md-2 ">
                        <input type="text" readonly class="form-control" name="{{ $vociExt[$header1].' prev' }}" value="0" required /><span>€</span>
                    </div>
                    @endif
                    @foreach($header2 as $h2=>$header3)
                    <div class="col-md-8 data-label">
                        <h2>{{ $h2 }}</h2>
                    </div>
                    @if(isset($support3[$vociExt[$h2]]))
                    <div class="col-md-2 ">
                        <input type="text" readonly class="form-control" name="{{ $vociExt[$h2].' curr' }}" value="{{number_format($support3[$vociExt[$h2]]['current'],0,'.',',')}}" required /><span>€</span>
                    </div>
                    <div class="col-md-2 ">
                        <input type="text" readonly class="form-control" name="{{ $vociExt[$h2].' prev' }}" value="{{ number_format($support3[$vociExt[$h2]]['prev'],0,'.',',') }}" required /><span>€</span>
                    </div>
                    @else
                    <div class="col-md-2 ">
                        <input type="text" readonly class="form-control" name="{{ $vociExt[$h2].' curr' }}" value="0" required /><span>€</span>
                    </div>
                    <div class="col-md-2 ">
                        <input type="text" readonly class="form-control" name="{{ $vociExt[$h2].' prev' }}" value="0" required /><span>€</span>
                    </div>
                    @endif
                    @foreach($header3 as $h3=>$header4)
                    <div class="col-md-8 data-label">
                        <h3>{{ $h3 }}</h3>
                    </div>
                    @if(isset($support3[$vociExt[$h3]]))
                    <div class="col-md-2 ">
                        <input type="text" readonly class="form-control" name="{{ $vociExt[$h3].' curr' }}" value="{{ number_format($support3[$vociExt[$h3]]['current'],0,'.',',')}}" required /><span>€</span>
                    </div>
                    <div class="col-md-2 ">
                        <input type="text" readonly class="form-control" name="{{ $vociExt[$h3].' prev' }}" value="{{ number_format($support3[$vociExt[$h3]]['prev'],0,'.',',') }}" required /><span>€</span>
                    </div>
                    @else
                    <div class="col-md-2 ">
                        <input type="text" readonly class="form-control" name="{{ $vociExt[$h3].' curr' }}" value="0" required /><span>€</span>
                    </div>
                    <div class="col-md-2 ">
                        <input type="text" readonly class="form-control" name="{{ $vociExt[$h3].' prev' }}" value="0" required /><span>€</span>
                    </div>
                    @endif
                    @foreach($header4 as $h4=>$header5)
                    <div class="col-md-8 data-label">
                        <h4>{{ $h4 }}</h4>
                    </div>
                    @if(isset($support3[$vociExt[$h4]]))
                    <div class="col-md-2 ">
                        <input type="text" readonly class="form-control" name="{{ $vociExt[$h4].' curr' }}" value="{{ number_format($support3[$vociExt[$h4]]['current'],0,'.',',')}}" required /><span>€</span>
                    </div>
                    <div class="col-md-2 ">
                        <input type="text" readonly class="form-control" name="{{ $vociExt[$h4].' prev' }}" value="{{ number_format($support3[$vociExt[$h4]]['prev'],0,'.',',') }}" required /><span>€</span>
                    </div>
                    @else
                    <div class="col-md-2 ">
                        <input type="text" readonly class="form-control" name="{{ $vociExt[$h4].' curr' }}" value="0" required /><span>€</span>
                    </div>
                    <div class="col-md-2 ">
                        <input type="text" readonly class="form-control" name="{{ $vociExt[$h4].' prev' }}" value="0" required /><span>€</span>
                    </div>
                    @endif
                    @foreach($header5 as $h5=>$h6)
                    <div class="col-md-8 data-label">
                        <h6>{{ $h5 }}</h6>
                    </div>
                    @if(isset($support3[$vociExt[$h5]]))
                    <div class="col-md-2 ">
                        <input type="text" readonly class="form-control" name="{{ $vociExt[$h5].' curr' }}" value="{{ number_format($support3[$vociExt[$h5]]['current'],0,'.',',')}}" required /><span>€</span>
                    </div>
                    <div class="col-md-2 ">
                        <input type="text" readonly class="form-control" name="{{ $vociExt[$h5].' prev' }}" value="{{ number_format($support3[$vociExt[$h5]]['prev'],0,'.',',') }}" required /><span>€</span>
                    </div>
                    @else
                    <div class="col-md-2 ">
                        <input type="text" readonly class="form-control" name="{{ $vociExt[$h5].' curr' }}" value="0" required /><span>€</span>
                    </div>
                    <div class="col-md-2 ">
                        <input type="text" readonly class="form-control" name="{{ $vociExt[$h5].' prev' }}" value="0" required /><span>€</span>
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

        <x-slot name="footer">
        </x-slot>
    </x-backend.card>
</x-forms.post>


@endsection
