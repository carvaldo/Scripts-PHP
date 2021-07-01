<?php
The MIT License (MIT)
Copyright © 2021 David Carvalho

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the “Software”), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED “AS IS”, WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

/**
* @author David Carvalho <davidcarvalho.developer@gmail.com>
*
* Script utilizado em um projeto particular para normalizar os nomes 
* dos arquivos e atualizar sua referência no banco de dados.
*
*/

require '../../bootstrap.php';

use Core\Controller\AppController;

$ini = AppController::getDatabaseConfig();
$user = $ini['db_user']; 
$password = $ini['db_password']; 
$database = $ini['db_name']; 
$hostname = $ini['db_host'];
$conn = mysqli_connect( $hostname, $user, $password ) or die( ' Erro na conexão ' );
mysqli_select_db($conn, $database) or die('Erro ao selecionar base de dados');

if (!file_exists("_log")) {
     mkdir("_log", 777);
}

$files = glob("*.pdf");
$fLog = fopen("_log" . DIRECTORY_SEPARATOR . "migration.log", 'w');
$fFilesOld = fopen("_log" . DIRECTORY_SEPARATOR . "old_arquivos.txt", 'w');
$fFilesNew = fopen("_log" . DIRECTORY_SEPARATOR . "new_arquivos.txt", 'w');
$fRevert = fopen("_log" . DIRECTORY_SEPARATOR . "revert.log", 'w');

foreach ($files as $file) {
    fwrite($fFilesOld, $file . PHP_EOL);
    $str = preg_replace('/[\"\*\:\<\>\?\'\|\r\n\t  %*,]/', '_', $file);
    $str = htmlentities($str, ENT_QUOTES, "utf-8");
    $str = preg_replace("/(&)([a-z])([a-z]+;)/i", '$2', $str);
    $newNames[] = $str;
    fwrite($fFilesNew, $str . PHP_EOL);
}

foreach ($newNames as $position => $name) {
    $oldName = $files[$position];
    echo "Atualizando $oldName..." . PHP_EOL;
    $queryArquivo = "UPDATE documento SET arquivo = '$name' WHERE arquivo LIKE '$oldName';" . PHP_EOL;
    $queryArquivoRevert = "UPDATE documento SET arquivo = '$oldName' WHERE arquivo LIKE '$name';" . PHP_EOL;
    $queryArquivoAssinado = "UPDATE documento SET arquivoAssinado = '$name' WHERE arquivoAssinado LIKE '$oldName';" . PHP_EOL;
    $queryArquivoAssinadoRevert = "UPDATE documento SET arquivoAssinado = '$oldName' WHERE arquivoAssinado LIKE '$name';" . PHP_EOL;
    rename($oldName, $name);
    fwrite($fLog, time() . ": $queryArquivo");
    fwrite($fLog, time() . ": $queryArquivoAssinado");
    fwrite($fRevert, $queryArquivo);
    fwrite($fRevert, $queryArquivoAssinado);
    mysqli_query($conn, $queryArquivo);
    mysqli_query($conn, $queryArquivoAssinado);
}

mysqli_close($conn);
fclose($fLog);
fclose($fRevert);
fclose($fFilesOld);
fclose($fFilesNew);
echo "Concluído!" . PHP_EOL;
