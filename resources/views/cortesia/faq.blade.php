@extends('backend.layouts.app')

@section('content')
<div class="page">
    <div class="page-main">
        <div class="main-content">
            <div class="container">
            <div class="text-center mt-5">
                <div class="page-leftheader">
                    <h4 class="page-title">FAQ | <span class="font-weight-normal text-muted ml-2" style="color: red!important;">Key Performance Softwares S.r.l.</span></h4>
                </div>
            </div>
            <div class="mt-5">
                        <!-- ---------------------------------- -->
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-body">
                            <div class="accordion accordion-flush" id="accordionFlushExample">
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="flush-headingOne">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#flush-collapseOne" aria-expanded="false" aria-controls="flush-collapseOne">
                                        Cosa è il formato .xbrl?
                                    </button>
                                    </h2>
                                    <div id="flush-collapseOne" class="accordion-collapse collapse" aria-labelledby="flush-headingOne" data-bs-parent="#accordionFlushExample">
                                    <div class="accordion-body">Con l’acronimo XBRL (Extensible Business Reporting Language) intendiamo un sistema standard di codifiche delle informazioni di bilancio. In Italia, ad eccezione di alcune tipologie di società - come le società quotate in mercati regolamentati, le società  che redigono il bilancio secondo i principi contabili IAS/IFRS, le società di assicurazione e riassicurazione, le banche e gli istituti finanziari, le società controllate dalle imprese sopraelencate, le società sono obbligate a depositare il loro bilancio definitivo presso la camera di commercio di appartenenza utilizzando il formato standard .xbrl che è tenuto a rispettare una determinata tassonomia.</div>
                                    </div>
                                </div>
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="flush-headingTwo">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#flush-collapseTwo" aria-expanded="false" aria-controls="flush-collapseTwo">
                                        Come posso reperire il bilancio in formato .xbrl?
                                    </button>
                                    </h2>
                                    <div id="flush-collapseTwo" class="accordion-collapse collapse" aria-labelledby="flush-headingTwo" data-bs-parent="#accordionFlushExample">
                                    <div class="accordion-body">Il bilancio in formato .xbrl viene generato in automatico dal software di contabilità utilizzato da chi si occupa della contabilità aziendale. Qualora la contabilità sia gestita da un consulente o da uno studio di commercialisti basterà richiedere a chi ha depositato il bilancio il file che è stato depositato.</div>
                                    </div>
                                </div>
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="flush-headingThree">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#flush-collapseThree" aria-expanded="false" aria-controls="flush-collapseThree">
                                        Posso analizzare anche un bilancio non depositato?
                                    </button>
                                    </h2>
                                    <div id="flush-collapseThree" class="accordion-collapse collapse" aria-labelledby="flush-headingThree" data-bs-parent="#accordionFlushExample">
                                    <div class="accordion-body">Sì, il software analizza qualsiasi file xbrl, anche se questo non è stato depositato presso la Camera di Commercio.</div>
                                    </div>
                                </div>
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="flush-headingFour">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#flush-collapseFour" aria-expanded="false" aria-controls="flush-collapseFour">
                                        Il sistema è in grado di analizzare anche il bilancio provvisorio?
                                    </button>
                                    </h2>
                                    <div id="flush-collapseFour" class="accordion-collapse collapse" aria-labelledby="flush-headingFour" data-bs-parent="#accordionFlushExample">
                                    <div class="accordion-body">Sì, è possibile analizzare anche il bilancio provvisorio. In questo caso i dati verranno inseriti manualmente dall’utente nella sezione apposita e dovrà essere indicata la data di riferimento del bilancio provvisorio.</div>
                                    </div>
                                </div>
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="flush-headingFive">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#flush-collapseFive" aria-expanded="false" aria-controls="flush-collapseFive">
                                        Come faccio a scaricare il file necessario all’analisi della Centrale rischi?
                                    </button>
                                    </h2>
                                    <div id="flush-collapseFive" class="accordion-collapse collapse" aria-labelledby="flush-headingFive" data-bs-parent="#accordionFlushExample">
                                    <div class="accordion-body">È possibile effettuare la richiesta on line, per utilizzare questa modalità sarà necessario identificarsi con SPID (Sistema Pubblico di Identità Digitale) o CNS (Carta Nazionale dei Servizi) e successivamente compilare e inoltrare la richiesta di accesso ai dati.
                                            https://arteweb.bancaditalia.it/arteweb-fe-web/cr In alternativa, qualora non disponesse di accesso tramite SPID o CNS, si potrà procedere all'invio della richiesta inviando una pec alla banca d'Italia di riferimento, contenente un modulo scaricabile dal sito di banca d’Italia e copia del documento del legale rappresentante.</div>
                                    </div>
                                </div>
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="flush-headingSix">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#flush-collapseSix" aria-expanded="false" aria-controls="flush-collapseSix">
                                        Quanti mesi deve prendere in considerazione la richiesta di Centrale Rischi?
                                    </button>
                                    </h2>
                                    <div id="flush-collapseSix" class="accordion-collapse collapse" aria-labelledby="flush-headingSix" data-bs-parent="#accordionFlushExample">
                                    <div class="accordion-body">La richiesta dovrebbe prendere in considerazione le ultime 36 rilevazioni ovvero gli ultimi 3 anni.</div>
                                    </div>
                                </div>
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="flush-headingSeven">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#flush-collapseSeven" aria-expanded="false" aria-controls="flush-collapseSeven">
                                        È possibile scaricare le analisi effettuate?
                                    </button>
                                    </h2>
                                    <div id="flush-collapseSeven" class="accordion-collapse collapse" aria-labelledby="flush-headingSeven" data-bs-parent="#accordionFlushExample">
                                    <div class="accordion-body">Sì, il sistema permette il download delle analisi effettuate e suggeriamo di archiviarle nell’eventualità di possibili controlli futuri sull’osservanza degli obblighi di legge.</div>
                                    </div>
                                </div>
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="flush-headingEight">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#flush-collapseEight" aria-expanded="false" aria-controls="flush-collapseEight">
                                        Qualora la mia azienda risultasse “a rischio” verrebbe automaticamente segnalata?
                                    </button>
                                    </h2>
                                    <div id="flush-collapseEight" class="accordion-collapse collapse" aria-labelledby="flush-headingEight" data-bs-parent="#accordionFlushExample">
                                    <div class="accordion-body">No, il software nasce per fornire all’azienda un sistema di monitoraggio interno all’azienda. Nel caso in cui l’azienda risultasse a rischio sarà cura della Direzione aziendale valutare le azioni di risanamento da intraprendere.</div>
                                    </div>
                                </div>
                                </div>
                        </div>
                    </div>
                </div>
            </div>
         </div>
    </div>
</div>
@endsection