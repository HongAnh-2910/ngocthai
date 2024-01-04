<?php

namespace App;

class MainHelpers {

    public static function getProductSame($idProduct, $sellLineID, $limit = 5){
        $getInfoProduct = Variation::where('product_id', $idProduct)->first()->toArray();
        if(empty($getInfoProduct)){
            goto _return;
        }

        $price = $getInfoProduct['sell_price_inc_tax'];
        $resultVariations = Variation::whereRaw('CAST(sell_price_inc_tax AS INT) >= ' . (int)$price)->limit((int)$limit + 1)->get();
        if($resultVariations == null){
            goto _return;
        }

        $resultVariations = $resultVariations->toArray();
        $aryIdProduct = array_column($resultVariations, 'product_id');
        $aryIdProduct = array_filter($aryIdProduct, function($item) use ($idProduct){
            return $item != $idProduct;
        });
        $allProductSame = Product::with('sub_unit')->with('variations')->whereIn('id', $aryIdProduct)->get();
        $infoThisProduct = Product::with('sub_unit')->with("unit")->with('variations')->where('id', $idProduct)->first();
        if($allProductSame == null || $infoThisProduct == null){
            goto _return;
        }

        //get transition sale line this product
        $transitionSellLineThisProduct = TransactionSellLinesChange::where('parent_id', $sellLineID)->first();
        if($transitionSellLineThisProduct == null){
            $transitionSellLineThisProduct = TransactionSellLine::where('id', $sellLineID)->first()->toArray();
        }else{
            $transitionSellLineThisProduct = $transitionSellLineThisProduct->toArray();
        }
        $infoThisProduct = array_merge($infoThisProduct->toArray(), $transitionSellLineThisProduct);

        return [
            'allProductSame' => $allProductSame->toArray(),
            'sellLineProduct' => $infoThisProduct
        ];

        _return :
        return [];
    }

}