<?php
session_cache_limiter(false);
$sessionID = session_id();
session_start();


require_once '../vendor/autoload.php';
require_once 'db.php';
require_once 'functions.php';

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

$log = new Logger('main');
$log->pushHandler(new StreamHandler('logs/everything.log', Logger::DEBUG));
$log->pushHandler(new StreamHandler('logs/errors.log', Logger::ERROR));

$app = new \Slim\Slim(array(
    'view' => new \Slim\Views\Twig()
        ));

$view = $app->view();
$view->parserOptions = array(
    'debug' => true,
    'cache' => dirname(__FILE__) . '/../cache'
);
$view->setTemplatesDirectory(dirname(__FILE__) . '/../templates');

$productList = DB::query("SELECT * FROM product");
$categoryList = DB::query("SELECT * FROM category");
$logoPath = '/images/logo.png';


//SET GLOBAL VARIABLES------------------------------------------------------------------------------------------------------------------------------------------------------------
$logoPath = '/images/logo.png';
$cartLogo = '/images/cartLogo.png';
$mainPagePic1 = '/images/mainpage1.jpg';
$mainPagePic2 = '/images/mainpage2.jpg';
$mainPagePic3 = '/images/mainpage3.jpg';
$mainPagePic4 = '/images/mainpage4.png';
$mainPagePic5 = '/images/mainpage5.jpg';
$minusPath = '/images/minus.png';
$plusPath = '/images/plus.png';
$sessionID = $_COOKIE['PHPSESSID'];

if (isset($_SESSION['auth'])) {
    $user = $_SESSION['auth']['user'];
}

$twig = $app->view()->getEnvironment();
$twig->addGlobal('categoryList', $categoryList);
$twig->addGlobal('productList', $productList);
$twig->addGlobal('logoPath', $logoPath);
$twig->addGlobal('mainPagePic1', $mainPagePic1);
$twig->addGlobal('mainPagePic2', $mainPagePic2);
$twig->addGlobal('mainPagePic3', $mainPagePic3);
$twig->addGlobal('mainPagePic4', $mainPagePic4);
$twig->addGlobal('mainPagePic5', $mainPagePic5);
$twig->addGlobal('cartLogo', $cartLogo);
$twig->addGlobal('minusPath', $minusPath);
$twig->addGlobal('plusPath', $plusPath);
$twig->addGlobal('sessionID', $sessionID);


if (isset($_SESSION['auth'])) {
    $twig->addGlobal('user', $user);
}

if (isset($_SESSION['auth'])) {
    \Slim\Route::setDefaultConditions(array(
        'user' => $user,
    ));
}

\Slim\Route::setDefaultConditions(array(
    'productID' => '\d+',
    'categoryID' => '\d+',
    'name' => '\s+',
    'categoryList' => $categoryList,
    'logoPath' => $logoPath,
    'productList' => $productList,
    'mainPagePic1' => $mainPagePic1,
    'mainPagePic2' => $mainPagePic2,
    'mainPagePic3' => $mainPagePic3,
    'mainPagePic4' => $mainPagePic4,
    'mainPagePic5' => $mainPagePic5,
    'sessionID' => $sessionID,
    'plusPath' => $plusPath,
    'minusPath' => $minusPath,
    'cartLogo' => $cartLogo
));


//TWIG TEMPLATES ------------------------------------------------------------------------------------------------------------------------------------------
//CATEGORY_ADD------------------------------------------------------------------------------------------------------------------------------------------------------------
$app->get('/category/add(/)', function() use ($app) {
    if (!isset($_SESSION['auth'])) {
        $app->redirect('/web/admin.php/forbidden/');
    } else {
        $app->render('category_add.html.twig');
    }
});

$app->post('/category/add(/)', function() use ($app) {
    $name = $app->request()->post('name');
    $errorList = array();

    if (strlen($name) > 50) {
        $categoryList['name'] = '';
        array_push($errorList, "Category name must be below 50 characters");
    }
    if ($name == "") {
        array_push($errorList, "Can not leave Category blank.");
    } else {
        $categoryCheck = DB::queryFirstRow('SELECT * FROM category WHERE name=%s', $name);
        if ($categoryCheck) {
            array_push($errorList, "Category with that name already exists");
        }
    }

    if ($errorList) {
        $app->render('category_add.html.twig', array(
            'errorList' => $errorList,
            'name' => $name
        ));
    } else {
        DB::insert('category', array('name' => $name));
        $app->render('category_add_success.html.twig');
    }
});

//CATEGORY_LIST------------------------------------------------------------------------------------------------------------------------------------------------------------
$app->get('/category/list(/)', function () use ($app) {
    if (!isset($_SESSION['auth'])) {
        $app->redirect('/web/admin.php/forbidden/');
    } else {
        $app->render('category_list.html.twig', array());
    }
});

//CATEGORY_DELETE------------------------------------------------------------------------------------------------------------------------------------------------------------
$app->get('/category/delete(/)(:categoryID)', function($categoryID = 0) use ($app) {

    if (!isset($_SESSION['auth'])) {
        $app->redirect('/web/admin.php/forbidden/');
    } else {
        if ($categoryID != 0) {
            $categoryInfo = DB::queryFirstRow('SELECT * FROM category WHERE categoryID=%d', $categoryID);

            $app->render('category_delete.html.twig', array(
                'categoryID' => $categoryInfo['categoryID'],
                'name' => $categoryInfo['name']));
        } else {
            $app->render('category_delete.html.twig');
            echo 'Enter category ID to delete in the URL.';
        }
    }
});

$app->post('/category/delete(/)(:categoryID)', function() use ($app) {

    $categoryID = $app->request()->post('categoryID');
    $errorList = array();

    $categoryInfo = DB::queryFirstRow('SELECT * FROM category WHERE categoryID=%d', $categoryID);

    $productCheck = DB::queryFirstRow('SELECT * FROM product WHERE categoryID=%d', $categoryID);
    if ($productCheck) {
        array_push($errorList, "Cannot delete category because product in that category exists.");
    }

    if ($errorList) {
        // STATE 2: failed submission
        $app->render('category_delete.html.twig', array(
            'categoryID' => $categoryInfo['categoryID'],
            'name' => $categoryInfo['name'],
            'errorList' => $errorList
        ));
    } else {
        // STATE 3: successful submission
        DB::delete('category', "categoryID=%d", $categoryID);
        $app->render('category_delete_success.html.twig');
    }
});


//CATEGORY_EDIT------------------------------------------------------------------------------------------------------------------------------------------------------------
$app->get('/category/edit(/)(:categoryID)', function($categoryID = 0) use ($app) {
    $errorList = array();
    if (!isset($_SESSION['auth'])) {
        $app->redirect('/web/admin.php/forbidden/');
    } else {
        $categoryCheck = productCheck($categoryID);
        if ($categoryID != 0) {
            if ($categoryCheck) {
                $categoryInfo = DB::queryFirstRow('SELECT * FROM category WHERE categoryID=%d', $categoryID);
                $app->render('category_edit.html.twig', array(
                    'categoryID' => $categoryInfo['categoryID'],
                    'name' => $categoryInfo['name']));
            } else {
                $app->redirect('/web/admin.php/category/add/');
            }
        } else {
            $app->redirect('/web/admin.php/category/add/');
        }
    }
});

$app->post('/category/edit/(:categoryID)', function($categoryID = 0) use ($app) {
    $name = $app->request()->post('name');
    $categoryID = $app->request()->post('categoryID');
    $errorList = array();

    //CREATE VALUE LIST FOR INPUTS
    //ENTER VALIDATION HERE --> IF $ERRORLIST{}ELSE{

    DB::update('category', array(
        'name' => $name), "categoryID=%d", $categoryID);
    $app->render('category_edit_success.html.twig');
});

//PRODUCT_ADD------------------------------------------------------------------------------------------------------------------------------------------------------------
$app->get('/product/add(/)', function() use ($app) {

    if (!isset($_SESSION['auth'])) {
        $app->redirect('/web/admin.php/forbidden/');
    } else {


        $app->render('product_add.html.twig');
    }
});

$app->post('/product/add(/)', function() use ($app) {
    $name = $app->request()->post('name');
    $categoryID = $app->request()->post('categoryID');
    $description = $app->request()->post('description');
    $price = $app->request()->post('price');
    $imageUpload = $_FILES['imageUpload'];
    $errorList = array();


    if ($imageUpload['error'] != 0) {
        array_push($errorList, "You must upload an image to create a product. code " . $imageUpload['error']);
    } else {
        $imageName = $imageUpload['name'];
        $info = getimagesize($imageUpload["tmp_name"]);

        if ($info == FALSE) {
            array_push($errorList, "Error uploading image, it doesn't look like a valid image file");
        }
    }

    $valueList = array('name' => $name, 'description' => $description, 'price' => $price, 'categoryID' => $categoryID);

    if ($name > 100) {
        $valueList['name'] = '';
        array_push($errorList, "Name must be less than 100 characters.");
    }

    if ($price < 0) {
        $valueList['price'] = '';
        array_push($errorList, "Price cannot be negative.");
    }

    if ($description > 1000) {
        $valueList['description'] = '';
        array_push($errorList, "Description must be less than 100.");
    }

    if ($errorList) {
        $app->render('product_add.html.twig', array(
            'errorList' => $errorList,
            'v' => $valueList
        ));
    } else {
        $imageName = preg_replace('/[^a-zA-Z0-9-]/', '_', $imageName);
        $imageName = md5($imageName . time());
        move_uploaded_file($imageUpload['tmp_name'], 'uploads/' . $imageName . '.jpg'); //might be something with the slash for upload
        DB::insert('product', array('name' => $name, 'description' => $description, 'price' => $price, 'categoryID' => $categoryID, 'image_path' => '/uploads/' . $imageName . '.jpg'));

        $app->render('product_add_success.html.twig', array(
            'imagePath' => '/uploads/' . $imageName,
            'description' => $description
        ));
    }
});

//PRODUCT_LIST------------------------------------------------------------------------------------------------------------------------------------------------------------
$app->get('/product/list(/)', function () use ($app) {
    if (!isset($_SESSION['auth'])) {
        $app->redirect('/web/admin.php/forbidden/');
    } else {
        $app->render('product_list.html.twig', array());
    }
});

//PRODUCT_DELETE------------------------------------------------------------------------------------------------------------------------------------------------------------
$app->get('/product/delete(/)(:productID)', function($productID = 0) use ($app) {
    if (!isset($_SESSION['auth'])) {
        $app->redirect('/web/admin.php/forbidden/');
    } else {
        if ($productID != 0) {
            $productInfo = DB::queryFirstRow('SELECT * FROM product WHERE productID=%d', $productID);

            $app->render('product_delete.html.twig', array(
                'productID' => $productInfo['productID'],
                'categoryID' => $productInfo['categoryID'],
                'description' => $productInfo['description'],
                'image_path' => $productInfo['image_path'],
                'price' => $productInfo['price'],
                'name' => $productInfo['name']));
        } else {
            $app->render('product_delete.html.twig');
            echo 'Enter product ID to delete in the URL.';
        }
    }
});

$app->post('/product/delete(/)(:productID)', function() use ($app) {
    $productID = $app->request()->post('productID');
    $errorList = array();


    //ENTER VALIDATION HERE --> IF $ERRORLIST{}ELSE{

    $productCheck = DB::queryFirstRow('SELECT * FROM product WHERE productID=%s', $productID);
    DB::delete('product', "productID=%d", $productID);
    $app->render('product_delete_success.html.twig');
});

//PRODUCT_EDIT------------------------------------------------------------------------------------------------------------------------------------------------------------
$app->get('/product/edit(/)(:productID)', function($productID = 0) use ($app) {
    $errorList = array();
    if (!isset($_SESSION['auth'])) {
        $app->redirect('/web/admin.php/forbidden/');
    } else {

        $productCheck = productCheck($productID);

        if ($productID != 0) {
            if ($productCheck) {
                $productInfo = DB::queryFirstRow('SELECT * FROM product WHERE productID=%d', $productID);
                $app->render('product_edit.html.twig', array(
                    'productID' => $productInfo['productID'],
                    'categoryID' => $productInfo['categoryID'],
                    'name' => $productInfo['name'],
                    'description' => $productInfo['description'],
                    'price' => $productInfo['price'],
                    'image_path' => $productInfo['image_path'],
                ));
            } else {
                $app->render('product_edit.html.twig');
                echo 'invalid product ID.';
            }
        } else {
            $app->render('product_edit.html.twig');
            echo 'Please select a product.';
        }
    }
});

$app->post('/product/edit/(:productID)', function($productID = 0) use ($app) {
    $name = $app->request()->post('name');
    $categoryID = $app->request()->post('categoryID');
    $price = $app->request()->post('price');
    $description = $app->request()->post('description');
    $imageUpload = $_FILES['imageUpload'];
    $errorList = array();

    if ($imageUpload['error'] != 0) {
        array_push($errorList, "You must upload an image to update a product. code " . $imageUpload['error']);
    } else {
        $imageName = $imageUpload['name'];
        $info = getimagesize($imageUpload["tmp_name"]);

        if ($info == FALSE) {
            array_push($errorList, "Error uploading image, it doesn't look like a valid image file");
        }
    }

    $valueList = array('name' => $name, 'description' => $description, 'price' => $price, 'categoryID' => $categoryID);

    if ($name > 100) {
        $valueList['name'] = '';
        array_push($errorList, "Name must be less than 100 characters.");
    }

    if ($price < 0) {
        $valueList['price'] = '';
        array_push($errorList, "Price cannot be negative.");
    }

    if ($description > 1000) {
        $valueList['description'] = '';
        array_push($errorList, "Description must be less than 100.");
    }

    if ($description == "" || $name == "") {
        array_push($errorList, "Cannot leave any blank fields.");
    }

    if ($errorList) {
        $app->render('product_edit.html.twig', array(
            'errorList' => $errorList,
            'v' => $valueList
        ));
    } else {
        $imageName = preg_replace('/[^a-zA-Z0-9-]/', '_', $imageName);
        $imageName = md5($imageName . time());
        move_uploaded_file($imageUpload['tmp_name'], 'uploads/' . $imageName . '.jpg');

        DB::update('product', array(
            'name' => $name,
            'categoryID' => $categoryID,
            'price' => $price,
            'description' => $description,
            'image_path' => '/uploads/' . $imageName . '.jpg'
                ), "productID=%d", $productID);
        $app->render('product_edit_success.html.twig');
    }
});


//LOGIN------------------------------------------------------------------------------------------------------------------------------------------------------------
$app->get('/login(/)', function() use ($app) {
    $app->render('login.html.twig');
});

$app->post('/login(/)', function() use ($app) {
    $user = $app->request()->post('user');
    $_SESSION['auth'] = array(
        'user' => $user,
    );
    $app->redirect('/web/admin.php/main');
});

//MAIN_PAGE------------------------------------------------------------------------------------------------------------------------------------------------------------
$app->get('/main(/)', function () use ($app) {
    $app->render('main.html.twig');
});

//PRODUCT------------------------------------------------------------------------------------------------------------------------------------------------------------
$app->get('/product(/)(:productID)', function($productID = 1) use ($app) {

    $productCheck = productCheck($productID);

    if ($productID != 0) {
        if ($productCheck) {
            $productInfo = DB::queryFirstRow('SELECT * FROM product WHERE productID=%d', $productID);
            $app->render('product.html.twig', array(
                'productID' => $productInfo['productID'],
                'categoryID' => $productInfo['categoryID'],
                'name' => $productInfo['name'],
                'description' => $productInfo['description'],
                'price' => $productInfo['price'],
                'image_path' => $productInfo['image_path'],
            ));
        } else {
            $app->render('product.html.twig');
            echo 'invalid product ID.';
        }
    } else {
        $app->render('product.html.twig');
        echo 'Please select a product.';
    }
});

$app->post('/product/(:productID)', function($productID = 0) use ($app, $sessionID) {
    $quantity = $app->request()->post('quantity');
    $errorList = array();

    $productCartCheck = productCartCheck($productID, $sessionID);

    if ($productCartCheck >= 1) {
        array_push($errorList, "This item is already in your cart. Access it from the Cart button in the menu.");
        $app->render('error_internal.html.twig', array(
            'errorList' => $errorList,
        ));
    } else {
        addCart($sessionID, $productID, $quantity);
        $app->render('product_cart_success.html.twig');
    }
}
);

//CATEGORY------------------------------------------------------------------------------------------------------------------------------------------------------------
$app->get('/category(/)(:categoryID)', function($categoryID = 1) use ($app) {
    $errorList = array();

    $categoryCheck = categoryCheck($categoryID);
    $categoryProducts = categoryProducts($categoryID);

    if ($categoryID != 0) {
        if ($categoryCheck) {
            $app->render('category.html.twig', array(
                'name' => $categoryCheck['name'],
                'categoryProducts' => $categoryProducts
            ));
        } else {
            $app->render('category.html.twig');
            echo 'invalid category ID.';
        }
    } else {
        $app->render('category.html.twig');
        echo 'No category selected.';
    }
});

$app->post('/category/edit/(:categoryID)', function($categoryID = 0) use ($app) {
    $name = $app->request()->post('name');
    $categoryID = $app->request()->post('categoryID');


    //ENTER VALIDATION HERE --> IF $ERRORLIST{}ELSE{
    DB::update('category', array(
        'name' => $name), "categoryID=%d", $categoryID);
    $app->render('category_edit_success.html.twig');
});

//CART------------------------------------------------------------------------------------------------------------------------------------------------------------
$app->get('/cart(/)', function() use ($app, $sessionID) {
    $errorList = array();
    $cartInfo = cartInfo($sessionID);

    $totalBeforeTaxes = totalBeforeTaxes($sessionID);
    $totalAfterTaxes = totalAfterTaxes($totalBeforeTaxes);

    if ($cartInfo) {
        $app->render('cart.html.twig', array(
            'cartInfo' => $cartInfo,
            'totalBeforeTaxes' => $totalBeforeTaxes,
            'totalAfterTaxes' => $totalAfterTaxes,
        ));
    } else {
        array_push($errorList, "You have no items in your cart.");
        $app->render('cart.html.twig', array(
            'errorList' => $errorList,
        ));
    }
});

$app->post('/cart/', function() use ($app, $sessionID) {
    $quantity = $app->request()->post('quantity');
    $productID = $app->request()->post('productID');
    $delete = $app->request()->post('delete');


    if ($delete) {
        deleteItemCart($productID, $sessionID);
    }


    updateCart($sessionID, $productID, $quantity);
    $cartInfo = cartInfo($sessionID);
    $totalBeforeTaxes = totalBeforeTaxes($sessionID);
    $totalAfterTaxes = totalAfterTaxes($totalBeforeTaxes);
    $app->render('cart.html.twig', array(
        'cartInfo' => $cartInfo,
        'updated' => '-- Cart updated successfully. --',
        'totalBeforeTaxes' => $totalBeforeTaxes,
        'totalAfterTaxes' => $totalAfterTaxes,
    ));
});

//ORDER------------------------------------------------------------------------------------------------------------------------------------------------------------
$app->get('/order(/)', function() use ($app, $sessionID) {
    $cartInfo = cartInfo($sessionID);
    $totalBeforeTaxes = totalBeforeTaxes($sessionID);
    $totalAfterTaxes = totalAfterTaxes($totalBeforeTaxes);
    $taxes = $totalAfterTaxes - $totalBeforeTaxes;

    $app->render('order.html.twig', array(
        'totalBeforeTaxes' => $totalBeforeTaxes,
        'totalAfterTaxes' => $totalAfterTaxes,
        'taxes' => $taxes
    ));
});

$app->post('/order(/)', function() use ($app, $sessionID) {
    $firstName = $app->request()->post('firstName');
    $lastName = $app->request()->post('lastName');
    $address = $app->request()->post('address');
    $postCode = $app->request()->post('postCode');
    $country = $app->request()->post('country');
    $province = $app->request()->post('province');
    $email = $app->request()->post('email');
    $phone = $app->request()->post('phone');
    $ccNumber = $app->request()->post('ccNumber');
    $ccExpiry = $app->request()->post('ccExpiry');
    $ccCVV = $app->request()->post('ccCVV');
    $errorList = array();


    $totalBeforeTaxes = totalBeforeTaxes($sessionID);
    $totalAfterTaxes = totalAfterTaxes($totalBeforeTaxes);
    $taxes = $totalAfterTaxes - $totalBeforeTaxes;

    $valueList = array(
        'firstName' => $firstName,
        'lastName' => $lastName,
        'address' => $address,
        'postCode' => $postCode,
        'country' => $country,
        'province' => $province,
        'email' => $email,
        'phone' => $phone,
        'ccNumber' => $ccNumber,
        'ccExpiry' => $ccExpiry,
        'totalBeforeTaxes' => $totalBeforeTaxes,
        'taxes' => $taxes,
        'totalAfterTaxes' => $totalAfterTaxes,
    );


    //VALIDATION OF ALL INPUTS HERE WITH ARRAY PUSH TO ERRORLIST

    if ($errorList) {
        $app->render('order.html.twig', array(
            'errorList' => $errorList,
            'v' => $valueList
        ));
    } else {

        DB::$error_handler = FALSE;
        DB::$throw_exception_on_error = TRUE;

        try {
            DB::startTransaction();
            // 1. create summary record in 'orders' table (insert)
            DB::insert('order_header', array(
                'first_name' => $firstName,
                'last_name' => $lastName,
                'address' => $address,
                'postCode' => $postCode,
                'country' => $country,
                'provinceorstate' => $province,
                'email' => $email,
                'phone' => $phone,
                'credit_card_no' => $ccNumber,
                'credit_card_expiry' => $ccExpiry,
                'credit_card_cvv' => $ccCVV,
                'total_before_tax_and_delivery' => $totalBeforeTaxes,
                'taxes' => $taxes,
                'total_final' => $totalAfterTaxes,
            ));
            $orderID = DB::insertId();
            // 2. copy all records from cartitems to 'orderitems' (select & insert)
            $cartitemList = DB::query('SELECT category.name AS categoryName, cart_item.quantity, product.name AS productName, product.description, product.price, '
                            . 'product.image_path FROM cart_item INNER JOIN product ON cart_item.productID=product.productID INNER JOIN category '
                            . 'ON category.categoryID=product.categoryID WHERE sessionID=%s', $sessionID);

            foreach ($cartitemList as $cart) {
                DB::insert('order_item', array(
                    'order_headerID' => $orderID,
                    'category_name' => $cart['categoryName'],
                    'name' => $cart['productName'],
                    'description' => $cart['description'],
                    'image_path' => $cart['image_path'],
                    'unit_price' => $cart['price'],
                    'quantity' => $cart['quantity']
                ));
            }

            DB::delete('cart_item', "sessionID=%s", $sessionID);

            DB::commit();

            $app->render('order_success.html.twig');
        } catch (MeekroDBException $e) {

            sql_error_handler(array(
                'error' => $e->getMessage(),
                'query' => $e->getQuery()
            ));
        }
    }
});

//PRODUCT_ADD_AJAX------------------------------------------------------------------------------------------------------------------------------------------------------------
$app->get('/ajax/cart/add/product/:productID/quantity/:quantity', function($productID = 0, $quantity = 0) use ($app, $sessionID) {
    updateCart($sessionID, $productID, $quantity);
});

//FORBIDDEN------------------------------------------------------------------------------------------------------------------------------------------------------------
$app->get('/forbidden(/)', function() use ($app) {
    $app->render('forbidden.html.twig');
});

//ERROR------------------------------------------------------------------------------------------------------------------------------------------------------------
$app->get('/error(/)', function() use ($app) {
    $app->render('error_internal.html.twig');
});

//LOGOUT------------------------------------------------------------------------------------------------------------------------------------------------------------
$app->get('/logout(/)', function() use ($app) {
    unset($_SESSION['auth']);
    $app->render('logout.html.twig');
});

$app->run();



















