@inject('model', '\App\Models\Account')

@extends('backend.layouts.app')

@section('title', __('Analisi Centrale Rischi'))

@section('content')

    <?php

    $totAcc = array();
    $totUti = array();
    foreach ($soldini as $anno=>$banca){
        $totAcc[$anno] = 0;
        $totUti[$anno] = 0;
        foreach ($banca as $data){
            $totAcc[$anno] += $data['Totale Accordato Operativo'];
            $totUti[$anno] += $data['Totale Utilizzato'];
        }
    }
    //    dd($totAcc, $totUti);

    $percentuali = array();

    foreach ($soldini as $anno=>$banca){
        foreach ($banca as $data=>$value){
//            dd($anno, $banca);
            $var1 = $value['Totale Utilizzato'];
            $var2 = $totUti[$anno];
            $var3 = $value['Totale Accordato Operativo'];
            $var4 = $totAcc[$anno];
            $percentuali[$anno][$data]['Utilizzato']= ($var1/$var2)*100;
            $percentuali[$anno][$data]['Accordato']= ($var3/$var4)*100;
        }
    }
    $datiUtilizzati =  array();
    $datiAccordato = array();

    foreach ($percentuali as $anno=>$banca){
        $count = 0;
        foreach ($banca as $data=>$value) {
            $dataUtilizzati[$anno][$count] = array("label"=>$data, "y"=>$value['Utilizzato']);
            $dataAccordato[$anno][$count] = array("label"=>$data, "y"=>$value['Accordato']);
            $count++;
        }
    }



    $dataPoints = array(
        array("label"=>"Chrome", "y"=>64.02),
        array("label"=>"Firefox", "y"=>12.55),
        array("label"=>"IE", "y"=>8.47),
        array("label"=>"Safari", "y"=>6.08),
        array("label"=>"Edge", "y"=>4.29),
        array("label"=>"Others", "y"=>4.59)
    );
    ksort($dataAccordato, SORT_STRING);

    $anni = array();

    foreach ($dataAccordato as $anno=>$banca){
        $anni[] = $anno;
    }
    ?>


    <div class="panel panel-default">
        <div class="panel-heading clearfix">
            <div class="pull-right">
            </div>
        </div>
        <div class="panel-body">
            <ul class="nav nav-pills mb-3" id="pills-tab" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" id="pills-home-tab" data-toggle="pill" href="#pills-home" role="tab" aria-controls="pills-home" aria-selected="true">Tutti i Dati</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="pills-profile-tab" data-toggle="pill" href="#pills-profile" role="tab" aria-controls="pills-profile" aria-selected="false">Totale Accordato/Utilizzato</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="pills-contact-tab" data-toggle="pill" href="#pills-contact" role="tab" aria-controls="pills-contact" aria-selected="false">Analisi Sconfini</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="pills-contact-tab" data-toggle="pill" href="#pills-linee" role="tab" aria-controls="pills-contact" aria-selected="false">Tensioni Linee di Credito</a>
                </li>
            </ul>
            <div class="tab-content" id="pills-tabContent">
                <div class="tab-pane fade show active" id="pills-linee" role="tabpanel" aria-labelledby="pills-home-tab">

                    <?php foreach($tensioniLine as $singleYear => $risksArray) { ?>
                    <h2>Tensioni sulle linee di credito nel <?php echo $singleYear; ?> </h2>
                    <table class="table" style="font-size: 11px;">
                        <tbody>


                        <?php   foreach($risksArray as $riskName => $value) { ?>
                        <tr>
                            <td> <?php echo $riskName; ?></td>
                            <td> <?php echo 'Si'; ?></td>
                        </tr>
                        <?php } ?>


                        </tbody>
                    </table>


                    <?php } ?>
                </div>
                <div class="tab-pane fade show active" id="pills-home" role="tabpanel" aria-labelledby="pills-home-tab">

                    <?php

                    $columns = array('Localizzazione');
                    ?>
                    <?php foreach($cleanCR as $singleYear => $months) { ?>
                    <?php foreach($months as $singleMonth => $banks) { ?>
                    <?php foreach($banks as $singleBank => $creditType) { ?>
                    <div id="accordion">
                        <div class="card">
                            <div class="card-header" id="<?php echo $singleYear.' - '.$singleMonth.' - '.$singleBank; ?>">
                                <h5 class="mb-0">
                                    <button class="btn btn-link" data-toggle="collapse" data-target="#<?php echo md5($singleYear.'-'.$singleMonth.'-'.$singleBank); ?>" aria-expanded="false" aria-controls="<?php echo md5($singleYear.'-'.$singleMonth.'-'.$singleBank); ?>">
                                        <?php echo $singleYear.' - '.$singleMonth.' - '.$singleBank; ?>
                                    </button>
                                </h5>
                            </div>
                            <div id="<?php echo md5($singleYear.'-'.$singleMonth.'-'.$singleBank); ?>" class="collapse show" aria-labelledby="headingOne" data-parent="#accordion">
                                <div class="card-body">
                                    <?php foreach($creditType as $singleCredit => $singleRow) {
                                    if($singleCredit == 'Cassa' || 1==1) {




                                    foreach($singleRow as $singleDataHead => $singleDataBody) {


                                    ?>



                                    <table class="table" style="font-size: 11px;">
                                        <thead class="thead-dark">
                                        <tr>
                                            <?php   foreach($singleDataBody as $singleheading => $singleData) { ?>
                                            <th scope="col"><?php echo $singleheading; ?></th>

                                            <?php } ?>
                                        </tr>
                                        </thead>
                                        <tbody>

                                        <tr>
                                            <?php   foreach($singleDataBody as $singleheading => $singleData) { ?>
                                            <td> <?php
                                                if(!is_array($singleData)) {


                                                echo $singleData; ?></td>
                                            <?php }} ?>
                                        </tr>

                                        </tbody>
                                    </table>



                                    <?php
                                    }?>
                                    <?php } ?>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php } ?>
                    <?php } ?>
                    <?php } ?>
                </div>
                <div class="tab-pane fade" id="pills-profile" role="tabpanel" aria-labelledby="pills-profile-tab">

                    <script src="https://canvasjs.com/assets/script/canvasjs.min.js"></script>

                    @for($i = 1; $i<=count($anni); $i++)
                        <div id="chartContainerAccordato<?=$i?>" style="height: 370px; width: 100%"></div>
                        <div id="chartContainerUtilizzato<?=$i?>" style="height: 370px; width: 100%"></div>
                    @endfor

                    <script>

                        let anni = <?=json_encode($anni)?>;

                        console.log(anni);

                        let percentualiAccordato = [];
                        let percentualiUtilizzato = [];

                        percentualiAccordato = <?=json_encode($dataAccordato)?>;
                        percentualiUtilizzato = <?=json_encode($dataUtilizzati)?>;
                        console.log(percentualiAccordato, percentualiUtilizzato);

                        for(var i = 0; i<anni.length; i++){
                            console.log(percentualiAccordato[anni[i]]);
                            var chart = new CanvasJS.Chart("chartContainerAccordato".concat(i+1), {
                                animationEnabled: true,
                                title: {
                                    text: "Totale Accordato per banca ".concat(anni[i])
                                },
                                subtitles: [{
                                    text: ""
                                }],
                                data: [{
                                    type: "pie",
                                    yValueFormatString: "#,##0.00\"%\"",
                                    indexLabel: "{label} ({y})",
                                    dataPoints: percentualiAccordato[anni[i]]
                                }]
                            });
                            chart.render();
                        }

                        for(var i = 0; i<anni.length; i++){
                            console.log(percentualiUtilizzato[anni[i]]);
                            var chart = new CanvasJS.Chart("chartContainerUtilizzato".concat(i+1), {
                                animationEnabled: true,
                                title: {
                                    text: "Totale Utilizzato per banca ".concat(anni[i])
                                },
                                subtitles: [{
                                    text: ""
                                }],
                                data: [{
                                    type: "pie",
                                    yValueFormatString: "#,##0.00\"%\"",
                                    indexLabel: "{label} ({y})",
                                    dataPoints: percentualiUtilizzato[anni[i]]
                                }]
                            });
                            chart.render();
                        }

                    </script>

                    <?php foreach($soldini as $singleYear => $banks) { ?>

                    <h2><?php echo $singleYear; ?></h2>
                    <hr>
                    <table class="table">
                        <thead class="thead-dark">
                        <tr>
                            <th scope="col">Banche</th>
                            <th scope="col">Totale Accordato Operativo</th>
                            <th scope="col">Totale Utilizzato</th>
                            <th scope="col"></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach($banks as $singleBank => $risk) { ?>
                        <tr>
                            <th scope="row"><?php echo $singleBank; ?></th>
                            <?php foreach($risk as $singlerisk => $value) {   ?>
                            <?php if($singlerisk == 'Totale Accordato Operativo' || $singlerisk == 'Totale Utilizzato' ) { ?>
                            <td>€ <?php echo $value; ?></td>
                            <?php }
                            } ?>
                        </tr>
                        <?php } ?>
                        </tbody>
                    </table>
                    <hr>
                    <?php } ?>

                </div>
                <div class="tab-pane fade" id="pills-contact" role="tabpanel" aria-labelledby="pills-contact-tab">


                    <?php foreach($sconfiniGeneral as $singleYear => $banks) { ?>


                    <?php foreach($banks as $singleBank => $risk) { ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead class="thead-dark">
                            <tr>
                                <th scope="col"><?php echo $singleYear.' - '.$singleBank; ?></th>
                                <th scope="row"><?php echo 'Totale Sconfini'; ?></th>
                            </tr>
                            </thead>
                            <tbody>

                            <?php foreach($risk as $singlerisk => $value) {   ?>
                            <tr>
                                <?php foreach($value as $tiskCategory => $value) {   ?>
                                <td><?php echo $singlerisk; ?></td>
                                <td> <?php echo $value; ?></td>
                            </tr>
                            <?php     } ?>
                            <?php    } ?>


                            </tbody>
                        </table>
                    </div>
                    <?php } ?>
                    <hr>
                    <?php } ?>



                </div>

            </div>

















        </div>
    </div>


@endsection
