<?php

namespace App\Services;

use SimpleXMLElement;
use Exception;

/**
 * Parser completo per il formato FatturaPA (Fattura Elettronica italiana).
 *
 * Gestisce:
 * - Namespace standard: urn:www.fatturapa.gov.it/sdi/fatturapa/v1.2.2
 * - FatturaPA v1.2, v1.2.1, v1.2.2
 * - Formati: FPA12 (PA), FPR12 (privati/B2B)
 * - Lotti di fatture (più body nello stesso XML)
 */
class FatturaPaParser
{
    /**
     * Namespace FatturaPA (le versioni 1.2.x condividono lo stesso NS base)
     */
    private const NAMESPACES = [
        'p'  => 'http://ivaservizi.agenziaentrate.gov.it/docs/xsd/fatture/v1.2',
        'ds' => 'http://www.w3.org/2000/09/xmldsig#',
    ];

    /**
     * Parsa un file XML FatturaPA e restituisce i dati estratti.
     *
     * @param string $xmlContent Contenuto XML raw
     * @return array{
     *   cedente: array,
     *   cessionario: array,
     *   fatture: array<int, array>,
     *   formato_trasmissione: string,
     *   progressivo_invio: string,
     * }
     * @throws Exception
     */
    public function parse(string $xmlContent): array
    {
        // Pulizia BOM e whitespace iniziale
        $xmlContent = ltrim($xmlContent, "\xEF\xBB\xBF");

        // Disabilita entity esterne per sicurezza
        libxml_disable_entity_loader(true);
        $prev = libxml_use_internal_errors(true);

        try {
            $xml = new SimpleXMLElement($xmlContent);
        } catch (Exception $e) {
            $errors = libxml_get_errors();
            libxml_clear_errors();
            libxml_use_internal_errors($prev);
            throw new Exception('XML non valido: ' . ($errors[0]->message ?? $e->getMessage()));
        }

        libxml_use_internal_errors($prev);

        // Registra namespace — se il file usa un default namespace, leggiamo senza prefisso
        $ns = $xml->getNamespaces(true);
        $prefix = '';
        foreach ($ns as $pfx => $uri) {
            if (str_contains($uri, 'fattur')) {
                $prefix = $pfx ? "{$pfx}:" : '';
                break;
            }
        }

        // Se non troviamo il namespace, proviamo senza prefisso (file senza NS)
        $header   = $this->findNode($xml, 'FatturaElettronicaHeader', $prefix);
        $bodies   = $this->findNodes($xml, 'FatturaElettronicaBody', $prefix);

        if (!$header) {
            throw new Exception('FatturaElettronicaHeader non trovato nel file XML');
        }

        // --- DATI TRASMISSIONE ---
        $datiTrasmissione = $this->findNode($header, 'DatiTrasmissione', $prefix);
        $formatoTrasmissione = (string) $this->findNode($datiTrasmissione, 'FormatoTrasmissione', $prefix);
        $progressivoInvio    = (string) $this->findNode(
            $this->findNode($datiTrasmissione, 'IdTrasmittente', $prefix),
            'IdCodice',
            $prefix
        );

        // --- CEDENTE/PRESTATORE ---
        $cedentePrestatore = $this->findNode($header, 'CedentePrestatore', $prefix);
        $cedente = $this->extractSoggetto($cedentePrestatore, $prefix);

        // --- CESSIONARIO/COMMITTENTE ---
        $cessionarioCommittente = $this->findNode($header, 'CessionarioCommittente', $prefix);
        $cessionario = $this->extractSoggetto($cessionarioCommittente, $prefix);

        // --- FATTURE (possono essere multiple: lotto) ---
        $fatture = [];
        foreach ($bodies as $body) {
            $fatture[] = $this->extractFattura($body, $prefix);
        }

        // Se nessun body trovato, almeno un array vuoto
        if (empty($fatture)) {
            $fatture[] = $this->extractFattura($xml, $prefix);
        }

        return [
            'cedente'               => $cedente,
            'cessionario'           => $cessionario,
            'fatture'               => $fatture,
            'formato_trasmissione'  => $formatoTrasmissione,
            'progressivo_invio'     => $progressivoInvio,
        ];
    }

    /**
     * Parsing batch: accetta più file e restituisce un array di risultati.
     */
    public function parseMultiple(array $xmlContents): array
    {
        $results = [];
        foreach ($xmlContents as $i => $content) {
            try {
                $results[] = ['index' => $i, 'data' => $this->parse($content), 'error' => null];
            } catch (Exception $e) {
                $results[] = ['index' => $i, 'data' => null, 'error' => $e->getMessage()];
            }
        }
        return $results;
    }

    /* ========================================================================
     * ESTRAZIONE SOGGETTO (cedente o cessionario)
     * ======================================================================== */
    private function extractSoggetto(?SimpleXMLElement $node, string $prefix): array
    {
        if (!$node) {
            return ['nome' => null, 'piva' => null, 'cf' => null, 'indirizzo' => null, 'citta' => null, 'provincia' => null, 'cap' => null, 'nazione' => null];
        }

        $datiAnagrafici = $this->findNode($node, 'DatiAnagrafici', $prefix);
        $sede           = $this->findNode($node, 'Sede', $prefix);

        // P.IVA
        $idFiscaleIva = $this->findNode($datiAnagrafici, 'IdFiscaleIVA', $prefix);
        $piva = null;
        if ($idFiscaleIva) {
            $idPaese  = (string) $this->findNode($idFiscaleIva, 'IdPaese', $prefix);
            $idCodice = (string) $this->findNode($idFiscaleIva, 'IdCodice', $prefix);
            $piva = trim($idPaese . $idCodice);
        }

        // Codice fiscale
        $cf = (string) $this->findNode($datiAnagrafici, 'CodiceFiscale', $prefix);

        // Nome: può essere persona fisica (Nome + Cognome) o giuridica (Denominazione)
        $anagrafica    = $this->findNode($datiAnagrafici, 'Anagrafica', $prefix);
        $denominazione = (string) $this->findNode($anagrafica, 'Denominazione', $prefix);
        $nomePersona   = (string) $this->findNode($anagrafica, 'Nome', $prefix);
        $cognome       = (string) $this->findNode($anagrafica, 'Cognome', $prefix);

        $nome = $denominazione ?: trim("{$nomePersona} {$cognome}");

        // Sede
        $indirizzo = (string) $this->findNode($sede, 'Indirizzo', $prefix);
        $numCivico = (string) $this->findNode($sede, 'NumeroCivico', $prefix);
        $cap       = (string) $this->findNode($sede, 'CAP', $prefix);
        $comune    = (string) $this->findNode($sede, 'Comune', $prefix);
        $provincia = (string) $this->findNode($sede, 'Provincia', $prefix);
        $nazione   = (string) $this->findNode($sede, 'Nazione', $prefix);

        if ($numCivico) {
            $indirizzo = trim("{$indirizzo}, {$numCivico}");
        }

        return [
            'nome'      => $nome ?: null,
            'piva'      => $piva ?: null,
            'cf'        => $cf ?: null,
            'indirizzo' => $indirizzo ?: null,
            'citta'     => $comune ?: null,
            'provincia' => $provincia ?: null,
            'cap'       => $cap ?: null,
            'nazione'   => $nazione ?: 'IT',
        ];
    }

    /* ========================================================================
     * ESTRAZIONE DATI FATTURA
     * ======================================================================== */
    private function extractFattura(?SimpleXMLElement $body, string $prefix): array
    {
        if (!$body) {
            return ['numero' => null, 'data' => null, 'tipo' => null, 'importo_netto' => 0, 'importo_lordo' => 0, 'causale' => null, 'scadenza' => null, 'metodo_pagamento' => null, 'linee' => []];
        }

        // DatiGenerali > DatiGeneraliDocumento
        $datiGenerali    = $this->findNode($body, 'DatiGenerali', $prefix);
        $datiGenDoc      = $this->findNode($datiGenerali, 'DatiGeneraliDocumento', $prefix);

        $tipoDocumento   = (string) $this->findNode($datiGenDoc, 'TipoDocumento', $prefix);
        $numero          = (string) $this->findNode($datiGenDoc, 'Numero', $prefix);
        $data            = (string) $this->findNode($datiGenDoc, 'Data', $prefix);
        $importoTotale   = (float) $this->findNode($datiGenDoc, 'ImportoTotaleDocumento', $prefix);
        $causale         = (string) $this->findNode($datiGenDoc, 'Causale', $prefix);

        // DatiBeniServizi > DettaglioLinee
        $datiBeniServizi = $this->findNode($body, 'DatiBeniServizi', $prefix);
        $linee = [];
        $totaleImponibile = 0;

        if ($datiBeniServizi) {
            $dettaglioLinee = $this->findNodes($datiBeniServizi, 'DettaglioLinee', $prefix);
            foreach ($dettaglioLinee as $linea) {
                $desc       = (string) $this->findNode($linea, 'Descrizione', $prefix);
                $qta        = (float)  $this->findNode($linea, 'Quantita', $prefix);
                $prezzoUnit = (float)  $this->findNode($linea, 'PrezzoUnitario', $prefix);
                $prezzoTot  = (float)  $this->findNode($linea, 'PrezzoTotale', $prefix);
                $aliquotaIVA = (float) $this->findNode($linea, 'AliquotaIVA', $prefix);

                $totaleImponibile += $prezzoTot;
                $linee[] = [
                    'descrizione'    => $desc,
                    'quantita'       => $qta,
                    'prezzo_unitario' => $prezzoUnit,
                    'prezzo_totale'  => $prezzoTot,
                    'aliquota_iva'   => $aliquotaIVA,
                ];
            }

            // DatiRiepilogo per l'imposta
            $datiRiepilogo = $this->findNodes($datiBeniServizi, 'DatiRiepilogo', $prefix);
            $totaleImposta = 0;
            foreach ($datiRiepilogo as $riepilogo) {
                $totaleImposta += (float) $this->findNode($riepilogo, 'Imposta', $prefix);
            }
        }

        // Calcola importo netto e lordo
        $importoNetto = $totaleImponibile ?: $importoTotale;
        $importoLordo = $importoTotale ?: ($totaleImponibile + ($totaleImposta ?? 0));

        // DatiPagamento > DettaglioPagamento
        $datiPagamento = $this->findNode($body, 'DatiPagamento', $prefix);
        $dettaglioPag  = $this->findNode($datiPagamento, 'DettaglioPagamento', $prefix);

        $scadenza        = (string) $this->findNode($dettaglioPag, 'DataScadenzaPagamento', $prefix);
        $metodoPagamento = (string) $this->findNode($dettaglioPag, 'ModalitaPagamento', $prefix);

        // Mappa codice modalità pagamento a label leggibile
        $metodoPagamento = $this->mapModalitaPagamento($metodoPagamento);

        return [
            'numero'            => $numero ?: null,
            'data'              => $data ?: null,
            'tipo_documento'    => $tipoDocumento,
            'tipo_label'        => $this->mapTipoDocumento($tipoDocumento),
            'importo_netto'     => round($importoNetto, 2),
            'importo_lordo'     => round($importoLordo, 2),
            'causale'           => $causale ?: null,
            'scadenza'          => $scadenza ?: null,
            'metodo_pagamento'  => $metodoPagamento,
            'linee'             => $linee,
        ];
    }

    /* ========================================================================
     * HELPERS XML
     * ======================================================================== */

    /**
     * Trova un singolo nodo child, con o senza namespace.
     */
    private function findNode(?SimpleXMLElement $parent, string $name, string $prefix): ?SimpleXMLElement
    {
        if (!$parent) return null;

        // Con prefisso namespace
        if ($prefix) {
            $node = $parent->{$name} ?? null;
            if ($node && $node->count() > 0) return $node;
        }

        // Senza prefisso (o fallback)
        $node = $parent->{$name} ?? null;
        if ($node !== null && ((string)$node !== '' || $node->count() > 0)) {
            return $node;
        }

        // XPath fallback per namespace complessi
        foreach ($parent->children() as $child) {
            $localName = $child->getName();
            if ($localName === $name) {
                return $child;
            }
        }

        // XPath con tutti i namespace registrati
        foreach (self::NAMESPACES as $pfx => $uri) {
            $parent->registerXPathNamespace($pfx, $uri);
        }
        $results = @$parent->xpath("*[local-name()='{$name}']");
        return !empty($results) ? $results[0] : null;
    }

    /**
     * Trova tutti i nodi child con un certo nome.
     */
    private function findNodes(?SimpleXMLElement $parent, string $name, string $prefix): array
    {
        if (!$parent) return [];

        $nodes = [];

        // Iterazione diretta
        foreach ($parent->children() as $child) {
            if ($child->getName() === $name) {
                $nodes[] = $child;
            }
        }

        if (!empty($nodes)) return $nodes;

        // XPath fallback
        foreach (self::NAMESPACES as $pfx => $uri) {
            $parent->registerXPathNamespace($pfx, $uri);
        }
        $results = @$parent->xpath("*[local-name()='{$name}']");
        return $results ?: [];
    }

    /* ========================================================================
     * MAPPATURE CODICI → LABEL
     * ======================================================================== */

    private function mapTipoDocumento(string $codice): string
    {
        return match ($codice) {
            'TD01' => 'Fattura',
            'TD02' => 'Acconto/Anticipo su fattura',
            'TD03' => 'Acconto/Anticipo su parcella',
            'TD04' => 'Nota di Credito',
            'TD05' => 'Nota di Debito',
            'TD06' => 'Parcella',
            'TD16' => 'Integrazione fattura reverse charge interno',
            'TD17' => 'Integrazione/autofattura acquisto servizi estero',
            'TD18' => 'Integrazione acquisto beni intracomunitari',
            'TD19' => 'Integrazione/autofattura acquisto beni art.17 c.2 DPR 633/72',
            'TD20' => 'Autofattura per regolarizzazione',
            'TD21' => 'Autofattura per splafonamento',
            'TD22' => 'Estrazione beni da Deposito IVA',
            'TD23' => 'Estrazione beni da Deposito IVA con versamento IVA',
            'TD24' => 'Fattura differita (art.21 c.4 lett.a)',
            'TD25' => 'Fattura differita (art.21 c.4 terzo periodo lett.b)',
            'TD26' => 'Cessione di beni ammortizzabili e per passaggi interni',
            'TD27' => 'Fattura per autoconsumo o cessioni gratuite senza rivalsa',
            default => $codice,
        };
    }

    private function mapModalitaPagamento(string $codice): string
    {
        return match ($codice) {
            'MP01' => 'Contanti',
            'MP02' => 'Assegno',
            'MP03' => 'Assegno circolare',
            'MP04' => 'Contanti c/o Tesoreria',
            'MP05' => 'Bonifico',
            'MP06' => 'Vaglia cambiario',
            'MP07' => 'Bollettino bancario',
            'MP08' => 'Carta di pagamento',
            'MP09' => 'RID',
            'MP10' => 'RID utenze',
            'MP11' => 'RID veloce',
            'MP12' => 'RIBA',
            'MP13' => 'MAV',
            'MP14' => 'Quietanza erario',
            'MP15' => 'Giroconto su conti di contabilità speciale',
            'MP16' => 'Domiciliazione bancaria',
            'MP17' => 'Domiciliazione postale',
            'MP18' => 'Bollettino di c/c postale',
            'MP19' => 'SEPA Direct Debit',
            'MP20' => 'SEPA Direct Debit CORE',
            'MP21' => 'SEPA Direct Debit B2B',
            'MP22' => 'Trattenuta su somme già riscosse',
            'MP23' => 'PagoPA',
            default => $codice ?: 'N/D',
        };
    }
}
