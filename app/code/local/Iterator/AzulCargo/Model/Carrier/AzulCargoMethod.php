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

class Iterator_AzulCargo_Model_Carrier_AzulCargoMethod
    extends Mage_Shipping_Model_Carrier_Abstract
    implements Mage_Shipping_Model_Carrier_Interface
{
    /**
     * _code property
     *
     * @var string
     */
    protected $_code = 'azulcargo';
    protected $_isFixed = true;

    /**
     * _result property
     *
     * @var Mage_Shipping_Model_Rate_Result|Mage_Shipping_Model_Tracking_Result
     */
    protected $_result = null;

    /**
     * ZIP code vars
     */
    protected $_fromZip = null;
    protected $_toZip = null;

    /**
     * Value and Weight
     */
    protected $_packageValue = null;
    protected $_packageWeight = null;
    protected $_volumeWeight = null;

    /**
     * Cart Products
     */
    protected $_cartProducts = null;
    protected $_cartProductsCount = null;


    /**
     * Collect Rates
     *
     * @param Mage_Shipping_Model_Rate_Request $request Mage request
     *
     * @return bool|Mage_Shipping_Model_Rate_Result|Mage_Shipping_Model_Tracking_Result
     */
    public function collectRates(Mage_Shipping_Model_Rate_Request $request) {
        if ($this->_inicialCheck($request) === false) {
            return false;
        }

        if (!preg_match('/^([0-9]{8})$/', $this->_toZip)) {
            $this->_throwError('zipcodeerror', 'Invalid Zip Code', __LINE__);
            return $this->_result;
        }

        if ($this->_packageWeight == 0) {
            $this->_packageWeight = $this->_getNominalWeight($request);
        }

        if ($this->_packageWeight <= 0) {
            $this->_throwError('weightzeroerror', 'Weight zero', __LINE__);
            return $this->_result;
        }

        $this->_generateVolumeWeight($request);

        $this->_getQuotes();

        return $this->_result;
    }

    /**
     * Retrieve all visible items from request
     *
     * @param Mage_Shipping_Model_Rate_Request $request Mage request
     *
     * @return array
     */
    protected function _getRequestItems($request) {
        $allItems = $request->getAllItems();
        $items = array();

        foreach ($allItems as $item) {
            if (!$item->getParentItemId()) {
                $items[] = $item;
            }
        }

        $items = $this->_loadBundleChildren($items);

        return $items;
    }

    /**
     * Filter visible and bundle children products.
     *
     * @param array $items Product Items
     *
     * @return array
     */
    protected function _loadBundleChildren($items)
    {
        $visibleAndBundleChildren = array();
        /* @var $item Mage_Sales_Model_Quote_Item */
        foreach ($items as $item) {
            $product = $item->getProduct();
            $isBundle = ($product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_BUNDLE);
            if ($isBundle) {
                /* @var $child Mage_Sales_Model_Quote_Item */
                foreach ($item->getChildren() as $child) {
                    $visibleAndBundleChildren[] = $child;
                }
            } else {
                $visibleAndBundleChildren[] = $item;
            }
        }
        return $visibleAndBundleChildren;
    }

    /**
    * Gets Nominal Weight
    *
    * @param Mage_Shipping_Model_Rate_Request $request Mage request
    *
    * @return number
    */
    protected function _getNominalWeight($request) {
        $weight = 0;
        $items = $this->_getRequestItems($request);

        foreach ($items as $item) {
            $product = Mage::getModel('catalog/product')->load($item->getProductId());
            $weight += $product->getWeight();
        }

        return $weight;
    }

    /**
     * Generate Volume weight
     *
     * @param Mage_Shipping_Model_Rate_Request $request Mage request
     *
     * @return bool
     */
    protected function _generateVolumeWeight($request)
    {
        $pesoCubicoTotal = 0;

        $items = $this->_getRequestItems($request);

        foreach ($items as $item) {
            $_product = $this->_getSimpleProduct($item->getProduct());

            if ($_product->getData('volume_altura') == '' || (int) $_product->getData('volume_altura') == 0) {
                $itemAltura = 2;
            } else {
                $itemAltura = $_product->getData('volume_altura');
            }

            if ($_product->getData('volume_largura') == '' || (int) $_product->getData('volume_largura') == 0) {
                $itemLargura = 11;
            } else {
                $itemLargura = $_product->getData('volume_largura');
            }

            if ($_product->getData('volume_comprimento') == '' || (int) $_product->getData('volume_comprimento') == 0) {
                $itemComprimento = 16;
            } else {
                $itemComprimento = $_product->getData('volume_comprimento');
            }

            $pesoCubicoTotal += (($itemAltura * $itemLargura * $itemComprimento) * $item->getTotalQty()) / 6000;

            $this->_cartProducts[] = array(
                'Volume' => $item->getTotalQty(),
                'Peso' => number_format($item->getWeight(), 2, '.', ''),
                'Altura' => $itemAltura,
                'Comprimento' => $itemComprimento,
                'Largura' => $itemLargura,
            );
            $itemsCount += $item->getTotalQty();
        }

        $this->_cartProductsCount = $itemsCount;
        $this->_volumeWeight = number_format($pesoCubicoTotal, 2, '.', '');

        return true;
    }

    /**
     * Retrieves a simple product
     *
     * @param Mage_Catalog_Model_Product $product Catalog Product
     *
     * @return Mage_Catalog_Model_Product
     */
    protected function _getSimpleProduct($product)
    {
        $type = $product->getTypeInstance(true);
        if ($type->getProduct($product)->hasCustomOptions()
            && ($simpleProductOption = $type->getProduct($product)->getCustomOption('simple_product'))
        ) {
            $simpleProduct = $simpleProductOption->getProduct($product);
            if ($simpleProduct) {
                return $this->_getSimpleProduct($simpleProduct);
            }
        }
        return $type->getProduct($product);
    }

    /**
     * Get shipping quote
     *
     * @return Mage_Shipping_Model_Rate_Result|Mage_Shipping_Model_Tracking_Result
     */
    protected function _getQuotes() {
        $azulCargoReturn = $this->getAzulCargoReturn();
        if(!$azulCargoReturn['HasErrors']) {
            foreach($azulCargoReturn['Value'] as $azulCargoService) {
                if($azulCargoService['NomeServico'] === 'AMANHA') {
                    continue;
                }
                $this->_appendShippingReturn($azulCargoService);
            }
        } else {
            return $this->_result;
        }
        
        return $this->_result;
    }

    /**
     * Make initial checks and iniciate module variables
     *
     * @param Mage_Shipping_Model_Rate_Request $request Mage request
     *
     * @return bool
     */
    protected function _inicialCheck(Mage_Shipping_Model_Rate_Request $request) {
        if(Mage::app()->getStore()->isAdmin()) {
            return false;
        }

        if (!$this->getConfigFlag('active')) {
            return false;
        }

        $origCountry = Mage::getStoreConfig('shipping/origin/country_id', $this->getStore());
        $destCountry = $request->getDestCountryId();
        if ($origCountry != 'BR' || $destCountry != 'BR') {
            return false;
        }

        $this->_fromZip = Mage::getStoreConfig('shipping/origin/postcode', $this->getStore());
        $this->_toZip   = $request->getDestPostcode();

        $this->_fromZip = str_replace(array('-', '.'), '', trim($this->_fromZip));
        $this->_toZip   = str_replace(array('-', '.'), '', trim($this->_toZip));

        if (!preg_match('/^([0-9]{8})$/', $this->_fromZip)) {
            return false;
        }

        if (!trim($this->_toZip)) {
            return false;
        }

        $this->_result       = Mage::getModel('shipping/rate_result');
        $this->_packageValue = $request->getBaseCurrency()->convert(
            $request->getPackageValue(),
            $request->getPackageCurrency()
        );

        $this->_packageWeight = number_format($request->getPackageWeight(), 2, '.', '');
    }

    /**
     * Get Gol Log return
     *
     * @return bool|SimpleXMLElement[]
     *
     * @throws Exception
     */
    protected function getAzulCargoReturn() {
        $integradorHelper = Mage::helper('azulcargo/IntegradorHelper');
        $arrayCotacao = array(
            'BaseOrigem' => '',
            'CEPOrigem' => $this->_fromZip,
            'BaseDestino' => '',
            'CEPDestino' => $this->_toZip,
            'PesoCubado' => $this->_volumeWeight,
            'PesoReal' => $this->_packageWeight,
            'Volume' => $this->_cartProductsCount,
            'ValorTotal' => $this->_packageValue,
            'Pedido' => '',
            'SiglaServico' => '',
            'TaxaColeta' => true,
            'Itens' => $this->_cartProducts
        );
        $cotacao = $integradorHelper->getCotacao($arrayCotacao);

        return $cotacao;
    }

    /**
     * Apend shipping value to return
     *
     * @param SimpleXMLElement $servico Service Data
     *
     * @return void
     */
    protected function _appendShippingReturn($azulCargoService) {
        $dateDelivery = (int)$azulCargoService['Prazo'];
        $shippingMethod   = (string)$azulCargoService['NomeServico'];
        $shippingPrice    = (float)$azulCargoService['Total'];
        if ($shippingPrice <= 0) {
            return;
        }

        $method = Mage::getModel('shipping/rate_result_method');
        $method->setCarrier($this->_code);
        $method->setCarrierTitle($this->getConfigData('title'));
        $method->setMethod($shippingMethod);

        $shippingCost  = $shippingPrice;

        $method->setMethodTitle($this->getConfigData('title').' ('.$shippingMethod.') - '.' Em Média '.$dateDelivery.' dia(s) + 15 dia(s) Separação e Procedimento Interno');

        $method->setPrice($shippingPrice);
        $method->setCost($shippingCost);

        $this->_result->append($method);
    }

    /**
     * Throw error
     *
     * @param string     $message Message placeholder
     * @param string     $log     Message
     * @param string|int $line    Line of log
     * @param string     $custom  Custom variables for placeholder
     *
     * @return void
     */
    protected function _throwError($message, $log = null, $line = 'NO LINE', $custom = null) {
        $this->_result = null;
        $this->_result = Mage::getModel('shipping/rate_result');

        $error = Mage::getModel('shipping/rate_result_error');
        $error->setCarrier($this->_code);
        $error->setCarrierTitle($this->getConfigData('title'));

        if (is_null($custom) || $this->getConfigData($message) == '') {
            Mage::log($this->_code . ' [' . $line . ']: ' . $log);
            $error->setErrorMessage($this->getConfigData($message));
        } else {
            Mage::log($this->_code . ' [' . $line . ']: ' . $log);
            $error->setErrorMessage(sprintf($this->getConfigData($message), $custom));
        }

        $this->_result->append($error);
    }

    /**
     * Returns the allowed carrier methods
     *
     * @return array
     */
    public function getAllowedMethods() {
        return array('gollog'=>$this->getConfigData('name'));
    }

    /**
     * Define ZIP Code as required
     *
     * @param string $countryId Country ID
     *
     * @return bool
     */
    public function isZipCodeRequired($countryId = null) {
        return true;
    }
}
