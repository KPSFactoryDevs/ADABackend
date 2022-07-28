<footer class="footer">
    <div class="container">
        <div class="row align-items-center flex-row-reverse">
            <div class="col-md-12 col-sm-12 mt-3 mt-lg-0 text-center">
                Copyright © 2021 | Powered by <a  class="kps" href="https://kpsfactory.com" target="_blank">Key Performance Softwares S.r.l.</a> Tutti i diritti riservati.
            </div>
        </div>
    </div>

    <script>

        function DSCRyes(){
            var x = document.getElementById("calcoloDSCR")
            x.style.display = ""
            
            var y = document.getElementById("soglieIndici")
            y.style.display = "none"
        }

        function DSCRno(){
            var x = document.getElementById("soglieIndici")
            x.style.display = ""

            var y = document.getElementById("calcoloDSCR")
            y.style.display = "none"
        }

        function calcolaDSCR(){
            var disponibilitaLiquida = parseInt(document.getElementsByName("DSCRdispLiquida")[0].value)

            var entrataDSCRCFmese1 = parseInt(document.getElementsByName("entrataDSCRCFmese1")[0].value)
            var entrataDSCRCFmese2 = parseInt(document.getElementsByName("entrataDSCRCFmese2")[0].value)
            var entrataDSCRCFmese3 = parseInt(document.getElementsByName("entrataDSCRCFmese3")[0].value)
            var entrataDSCRCFmese4 = parseInt(document.getElementsByName("entrataDSCRCFmese4")[0].value)
            var entrataDSCRCFmese5 = parseInt(document.getElementsByName("entrataDSCRCFmese5")[0].value)
            var entrataDSCRCFmese6 = parseInt(document.getElementsByName("entrataDSCRCFmese6")[0].value)

            var uscitaDSCRCFmese1 = parseInt(document.getElementsByName("uscitaDSCRCFmese1")[0].value)
            var uscitaDSCRCFmese2 = parseInt(document.getElementsByName("uscitaDSCRCFmese2")[0].value)
            var uscitaDSCRCFmese3 = parseInt(document.getElementsByName("uscitaDSCRCFmese3")[0].value)
            var uscitaDSCRCFmese4 = parseInt(document.getElementsByName("uscitaDSCRCFmese4")[0].value)
            var uscitaDSCRCFmese5 = parseInt(document.getElementsByName("uscitaDSCRCFmese5")[0].value)
            var uscitaDSCRCFmese6 = parseInt(document.getElementsByName("uscitaDSCRCFmese6")[0].value)

            var rimborsoDSCRmese1 = parseInt(document.getElementsByName("rimborsoDSCRmese1")[0].value)
            var rimborsoDSCRmese2 = parseInt(document.getElementsByName("rimborsoDSCRmese2")[0].value)
            var rimborsoDSCRmese3 = parseInt(document.getElementsByName("rimborsoDSCRmese3")[0].value)
            var rimborsoDSCRmese4 = parseInt(document.getElementsByName("rimborsoDSCRmese4")[0].value)
            var rimborsoDSCRmese5 = parseInt(document.getElementsByName("rimborsoDSCRmese5")[0].value)
            var rimborsoDSCRmese6 = parseInt(document.getElementsByName("rimborsoDSCRmese6")[0].value)

            var DSCR = (disponibilitaLiquida + entrataDSCRCFmese1 +entrataDSCRCFmese2 + entrataDSCRCFmese3 + entrataDSCRCFmese4 + entrataDSCRCFmese5 + entrataDSCRCFmese6 - uscitaDSCRCFmese1 - uscitaDSCRCFmese2 - uscitaDSCRCFmese3 - uscitaDSCRCFmese4 - uscitaDSCRCFmese5 - uscitaDSCRCFmese6) / (rimborsoDSCRmese1 + rimborsoDSCRmese2 + rimborsoDSCRmese3 + rimborsoDSCRmese4 + rimborsoDSCRmese5 + rimborsoDSCRmese6)
        

            if(isNaN(DSCR)){
                DSCR = "Errore!"
            }

            var DSCRelement = document.getElementById("risultatoDSCR")

            var DSCRAlert = document.getElementById("AlertDSCR")

            if(!isNaN(DSCR)){
                if(DSCR > 1){
                    DSCRelement.innerText = DSCR.toFixed(3) 
                    var inputDSCR = document.getElementById("allertaDSCR")
                    inputDSCR.value = "Azienda non a rischio"
                    DSCRAlert.innerText = "Azienda non a rischio"
                }
                else{
                    DSCRelement.innerText = DSCR.toFixed(3) 
                    var inputDSCR = document.getElementById("allertaDSCR")
                    inputDSCR.value = "Azienda a rischio"
                    DSCRAlert.innerText = "Azienda a rischio"
                }
            }
        }

        function calcolaAgenziaEntrate(){
            var alert = "No"
            var agenziaEntrate1 = parseInt(document.getElementsByName("agenziaEntrate1")[0].value)
            var agenziaEntrate2 = parseInt(document.getElementsByName("agenziaEntrate2")[0].value)
            var agenziaEntrate3 = parseInt(document.getElementsByName("agenziaEntrate3")[0].value)
            var agenziaEntrate4 = document.getElementsByName("agenziaEntrate4")[0]

            if(!isNaN(agenziaEntrate1) && !isNaN(agenziaEntrate2)){
               if(agenziaEntrate1 >= ((agenziaEntrate2/100)*30)){
                    if(agenziaEntrate3 >= 0 && agenziaEntrate3 < 2000000){
                        if(agenziaEntrate1 >= 25000){
                            alert = "Si"
                        } 
                    }
                    else if(agenziaEntrate3 >= 2000000 && agenziaEntrate3 < 10000000){
                        if(agenziaEntrate1 >= 50000){
                            alert = "Si"
                        } 
                    }
                    else if(agenziaEntrate3 > 10000000){
                        if(agenziaEntrate1 > 100000){
                            alert = "Si"
                        } 
                    }
                }
            }

            if(!isNaN(agenziaEntrate1 / agenziaEntrate2)){
                if(isFinite(agenziaEntrate1 / agenziaEntrate2)){
                    agenziaEntrate4.value = ((agenziaEntrate1 / agenziaEntrate2)).toFixed(2)
                }
                else{
                    agenziaEntrate4.value = "0"
                }
            }

            var alertAgenziaEntrate = document.getElementById("allertaAgenziaEntrate")
            alertAgenziaEntrate.value = alert
        }

        function calcolaINPS(){
            var alert = "No"
            var inps1 = parseInt(document.getElementsByName("INPS1")[0].value)
            var inps2 = parseInt(document.getElementsByName("INPS2")[0].value)
            var inps3 = document.getElementsByName("INPS3")[0]

            var alertINPS = document.getElementById("allertaINPS")
            

            var nonVersatiFrattoTotali = (inps1 / inps2).toFixed(2)

            if(!isNaN(nonVersatiFrattoTotali)){
                inps3.value = ((inps1 / inps2)*100).toFixed(2)
            }
            else{
                inps3.value = ""
                alert = "No"
            }

            if(inps1 > 50000){
                if(inps3.value > 50.50){
                    alert = "Si"
                }
            }
            alertINPS.value = alert
        }

        function calcolaRiscossione(){
            formaGiuridica = "<?php if(isset($formaGiudirica)){ echo ($formaGiuridica); }?>"
            var riscossione = parseInt(document.getElementById("riscossione").value)
            var alert = "No"

            if(!isNaN(riscossione)){
                if(formaGiuridica == "DITTA INDIVIDUALE"){
                    if(riscossione > 500000){
                        alert = "Si"
                    }
                    else{
                        alert = "No"
                    }
                }
                else{
                    if(riscossione > 1000000){
                        alert = "Si"
                    }
                    else{
                        alert = "No"
                    }
                }
            }   

            var alertRiscossione = document.getElementById("allertaRiscossione")

            alertRiscossione.value = alert

        }

        function calcolaRetribuzione(){
            var alert = "No"

            var retribuzioni1 = parseInt(document.getElementsByName("retribuzioni1")[0].value)
            var retribuzioni2 = parseInt(document.getElementsByName("retribuzioni2")[0].value)
            var retribuzioni3 = document.getElementsByName("retribuzioni3")[0]
            var retribuzioniAlert = document.getElementById("allertaRetribuzioni")

            if(!isNaN(retribuzioni1)){
                if((retribuzioni1 / retribuzioni2)*100 >= 50){
                    alert = "Si"
                }
                else{
                    alert = "No"
                }
                if(isFinite(retribuzioni1 / retribuzioni1)){
                    retribuzioni3.value = ((retribuzioni1 / retribuzioni2)*100).toFixed(2)
                }
                else{
                    retribuzioni3.value = "0"
                }
            }

            retribuzioniAlert.value = alert

        }

        function calcolaFornitori(){
            var fornitori1 = parseInt(document.getElementsByName("fornitori1")[0].value)
            var fornitori2 = parseInt(document.getElementsByName("fornitori2")[0].value)
            var alertFornitori = document.getElementById("allertaFornitori")

            var alert = "No"

            if(fornitori1 > fornitori2){
                alert = "Si"
            }
            else{
                alert = "No"
            }
            

            alertFornitori.value = alert
        }

        function recapAlert(){
            var alertAgenziaEntrate = document.getElementById("allertaAgenziaEntrate").value
            var alertINPS = document.getElementById("allertaINPS").value
            var alertRiscossione = document.getElementById("allertaRiscossione").value
            var alertRetribuzioni = document.getElementById("allertaRetribuzioni").value
            var alertFornitori = document.getElementById("allertaFornitori").value

            var recapAgenziaEntrate = document.getElementById("recapAgenziaEntrate")
            var recapINPS = document.getElementById("recapINPS")
            var recapRiscossione = document.getElementById("recapRiscossione")
            var recapRetribuzioni = document.getElementById("recapRetribuzioni")
            var recapFornitori = document.getElementById("recapFornitori")

            if(alertAgenziaEntrate === "" || alertINPS === "" || alertRiscossione === "" || alertRetribuzioni === "" || alertFornitori === ""){
            }
            else{
                recapAgenziaEntrate.innerText = alertAgenziaEntrate == "Si" ? "Fuori Soglia" : "Nella norma"
                recapINPS.innerText = alertINPS == "Si" ? "Fuori Soglia" : "Nella norma"
                recapRiscossione.innerText = alertRiscossione == "Si" ? "Fuori Soglia" : "Nella norma"
                recapRetribuzioni.innerText = alertRetribuzioni == "Si" ? "Fuori Soglia" : "Nella norma"
                recapFornitori.innerText = alertFornitori == "Si" ? "Fuori Soglia" : "Nella norma"
            }
        }

        calcolaDSCR()
        calcolaAgenziaEntrate()
        calcolaINPS()
        calcolaRiscossione()
        calcolaRetribuzione()
        calcolaFornitori()
        recapAlert()

        

    </script>

    
</footer>
