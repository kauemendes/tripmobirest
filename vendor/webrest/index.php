<?php
/*

	API restful by Fabio Pedrosa
	for TripMobi App Beta on amazon AWS SERVICE
	
	TODO: 

*/


require '../slim/slim/Slim/Slim.php';

\Slim\Slim::registerAutoloader();

$app = new \Slim\Slim();
$app->response()->header('Content-Type', 'application/json;charset=utf-8');
$app->response()->header('Access-Control-Allow-Origin', '*');
// $app->response()->header("Access-Control-Allow-Methods: PUT, GET, POST, DELETE, OPTIONS");


/*$app->get('/wines', 'getWines');
$app->get('/wines/:id',  'getWine');
$app->get('/wines/search/:query', 'findByName');
$app->post('/wines', 'addWine');
$app->put('/wines/:id', 'updateWine');
$app->delete('/wines/:id',   'deleteWine');*/




// $app->get('/checklist','getChecklist');

$app->get('/login/:username/:password','getLogin');
$app->post('/login/:username/:password','newLogin');

$app->get('/destino','listDestinos');
$app->get('/destino/:id','getDestino');

$app->run();

function getConn()
{
	return new PDO('mysql:host=104.236.96.121;dbname=tripmobi', 'fellas', 'fellas1020', 
		array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8")
	);
}
function getLogin($user,$password)
{
	$sh = md5($password);
    $sql = "SELECT * FROM trip_user WHERE email = '$user' AND passwd = '$sh'";
    try {
        $db = getConn();
        $stmt = $db->query($sql);
        $login = $stmt->fetchAll(PDO::FETCH_OBJ);
        $db = null;
		$arrar['logged'] = $login;
		echo json_encode($arrar);
    } catch(PDOException $e) {
        echo '{"error":{"text":'. $e->getMessage() .'}}';
    }

}
function newLogin($user,$psswd)
{
	$request = \Slim\Slim::getInstance()->request();
	$trip_user = json_decode($request->getBody());
	$db = getConn();

	$psswd = md5($psswd);

	$sql = "SELECT * FROM trip_user WHERE email = '$user' AND passwd = '$psswd'";
	$stmt = $db->query($sql);

	if( $stmt->rowCount() ){
		$login = $stmt->fetchAll(PDO::FETCH_OBJ);
		$db = null;
		$arrar['logged'] = $login;
		$arrar['logged']['notnew'] = 1;
		echo json_encode($arrar);
	} else {
		$sql = "INSERT INTO trip_user (email,passwd) values (:email,:passwd) ";
		
		$stmt = $db->prepare($sql);
		$stmt->bindParam("email",$user);
		$stmt->bindParam("passwd",$psswd);
		$stmt->execute();

		$lastInsertId = $db->lastInsertId();
		// echo json_encode($trip_user);

	    $sql = "SELECT * FROM trip_user WHERE id = '$lastInsertId'";
	    try {
	        $db = getConn();
	        $stmt = $db->query($sql);
	        $login = $stmt->fetchAll(PDO::FETCH_OBJ);
	        $db = null;
			$arrar['logged'] = $login;
			$arrar['logged']['notnew'] = 0;
			echo json_encode($arrar);
	    } catch(PDOException $e) {
	        echo '{"error":{"text":'. $e->getMessage() .'}}';
	    }
	}

}

function listDestinos()
{
    $sql = "SELECT * FROM trip_destino";
    try {
        $db = getConn();
        $stmt = $db->query($sql);
        $login = $stmt->fetchAll(PDO::FETCH_OBJ);
        $db = null;
		$arrar['destino'] = $login;
		echo json_encode($arrar);
    } catch(PDOException $e) {
        echo '{"error":{"text":'. $e->getMessage() .'}}';
    }
}
function getDestino($id)
{
    $sql = "SELECT td.desc, td.html, td.destino, td.id, tdc.desc_full  FROM trip_destino_completo tdc JOIN trip_destino td ON td.id = tdc.id_destino  WHERE tdc.id_destino = '$id'";
    try {
        $db = getConn();
        $stmt = $db->query($sql);
        $destino = $stmt->fetchAll(PDO::FETCH_OBJ);
        $db = null;
		echo json_encode($destino);
    } catch(PDOException $e) {
        echo '{"error":{"text":'. $e->getMessage() .'}}';
    }

}

function addDestino()
{
	$request = \Slim\Slim::getInstance()->request();
	$trip_destino = json_decode($request->getBody());
	
	$sql = "INSERT INTO trip_destino (id_user,destino,lat,lng,id_checklist) values (:id_user,:destino,:lat,:lng,:id_checklist) ";
	$conn = getConn();
	
	$stmt = $conn->prepare($sql);
	$stmt->bindParam("id_user",$trip_destino->id_user);
	$stmt->bindParam("destino",$trip_destino->destino);
	$stmt->bindParam("lat",$trip_destino->lat);
	$stmt->bindParam("lng",$trip_destino->lng);
	$stmt->bindParam("id_checklist",$trip_destino->id_checklist);
	$stmt->execute();

	$trip_destino->id = $conn->lastInsertId();
	echo json_encode($trip_destino);
}

function getProduto($id)
{
	$conn = getConn();
	$sql = "SELECT * FROM trip_destino WHERE id=:id";
	$stmt = $conn->prepare($sql);
	$stmt->bindParam("id",$id);
	$stmt->execute();
	$trip_destino = $stmt->fetchObject();

	//categoria
	$sql = "SELECT * FROM trip_checklist WHERE id=:id";
	$stmt = $conn->prepare($sql);
	$stmt->bindParam("id",$trip_destino->id_checklist);
	$stmt->execute();
	$trip_destino->trip_checklist = $stmt->fetchObject();

	echo json_encode($trip_destino);
}

function saveProduto($id)
{
	$request = \Slim\Slim::getInstance()->request();
	$produto = json_decode($request->getBody());
	$sql = "UPDATE produtos SET nome=:nome,preco=:preco,dataInclusao=:dataInclusao,idCategoria=:idCategoria WHERE   id=:id";
	$conn = getConn();
	$stmt = $conn->prepare($sql);
	$stmt->bindParam("nome",$produto->nome);
	$stmt->bindParam("preco",$produto->preco);
	$stmt->bindParam("dataInclusao",$produto->dataInclusao);
	$stmt->bindParam("idCategoria",$produto->idCategoria);
	$stmt->bindParam("id",$id);
	$stmt->execute();
	echo json_encode($produto);
}

function deleteProduto($id)
{
	$sql = "DELETE FROM produtos WHERE id=:id";
	$conn = getConn();
	$stmt = $conn->prepare($sql);
	$stmt->bindParam("id",$id);
	$stmt->execute();
	echo "{'message':'Produto apagado'}";
}

function getProdutos()
{
	$sql = "SELECT *,Categorias.nome as nomeCategoria FROM Produtos,Categorias WHERE Categorias.id=Produtos.idCategoria";
	$stmt = getConn()->query($sql);
	$produtos = $stmt->fetchAll(PDO::FETCH_OBJ);
	echo "{\"produtos\":".json_encode($produtos)."}";
}