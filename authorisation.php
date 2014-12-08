<?php
$xml = <<<XML
<?xml version="1.0"?>
<Document xmlns="urn:AcceptorAuthorisationRequestV02.1">
    <AccptrAuthstnReq>
        <!-- Cabeçalho da requisição -->
        <Hdr>
            <!-- Identifica o tipo de processo em que a mensagem se propõe. -->
            <MsgFctn>AUTQ</MsgFctn>
            <!-- Versão do protocolo utilizado na mensagem. -->
            <PrtcolVrsn>2.0</PrtcolVrsn>
        </Hdr>
        <!-- Dados da requisição de autorização. -->
        <AuthstnReq>
            <!-- Ambiente da transação. -->
            <Envt>
                <!-- Dados do estabelecimento. -->
                <Mrchnt>
                    <!-- Identificação do estabelecimento. -->
                    <Id>
                        <!-- Identificação do estabelecimento comercial no adquirente.
                             Também conhecido internamente como “SaleAffiliationKey”. -->
                        <Id>%s</Id>
                        <!-- O nome que aparecerá na fatura.
                             - se a transação for mastercard, o limite é 22 caracteres;
                             - se a transação for visa, o limite é 25 caracteres;
                             - se for parcelado, a visa usa os 8 primeiros caracteres do
                               nome do lojista pra passar a informação de parcelamento,
                               sobrando 17 caracteres. -->
                        <ShortName>Nome da fatura</ShortName>
                    </Id>
                </Mrchnt>
                <!-- Dados do ponto de interação -->
                <POI>
                    <!-- Identificação do ponto de interação -->
                    <Id>
                        <!-- Código de identificação do ponto de interação
                             atribuído pelo estabelecimento. -->
                        <Id>2FB4C89A</Id>
                    </Id>
                    <!-- Capacidades do Ponto de interação. -->
                    <Cpblties>
                        <!-- Número máximo de colunas de cada linha a ser impressa
                             no cupom. A quantidade mínima de colunas é de 38.
                             Se o POI enviar menos do que 38, o Host Stone não irá
                             retornar os dados do recibo. -->
                        <PrtLineWidth>50</PrtLineWidth>
                    </Cpblties>
                </POI>
                <!-- Dados do cartão utilizado na transação. -->
                <Card>
                    <!-- Dados não criptografados do cartão utilizado na transação. -->
                    <PlainCardData>
                        <!-- Número do cartão. (Primary Account Number) -->
                        <PAN>4066559930861909</PAN>
                        <!-- Data de validade do cartão no formato “yyyy-MM”. -->
                        <XpryDt>2017-10</XpryDt>
                    </PlainCardData>
                </Card>
            </Envt>
            <!-- Informações da transação a ser realizada. -->
            <Cntxt>
                <!-- Informações sobre o pagamento. -->
                <PmtCntxt>
                    <!-- Modo da entrada dos dados do cartão.
                         PHYS = Ecommerce ou Digitada; -->
                    <CardDataNtryMd>PHYS</CardDataNtryMd>
                    <!-- Tipo do canal de comunicação utilizado na transação.
                         ECOM = Ecommerce ou Digitada -->
                    <TxChanl>ECOM</TxChanl>
                </PmtCntxt>
            </Cntxt>
            <!-- Informações da transação. -->
            <Tx>
                <!-- Identificação da transação definida pelo sistema que se
                     comunica com o Host Stone. -->
                <InitrTxId>123123123</InitrTxId>
                <!-- Indica se os dados da transação devem ser capturados (true)
                     ou não (false) imediatamente. -->
                <TxCaptr>false</TxCaptr>
                <!-- Dados de identificação da transação atribuída pelo POI. -->
                <TxId>
                    <!-- Data local e hora da transação atribuídas pelo POI. -->
                    <TxDtTm>2014-03-12T15:11:06</TxDtTm>
                    <!-- Identificação da transação definida pelo ponto de interação (POI,
                         estabelecimento, lojista, etc). O formato é livre contendo no
                         máximo 32 caracteres. -->
                    <TxRef>06064f516a50483da7f189243c95ccca</TxRef>
                </TxId>
                <!-- Detalhes da transação. -->
                <TxDtls>
                    <!-- Moeda utilizada na transação em conformidade com a ISO 4217.-->
                    <Ccy>986</Ccy>
                    <!-- Valor total da transação em centavos. -->
                    <TtlAmt>100</TtlAmt>
                    <!-- Modalidade do cartão utilizado na transação. -->
                    <AcctTp>CRDT</AcctTp>
                    <!-- Os dados relativos à(s) parcela(s) ou a uma transação recorrente. -->
                    <RcrngTx>
                        <!-- Tipo de parcelamento. -->
                        <InstlmtTp>NONE</InstlmtTp>
                        <!-- Número do total de parcelas. -->
                        <TtlNbOfPmts>0</TtlNbOfPmts>
                    </RcrngTx>
                </TxDtls>
            </Tx>
        </AuthstnReq>
    </AccptrAuthstnReq>
</Document>
XML;

// Utilizado para identificar o ambiente de integração.
$test = true;
$curl = curl_init();

if ($test) {
    // Caso estejamos utilizando o ambiente de testes, definimos
    // o endpoint de testes e credenciais adequadas.
    $apiEndpoint = 'http://dev-pos.stone.com.br/Authorize';
    $affiliationKey = 'xxxxxxxx';

    // Não precisamos fazer verificações do certificado digital
    // no ambiente de testes
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
} else {
    $apiEndpoint = 'http://pos.stone.com.br/Authorize';
    $affiliationKey = 'xxxxxxxx';
}

// Definimos o ambiente de integração
curl_setopt($curl, CURLOPT_URL, $apiEndpoint);

// Informamos que estamos enviando um XML
curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: text/xml; charset=utf-8'));

// Solicitamos a resposta da requisição para que possamos trabalhar com ela
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

// Definimos o método HTTP POST e informamos a chave de afiliação adequada para
// o ambiente.
curl_setopt($curl, CURLOPT_POST, true);
curl_setopt($curl, CURLOPT_POSTFIELDS, sprintf($xml, $affiliationKey));

// Enviamos a requisição e aguardamos a resposta.
$response = curl_exec($curl);

$errno = curl_errno($curl);
$error = curl_error($curl);

curl_close($curl);

// Verificamos se houve algum erro no envio da requisição
if ($errno == 0) {
    // Manipulamos o XML de resposta e obtemos o status da aprovação ou
    // rejeição
    $dom = new DOMDocument();
    $dom->loadXML($response);

    $approved = $dom->getElementsByTagName('Rspn')->item(0);
    $authorizationCode = $dom->getElementsByTagName('AuthstnCd')->item(0);

    if ($approved !== null) {
        printf("[Status: %s] Código autorização: %s\n", $approved->nodeValue, $authorizationCode->nodeValue);
    } else {
        $rejected = $dom->getElementsByTagName('RjctRsn')->item(0);
        $additionalInfo = $dom->getElementsByTagName('AddtlInf')->item(0);

        if ($rejected !== null) {
            printf("[Rejeitado: %s] Motivo: %s\n", $rejected->nodeValue, $additionalInfo->nodeValue);
        }
    }
} else {
    echo $error;
}


