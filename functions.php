<?php

function productCheck($productID) {
    $productCheck = DB::queryFirstRow('SELECT * FROM product WHERE productID=%s', $productID);
    return $productCheck;
}

function categoryCheck($categoryID) {
    $categoryCheck = DB::queryFirstRow('SELECT * FROM category WHERE categoryID=%s', $categoryID);
    return $categoryCheck;
}

function categoryProducts($categoryID) {
    $categoryProducts = DB::query('SELECT * FROM product WHERE categoryID=%s', $categoryID);
    return $categoryProducts;
}

function addCart($sessionID, $productID, $quantity) {
   DB::insert('cart_item', array('sessionID' => $sessionID, 'productID' => $productID, 'quantity' => $quantity));
}

function updateCart($sessionID, $productID, $quantity) {
   DB::update('cart_item', array('quantity' => $quantity), 'productID=%i and sessionID=%s', $productID, $sessionID);
}

function totalBeforeTaxes($sessionID) {
   $totalBeforeTaxes = DB::queryFirstField('SELECT sum(cart_item.quantity * product.price) as Total FROM cart_item INNER JOIN product ON cart_item.productID=product.productID WHERE sessionID=%s', $sessionID );
   return $totalBeforeTaxes;
}
    
function totalAfterTaxes($totalBeforeTaxes) {
   $totalAfterTaxes =  $totalBeforeTaxes * 1.15;
   $totalAfterTaxes = round($totalAfterTaxes, 2);
   return $totalAfterTaxes;
}
    
function cartInfo($sessionID) {
    $cartInfo = DB::query('SELECT cart_item.quantity, product.name, product.description, product.price, product.image_path, product.productID FROM cart_item INNER JOIN product ON cart_item.productID=product.productID WHERE sessionID=%s', $sessionID);
    return $cartInfo;
}

function productCartCheck($productID, $sessionID) {
    DB::query('SELECT * FROM cart_item WHERE sessionID=%s AND productID=%i', $sessionID, $productID);
    $productCartCheck = DB::count();
    return $productCartCheck;
}

function deleteItemCart($productID, $sessionID){
    DB::delete('cart_item', "productID=%d", $productID,"sessionID=%s", $sessionID);
}