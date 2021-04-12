<?php

include('../config/config.inc.php');
include('../init.php');
// leer fichero CSV
$csv_file         = file_get_contents('products.csv');
$data             = explode("\n", $csv_file);
$data             = array_filter(array_map("trim", $data));
$default_language = Configuration::get('PS_LANG_DEFAULT');

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
    $category          = 2;
    $description       = '';
    $description_short = '';
    $product_url       = Tools::link_rewrite($name);
    $ean13             = $csv_values[2];
    $wholesale_price   = $csv_values[4];
    $ecotax            =  $csv_values[5];
    $marca             = $csv_values[8];
    // devolvemos las ID creadas/actualizadas de las categorias del excel 
    $arraydecategorias = crearCategoria($csv_values[7]);
    //comprobacion si el producto existe en la BD
    $product_exists = Db::getInstance()->getValue("SELECT reference FROM " . _DB_PREFIX_ . "product WHERE reference = '" . $reference . "'");
    // si el producto no existe lo inserta y si existe recupera la ID y lo recupera
    if (empty($product_exists)) {
        $action = 'insert';
    } else {
        $action = 'update';
        $product_id = Db::getInstance()->getValue("SELECT id_product FROM " . _DB_PREFIX_ . "product WHERE reference = '" . $reference . "'");
    }
    // pasamos los valores a un objeto de producto y lo crea 
    if ($action == 'insert') {
        $product                      = new Product();
        $product->reference           = $reference;
        $product->name                = [$default_language => $name];
        $product->price               = round($price, 6);
        $product->wholesale_price     = '0.000000';
        $product->quantity            = $quantity;
        $product->link_rewrite        = [$default_language => $product_url];
        $product->id_category         = $arraydecategorias;
        $product->id_category_default = $category;
        $product->description         = [$default_language => $description];
        $product->description_short   = [$default_language => $description_short];
        $product->meta_title          = [$default_language => $name];
        $product->meta_description    = [$default_language => $name];
        $product->meta_keywords       = [$default_language => $name];
        $product->id_tax_rules_group  = 0;
        $product->redirect_type       = '404';
        $product->minimal_quantity    = 1;
        $product->show_price          = 1;
        $product->on_sale             = 0;
        $product->online_only         = 0;
        $product->ean13               = $ean13;
        $product->active              = 1;

        //añade el producto 
        if ($product->add()) {
            $product->updateCategories($product->id_category);
            StockAvailable::setQuantity((int)$product->id, 0, $product->quantity, Context::getContext()->shop->id);
            $tag_list[] = str_replace('-', ',', $product_url);
            Tag::addTags($default_language, $product->id, $tag_list);
        }
        //url del producto
        $link = new Link();
        $url  = $link->getProductLink($product->id);
    }
    // actualiza el producto 
    if ($action == 'update') {
        $product                      = new Product($product_id);
        $product->reference           = $reference;
        $product->name                = [$default_language => $name];
        $product->price               = round($price, 6);
        $product->wholesale_price     = '0.000000';
        $product->quantity            = $quantity;
        $product->link_rewrite        = [$default_language => $product_url];
        $product->id_category         = $arraydecategorias;
        $product->id_category_default = $category;
        $product->description         = [$default_language => $description];
        $product->description_short   = [$default_language => $description_short];
        $product->meta_title          = [$default_language => $name];
        $product->meta_description    = [$default_language => $name];
        $product->meta_keywords       = [$default_language => $name];
        $product->id_tax_rules_group  = 0;
        $product->redirect_type       = '404';
        $product->minimal_quantity    = 1;
        $product->show_price          = 1;
        $product->on_sale             = 0;
        $product->online_only         = 0;
        $product->ean13               = $ean13;
        $product->active              = 1;
        //actualiza las categorias y ajusta la cantidad de shop
        if ($product->update()) {
            $product->updateCategories($product->id_category);
            StockAvailable::setQuantity((int)$product->id, 0, $product->quantity, Context::getContext()->shop->id);
            $tag_list[] = str_replace('-', ',', $product_url);
            Tag::addTags($default_language, $product->id, $tag_list);
        }
        //actualiza la url del producto
        $link = new Link();
        $url = $link->getProductLink($product->id);
    }
}
// funcion que crea las categorias 
function crearCategoria($datoscat)
{
    $home              = (int)Configuration::get('PS_HOME_CATEGORY');
    $nombres           = str_replace(";", ",", $datoscat);
    $nombrearray       = explode(",", $nombres);
    $arraydecategorias = array();
    $limite            = count($nombrearray);
        //recorre las categorias 
    for ($i = 0; $i < $limite; $i++) 
    {
        $category                    = new Category();
        $category->name              = array((int)(Configuration::get('PS_LANG_DEFAULT')) => $nombrearray[$i]);
        $category->link_rewrite      = array((int)Configuration::get('PS_LANG_DEFAULT')   => Tools::str2url($nombrearray[$i]));
        $category->description_short = array((int)(Configuration::get('PS_LANG_DEFAULT')) => "");
        $category->description       = array((int)(Configuration::get('PS_LANG_DEFAULT')) => "");
        $category->id_parent         = $home;
        $category->active            = 1;
        //comprueba si existe y si no la crea 
        if (!$id_category = Db::getInstance()->getValue('SELECT id_category FROM ' . _DB_PREFIX_ . 'category_lang WHERE name in("' . pSQL($nombrearray[$i]) . '")')) 
        {
            $category->add();
            array_push($arraydecategorias, $category->id);
    
        } else {

            array_push($arraydecategorias, $id_category);
        }
    }
    //devuelve el array con el ID de las categorias 
    return $arraydecategorias;
}
