<?php 
session_start();

require_once("vendor/autoload.php");

use \Slim\Slim;
use \Hcode\Page;
use \Hcode\PageAdmin;
use \Hcode\Model\User;
use \Hcode\Model\Category;
use \Hcode\Model\Products;
use \Hcode\Model\Cart;
use \Hcode\Model\Address;


$app = new Slim();

$app->config('debug', true);

require_once("functions.php");

$app->get('/', function() {
    
    $products = Products::listAll();

	$page = new Page();

	$page->setTpl("index",	[
		'products'=>Products::checkList($products)
		]);

});

$app->get('/admin', function() {

	User::verifyLogin(true);

	$page = new PageAdmin();

	$page->setTpl("index");

});

$app->get('/admin/login', function() {

	$page = new PageAdmin([
		"header"=>false, 
		"footer"=>false
	]);

	$page->setTpl("login",[
		'error'=>User::getMsgError()
	]);

});

$app->post('/admin/login', function()
{
try{
		User::login($_POST['login'], $_POST['password']);
	} catch(Exception $e){
		User::setMsgError($e->getMessage());
	}

	header("location: /admin");

	exit;

});

$app->get('/admin/logout', function()
{
	User::logout();
	header("location: /admin/login");
	exit;
});


$app->get("/admin/users", function() {
	User::verifyLogin(true);
	$users= new User();
	$users =User::listAll();

	$page = new PageAdmin();

	$page->setTpl("users", array(
		"users" => $users
	));

}); 

$app->get('/admin/users/create', function() {
	User::verifyLogin();
	
	$page = new PageAdmin();
	$page->setTpl("users-create");

});

$app->get('/admin/users/:iduser/delete', function($iduser) {
	User::verifyLogin();
	$user = new User();
	$user->get((int)$iduser);
	$user->setData($_POST);
	$user->delete();
	header("Location: /admin/users");
	exit;

});

$app->get('/admin/users/:iduser', function($iduser) {
	User::verifyLogin();

	$user = new User();

	$user->get((int)$iduser);
	
	$page = new PageAdmin();

	$page->setTpl("users-update", array(
		"user"=>$user->getValues()
	));

});

$app->post('/admin/users/create', function() {
	
	User::verifyLogin(true);

	$user = new User();

	$_POST["inadmin"] = (isset($_POST["inadmin"]))?1:0;
	
	$user->setData($_POST);
	
	$user->save();

	header("Location: /admin/users");

	 exit;

});

$app->post('/admin/users/:iduser', function($iduser) {
	User::verifyLogin();
	$user = new User();
	$_POST["inadmin"] = (isset($_POST["inadmin"]))?1:0;
	$user->get((int)$iduser);
	$user->setData($_POST);
	$user->update();
	header("Location: /admin/users");

	 exit;
});

$app->get('/admin/forgot', function() {

	$page = new PageAdmin([
		"header"=>false, 
		"footer"=>false,
	]);

	$page->setTpl("forgot");

});

$app->post("/admin/forgot", function(){
	
	$user = User::getForgot($_POST["email"]);

	header("Location: /admin/forgot/sent");
	exit;
});

$app->get("/admin/forgot/sent", function(){
	$page = new PageAdmin([
		"header"=>false, 
		"footer"=>false,
	]);

	$page->setTpl("forgot-sent");
 
});

$app->get("/admin/forgot/reset", function(){
	try{
		$user = User::validForgotDecrypt($_GET["code"]);
	} catch(Exception $e){
		User::setMsgError($e->getMessage());
		$user =['desperson'=>''];
	}
	
	$page = new PageAdmin([
		"header"=>false, 
		"footer"=>false,
	]);
	$page->setTpl("forgot-reset", array(
		"name"=>$user["desperson"],
		"code"=>$_GET["code"],
		"error"=>User::getMsgError()
	));
 
});

$app->post("/admin/forgot/reset", function(){

	$forgot = User::validForgotDecrypt($_POST["code"]);

	User::setForgotUsed($forgot["idrecovery"]);

	$user = new User();

	$user->get((int)$forgot["iduser"]);

	$password = password_hash($_POST["password"], PASSWORD_DEFAULT, [
		"cost"=>12
	]);

	$user->setPassword($password);

	$page = new PageAdmin([
		"header"=>false, 
		"footer"=>false,
	]);

	$page->setTpl("forgot-reset-success"
	);

});

$app->get('/admin/categories', function()
{
	User::verifyLogin();

	$categories = Category::listAll();

	$page = new PageAdmin();

	$page->setTpl("categories",[
		'categories'=>$categories]);

});

$app->get('/admin/categories/create', function()
{
	User::verifyLogin();

	$page = new PageAdmin();

	$page->setTpl("categories-create");

});

$app->post('/admin/categories/create', function()
{
	User::verifyLogin();

	$category = new Category();

	$category->setData($_POST);

	$category->save();

	header('Location: /admin/categories');
	exit;

});

$app->get('/admin/categories/:idcategory/delete', function($idcategory)
{
	User::verifyLogin();

	$category = new Category();

	$category->get((int)$idcategory);

	$category->delete();

	header('Location: /admin/categories');
	exit;

});

$app->get('/admin/categories/:idcategory', function($idcategory)
{
	User::verifyLogin();

	$category = new Category();

	$category->get((int)$idcategory);

	$page = new PageAdmin();

	$page->setTpl("categories-update", ['category'=>$category->getValues()]);

});

$app->post('/admin/categories/:idcategory', function($idcategory)
{		
	User::verifyLogin();

	$category = new Category();

	$category->get((int)$idcategory);

	$category->setData($_POST);

	$category->SAVE();

	header('Location: /admin/categories');

	exit;

	});


$app->get("/admin/categories/:idcategory/products", function($idcategory)
{
	User::verifyLogin();

	$category = new Category();

	$category->get((int)$idcategory);

	$page = new PageAdmin();

	$page->setTpl("categories-products", [
		'category'=>$category->getValues(),
		'productsRelated'=>$category->getProducts(),
		'productsNotRelated'=>$category->getProducts(false)
	]);

	});


$app->get("/admin/categories/:idcategory/products/:idproduct/add", function($idcategory, $idproduct)
{
	User::verifyLogin();

	$category = new Category();

	$category->get((int)$idcategory);

	$product = new Products();

	$product->get((int)$idproduct);

	$category->addProduct($product);

	header("Location: /admin/categories/".$idcategory."/products");
	exit;

	});

$app->get("/admin/categories/:idcategory/products/:idproduct/remove", function($idcategory, $idproduct)
{
	User::verifyLogin();

	$category = new Category();

	$category->get((int)$idcategory);

	$product = new Products();

	$product->get((int)$idproduct);

	$category->removeProduct($product);

	header("Location: /admin/categories/".$idcategory."/products");
	exit;

	});


$app->get('/categories/:idcategory', function($idcategory)
{
	$page = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;

	$category = new Category();

	$category->get((int)$idcategory);

	$pagination = $category->getProductsPage($page);

	$pages = [];

	for ($i=1; $i <= $pagination["pages"] ; $i++) { 
		array_push($pages, [
			'link'=>'/categories/'.$category->getidcategory().'?page='.$i,
			'page'=>$i
		]);
	}

	$page = new Page();

	$page->setTpl("category", [
		'category'=>$category->getValues(),
		'products'=>$pagination['data'],
		'pages'=>$pages
	]);

	});

$app->get("/products/:desurl", function($desurl){

	$product = new Products();

	$product->getFromURL($desurl);

	$page = new Page();

	$page->setTpl('product-detail',[
		'product'=>$product->getValues(),
		'categories'=>$product->getCategories()

	]);

});

$app->get("/cart", function(){

	$cart = new Cart();

	$cart = Cart::getFromSession();

	$page = new Page();

	$page->setTpl("cart", [
		'cart'=>$cart->getValues(),
		'products'=>$cart->getProducts(),
		'error'=>Cart::getMsgError()
	]);

});

$app->get("/cart/:idproduct/add", function($idproduct){

$product = new Products();

$product->get((int)$idproduct);

$cart = Cart::getFromSession();

$qtd = (isset($_GET['qtd'])) ? (int)$_GET['qtd'] : 1;

for ($i=0; $i < $qtd; $i++) { 
	$cart->addProduct($product);
}

header("Location: /cart");

exit;

});

$app->get("/cart/:idproduct/minus", function($idproduct){

$product = NEW Products();

$product->get((int)$idproduct);

$cart = Cart::getFromducts();

$product->get((int)$idproduct);

$cart = Cart::getFromSession();

$cart->removeProduct($product, true);

header("Location: /cart");

exit;

});

$app->post("/cart/freight", function()
{

	$cart =  Cart::getFromSession();

	$cart->setFreight($_POST['zipcode']);

	header("Location: /cart");
	exit;
});
$app->get("/checkout", function(){

	User::verifyLogin(false);

	$address = new Address();

	$cart = Cart::getFromSession();

			if (isset($_GET['zipcode'])) {
				$address->loadFromCEP($_GET['zipcode']);

				$cart->setdeszipcode($_GET['zipcode']);

				$cart->save();

				$cart->getCalculateTotal();
			} 

			if(!$address->getdesaddress()) $address->setdesaddress('');
			if(!$address->getdescomplement()) $address->setdescomplement('');
			if(!$address->getdesdistrict()) $address->setdesdistrict('');
			if(!$address->getdescity()) $address->setdescity('');
			if(!$address->getdesstate()) $address->setdesstate('');
			if(!$address->getdescountry()) $address->setdescountry('');
			if(!$address->getdeszipcode()) $address->setdeszipcode('');

	$page = new Page();

	$page->setTpl("checkout",[
		'cart'=>$cart->getValues(),
		'address'=>$address->getValues(),
		'error'=>User::getMsgError()
	]);
});

$app->post('/checkout', function() 
{

	User::verifyLogin(false);

	if (!isset($_POST['zipcode']) || $_POST['zipcode'] == ''){

			User::setMsgError('Informe o CEP.');
			header("location: /checkout");
			exit;
		}

		if (!isset($_POST['desaddress']) || $_POST['desaddress'] == ''){

			User::setMsgError('Informe o endereço.');
			header("location: /checkout");
			exit;
		}
		if (!isset($_POST['desdistrict']) || $_POST['desdistrict'] == ''){

			User::setMsgError('Informe o bairro.');
			header("location: /checkout");
			exit;
		}
		if (!isset($_POST['descity']) || $_POST['descity'] == ''){

			User::setMsgError('Informe a cidade.');
			header("location: /checkout");
			exit;
		}
		if (!isset($_POST['desstate']) || $_POST['desstate'] == ''){

			User::setMsgError('Informe o estado UF.');
			header("location: /checkout");
			exit;
		}
		if (!isset($_POST['descountry']) || $_POST['descountry'] == ''){

			User::setMsgError('Informe o país.');
			header("location: /checkout");
			exit;
		}


	$user = User::getFromSession();

	$address = new Address();

	$_POST['deszipcode'] = $_POST['zipcode'];
	$_POST['idperson'] = $user->getidperson();

	$address->setData($_POST);

	$address->save();

	header("Location: /order");
	exit;

});

$app->get('/forgot', function() {

	$page = new Page();

	$page->setTpl("forgot");

});

$app->post("/forgot", function(){
	
	$user = User::getForgot($_POST["email"], false);

	header("Location: /forgot/sent");
	exit;
});

$app->get("/forgot/sent", function(){
	$page = new Page();

	$page->setTpl("forgot-sent");
 
});

$app->get("/forgot/reset", function(){
	try{
		$user = User::validForgotDecrypt($_GET["code"]);
	} catch(Exception $e){
		User::setMsgError($e->getMessage());
		$user =['desperson'=>''];
	}

	$page = new Page();
	$page->setTpl("forgot-reset", array(
		"name"=>$user["desperson"],
		"code"=>$_GET["code"],
		"error"=>User::getMsgError()
	));
 
});

$app->post("/forgot/reset", function(){

	$forgot = User::validForgotDecrypt($_POST["code"]);

	User::setForgotUsed($forgot["idrecovery"]);

	$user = new User();

	$user->get((int)$forgot["iduser"]);

	$password = password_hash($_POST["password"], PASSWORD_DEFAULT, [
		"cost"=>12
	]);

	$user->setPassword($password);

	$page = new Page();

	$page->setTpl("forgot-reset-success"
	);

});

$app->get("/login", function(){

	$page = new Page();

	$page->setTpl("login", [
		'error'=>User::getMsgError(),
		'errorRegister'=>User::getErrorRegister(),
		'registervalues'=>(isset($_SESSION['registervalues'])) ? $_SESSION['registervalues'] :['name'=>'', 'email'=>'', 'phone'=>'']
	]);
});

$app->post("/login", function(){
	try{
		User::login($_POST['login'], $_POST['password']);
	} catch(Exception $e){
		User::setMsgError($e->getMessage());
	}
header('Location: /checkout');
	exit;
	
});

$app->get("/logout", function(){

	User::logout();

	header('Location: /login');
	exit;

});

$app->post("/register", function()
{
	$_SESSION['registervalues'] = $_POST;
		if (!isset($_POST['name']) || $_POST['name'] == ''){

			User::setErrorRegister('Preebcha o seu nome.');
			header("location: /login");
			exit;
		}

		$_SESSION['registervalues'] = $_POST;
		if (!isset($_POST['email']) || $_POST['email'] == ''){

			User::setErrorRegister('Preebcha o seu email.');
			header("location: /login");
			exit;
		}

		$_SESSION['registervalues'] = $_POST;
		if (!isset($_POST['phone']) || $_POST['phone'] == ''){

			User::setErrorRegister('Preebcha o seu telefone.');
			header("location: /login");
			exit;
		}

		if (User::checkloginExist($_POST['email']) === true){
			User::setErrorRegister('Este endereço de e-mail já esta sendo usado por outro usuário.');
			header("location: /login");
			exit;
		}

		$_SESSION['registervalues'] = $_POST;
		if (!isset($_POST['password']) || $_POST['password'] == ''){

			User::setErrorRegister('Preebcha a sua senha.');
			header("location: /login");
			exit;
		}

	$user = new User();
	
	$user->setData([
		'inadmin'=>0,
		'deslogin'=>$_POST['email'],
		'desperson'=>$_POST['name'],
		'desemail'=>$_POST['email'],
		'despassword'=>$_POST['password'],
		'nrphone'=>$_POST['phone']

	]);

	$user->save();

	User::login($_POST['email'], $_POST['password']);

	header("location: /checkout");
			exit;

});

$app->get("/profile", function(){
	User::verifyLogin(false);
	$user = User::getFromSession();
	$page = new Page();
	$page->setTpl("profile", [
		'user'=>$user->getValues(),
		'profileMsg'=>User::getSuccess(),
		'profileError'=>User::getMsgError()
	]);
});

$app->post("/profile",function(){
	User::verifyLogin(false);

	if (!isset($_POST['desperson']) || $_POST['desperson'] === ''){
		User::setMsgError("Preencha o seu nome.");
		header('Location: /profile');
		exit;

	}

	if (!isset($_POST['desemail']) || $_POST['desemail'] === ''){
		User::setMsgError("Preencha o seu e-mail.");
		header('Location: /profile');
		exit;
	}

	$user = User::getFromSession();

	if ($_POST['desemail'] !== $user->getdesemail()){

		if (User::checkloginExist($_POST['desemail']) === true){
			User::setMsgError("Este endereço de e-mail ja está cadastrado.");
			header('Location: /profile');
			exit;
		}
}

	$_POST['inadmin'] = $user->getinadmin();
	$_POST['despassword'] = $user->getdespassword();
	$_POST['deslogin'] = $_POST['desemail'];

	$user->setData($_POST);

	$user->update();

	User::setSuccess("Dados alterados com sucesso!");

	header('Location: /profile');
	exit;
});

$app->get('/admin/products', function()
{
	User::verifyLogin();
	$products = Products::listAll();
	$page = new PageAdmin();
	$page->setTpl("products",[
		'products'=>$products]);

});

$app->get('/admin/products/create', function()
{
	User::verifyLogin();
	$page = new PageAdmin();
	$page->setTpl("products-create");

});

$app->post('/admin/products/create', function()
{
	User::verifyLogin();

	$product = new Products();

	$product->setData($_POST);

	$product->save();

	header('Location: /admin/products');

	exit;
});

$app->get('/admin/products/:idproduct/delete', function($idproduct)
{
	User::verifyLogin();
	$product= new Products();
	$product->get((int)$idproduct);
	$product->delete();
	header('Location: /admin/products');
	exit;
});

$app->get('/admin/products/:idproduct', function($idproduct)
{
	User::verifyLogin();
	$product= new Products();
	$product->get((int)$idproduct);
	$page = new PageAdmin();
	$page->setTpl("products-update",[
		'product'=>$product->getValues()
	]);

});

$app->post('/admin/products/:idproduct', function($idproduct)
{
	User::verifyLogin();
	$product= new Products();
	$product->get((int)$idproduct);
	$product->setData($_POST);
	$product->save();
	if($_FILES["file"]["name"] !== "") $product->setPhoto($_FILES["file"]);
	header('Location: /admin/products');
	exit;
});


$app->run();

 ?>