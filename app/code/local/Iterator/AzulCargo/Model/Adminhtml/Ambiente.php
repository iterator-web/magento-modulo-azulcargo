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

class Iterator_AzulCargo_Model_Adminhtml_Ambiente {
    
    public function toOptionArray() {
        return array
		(
            array('value' => 'https://hmg.onlineapp.com.br/EDIv2_API_INTEGRACAO_Toolkit/api', 'label'=>Mage::helper('adminhtml')->__('Homologação')),
            array('value' => 'https://ediapi.onlineapp.com.br/toolkit/api', 'label'=>Mage::helper('adminhtml')->__('Produção')),
        );
    }
    
    public function toArray() {
        return array (
            'homologacao' => Mage::helper('adminhtml')->__('Homologação'),
            'producao' => Mage::helper('adminhtml')->__('Produção'),
        );
    }

}