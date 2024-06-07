<?php
header("Access-Control-Allow-Origin: *");  // 允许所有域进行跨域请求
header('Content-Type: application/json');

$info_type = $_GET['info_type'];
$movie_id = $_GET['movie_id'];
$book_id = $_GET['book_id'];
$api_key = '0ab215a8b1977939201640fa14c66bab';
$url = "";
if (empty($info_type)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing Info Type']);
    exit;
} else if ($info_type === 'movie') {
    $url = "https://api.douban.com/v2/movie/subject/$movie_id";
    if (empty($movie_id)) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing movie ID']);
        exit;
    }
} else if ($info_type === 'book') {
    $url = "https://api.douban.com/v2/book/$book_id";
    if (empty($book_id)) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing book ID']);
        exit;
    }
}

// 初始化 cURL
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(['apikey' => $api_key]));  // 使用http_build_query来确保数据被正确编码
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/x-www-form-urlencoded',  // 确保发送正确的Content-Type
    'Referer: '
]);

// 发送请求
$response = curl_exec($ch);
$error = curl_error($ch);

if ($response === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Curl error: ' . $error]);
    curl_close($ch);
    exit;
}
echo $response;

// 处理响应数据

// 关闭 cURL 资源，并释放系统资源
curl_close($ch);