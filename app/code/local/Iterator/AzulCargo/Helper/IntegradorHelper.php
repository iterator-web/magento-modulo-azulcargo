<?php
/**
 * Iterator Sistemas Web
 *
 * NOTAS SOBRE LICENÇA
 *
 * Este arquivo de código-fonte está em vigência dentro dos termos da EULA.
 * Ao fazer uso deste arquivo em seu produto, automaticamente você está
 * concordando com os termos do Contrato de Licença de Usuário Final(EULA)
 * propostos pela empresa Iterator Sistemas Web.
 *
 * =================================================================
 *               MÓDULO DE FRETES AZUL CARGO EXPRESS
 * =================================================================
 * Este produto foi desenvolvido para o Ecommerce Magento visando
 * integrar as formas de entregas oferecidas pela transportadora
 * Azul Cargo Express.
 * Através deste módulo a loja virtual do contratante do serviço
 * passará a conter todas as opções de entregas disponíveis na
 * API da Azul Cargo Express.
 * =================================================================
 *
 * @category   Iterator
 * @package    Iterator_AzulCargo
 * @author     Ricardo Auler Barrientos <contato@iterator.com.br>
 * @copyright  Copyright (c) Iterator Sistemas Web - CNPJ: 19.717.703/0001-63
 * @license    O Produto é protegido por leis de direitos autorais, bem como outras leis de propriedade intelectual.
 */

class Iterator_AzulCargo_Helper_IntegradorHelper extends Mage_Core_Helper_Abstract {

    private $accessToken;
    private $checkToken = true;

    public function getCotacao($arrayCotacao) {
        if($this->checkToken) {
            $this->setAccessToken();
            unset($arrayCotacao['AccessToken']);
            $arrayCotacao['AccessToken'] = $this->accessToken;
        }
        $urlApi = $this->getUrlApi();
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $urlApi."/Cotacao/Enviar",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode($arrayCotacao),
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER => array(
                "Accept: application/json",
                "Content-Type: application/json",
                "cache-control: no-cache"
            ),
        ));
        $resultado = curl_exec($curl);
        $erro = curl_error($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);

        if($httpCode === 401) {
            $this->conectar();
            $this->getCotacao($arrayCotacao);
        } else if($erro) {
            Mage::log($erro, null, 'AzulExpress.log');
            exit();
        } else {
            return json_decode($resultado, true);
        }
    }

    private function conectar() {
        $urlApi = $this->getUrlApi();
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $urlApi."/Autenticacao/AutenticarUsuario",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $this->getDadosConexao(),
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER => array(
                "Accept: application/json",
                "Content-Type: application/json",
                "cache-control: no-cache"
            ),
        ));
        $resultado = curl_exec($curl);
        $erro = curl_error($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);

        if($httpCode === 401) {
            Mage::log($resultado, null, 'AzulExpress.log');
            exit();
        } else if($erro) {
            Mage::log($erro, null, 'AzulExpress.log');
            exit();
        } else {
            $retornoAutenticacao = json_decode($resultado, true);
            $this->saveAccessToken($retornoAutenticacao['Value']);
            $this->accessToken = $retornoAutenticacao['Value'];
            $this->checkToken = false;
        }
    }

    private function getDadosConexao() {
        $dadosConexao = array(
            'email' => Mage::getStoreConfig('carriers/azulcargo/email'),
            'password' => Mage::getStoreConfig('carriers/azulcargo/password')
        );
        return json_encode($dadosConexao);
    }

    private function getUrlApi() {
        return Mage::getStoreConfig('carriers/azulcargo/ambiente');
    }

    private function setAccessToken() {
        $this->accessToken = Mage::getStoreConfig('carriers/azulcargo/access_token');
    }

    private function saveAccessToken($accessToken) {
        Mage::getModel('core/config')->saveConfig('carriers/azulcargo/access_token', $accessToken);
        Mage::getModel('core/config')->cleanCache();
    }
}
?>
