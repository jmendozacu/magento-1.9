<?php

/*
 * @copyright  Copyright (c) 2013 by  ESS-UA.
 */

abstract class Ess_M2ePro_Model_Connector_Amazon_Search_ByQuery_ItemsResponser
    extends Ess_M2ePro_Model_Connector_Amazon_Responser
{
    // ########################################

    protected function validateResponseData($response)
    {
        if (!isset($response['items']) && !isset($response['unavailable'])) {
            return false;
        }

        return true;
    }

    // ########################################

    protected function processResponseData($response)
    {
        if (!empty($response['unavailable'])) {
            $this->processParsedResult(false);
            return;
        }

        $result = array();

        foreach ($response['items'] as $item) {

            $product = array(
                'general_id' => $item['product_id'],
                'brand' => isset($item['brand']) ? $item['brand'] : '',
                'title' => $item['title'],
                'image_url' => $item['image_url'],
                'is_variation_product' => $item['is_variation_product'],
            );

            if ($product['is_variation_product']) {
                $product += array(
                    'parentage' => $item['parentage'],
                    'variations' => $item['variations']
                );
            }

            if (!empty($item['list_price'])) {
                $product['list_price'] = array(
                    'amount' => $item['list_price']['amount'],
                    'currency' => $item['list_price']['currency'],
                );
            }

            if (!empty($item['requested_child_id'])) {
                $product['requested_child_id'] = $item['requested_child_id'];
            }

            $result[] = $product;
        }

        $this->processParsedResult($result);
    }

    // ########################################

    abstract protected function processParsedResult($result);

    // ########################################
}