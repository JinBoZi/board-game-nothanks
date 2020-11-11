<?php

// 连接数据库
function connectDB(){
	$servername = "localhost";
	$username = "nothanks";
	$password = "318NoThanks";
	$dbname = "nothanks";
	
	return new mysqli($servername, $username, $password, $dbname);
}

// 释放数据库查询得到的结果集
function freeOnce($conn){
	do{
		if($result = $conn->store_result()) $result->close();
	} while ($conn->next_result());
}

// 向客户端返回游戏信息
function show($conn, $room){
	$res = "";
	$sql = "SELECT * FROM card" . $room . ";";
	freeOnce($conn);
	$result = $conn->query($sql);
	while($row = $result->fetch_assoc()) echo $row["playerid"] . ",";
	
	$sql = "SELECT * FROM room" . $room . ";";
	freeOnce($conn);
	$result = $conn->query($sql);
	while($row = $result->fetch_assoc()) echo $row["playername"] . "," . $row["coin"] . ",";
}

// 创建游戏
function createGame($conn, $room){
	
	// 如果房间已创建(数据库表已存在)则直接返回
	$sql = "SELECT COUNT(*) AS cnt FROM room" . $room . ";";
	freeOnce($conn);
	$result = $conn->query($sql);
	if($result) return;
	
	// 创建数据库表
	$sql = "CREATE TABLE card" . $room . " (
    cardid TINYINT UNSIGNED PRIMARY KEY,
    playerid TINYINT UNSIGNED NOT NULL
);
CREATE TABLE room" . $room . " (
    playerid TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    playername VARCHAR(30) NOT NULL,
    coin TINYINT UNSIGNED NOT NULL
);";
	freeOnce($conn);
	$conn->multi_query($sql);
	
	// 初始化牌堆,随机给第一位玩家发一张牌
	$sql = "";
	for($index=3;$index<36;$index++){
		$sql .= "INSERT INTO card" . $room . " (cardid,playerid) VALUES (" . $index . ",0);";
	}
	$sql .= "INSERT INTO card" . $room . " (cardid,playerid) VALUES (36, " . mt_rand(3, 35) . ");";
	$sql .= "INSERT INTO card" . $room . " (cardid,playerid) VALUES (37, 1);";
	$sql .= "INSERT INTO card" . $room . " (cardid,playerid) VALUES (38, 0);";
	freeOnce($conn);
	$conn->multi_query($sql);
}

// 加入游戏
function joinGame($conn, $room, $name){
	
	// 如果玩家已加入(存在玩家昵称)则直接返回其ID
	$sql = "SELECT playerid FROM room" . $room . " WHERE playername=\"" . $name . "\";";
	freeOnce($conn);
	$result = $conn->query($sql);
	$row = $result->fetch_assoc();
	if($row) return $row["playerid"];
	
	// ID字段自增,所以直接插入即可
	$sql = "INSERT INTO room" . $room . " (playername,coin) VALUES (\"" . $name . "\",11);";
	freeOnce($conn);
	$conn->query($sql);
	
	// 查询并返回玩家ID
	$sql = "SELECT playerid FROM room" . $room . " WHERE playername=\"" . $name . "\";";
	freeOnce($conn);
	$result = $conn->query($sql);
	$row = $result->fetch_assoc();
	return $row["playerid"];
}

//36.which card
//37.where is card
//38.how many coin in card
// 接收卡牌
function acceptCard($conn, $room, $playerid){
	
	// 确定是哪一张牌即将接收
	$sql = "SELECT playerid FROM card" . $room . " WHERE cardid=36;";
	freeOnce($conn);
	$result = $conn->query($sql);
	$row = $result->fetch_assoc();
	$which = $row["playerid"];
	
	// 确定卡牌上的筹码数
	$sql = "SELECT playerid FROM card" . $room . " WHERE cardid=38;";
	freeOnce($conn);
	$result = $conn->query($sql);
	$row = $result->fetch_assoc();
	$howmany = $row["playerid"];
	
	// 清空卡牌上的筹码数
	$sql = "UPDATE card" . $room . " SET playerid=0 WHERE cardid=38;";
	// 标记该卡牌属于该玩家
	$sql .= "UPDATE card" . $room . " SET playerid=" . $playerid . " WHERE cardid=" . $which . ";";
	// 该玩家获得卡牌上的所有筹码
	$sql .= "UPDATE room" . $room . " SET coin=coin+" . $howmany . " WHERE playerid=" . $playerid . ";";
	freeOnce($conn);
	$conn->multi_query($sql);
}

// 传递卡牌
function passCard($conn, $room, $playerid){
	
	// 确定下一个玩家是谁
	$sql = "SELECT COUNT(*) AS cnt FROM room" . $room . ";";
	freeOnce($conn);
	$result = $conn->query($sql);
	$row = $result->fetch_assoc();
	$players = $row["cnt"];

	// 当前玩家筹码数减一
	$sql = "UPDATE room" . $room . " SET coin=coin-1 WHERE playerid=" . $playerid . ";";
	if($playerid == $players) $playerid=1;
	else $playerid++;
	// 将卡牌传给下一个玩家
	$sql .= "UPDATE card" . $room . " SET playerid=" . $playerid . " WHERE cardid=37;";
	// 卡牌上的筹码数加一
	$sql .= "UPDATE card" . $room . " SET playerid=playerid+1 WHERE cardid=38;";
	freeOnce($conn);
	$conn->multi_query($sql);
}

// 发牌
function deal($conn, $room){
	// 计算牌库中还有多少张牌
	$remain;
	$sql = "SELECT * FROM card" . $room . ";";
	freeOnce($conn);
	$result = $conn->query($sql);
	while($row = $result->fetch_assoc()){
		if($row["cardid"]<36 && $row["playerid"]==0) $remain[]=$row["cardid"];
	}
	
	$sql = "UPDATE card" . $room . " SET playerid=" . $remain[mt_rand(0, count($remain)-1)] . " WHERE cardid=36;";
	if(count($remain) == 9){
		// 剩余牌为9即游戏结束,将传递的卡牌点数置为0以便各个客户端都能获取到该信息
		$sql = "UPDATE card" . $room . " SET playerid=0 WHERE cardid=36;";
	}
	freeOnce($conn);
	$conn->query($sql);
}

// 删除该房间-数据库表
function destory($conn, $room){
	$sql = "DROP TABLE card" . $room . ";";
	freeOnce($conn);
	$conn->query($sql);
	$sql = "DROP TABLE room" . $room . ";";
	freeOnce($conn);
	$conn->query($sql);
}

$conn = connectDB();

switch($_GET["cmd"]){
	case "create":
		createGame($conn, $_GET["room"]);
	case "join":
		$playerid = joinGame($conn, $_GET["room"], $_GET["name"]);
		setcookie("room", $_GET["room"], time()+3600);
		setcookie("playerid", $playerid, time()+3600);
		break;
	case "accept":
		acceptCard($conn, $_COOKIE["room"], $_COOKIE["playerid"]);
		deal($conn, $_COOKIE["room"]);
		break;
	case "pass":
		passCard($conn, $_COOKIE["room"], $_COOKIE["playerid"]);
		break;
	case "over":
		destory($conn, $_COOKIE["room"]);
}

if($_GET["cmd"] != "over") show($conn, $_COOKIE["room"]);

$conn->close();

?>
