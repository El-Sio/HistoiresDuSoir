<?php

/*

Histoires du soir by El-Sio (elsiokun+histoires@gmail.com)
Simple PHP sript for fullfilment on Action On Google Agent to build a vocal story from user's voice input.

*/

header('Content-Type: application/json');


//Variable init

$perso1 = "error";
$perso2 = "error";
$perso3 = "error";
$lieu1 = "error";
$lieu2 = "error";
$objet = "error";
$mechant = "error";
$objectif = "error";

//helper functions

//remove expletive from user input
function censor($s) {
    //expletive list in French (to be completed)
    $gros_mots = array("bite", "couille", "con", "conne", "connard", "connasse ", "pute", "salope", "enculé", "salopard", "pétasse", "putain", "zob", "bordel", "pd", "merde", "salop");
    foreach ($gros_mots as $mot) {
        //check for expletive in begining of input
        if(strpos($s, $mot) === 0) {
            if($mot == "con" && substr($s, strpos($s,$mot)+1) !==false) {
                return $s;
            }
            $censure = str_replace($mot, "<say-as interpret-as=\"expletive\">".$mot."</say-as>", $s);
            return $censure;
        }
        //check for expletive in end of input
        if(strpos($s, $mot) === max((strlen($s) - strlen($mot)),0)) {
            if($mot == "con" && substr($s, strpos($s,$mot)-1, 1) !== " ") {
                return $s;
            }
            $censure = str_replace($mot, "<say-as interpret-as=\"expletive\">".$mot."</say-as>", $s);
            return $censure;
        }
        
        //check for expletive in middle of input (should be preceded and followed by space)
       if(strpos($s, " ".$mot." ") !== false) {
           $censure = str_replace($mot, "<say-as interpret-as=\"expletive\">".$mot."</say-as>", $s);
            return $censure;
        }
    }
    
    //no expletive found : do nothing
    return $s;
}

function gender($s){
    
    $articles_masculin = array("un ", "le ", "mon ");
    $articles_feminin = array("une ", "la ", "ma ");
    $gender = 3;
    
    foreach($articles_masculin as $articlem){
        if (strpos($s, $articlem) !== false) {
            $gender = 1;
        }
    }

    foreach($articles_feminin as $articlef){
        if (strpos($s, $articlef) !== false) {
            $gender = 2;
        }
    }
    
    return $gender;
}

//Transform the undefinite article into the coresponding finite article. Pesky french grammar for you...
function changearticle($s) {
    
    $voyelles = array("a", "e", "é", "è", "i", "o", "u", "y");
    $articles_pluriel = array("des ", "les ", "mes ", "les ","nos ", "vos ", "tes ", "ta ", "ma ", "ton ", "l'", "le ", "la ", "1", "2", "3", "4", "5", "6", "7", "8", "9", "10");
    $chiffres = array("1", "2", "3", "4", "5", "6", "7", "8", "9", "10");
    $articles_total = array("le ", "la ", "les ", "ma ", "mon ", "mes ", "un ", "une ", "des ", "nos ", "vos ", "des", "tes ", "ton ", "ta ", "1", "2", "3", "4", "5", "6", "7", "8", "9", "10");
    
    $sarr = explode(' ', trim($s),2);
    $ssart = $sarr[1];
    $trans = "error";
    $pluriel = false;
    $pasdarticle = true;
    
    //detect plural through articles
    foreach($articles_pluriel as $art) {
        if(strpos($s, $art) === 0) {
            $pluriel = true;
        }
    }
    
    //No article, no change like plural
    if(!$ssart || strpos($s,"l'") !==false) {
        $pluriel = true;}
    
    //No article but two items like "Jhon Doe"
    foreach($articles_total as $arti) {
        if(strpos($s, $arti) === 0) {
            $pasdarticle = false;
        }
    }
    
    if($pasdarticle) {
        $trans = $s;
        return $trans;
    }
    
    if($pluriel) {
        //case "des -> les"
        if(strpos($s,"des") === 0) {
            $trans = "les ".$ssart;
            return $trans;   
        }
        
        //case chiffres : + les
        foreach($chiffres as $chiffre) {
            if(strpos($s,$chiffre) === 0) {
                $trans = "les ".$s;
                return $trans;
            }
        }
        
        //default no change in plural
        $trans = $s;
        return $trans;
    }
    
    if(!$pluriel) {
        foreach($voyelles as $voyelle) {
            if(strpos($ssart, $voyelle) === 0) {
                $trans = "l'".$ssart;
                return $trans;
            }
        }
        if(gender($s) == 1) {
                
                $trans = "le ".$ssart;
                return $trans;
            }
        if(gender($s) == 2) {
                $trans = "la ".$ssart;
                return $trans;
            }
        }
    
    return $trans;
}

//TESTS
/*
echo "test :  un méchant --> ".changearticle("un méchant")." / ";
echo "test :  une méchante --> ".changearticle("une méchante")." / ";
echo "test :  un ogre --> ".changearticle("un ogre")." / ";
echo "test : une ouvrière -->".changearticle("une ouvrière")." / ";
echo "test : les ouvriers -->".changearticle("les ouvriers")." / ";
echo "test : des ouvriers -->".changearticle("des ouvriers")." / ";
echo "test : un roi -->".changearticle("un roi")." / ";
echo "test : une princesse -->".changearticle("une princesse")." / ";
*/

//POST request handling

$json = file_get_contents('php://input'); 
$request = json_decode($json, true);
$parameters = $request['queryResult']['parameters'];

//fill out parameters from POST

$perso1 = censor(strtolower($parameters['perso1']));
$perso2 = censor(strtolower($parameters['perso2']));
$perso3 = censor(strtolower($parameters['perso3']));
$lieu1 = censor(strtolower($parameters['lieu1']));
$lieu2 = censor(strtolower($parameters['lieu2']));
$objet = censor(strtolower($parameters['objet']));
$objectif = censor(strtolower($parameters['objectif']));
$mechant = censor(strtolower($parameters['mechant']));

//test gender of bad guy

switch(gender($mechant)) {
    case 1:
        $mechant_sound = "<audio src=\"https://japansio.info/sounds/rire_mechant_h.ogg\" clipEnd=\"+6s\"></audio>";
        break;
    case 2:
        $mechant_sound = "<audio src=\"https://japansio.info/sounds/rire_mechant_f.ogg\"></audio>";
        break;
    case 3:
        $mechant_sound = "<audio src=\"https://japansio.info/sounds/mechant_neutre.ogg\"></audio>";
        break;
}


//select a random transport for the story

$hasard_transport = rand(1,7);

switch($hasard_transport) {
    case 1:
            $transport_txt = "en train";
            $transport_sound = "<audio src=\"https://japansio.info/sounds/train.ogg\" clipEnd=\"+6s\"></audio>";
            break;
    case 2:
            $transport_txt = "en bateau";
            $transport_sound = "<audio src=\"https://japansio.info/sounds/bateau_short.ogg\"></audio>";
            break;
    case 3:
            $transport_txt = "en voiture";
            $transport_sound = "<audio src=\"https://japansio.info/sounds/voiture_short.ogg\"></audio>";
            break;
    case 4:
            $transport_txt = "à cheval";
            $transport_sound = "<audio src=\"https://japansio.info/sounds/cheval.ogg\"></audio>";
            break;
    case 5:
            $transport_txt = "en avion";
            $transport_sound = "<audio src=\"https://japansio.info/sounds/avion_short.ogg\"></audio>";
            break;
    case 6:
            $transport_txt = "en vélo";
            $transport_sound = "<audio src=\"https://japansio.info/sounds/velo.ogg\"></audio>";
            break;
    case 7:
            $transport_txt = "en moto";
            $transport_sound = "<audio src=\"https://japansio.info/sounds/moto.ogg\" clipEnd=\"+6s\"></audio>";
            break;
}

//Opening and closing sound
$jingle = "<audio src=\"https://actions.google.com/sounds/v1/cartoon/magic_chime.ogg\" clipEnd=\"+3s\"></audio>";


//cheering sound
$bravo = "<audio src=\"https://japansio.info/sounds/bravo.ogg\"></audio>";

// Build different versions of the story with the same ingredients.

$phrase1 = "<speak>".$jingle."<p><s>Il était une fois ".$perso1." qui habitait ".$lieu1.".</s><s>Un jour ".$perso2." lui rendit visite pour lui donner ".$objet." magique, et ils partirent tous les deux ".$transport_txt." à la recherche d'un trésor fabuleux : ".$objectif.".</s>".$transport_sound."<s>En chemin ils rencontrèrent ".$perso3.", qui leur expliqua que ".changearticle($objectif)." était caché ".$lieu2." et gardé par ".$mechant.".</s>".$mechant_sound."<s>".changearticle($perso1)." et ".changearticle($perso2)." utilisèrent ".changearticle($objet)." pour vaincre ".changearticle($mechant)." et récupérer ".changearticle($objectif).", puis ils retournèrent ".$lieu1." avec ".changearticle($perso3)." pour y faire la fête.</s>".$bravo."</p><p><s>Fin de l'histoire.</s>".$jingle."</p><p><s>Veux tu écrire une autre histoire ?</s></p></speak>";

$phrase2 = "<speak>".$jingle."<p><s>Cette histoire se passe ".$lieu1.".</s><s>".$perso1." rencontra un jour ".$perso2." qui lui proposa de partir ".$transport_txt." à la recherche d'un trésor merveilleux : ".$objectif.".</s>".$transport_sound."<s> En chemin ils rencontrèrent ".$perso3.", qui leur offrit ".$objet." magique.</s><s> Ils arrivèrent enfin ".$lieu2." ou était caché ".changearticle($objectif)." gardé par ".$mechant.".</s>".$mechant_sound."<s>".changearticle($perso1)." utilisa ".changearticle($objet).", réussit à vaincre ".changearticle($mechant)." et put ainsi ramener ".changearticle($objectif)." ".$lieu1." avec ".changearticle($perso2)." ou ils furent célébrés en héros.</s>".$bravo."</p><p><s>Fin de l'histoire.</s>".$jingle."<s>Veux tu écrire une autre histoire ?</s></p></speak>";

$phrase3 = "<speak>".$jingle."<p><s>Connais-tu l'histoire de ce célèbre personnage : ".$perso1." ?</s><s>Un jour, ".$lieu1.", ".$perso2." lui rendit visite et lui parla d'un fabuleux trésor : ".$objectif.", qui était gardé par ".$mechant.".</s>".$mechant_sound."<s> Ils partirent alors tous les deux à l'aventure ".$transport_txt." ".$lieu2." ou habitait ".changearticle($mechant).".</s>".$transport_sound."<s>Sur la route, ils rencontrèrent ".$perso3." qui leur offit ".$objet.".</s><s>Avec ".changearticle($objet).", qui était en fait ".$objet." magique, ils réussirent à vaincre ".changearticle($mechant)." et à ramener ".changearticle($objectif)." ".$lieu1." pour y faire une grande fête avec ".changearticle($perso3).".</s>".$bravo."</p><p><s>Fin de l'histoire.</s>".$jingle."<s>Veux tu écrire une autre histoire ?</s></p></speak>";

$phrase4 = "<speak>".$jingle."<p><s>Il y a bien longtemps, ".$lieu1.", ".$perso1." s'ennuyait et voulait partir à l'aventure.</s><s>Un beau matin, ".$perso2." frappa à sa porte et lui dit que son trésor le plus précieux, ".$objectif." avait été dérobé par ".$mechant.".</s>".$mechant_sound."<s> Aussitôt, ".changearticle($perso1)." prit ".changearticle($perso2)." par la main et partit ".$transport_txt." ".$lieu2." où habitait ".changearticle($mechant).".</s>".$transport_sound."<s> Arrivé sur place, ils rencontrèrent ".$perso3." qui leur donna ".$objet." pour vaincre ".changearticle($mechant).".</s><s> Après une terrible bataille, ".changearticle($perso1)." et ".changearticle($perso2)." réussirent à vaincre ".changearticle($mechant)." et rentrèrent ".$lieu1." pour y faire une grande fête avec ".changearticle($perso3).".</s></p>".$bravo."<p><s>Fin de l'histoire.</s>".$jingle."<s>Veux-tu écrire une autre histoire ?</s></p></speak>";


//select a random version of the story
$hasard_histoire = rand(1,4);
//debug
//$hasard = 1;

switch($hasard_histoire) {
        case 1:
            $phrase = $phrase1;
            break;
        case 2:
            $phrase = $phrase2;
            break;
        case 3:
            $phrase = $phrase3;
            break;
        case 4:
            $phrase = $phrase4;
            break;
}

//Fill out the JSON response expected by Google

//$response['debug'] = $parameters;
$response['fulfillmentText'] = $phrase;

echo json_encode($response);
?>