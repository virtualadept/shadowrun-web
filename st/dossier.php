<?

include "../header.php";
$playerid = $mysqli->real_escape_string($_GET['playerid']);

$getplayernotes = $mysqli->prepare("SELECT playerid,type,notes FROM playernotes WHERE playerid=?");                                             
$getplayernotes->bind_param('i',$playerid);
$getplayernotes->execute();                                                                                                    
$getplayernotes->bind_result($playernoteid,$notetype,$notenote);                                                               
while ($getplayernotes->fetch()) {                                                                                             
       $playernotes[$playernoteid][$notetype] = $notenote;                                                                    
}                                                                                                                              
$getplayernotes->close();                                                                                                      
$pcid2un = userid2playername(); // Get the playerid mappings                                                                   
foreach ($playernotes as $arrayid => $typenotearray){                                                                          
        print "<ul><li>$pcid2un[$arrayid]<br>";                                                                                
        foreach ($typenotearray as $arraytype => $arraynotes) {                                                                
                print "<ul><li>$arraytype                                                                                      
                        <ul><li>$arraynotes</ul></ul>";                                                                        
        }                                                                                                                      
        print "</ul>";                                                                                                         
}

include "../footer.php";

?>
                                                                                                                                       
