<?php

include('../config/config.inc.php');
include('../init.php');
// leer fichero CSV
$csv_file         = file_get_contents('products.csv');
$data             = explode("\n", $csv_file);
$data             = array_filter(array_map("trim", $data));
$i = 0;
//recorre el fichero
foreach ($data as $csv) {
    $i++;
    // comprobamos la linea de cabecera
    if ($i < 2) {
        continue;
    }
    // crea un array en base a la linea del excel separado por coma
    $csv_values = explode(",", $csv);   
    //nos quedamos con los valores para crear el producto
    $reference         = $csv_values[1];
    $name              = $csv_values[0];
    $price             = $csv_values[3];
    $quantity          = $csv_values[6];
    $ean13             = $csv_values[2];
    $wholesale_price   = $csv_values[4];
    $ecotax            = $csv_values[5];
    $marca             = $csv_values[8];
    // devolvemos las ID creadas/actualizadas de las categorias del excel 
   $arraydecategorias = crearCategoria($csv_values[7]);
    $product                      = new Product();
    $product->reference           = $reference;
    $product->name                = createMultiLangField($name);
    $product->price               = $price;
    $product->quantity            = $quantity;
    $product->id_category_default = $arraydecategorias[0];
    $product->ean13               = $ean13;
    $product->active              = 1; 
    //añade el producto 
  $product->add();
  $product->updateCategories($arraydecategorias);
  StockAvailable::setQuantity((int)$product->id, 0, $product->quantity, Context::getContext()->shop->id);
    }      
// funcion que crea las categorias 
function crearCategoria($datoscat) {
   $nombrearray       = explode(";", $datoscat);
   $default_language = Configuration::get('PS_LANG_DEFAULT');
   $arraydecategorias = array();
   //recorre las categorias
    foreach($nombrearray as $valor1) { 
       $existe_categoria = Category::searchByName($default_language, $valor1); 
        if(count($existe_categoria) < 1) {
          $category = new Category();
          $category->name = createMultiLangField($valor1);
          $category->active = 1;
          $category_link_rewrite = Tools::link_rewrite($category->name[$default_language]);
          $category->link_rewrite = createMultiLangField($category_link_rewrite);
          $category->id_parent = Configuration::get('PS_HOME_CATEGORY'); 
          $category->add();       
        foreach (Shop::getContextListShopID() as $shop) { 
            if (!empty($shop) && !is_numeric($shop)) {
             $category->addShop(Shop::getIdByName($shop));
          } elseif (!empty($shop)) {
              $category->addShop($shop);
           }
        }
          array_push($arraydecategorias, $category->id);
        } else {
          array_push($arraydecategorias, $existe_categoria[0]['id_category']);
        }
    }
    //devuelve el array con el ID de las categorias 
    return $arraydecategorias;  
}
function createMultiLangField($field) {
    $res = [];
    foreach (Language::getIDs(false) as $id_lang) {
        $res[$id_lang] = $field;
    }
    return $res;
}
