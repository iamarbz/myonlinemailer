<?php

/***********
 * Name: Inoc, Arbie S.
 * 
 * I use this code as a email dequeue manager for my email blasting...
 * Hope someone got the idea..
 * It's not yet finished as of now.. But will be, soon!'
 * 
 * 
 *  (".).. i am ArbZ
 * **************/

<form method=POST>
<input name=d type=number>
<input type=submit name=post>
</form>

<div id=st><!-- Sending Batch # will be printed right here --></div>
<div id=str><!-- Emails Processed will be once viewed here --></div>

<?php
ignore_user_abort(true);
set_time_limit(0);

define( 'BATCH_DIRECTORY', 'batch_items/' );
class BatchFiles
{
  public function delete( $id )
  {
    unlink( $id );
    return true;
  }
  public function add( $function, $args )
  {
    $path = '';
    while( true )
    {
        $path = BATCH_DIRECTORY.time();
        if ( file_exists( $path ) == false )
            break;
    }

    $fh = fopen( $path, "w" );
    fprintf( $fh, $function."\n" );
    foreach( $args as $k => $v )
    {
        fprintf( $fh, $k.":".$v."\n" );


    }
    fclose( $fh );

    return true;
  }
  public function get_all()
  {
    $rows = array();
    if (is_dir(BATCH_DIRECTORY)) {
        if ($dh = opendir(BATCH_DIRECTORY)) {
            while (($file = readdir($dh)) !== false) {
                $path = BATCH_DIRECTORY.$file;
                if ( is_dir( $path ) == false )
                {
                    $item = array();
                    $item['id'] = $path;
                    $fh = fopen( $path, 'r' );
                    if ( $fh )
                    {
                        $item['function'] = trim(fgets( $fh ));
                        $item['args'] = array();
                        while( ( $line = fgets( $fh ) ) != null )
                        {
                            $args = split( ':', trim($line) );
                            $item['args'][$args[0]] = $args[1];
                        }
                        $rows []= $item;
                        fclose( $fh );
                    }
                }
            }
            closedir($dh);
        }
    }
    return $rows;
  }
}

function printvalue( $args ) {
  static $ctr=1;
  foreach($args as $k => $v){
  echo '<script>document.getElementById("str").innerHTML="Sending to -> ['.$ctr.']: '.$v.'"</script>';
  $ctr+=1;

}
}

$file= new BatchFiles();
if(is_numeric($_POST['d']) && isset($_POST['post'])){
for($i=0;$i<$_POST['d'];$i++){
echo "<script>document.getElementById('st').innerHTML='Sending Batch ".$i."';</script>";
$file->add("printvalue",array('1'=>'michgubz@yahoo.com','2'=>'kdpublisherpro@gmail.com','3'=>'kdpublisherpro@gmail.com','4'=>'kdpublisherpro@gmail.com') );
$file->add("printvalue",array('1'=>'michgubz@yahoo.com','2'=>'kdpublisherpro@gmail.com') );
$file->add("printvalue",array('1'=>'michgubz@yahoo.com','2'=>'kdpublisherpro@gmail.com') );
$file->add("printvalue",array('1'=>'michgubz@yahoo.com','2'=>'kdpublisherpro@gmail.com') );
$file->add("printvalue",array('1'=>'michgubz@yahoo.com','2'=>'kdpublisherpro@gmail.com') );
$file->add("printvalue",array('1'=>'michgubz@yahoo.com','2'=>'kdpublisherpro@gmail.com','3'=>'kdpublisherpro@gmail.com','4'=>'kdpublisherpro@gmail.com') );
$file->add("printvalue",array('1'=>'michgubz@yahoo.com','2'=>'kdpublisherpro@gmail.com') );
$file->add("printvalue",array('1'=>'michgubz@yahoo.com','2'=>'kdpublisherpro@gmail.com') );
$file->add("printvalue",array('1'=>'michgubz@yahoo.com','2'=>'kdpublisherpro@gmail.com') );
$file->add("printvalue",array('1'=>'michgubz@yahoo.com','2'=>'kdpublisherpro@gmail.com') );
$file->add("printvalue",array('1'=>'michgubz@yahoo.com','2'=>'kdpublisherpro@gmail.com','3'=>'kdpublisherpro@gmail.com','4'=>'kdpublisherpro@gmail.com') );
$file->add("printvalue",array('1'=>'michgubz@yahoo.com','2'=>'kdpublisherpro@gmail.com') );
$file->add("printvalue",array('1'=>'michgubz@yahoo.com','2'=>'kdpublisherpro@gmail.com') );
$file->add("printvalue",array('1'=>'michgubz@yahoo.com','2'=>'kdpublisherpro@gmail.com') );
$file->add("printvalue",array('1'=>'michgubz@yahoo.com','2'=>'kdpublisherpro@gmail.com') );

foreach($file->get_all() as $item ) {
call_user_func_array( $item['function'], array( $item['args']) );
$file->delete( $item['id'] );
flush(); sleep(1);
}

flush(); sleep(1);
}

}

?>
