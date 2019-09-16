<?php
//如果需要设置允许所有域名发起的跨域请求，可以使用通配符 *
header("Access-Control-Allow-Origin: *"); // 允许任意域名发起的跨域请求
header('Access-Control-Allow-Headers: X-Requested-With,X_Requested_With');
set_time_limit(0);
ini_set('memory_limit','0M');
//删除文件夹
 function deldir($dirname){    

    if(file_exists($dirname)){//首先判断目录是否有效    

        $dir = opendir($dirname);//用opendir打开目录    

        while($filename = readdir($dir)){//使用readdir循环读取目录里的内容    

         if($filename != "." && $filename != ".."){//排除"."和".."这两个特殊的目录    

            $file = $dirname."/".$filename;    

            if(is_dir($file)){//判断是否是目录，如果是则调用自身    

                deldir($file); //使用递归删除子目录      

            }else{    

              unlink($file);//删除文件    

            }    

          }    

        }    
            closedir($dir);//关闭文件操作句柄    

            rmdir($dirname);//删除目录    

    }    

}
/**
 * 总接口
 * @param $dir_path 需要压缩的目录地址（绝对路径）
 * @param $zipName 需要生成的zip文件名（绝对路径）
 */
function zip($dir_path,$zipName){
    $relationArr = [$dir_path=>[
        'originName'=>$dir_path,
        'is_dir' => true,
        'children'=>[]
    ]];
    modifiyFileName($dir_path,$relationArr[$dir_path]['children']);
    $zip = new ZipArchive();
    $zip->open($zipName,ZipArchive::CREATE);
    zipDir(array_keys($relationArr)[0],'',$zip,array_values($relationArr)[0]['children']);
    $zip->close();
    // restoreFileName(array_keys($relationArr)[0],array_values($relationArr)[0]['children']);
}

/**
 * 递归添加文件进入zip
 * @param $real_path 在需要压缩的本地的目录
 * @param $zip_path zip里面的相对目录
 * @param $zip ZipArchive对象
 * @param $relationArr 目录的命名关系
 */
function zipDir($real_path,$zip_path,&$zip,$relationArr){
    $sub_zip_path = empty($zip_path)?'':$zip_path.'\\';
    if (is_dir($real_path)){
        foreach($relationArr as $k=>$v){
            if($v['is_dir']){  //是文件夹
                $zip->addEmptyDir($sub_zip_path.$v['originName']);
                zipDir($real_path.'\\'.$k,$sub_zip_path.$v['originName'],$zip,$v['children']);
            }else{ //不是文件夹
                $zip->addFile($real_path.'\\'.$k,$sub_zip_path.$k);
                $zip->deleteName($sub_zip_path.$v['originName']);
                $zip->renameName($sub_zip_path.$k,$sub_zip_path.$v['originName']);
            }
        }
    }
}

/**
 * 递归将目录的文件名更改为随机不重复编号，然后保存原名和编号关系
 * @param $path 本地目录地址
 * @param $relationArr 关系数组
 * @return bool
 */
function modifiyFileName($path,&$relationArr){
    if(!is_dir($path) || !is_array($relationArr)){
        return false;
    }
    if($dh = opendir($path)){
        $count = 0;
        while (($file = readdir($dh)) !== false){
            if(in_array($file,['.','..',null])) continue; //无效文件，重来
            if(is_dir($path.'\\'.$file)){
                $newName = md5(rand(0,99999).rand(0,99999).rand(0,99999).microtime().'dir'.$count);
                $relationArr[$newName] = [
                    'originName' => iconv('GBK','UTF-8',$file),
                    'is_dir' => true,
                    'children' => []
                ];
                rename($path.'\\'.$file, $path.'\\'.$newName);
                modifiyFileName($path.'\\'.$newName,$relationArr[$newName]['children']);
                $count++;
            }
            else{
                $extension = strchr($file,'.');
                $newName = md5(rand(0,99999).rand(0,99999).rand(0,99999).microtime().'file'.$count);
                $relationArr[$newName.$extension] = [
                    'originName' => iconv('GBK','UTF-8',$file),
                    'is_dir' => false,
                    'children' => []
                ];
                rename($path.'\\'.$file, $path.'\\'.$newName.$extension);
                $count++;
            }
        }
    }
}
function searchDir($path,&$files){

    if(is_dir($path)){
  
      $opendir = opendir($path);
  
      while ($file = readdir($opendir)){
        if($file != '.' && $file != '..'){
          searchDir($path.'/'.$file, $files);
        }
      }
      closedir($opendir);
    }
    if(!is_dir($path)){
      $files[] = $path;
    }
  }
  //得到目录名
  function getDir($dir){
    $files = array();
    searchDir($dir, $files);
    return $files;
  }
/**
 * 根据关系数组，将本地目录的文件名称还原成原文件名
 * @param $path 本地目录地址
 * @param $relationArr 关系数组
 */
function restoreFileName($path,$relationArr){
    foreach($relationArr as $k=>$v){
        if(!empty($v['children'])){
            restoreFileName($path.'\\'.$k,$v['children']);
            rename($path.'\\'.$k,iconv('UTF-8','GBK',$path.'\\'.$v['originName']));
        }else{
            rename($path.'\\'.$k,iconv('UTF-8','GBK',$path.'\\'.$v['originName']));
        }
    }
}
function get_git_files($first_commit='a7f8ee7fd58455c6a1982c2763815e0ab200b72f',$second_commit='2d68689d91c66782c4c6105f6e7419c4fc6649df',$folder='bupload',$path="D:\www\biadIt",$upload=false){
    
    $path_arr=explode(':',$path);
    $cmd=$path_arr[0].":&cd {$path}".'&&git diff --name-only '.$first_commit.'  '.$second_commit.'  >'.__DIR__.DIRECTORY_SEPARATOR.'file.txt';
    // var_dump($cmd);exit();
    exec($cmd);
    $a=file_get_contents("file.txt");
    $arr=explode("\n",$a);
    foreach ($arr as $key => $value) {
        if($value){
            //截取其中的部分
            if(!file_exists(__DIR__.DIRECTORY_SEPARATOR.$folder.DIRECTORY_SEPARATOR.dirname($value))){
                // var_dump(__DIR__.DIRECTORY_SEPARATOR.$folder.DIRECTORY_SEPARATOR.dirname($value));
                mkdir(__DIR__.DIRECTORY_SEPARATOR.$folder.DIRECTORY_SEPARATOR.dirname($value),'0777',true);
            }
                @copy($path.DIRECTORY_SEPARATOR.$value,__DIR__.DIRECTORY_SEPARATOR.$folder.DIRECTORY_SEPARATOR.$value);
        }
    }

    if($upload){
    // 上传ftp
    $ftp=ftp_connect($upload['host']);
    $ftp_login = ftp_login($ftp,$upload['user'],$upload['pwd']);
    $in_dir = ftp_pwd($ftp);
    //获取服务器端系统信息ftp_systype()
    $server_os = ftp_systype($ftp);

    //被动模式（PASV）的开关，打开或关闭PASV（1表示开）
    ftp_pasv($ftp, 1);

    //进入目录中用ftp_chdir()函数，它接受一个目录名作为参数。
    @ftp_mkdir($ftp, $upload['path']);
    ftp_chdir($ftp, $upload['path']);
    //ftp_mkdir($conn, "test");
    $file_lists=getDir($folder);
    foreach ($file_lists as $key => $value1) {
        # code...
        //判断目录是否在服务器存在
        $dir=dirname($value1);
        $dir_arr=explode("/",$dir);
        $dir_path='';
        foreach ($dir_arr as $key => $value) {
            # code...
            if($key!==0){
                $dir_path.=$value.'/';
                @ftp_mkdir($ftp,$dir_path);
            }

        }
        ftp_put($ftp,$dir_path.DIRECTORY_SEPARATOR.basename($value1), __DIR__.DIRECTORY_SEPARATOR.$value1, FTP_ASCII);
    }
    ftp_quit($ftp);
    deldir($folder.DIRECTORY_SEPARATOR);
    echo "上传成功！";
    }else{
        zip($folder,$folder.'.zip');
        deldir($folder.DIRECTORY_SEPARATOR);
    }
    
    
}
$ks=isset($_POST['ks'])?$_POST['ks']:'';
$js=isset($_POST['js'])?$_POST['js']:'';
$dz=isset($_POST['dz'])?$_POST['dz']:'';
$mc=isset($_POST['mc'])?$_POST['mc']:'';
$host=isset($_POST['host'])?$_POST['host']:'';
$user=isset($_POST['user'])?$_POST['user']:'';
$pwd=isset($_POST['pwd'])?$_POST['pwd']:'';
$path=isset($_POST['path'])?$_POST['path']:'';

if($host&&$user&&$pwd){
    $upload=['host'=>$host,'user'=>$user,'pwd'=>$pwd,'path'=>$path];
    
}else{
    $upload=0;
}
if($ks&&$js&$dz&$mc){
    get_git_files($ks,$js,$mc,$dz,$upload);
}

